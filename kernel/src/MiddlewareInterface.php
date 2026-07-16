<?php

declare(strict_types=1);

namespace Monsoon\Kernel;

interface MiddlewareInterface
{
    public function handle(Request $request, callable $next): Response;
}
