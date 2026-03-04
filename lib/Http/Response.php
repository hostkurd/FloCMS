<?php
namespace HostKurd\Flocms\Lib\Http;

final class Response
{
    private int $status = 200;
    private array $headers = [];
    private string $body = '';

    public static function html(string $html, int $status = 200): self
    {
        $r = new self();
        $r->status = $status;
        $r->headers['Content-Type'] = 'text/html; charset=utf-8';
        $r->body = $html;
        return $r;
    }

    public static function json(array $data, int $status = 200): self
    {
        $r = new self();
        $r->status = $status;
        $r->headers['Content-Type'] = 'application/json; charset=utf-8';
        $r->body = json_encode($data, JSON_UNESCAPED_UNICODE);
        return $r;
    }

    public function header(string $name, string $value): self
    {
        $this->headers[$name] = $value;
        return $this;
    }

    public function send(): void
    {
        if (!headers_sent()) {
            http_response_code($this->status);
            foreach ($this->headers as $k => $v) header("$k: $v");
        }
        echo $this->body;
    }
}