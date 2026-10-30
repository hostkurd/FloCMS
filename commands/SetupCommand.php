<?php
declare(strict_types=1);

namespace App\Commands;

use FloCMS\CLI\Console\Command;
use FloCMS\CLI\Console\Input\Definition;
use FloCMS\CLI\Console\Input\Option;

/**
 * php flo setup: what are you building? Run by `composer create-project`
 * once, and again whenever you want to add the admin panel or the API.
 *
 * It only adds: packages (with Composer), their installers, the 'home'
 * setting, and on a fresh project the removal of the example pages an
 * admin-only or API-only project doesn't need. It never removes a package;
 * it prints the steps instead. You can delete this file once the project is
 * set up.
 */
final class SetupCommand extends Command
{
    public const SETUPS = [
        'site-admin' => 'Website + admin panel',
        'site' => 'Website only',
        'admin' => 'Admin panel only',
        'api-admin' => 'API + admin panel',
        'api' => 'API only',
    ];

    /** The packages each setup adds, with the version each one needs. */
    public const PACKAGES = [
        'hostkurd/flocms-admin' => '^0.39.3',
        'hostkurd/flocms-api' => '^1.3',
    ];

    /**
     * The skeleton's example files an admin-only or API-only project removes,
     * with their sha256 as shipped
     * (see fingerprint()). A file that differs was changed by you
     * and is kept.
     */
    public const EXAMPLES = [
        'views/pages/index.html' => 'c301b903c127616f8707fadf2d7b94a858e76e7404ec643a26bb490123739a59',
        'templates/dark/layouts/default.html' => 'ec78e0925c7a21ac2bc0c0d7f6de18ea599905e18035ae4aebeb4ed9f354a7b8',
        'public/themes/dark/css/style.css' => 'e8ea7d1d089d4c17b5d30a9c71328533ebb8decedf22825ffbf8eb157b0dcfec',
    ];

    /** The view of the website's home page (PagesController::index()). */
    public const HOME_VIEW = 'views/pages/index.html';

    /**
     * Runs a command and returns its exit code (tests replace it).
     *
     * @var null|\Closure(list<string>, string): int
     */
    public static ?\Closure $runner = null;

    /**
     * Whether a person can answer (tests replace it). Composer runs this
     * command in a terminal only when it can ask questions itself.
     *
     * @var null|\Closure(): bool
     */
    public static ?\Closure $terminal = null;

    protected string $name = 'setup';

    protected string $description = 'Choose what you build: website, admin panel, API (adds the packages)';

    protected string $help = <<<'TXT'
Asks "What are you building?" and sets the project up for it:

  site-admin  Website + admin panel   adds flocms-admin
  site        Website only            nothing to add
  admin       Admin panel only        adds flocms-admin; / goes to /admin
  api-admin   API + admin panel       adds flocms-admin and flocms-api
  api         API only                adds flocms-api; / answers JSON

Packages are added with `composer require`, then their installers run
(`php flo admin:install`, `php flo api:install`). An admin-only or API-only
project also gets 'home' in config/config.php and, when they are still the
skeleton's own files, loses the welcome page and the dark example template.

Without a terminal (no --as) nothing changes: the project stays website
only. Run it again later to add the admin panel or the API. It never
removes a package.
TXT;

    protected array $examples = [
        'php flo setup',
        'php flo setup --as=admin',
        'php flo setup --as=api-admin --dry-run',
    ];

    protected function configure(Definition $definition): void
    {
        $definition
            ->option('as', null, Option::VALUE_REQUIRED, 'The setup: ' . implode(', ', array_keys(self::SETUPS)))
            ->option('dry-run', null, Option::VALUE_NONE, 'Print the plan, change nothing');
    }

    protected function handle(): int
    {
        $as = $this->option('as');
        $canAsk = $this->input->isInteractive() && (self::$terminal ?? self::hasTerminal(...))();

        if (is_string($as)) {
            if (!isset(self::SETUPS[$as])) {
                $this->invalid('Unknown setup "' . $as . '". Choose one of: ' . implode(', ', array_keys(self::SETUPS)) . '.');
            }
            $setup = $as;
        } elseif ($canAsk) {
            $keys = array_keys(self::SETUPS);
            $choices = [];
            foreach (array_values(self::SETUPS) as $index => $label) {
                $choices[$index + 1] = $label;
            }
            $setup = $keys[(int) $this->choice('What are you building?', $choices, 1) - 1];
        } else {
            // Composer gives scripts no terminal on Windows, with -n, or when its output is piped.
            $this->note('No terminal to ask in: the project stays as it is (website only). To choose, run: php flo setup');

            return self::SUCCESS;
        }

        $plan = $this->plan($setup);
        $this->line('');
        $this->line(self::SETUPS[$setup] . ':');
        if ($plan['steps'] === []) {
            $this->line('    nothing to change');
        }
        foreach ($plan['steps'] as $step) {
            $this->line('    ' . $step['show']);
        }
        foreach ($plan['kept'] as $file) {
            $this->line('    keep ' . $file . ' (changed since the skeleton)');
        }
        foreach ($plan['extra'] as $package) {
            $this->warn($package . ' is installed but not part of this setup. setup never removes packages; to remove it yourself:');
            foreach (self::removalSteps($package) as $line) {
                $this->line('    ' . $line);
            }
        }

        if ($this->option('dry-run') || $plan['steps'] === []) {
            return self::SUCCESS;
        }
        if ($canAsk && !$this->confirm('Go ahead?', true)) {
            $this->line('Nothing changed.');

            return self::SUCCESS;
        }

        foreach ($plan['steps'] as $step) {
            $step['run']();
        }

        $this->line('');
        $this->success('Done. Next: set DB_* in .env, then `php flo migrate`' . ($setup === 'site' ? '.' : ' and `php flo user:create`.'));

        return self::SUCCESS;
    }

    /**
     * What a setup changes in this project.
     *
     * @return array{steps: list<array{show: string, run: \Closure(): void}>, kept: list<string>, extra: list<string>}
     */
    private function plan(string $setup): array
    {
        $project = $this->project();
        $wants = [
            'hostkurd/flocms-admin' => in_array($setup, ['site-admin', 'admin', 'api-admin'], true),
            'hostkurd/flocms-api' => in_array($setup, ['api-admin', 'api'], true),
        ];
        $required = $this->requiredPackages();
        $steps = [];
        $extra = [];

        $add = [];
        foreach ($wants as $package => $wanted) {
            if ($wanted && !in_array($package, $required, true)) {
                $add[] = $package . ':' . self::PACKAGES[$package];
            } elseif (!$wanted && in_array($package, $required, true)) {
                $extra[] = $package;
            }
        }
        // A run without questions keeps the commands it starts quiet too.
        $quiet = $this->input->isInteractive() ? [] : ['--no-interaction'];
        if ($add !== []) {
            $command = [...self::composer(), 'require', ...$add, ...$quiet];
            $steps[] = ['show' => 'composer require ' . implode(' ', $add), 'run' => fn () => $this->runStep($command)];
        }

        // The installers only add what is missing, so they run every time.
        $flo = [PHP_BINARY, $project->path('flo')];
        if ($wants['hostkurd/flocms-admin']) {
            $steps[] = ['show' => 'php flo admin:install', 'run' => fn () => $this->runStep([...$flo, 'admin:install', ...$quiet])];
        }
        if ($wants['hostkurd/flocms-api']) {
            $steps[] = ['show' => 'php flo api:install', 'run' => fn () => $this->runStep([...$flo, 'api:install', ...$quiet])];
        }

        $kept = [];
        $home = match ($setup) {
            'admin', 'api-admin' => 'admin',
            'api' => 'api',
            default => 'site',
        };
        if ($home !== 'site') {
            $remove = [];
            foreach (self::EXAMPLES as $file => $hash) {
                $path = $project->path($file);
                if (!is_file($path)) {
                    continue;
                }
                if (self::fingerprint($path) === $hash) {
                    $remove[] = $file;
                } else {
                    $kept[] = $file;
                }
            }
            if ($remove !== []) {
                $steps[] = ['show' => 'remove ' . implode(', ', $remove), 'run' => fn () => $this->remove($remove)];
            }
        }

        // An earlier admin-only or API setup removed the welcome page: the website needs one.
        if ($home === 'site' && !is_file($project->path(self::HOME_VIEW))) {
            $steps[] = ['show' => 'write ' . self::HOME_VIEW . ' (a simple home page)', 'run' => fn () => $this->writeHomeView()];
        }

        if ($this->currentHome() !== $home) {
            $steps[] = ['show' => 'config/config.php: home = ' . $home, 'run' => fn () => $this->setHome($home)];
        }
        if ($home !== 'site' && $wants['hostkurd/flocms-admin']) {
            $steps[] = ['show' => 'config/admin.php: site_link = false', 'run' => fn () => $this->hideSiteLink()];
        }

        return ['steps' => $steps, 'kept' => $kept, 'extra' => $extra];
    }

    /** @return list<string> the hostkurd packages composer.json requires */
    private function requiredPackages(): array
    {
        $composer = json_decode((string) @file_get_contents($this->project()->path('composer.json')), true);

        return array_keys(array_merge((array) ($composer['require'] ?? []), (array) ($composer['require-dev'] ?? [])));
    }

    /** @param list<string> $command */
    private function runStep(array $command): void
    {
        $this->line('');
        $this->comment('> ' . implode(' ', array_map(static fn (string $part): string => str_contains($part, ' ') ? '"' . $part . '"' : $part, $command)));
        $code = (self::$runner ?? self::execute(...))($command, $this->project()->root());
        if ($code !== 0) {
            $this->fail('That command failed (exit code ' . $code . '). Fix the problem, then run `php flo setup` again: it continues from where it stopped.');
        }
    }

    /** @param list<string> $command */
    private static function execute(array $command, string $cwd): int
    {
        if (DIRECTORY_SEPARATOR === '\\') {
            $command = self::windowsCommandLine($command);
        }
        $process = proc_open($command, [0 => STDIN, 1 => STDOUT, 2 => STDERR], $pipes, $cwd);

        return is_resource($process) ? proc_close($process) : 1;
    }

    /**
     * A command line for cmd.exe. On Windows proc_open() sends a string through
     * cmd.exe, where "^" is only literal inside quotes
     * ("hostkurd/flocms-admin:^0.39.3"), so parts with special characters are
     * quoted. A bare program name is not: a batch file started as "composer"
     * (quoted, found on PATH) gets the current folder as %~dp0, and
     * Composer's composer.bat then looks for composer.phar in the project.
     *
     * @param list<string> $command
     */
    public static function windowsCommandLine(array $command): string
    {
        return implode(' ', array_map(
            static fn (string $part): string => $part !== '' && strpbrk($part, " \t^&|<>()%!,;=\"") === false
                ? $part
                : '"' . str_replace('"', '', $part) . '"',
            $command,
        ));
    }

    /** @return list<string> how to call Composer: the one running this script, else the one on PATH */
    private static function composer(): array
    {
        $binary = getenv('COMPOSER_BINARY');
        if (is_string($binary) && $binary !== '' && is_file($binary)) {
            return str_ends_with(strtolower($binary), '.bat') ? [$binary] : [PHP_BINARY, $binary];
        }
        if (DIRECTORY_SEPARATOR === '\\') {
            return self::windowsComposer((string) getenv('PATH'), ';');
        }

        return ['composer'];
    }

    /**
     * Composer on a Windows PATH, by its full path. composer.bat or
     * composer.cmd with composer.phar beside it (Composer-Setup, XAMPP,
     * Laragon) runs the phar with this PHP, so no batch file is involved.
     *
     * @return list<string>
     */
    public static function windowsComposer(string $path, string $separator): array
    {
        foreach (explode($separator, $path) as $dir) {
            $dir = rtrim(trim($dir, " \t\""), '\\/');
            if ($dir === '') {
                continue;
            }
            foreach (['composer.exe', 'composer.bat', 'composer.cmd'] as $name) {
                $file = $dir . DIRECTORY_SEPARATOR . $name;
                if (!is_file($file)) {
                    continue;
                }
                $phar = $dir . DIRECTORY_SEPARATOR . 'composer.phar';
                if ($name !== 'composer.exe' && is_file($phar)) {
                    return [PHP_BINARY, $phar];
                }

                return [$file];
            }
        }

        return ['composer'];
    }

    /** sha256 with LF line endings, so a Windows checkout (CRLF) still matches. */
    public static function fingerprint(string $path): string
    {
        return hash('sha256', str_replace("\r\n", "\n", (string) file_get_contents($path)));
    }

    private static function hasTerminal(): bool
    {
        return defined('STDIN') && function_exists('stream_isatty') && @stream_isatty(STDIN);
    }

    /** @param list<string> $files */
    private function remove(array $files): void
    {
        foreach ($files as $file) {
            $path = $this->project()->path($file);
            if ($this->throughLink($file)) {
                $this->note('Kept ' . $file . ': it is reached through a link, which may be shared.');
                continue;
            }
            if (@unlink($path)) {
                $this->success('Removed ' . $file);
            } else {
                $this->warn('Could not remove ' . $file . ': delete it yourself.');
                continue;
            }
            // Folders left empty (templates/dark/layouts, templates/dark), never a top-level one
            for ($dir = dirname($file); str_contains($dir, '/'); $dir = dirname($dir)) {
                $full = $this->project()->path($dir);
                if (!is_dir($full) || count(scandir($full) ?: []) !== 2 || !@rmdir($full)) {
                    break;
                }
            }
        }
    }

    /** Whether the file, or a folder on the way to it, is a symbolic link. */
    private function throughLink(string $file): bool
    {
        for ($part = $file; $part !== '.' && $part !== ''; $part = dirname($part)) {
            if (is_link($this->project()->path($part))) {
                return true;
            }
            if (!str_contains($part, '/')) {
                break;
            }
        }

        return false;
    }

    private function writeHomeView(): void
    {
        $path = $this->project()->path(self::HOME_VIEW);
        $html = "<section style=\"max-width:40rem;margin:4rem auto;padding:0 1rem;font-family:system-ui,sans-serif\">\n"
            . "    <h1>Welcome</h1>\n"
            . "    <p>Your website starts here. Edit " . self::HOME_VIEW . " to change this page.</p>\n"
            . "</section>\n";
        if ((!is_dir(dirname($path)) && !@mkdir(dirname($path), 0755, true)) || @file_put_contents($path, $html, LOCK_EX) === false) {
            $this->warn('Could not write ' . self::HOME_VIEW . ': the home page needs it.');

            return;
        }
        $this->success('Created ' . self::HOME_VIEW);
    }

    private function currentHome(): string
    {
        $config = (string) @file_get_contents($this->project()->path('config/config.php'));

        return preg_match("/Config::set\\('home',\\s*'(site|admin|api)'\\)/", $config, $match) === 1 ? $match[1] : 'site';
    }

    private function setHome(string $home): void
    {
        $file = $this->project()->path('config/config.php');
        $config = (string) @file_get_contents($file);
        $line = "Config::set('home', '" . $home . "');";
        $updated = preg_replace("/Config::set\\('home',\\s*'[a-z]*'\\);/", $line, $config, 1, $count);
        if ($count !== 1 || !is_string($updated) || @file_put_contents($file, $updated, LOCK_EX) === false) {
            $this->warn('Could not set home in config/config.php. Add this line yourself: ' . $line);

            return;
        }
        $this->success('config/config.php: home = ' . $home);
    }

    private function hideSiteLink(): void
    {
        $file = $this->project()->path('config/admin.php');
        $config = is_file($file) ? (string) file_get_contents($file) : '';
        $updated = preg_replace("/('site_link'\\s*=>\\s*)true\\b/", '${1}false', $config, 1, $count);
        if ($count !== 1 || !is_string($updated) || @file_put_contents($file, $updated, LOCK_EX) === false) {
            if (preg_match("/'site_link'\\s*=>\\s*false/", $config) !== 1) {
                $this->warn("Could not change config/admin.php. Set 'site_link' => false there to hide the panel's View site link.");
            }

            return;
        }
        $this->success('config/admin.php: site_link = false');
    }

    /** @return list<string> */
    private static function removalSteps(string $package): array
    {
        return match ($package) {
            'hostkurd/flocms-admin' => [
                'composer remove hostkurd/flocms-admin',
                'then delete config/admin.php and admin/ (your resources), and roll back or drop the admin_* tables',
            ],
            'hostkurd/flocms-api' => [
                'composer remove hostkurd/flocms-api',
                'then delete public/api.php and api/ (your routes and controllers), and drop the api_* tables',
            ],
            default => ['composer remove ' . $package],
        };
    }
}
