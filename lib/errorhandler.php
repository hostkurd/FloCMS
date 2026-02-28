<?php

class ErrorHandler
{
    public static function register(): void
    {
        $raw = trim((string)Env::get('APP_DEBUG', 'false'));
        $debug = filter_var($raw, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        $debug = ($debug === null) ? false : $debug;

        ini_set('display_errors', $debug ? '1' : '0');
        ini_set('log_errors', '1');
        error_reporting(E_ALL);

        set_error_handler(function ($severity, $message, $file, $line) {
            if (!(error_reporting() & $severity)) return false;
            throw new ErrorException($message, 0, $severity, $file, $line);
        });

        set_exception_handler(function (Throwable $e) use ($debug) {
            self::handle($e, $debug);
        });

        register_shutdown_function(function () use ($debug) {
            $err = error_get_last();
            if (!$err) return;

            $fatalTypes = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR];
            if (in_array($err['type'], $fatalTypes, true)) {
                $e = new ErrorException($err['message'], 0, $err['type'], $err['file'], $err['line']);
                self::handle($e, $debug);
            }
        });
    }

    private static function handle(Throwable $e, bool $debug): void
    {
        // Never let the handler throw and cause cascading failures
        try {
            error_log((string) $e);

            // Clean any partially rendered output so we can show the error page cleanly
            while (ob_get_level() > 0) {
                ob_end_clean();
            }

            [$status, $page] = self::mapToErrorPage($e);

            // Set HTTP status (only if headers not already sent)
            if (!headers_sent()) {
                http_response_code($status);
            }

            if ($debug) {
                while (ob_get_level() > 0) ob_end_clean();
                if (!headers_sent()) {
                    http_response_code($status);
                    header('Content-Type: text/html; charset=utf-8');
                }
                self::renderDebugPage($e, $status);
                exit;

                // if (!headers_sent()) {
                //     header('Content-Type: text/plain; charset=utf-8');
                // }
                // echo $e;
                // exit;
            }

            $base = ROOT . DS . 'views' . DS . 'errors' . DS;
            $file = $base . $page;

            // Only compute DB-friendly messages for DB errors
            $data = [
                'message'   => ($e instanceof PDOException) ? self::friendlyDbMessage($e) : 'Internal Server Error',
                'errorCode' => ($e instanceof PDOException) ? self::pdoDriverCode($e) : null,
            ];

            if (is_file($file)) {
                include $file;
                exit;
            }

            $fallback = $base . '500.html';
            if (is_file($fallback)) {
                include $fallback;
                exit;
            }

            echo "Internal Server Error";
            exit;

        } catch (Throwable $handlerFailure) {
            // Absolute last-resort fallback (don’t recurse)
            error_log("ErrorHandler failed: " . (string) $handlerFailure);

            while (ob_get_level() > 0) {
                ob_end_clean();
            }

            if (!headers_sent()) {
                http_response_code(500);
            }

            echo "Internal Server Error";
            exit;
        }
    }

    private static function mapToErrorPage(Throwable $e): array
    {
        // 404 etc.
        if ($e instanceof HttpException) {
            $status = $e->status;
            return match ($status) {
                404 => [404, '404.html'],
                default => [$status, '500.html'],
            };
        }

        // Parse / compile errors
        if ($e instanceof ParseError) {
            return [500, 'parserror.html'];
        }

        // If converted to ErrorException by set_error_handler:
        if ($e instanceof ErrorException) {
            // You can refine by $e->getSeverity() if you want
            // But parse/compile are usually caught by shutdown (above)
            return [500, '500.html'];
        }

        // PDO errors (DB)
        if ($e instanceof PDOException) {
            $code = self::pdoDriverCode($e);
            // server down / connection refused / can't connect
            if (in_array($code, [2002, 2006, 2013], true)) {
                return [500, 'nodbserver.html'];
            }
            // other query issues
            return [500, 'queryerror.html'];
        }

        // Default
        return [500, '500.html'];
    }

    private static function pdoDriverCode(PDOException $e): ?int
    {
        $info = $e->errorInfo ?? null;
        if (is_array($info) && isset($info[1]) && is_numeric($info[1])) {
            return (int)$info[1];
        }
        return null;
    }

    private static function friendlyDbMessage(PDOException $e): string
    {
        $code = self::pdoDriverCode($e);

        return match ($code) {
            1146 => "Database table does not exist.",
            1064 => "SQL syntax error.",
            1045 => "Database access denied (invalid username/password).",
            1049 => "Database not found.",
            1062 => "Duplicate record (already exists).",
            1451, 1452 => "Operation blocked due to related data (foreign key constraint).",
            2002, 2006, 2013 => "Database server is not available right now.",
            default => "A database error occurred.",
        };
    }

    // In debug mode, show a detailed error page with code context, trace, and request info
    private static function renderDebugPage(Throwable $e, int $status = 500): void
    {
        $message = self::e($e->getMessage());
        $type    = self::e(get_class($e));
        $file    = $e->getFile();
        $line    = (int)$e->getLine();

        $fileEsc = self::e($file);
        $trace   = $e->getTrace();

        [$codeHtml, $codeTitle] = self::renderCodeContext($file, $line, 6);

        $req = self::collectRequestInfo();
        $reqHtml = self::renderKeyValueTable($req);

        // Collapse heavy globals by default
        $sessionHtml = self::renderDumpSection('Session', $_SESSION ?? [], true);
        $getHtml     = self::renderDumpSection('GET', $_GET ?? [], true);
        $postHtml    = self::renderDumpSection('POST', $_POST ?? [], true);
        $serverHtml  = self::renderDumpSection('SERVER (filtered)', self::filteredServer($_SERVER ?? []), true);

        $traceHtml = self::renderTrace($e);

        $file = ROOT . DS . 'views' . DS . 'errors' . DS . 'debug.html';

        if (!is_file($file)) {
            echo "<h1>Debug template missing.</h1>";
            echo "<pre>" . htmlspecialchars((string)$e) . "</pre>";
            return;
        }

        include $file;
    }

    private static function renderCodeContext(string $file, int $line, int $radius = 6): array
    {
        $title = 'Code Context';

        if (!is_file($file) || !is_readable($file)) {
            $html = "<div class='code'><div class='row'><div class='ln'>—</div><div class='src'>Unable to read file: " . self::e($file) . "</div></div></div>";
            return [$html, $title];
        }

        $lines = @file($file, FILE_IGNORE_NEW_LINES);
        if (!is_array($lines)) {
            $html = "<div class='code'><div class='row'><div class='ln'>—</div><div class='src'>Unable to load file contents.</div></div></div>";
            return [$html, $title];
        }

        $total = count($lines);
        $start = max(1, $line - $radius);
        $end   = min($total, $line + $radius);

        $title = "Code Context · " . self::e(basename($file));

        $out = "<div class='code'>";
        for ($i = $start; $i <= $end; $i++) {
            $src = $lines[$i - 1] ?? '';
            $hit = ($i === $line) ? " hit" : "";
            $out .= "<div class='row{$hit}'>
                        <div class='ln'>{$i}</div>
                        <div class='src'>" . self::e($src) . "</div>
                    </div>";
        }
        $out .= "</div>";

        return [$out, $title];
    }

    private static function renderTrace(Throwable $e): string
    {
        // Use Trace + top frame info for the thrown location
        $trace = $e->getTrace();

        if (!$trace) {
            return "<div class='muted'>No trace available.</div>";
        }

        // Collapsible "raw trace" too
        $raw = self::e($e->getTraceAsString());

        $items = "";
        foreach ($trace as $idx => $t) {
            $tFile = isset($t['file']) ? self::e($t['file']) : '<span class="muted">[internal]</span>';
            $tLine = isset($t['line']) ? (int)$t['line'] : 0;

            $class = $t['class'] ?? '';
            $type  = $t['type'] ?? '';
            $func  = $t['function'] ?? '';

            $call = self::e($class . $type . $func . '()');

            $items .= "<div class='trace-item'>
                <div class='trace-head'>
                    <div>#{$idx} <span class='trace-file'>{$tFile}</span>" . ($tLine ? " : {$tLine}" : "") . "</div>
                    <div class='pill'>{$call}</div>
                </div>
            </div>";
        }

        return $items . self::renderDetails("Raw trace", "<pre>{$raw}</pre>", true);
    }

    private static function collectRequestInfo(): array
    {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $uri    = $_SERVER['REQUEST_URI'] ?? '';
        $url    = $scheme . '://' . $host . $uri;

        return [
            'URL'        => $url,
            'Method'     => $_SERVER['REQUEST_METHOD'] ?? '',
            'IP'         => $_SERVER['REMOTE_ADDR'] ?? '',
            'User Agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'Controller' => method_exists('App', 'getRouter') && App::getRouter() ? (App::getRouter()->getController() ?? '') : '',
            'Action'     => method_exists('App', 'getRouter') && App::getRouter() ? (App::getRouter()->getAction() ?? '') : '',
            'Language'   => method_exists('App', 'getRouter') && App::getRouter() ? (App::getRouter()->getLanguage() ?? '') : '',
        ];
    }

    private static function filteredServer(array $server): array
    {
        // Avoid dumping secrets
        $blocked = ['PHP_AUTH_PW', 'HTTP_COOKIE', 'COOKIE', 'Authorization', 'HTTP_AUTHORIZATION'];
        foreach ($blocked as $k) {
            if (isset($server[$k])) $server[$k] = '[filtered]';
        }
        return $server;
    }

    private static function renderKeyValueTable(array $data): string
    {
        $rows = "";
        foreach ($data as $k => $v) {
            $rows .= "<tr>
                <td>" . self::e((string)$k) . "</td>
                <td>" . self::e(self::stringify($v)) . "</td>
            </tr>";
        }

        return "<table class='kv'>{$rows}</table>";
    }

    private static function renderDumpSection(string $title, mixed $value, bool $collapsed = true): string
    {
        // Limit size so huge arrays don't kill the page
        $pretty = self::e(self::prettyPrintLimited($value, 20000));
        return self::renderDetails($title, "<pre>{$pretty}</pre>", $collapsed);
    }

    private static function renderDetails(string $title, string $htmlBody, bool $collapsed = true): string
    {
        $open = $collapsed ? "" : " open";
        $badge = "<span class='pill'>{$title}</span>";

        return "<details{$open}>
            <summary>{$badge}<span class='muted'>click to " . ($collapsed ? "expand" : "collapse") . "</span></summary>
            <div class='details-body'>{$htmlBody}</div>
        </details>";
    }

    private static function prettyPrintLimited(mixed $value, int $maxChars): string
    {
        $out = print_r($value, true);
        if (strlen($out) > $maxChars) {
            $out = substr($out, 0, $maxChars) . "\n… (truncated)";
        }
        return $out;
    }

    private static function stringify(mixed $v): string
    {
        if (is_bool($v)) return $v ? 'true' : 'false';
        if ($v === null) return 'null';
        if (is_scalar($v)) return (string)$v;
        return json_encode($v, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '[unprintable]';
    }

    private static function e(string $s): string
    {
        return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

}