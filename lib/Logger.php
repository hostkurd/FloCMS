<?php
namespace HostKurd\Flocms\Lib;

use Psr\Log\AbstractLogger;

final class Logger extends AbstractLogger
{
    public function __construct(private string $path) {}

    public function log($level, $message, array $context = []): void
    {
        $ts = date('Y-m-d H:i:s');
        $ctx = $context ? json_encode($context, JSON_UNESCAPED_UNICODE) : '';
        $line = "[$ts] $level: $message $ctx\n";
        file_put_contents($this->path, $line, FILE_APPEND);
    }
}