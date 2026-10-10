<?php
declare(strict_types=1);

namespace App\Commands;

use FloCMS\CLI\Console\Command;
use FloCMS\CLI\Console\Input\Argument;
use FloCMS\CLI\Console\Input\Definition;
use FloCMS\Support\LoginThrottle;

/**
 * An application command: every class in commands/ (namespace App\Commands)
 * is a `php flo` command. Create your own with `php flo make:command`.
 *
 *     php flo login:unlock admin@example.com
 *     php flo login:unlock 203.0.113.7
 */
final class LoginUnlockCommand extends Command
{
    protected string $name = 'login:unlock';

    protected string $description = 'Clear the admin login throttle for an email address or IP address';

    protected string $help = <<<'TXT'
After too many failed sign-ins the admin login is blocked for a while
(config 'login_throttle'). This lifts the block for one email address or
client IP right away.
TXT;

    protected function configure(Definition $definition): void
    {
        $definition->argument('who', Argument::REQUIRED, 'The email address or IP address to unlock');
    }

    protected function handle(): int
    {
        $who = trim((string) $this->argument('who'));
        $this->project()->boot();
        $throttle = LoginThrottle::fromConfig();

        if (filter_var($who, FILTER_VALIDATE_IP) !== false) {
            $throttle->unlockIp($who);
            $this->success('Logins from ' . $who . ' are unlocked.');

            return self::SUCCESS;
        }
        if (filter_var($who, FILTER_VALIDATE_EMAIL) !== false) {
            $throttle->clear($who);
            $this->success('Logins for ' . $who . ' are unlocked.');

            return self::SUCCESS;
        }

        $this->invalid('"' . $who . '" is neither an email address nor an IP address.');
    }
}
