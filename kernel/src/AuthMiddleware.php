<?php

declare(strict_types=1);

namespace Monsoon\Kernel;

final class AuthMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, callable $next): Response
    {
        $path = $request->path();

        if (!str_starts_with($path, '/manage/') || $path === '/manage/login') {
            return $next($request);
        }

        Session::start();
        $auth = Auth::getInstance();

        if (!$auth->isAuthenticated()) {
            if ($request->wantsJson() || $request->isApi()) {
                return Response::error(401, 'Authentication required.');
            }
            return Response::redirect('/manage/login');
        }

        return $next($request);
    }
}
