<?php
declare(strict_types=1);

namespace HostKurd\Flocms\Tests\Support;

use RuntimeException;

/**
 * Runs a throw-away copy of the skeleton under PHP's built-in web server.
 *
 * The copy gets a fresh .env made from .env.example (like
 * `composer create-project` does), so tests see what a new install sees.
 * Environment variables passed to start() override .env values.
 */
final class AppServer
{
    private string $root;
    /** @var resource|null */
    private $process = null;
    private int $port;
    private string $cookieJar;

    private function __construct()
    {
        $this->root = sys_get_temp_dir() . '/flocms-test-' . bin2hex(random_bytes(6));
        $this->cookieJar = $this->root . '.cookies';
    }

    /** @param array<string, string> $env */
    public static function start(array $env = [], ?callable $prepare = null): self
    {
        $server = new self();
        $server->copySkeleton();

        if ($prepare !== null) {
            $prepare($server->root);
        }

        $server->launch($env);

        return $server;
    }

    public function root(): string
    {
        return $this->root;
    }

    /**
     * @param array<string, string> $data form fields (POST)
     * @param array<string, string> $headers
     * @return array{status: int, headers: array<string, string>, body: string}
     */
    public function request(string $method, string $path, array $data = [], array $headers = []): array
    {
        $ch = curl_init('http://127.0.0.1:' . $this->port . $path);
        $headerLines = ['Host: localhost'];
        foreach ($headers as $name => $value) {
            $headerLines[] = $name . ': ' . $value;
        }

        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_COOKIEJAR => $this->cookieJar,
            CURLOPT_COOKIEFILE => $this->cookieJar,
            CURLOPT_HTTPHEADER => $headerLines,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_PROXY => '',
            CURLOPT_NOPROXY => '*',
        ]);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        }

        $raw = curl_exec($ch);
        if ($raw === false) {
            throw new RuntimeException('Request failed: ' . curl_error($ch));
        }

        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $headerSize = (int) curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        curl_close($ch);

        $parsed = [];
        foreach (explode("\r\n", substr($raw, 0, $headerSize)) as $line) {
            if (str_contains($line, ':')) {
                [$name, $value] = explode(':', $line, 2);
                $parsed[strtolower(trim($name))] = trim($value);
            }
        }

        return ['status' => $status, 'headers' => $parsed, 'body' => substr($raw, $headerSize)];
    }

    /** @return array{status: int, headers: array<string, string>, body: string} */
    public function get(string $path): array
    {
        return $this->request('GET', $path);
    }

    public function forgetCookies(): void
    {
        @unlink($this->cookieJar);
    }

    public function stop(): void
    {
        if (is_resource($this->process)) {
            proc_terminate($this->process);
            proc_close($this->process);
        }
        $this->process = null;

        self::removeDirectory($this->root);
        @unlink($this->cookieJar);
    }

    public function __destruct()
    {
        $this->stop();
    }

    private function copySkeleton(): void
    {
        $source = dirname(__DIR__, 2);
        mkdir($this->root, 0777, true);

        foreach (scandir($source) ?: [] as $entry) {
            if (in_array($entry, ['.', '..', '.git', '.env', 'tests', '.phpunit.cache'], true)) {
                continue;
            }

            if ($entry === 'vendor') {
                symlink($source . '/vendor', $this->root . '/vendor');
                continue;
            }

            self::copy($source . '/' . $entry, $this->root . '/' . $entry);
        }

        copy($source . '/.env.example', $this->root . '/.env');
        @mkdir($this->root . '/storage/logs', 0777, true);
    }

    /** @param array<string, string> $env */
    private function launch(array $env): void
    {
        $socket = stream_socket_server('tcp://127.0.0.1:0');
        $name = stream_socket_get_name($socket, false);
        fclose($socket);
        $this->port = (int) substr((string) $name, strrpos((string) $name, ':') + 1);

        $command = [
            PHP_BINARY, '-S', '127.0.0.1:' . $this->port,
            '-t', $this->root . '/public',
            $this->root . '/public/index.php',
        ];

        $this->process = proc_open(
            $command,
            [0 => ['pipe', 'r'], 1 => ['file', '/dev/null', 'a'], 2 => ['file', '/dev/null', 'a']],
            $pipes,
            $this->root,
            array_merge(self::baseEnvironment(), $env)
        );

        for ($i = 0; $i < 100; $i++) {
            $connection = @fsockopen('127.0.0.1', $this->port);
            if ($connection !== false) {
                fclose($connection);
                return;
            }
            usleep(50_000);
        }

        throw new RuntimeException('The PHP built-in server did not start.');
    }

    /** @return array<string, string> */
    private static function baseEnvironment(): array
    {
        $env = [];
        foreach (['PATH', 'HOME', 'TMPDIR'] as $key) {
            $value = getenv($key);
            if ($value !== false) {
                $env[$key] = $value;
            }
        }

        return $env;
    }

    private static function copy(string $from, string $to): void
    {
        if (is_dir($from) && !is_link($from)) {
            mkdir($to, 0777, true);
            foreach (scandir($from) ?: [] as $entry) {
                if ($entry !== '.' && $entry !== '..') {
                    self::copy($from . '/' . $entry, $to . '/' . $entry);
                }
            }
            return;
        }

        copy($from, $to);
    }

    private static function removeDirectory(string $path): void
    {
        if (is_link($path) || is_file($path)) {
            @unlink($path);
            return;
        }

        if (!is_dir($path)) {
            return;
        }

        foreach (scandir($path) ?: [] as $entry) {
            if ($entry !== '.' && $entry !== '..') {
                self::removeDirectory($path . '/' . $entry);
            }
        }

        @rmdir($path);
    }
}
