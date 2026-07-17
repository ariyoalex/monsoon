<?php

declare(strict_types=1);

namespace Monsoon\Kernel;

final class CsrfMiddleware implements MiddlewareInterface
{
    private const TOKEN_LENGTH = 32;

    public function handle(Request $request, callable $next): Response
    {
        if ($request->isApi()) {
            return $next($request);
        }

        Session::start();

        if ($request->isPost() || $request->isPut() || $request->isDelete()) {
            $token = $request->input('_csrf_token') ?? $request->header('X-CSRF-Token');
            $sessionToken = Session::get('_csrf_token');

            if ($token === '' || $sessionToken === null || $sessionToken === '' || !hash_equals((string) $sessionToken, (string) $token)) {
                if ($request->wantsJson()) {
                    return Response::error(403, 'CSRF token invalid or missing.');
                }
                return Response::error(403, 'CSRF token invalid or missing.');
            }
        }

        if (Session::get('_csrf_token') === null) {
            Session::set('_csrf_token', bin2hex(random_bytes(self::TOKEN_LENGTH)));
        }

        return $next($request);
    }

    public static function token(): string
    {
        Session::start();
        return Session::get('_csrf_token', '');
    }

    public static function field(): string
    {
        $token = self::token();
        return '<input type="hidden" name="_csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }
}
