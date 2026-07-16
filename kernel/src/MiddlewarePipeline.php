<?php

declare(strict_types=1);

namespace Monsoon\Kernel;

final class MiddlewarePipeline
{
    private array $middleware = [];

    public function pipe(MiddlewareInterface $middleware): self
    {
        $this->middleware[] = $middleware;
        return $this;
    }

    public function run(Request $request, callable $handler): Response
    {
        $pipeline = $handler;

        foreach (array_reverse($this->middleware) as $mw) {
            $pipeline = function (Request $req) use ($mw, $pipeline) {
                return $mw->handle($req, $pipeline);
            };
        }

        return $pipeline($request);
    }
}
