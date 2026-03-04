<?php
namespace HostKurd\Flocms\Lib\Http;

final class Request
{
    public function __construct(
        private array $get,
        private array $post,
        private array $server,
        private array $files,
        private array $cookie
    ) {}

    public static function fromGlobals(): self
    {
        return new self($_GET, $_POST, $_SERVER, $_FILES, $_COOKIE);
    }

    public function method(): string
    {
        return strtoupper($this->server['REQUEST_METHOD'] ?? 'GET');
    }

    public function isStateChanging(): bool
    {
        return in_array($this->method(), ['POST','PUT','PATCH','DELETE'], true);
    }

    public function uri(): string
    {
        return (string)($this->server['REQUEST_URI'] ?? '/');
    }

    public function header(string $name): ?string
    {
        $key = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
        return $this->server[$key] ?? null;
    }

    // unified input getter: POST overrides GET (common pattern)
    public function input(string $key, mixed $default = null): mixed
    {
        if (array_key_exists($key, $this->post)) return $this->post[$key];
        if (array_key_exists($key, $this->get))  return $this->get[$key];
        return $default;
    }

    public function all(): array
    {
        return array_merge($this->get, $this->post);
    }

    public function files(): array
    {
        return $this->files;
    }

    public function file(string $key): ?array
    {
        return $this->files[$key] ?? null;
    }
}