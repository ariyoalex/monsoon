<?php

declare(strict_types=1);

namespace Monsoon\Kernel;

final class PageCacheMiddleware
{
    private PageCache $pageCache;

    public function __construct(PageCache $pageCache)
    {
        $this->pageCache = $pageCache;
    }

    public function handle(Request $request, callable $next): Response
    {
        $method = $request->getMethod();
        $uri = $request->getUri();

        if ($this->pageCache->shouldCache($method, $uri, $request->getHeaders(), $request->getCookies())) {
            $cacheKey = $this->pageCache->generateKey($method, $uri, $request->getHeaders());
            
            if ($method === 'HEAD') {
                $cached = $this->pageCache->get($cacheKey);
                if ($cached !== null) {
                    return Response::html('')->withStatus(200);
                }
            } else {
                $cached = $this->pageCache->get($cacheKey);
                if ($cached !== null) {
                    $response = Response::html($cached);
                    $response->headers['X-Cache'] = 'HIT';
                    return $response;
                }
            }
        }

        $response = $next($request);

        if ($this->pageCache->shouldCache($method, $uri, $request->getHeaders(), $request->getCookies())
            && $response->getStatusCode() === 200
            && $method === 'GET'
        ) {
            $cacheKey = $this->pageCache->generateKey($method, $uri, $request->getHeaders());
            $body = $response->getBody();
            
            if ($this->pageCache->set($cacheKey, $body)) {
                $response->headers['X-Cache'] = 'MISS';
            }
        }

        return $response;
    }
}