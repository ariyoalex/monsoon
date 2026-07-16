<?php

declare(strict_types=1);

namespace Monsoon\Kernel;

use ErrorException;
use Throwable;

final class ErrorHandler
{
    private static ?array $previousHandlers = null;

    public static function register(): void
    {
        self::$previousHandlers = [
            'error' => set_error_handler(self::handleError(...)),
            'exception' => set_exception_handler(self::handleException(...)),
        ];

        register_shutdown_function(self::restore(...));
    }

    public static function handleError(int $severity, string $message, string $file, int $line): bool
    {
        if (!(error_reporting() & $severity)) {
            return false;
        }

        throw new ErrorException($message, 0, $severity, $file, $line);
    }

    public static function handleException(Throwable $exception): void
    {
        http_response_code(500);

        $env = defined('MONSOON_ENV') ? MONSOON_ENV : 'development';

        if ($env === 'development') {
            $html = '<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Monsoon CMS - Error</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
<div class="card shadow-sm">
<div class="card-header bg-danger text-white">
<h1 class="h4 mb-0">Monsoon CMS: Unhandled Exception</h1>
</div>
<div class="card-body">
<h2 class="h5 text-danger">' . htmlspecialchars($exception->getMessage(), ENT_QUOTES, 'UTF-8') . '</h2>
<hr>
<dl class="row mb-0">
<dt class="col-sm-2">File</dt>
<dd class="col-sm-10"><code>' . htmlspecialchars($exception->getFile(), ENT_QUOTES, 'UTF-8') . '</code></dd>
<dt class="col-sm-2">Line</dt>
<dd class="col-sm-10">' . $exception->getLine() . '</dd>
<dt class="col-sm-2">Type</dt>
<dd class="col-sm-10">' . htmlspecialchars(get_class($exception), ENT_QUOTES, 'UTF-8') . '</dd>
</dl>
<hr>
<h3 class="h6">Stack Trace</h3>
<pre class="bg-dark text-light p-3 rounded overflow-auto" style="max-height: 400px;">' . htmlspecialchars($exception->getTraceAsString(), ENT_QUOTES, 'UTF-8') . '</pre>
</div>
</div>
</div>
</body>
</html>';

            echo $html;
        } else {
            echo '<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Something went wrong</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
<div class="card shadow-sm">
<div class="card-header bg-secondary text-white">
<h1 class="h4 mb-0">Something went wrong</h1>
</div>
<div class="card-body">
<p class="mb-0">An unexpected error occurred. Please try again later.</p>
</div>
</div>
</div>
</body>
</html>';
        }

        error_log(sprintf(
            'Monsoon CMS Error: %s in %s:%d',
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine()
        ));
    }

    public static function restore(): void
    {
        if (self::$previousHandlers !== null) {
            if (self::$previousHandlers['error'] !== null) {
                set_error_handler(self::$previousHandlers['error']);
            }

            if (self::$previousHandlers['exception'] !== null) {
                set_exception_handler(self::$previousHandlers['exception']);
            }
        }
    }
}
