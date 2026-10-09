<?php
declare(strict_types=1);

namespace FloCMS\Support;

use RuntimeException;

/**
 * Generates a per-site APP_KEY and writes it to .env.
 *
 * Used by `php flo key:generate` and by composer's post-create-project-cmd.
 */
final class KeyGenerator
{
    public static function generate(): string
    {
        return 'base64:' . base64_encode(random_bytes(32));
    }

    /**
     * Set APP_KEY in $envFile when it is empty (or always with $force).
     *
     * @return string|null the new key, or null when a key was already set
     */
    public static function writeToEnv(string $envFile, bool $force = false): ?string
    {
        if (!is_file($envFile)) {
            throw new RuntimeException(".env file not found: {$envFile}");
        }

        $contents = (string) file_get_contents($envFile);

        if (preg_match('/^APP_KEY=(.*)$/m', $contents, $m)) {
            if (!$force && trim($m[1], " \t\"'") !== '') {
                return null;
            }

            $key = self::generate();
            $contents = (string) preg_replace('/^APP_KEY=.*$/m', 'APP_KEY=' . $key, $contents, 1);
        } else {
            $key = self::generate();
            $contents = rtrim($contents, "\r\n") . "\nAPP_KEY=" . $key . "\n";
        }

        if (file_put_contents($envFile, $contents, LOCK_EX) === false) {
            throw new RuntimeException("Unable to write {$envFile}");
        }

        return $key;
    }
}
