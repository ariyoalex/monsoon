<?php

declare(strict_types=1);

namespace Monsoon\Modules\SeoBasics;

use Monsoon\Kernel\Response;
use Monsoon\Kernel\Router;
use Monsoon\Kernel\ThemeHooks;

final class SeoModule
{
    private \mysqli $db;
    private SeoService $seoService;

    public function __construct(\mysqli $db)
    {
        $this->db = $db;
        $this->seoService = new SeoService($db);
    }

    public function registerRoutes(Router $router): void
    {
        $router->addRoute('GET', '/api/v1/seo/{contentId}', function (array $params) {
            $meta = $this->seoService->getMetaForContent($params['contentId']);
            return Response::json(['data' => $meta]);
        });

        $router->addRoute('PUT', '/api/v1/seo/{contentId}', function (array $params) {
            $data = json_decode((string) file_get_contents('php://input'), true) ?? [];
            $meta = $this->seoService->saveMeta($params['contentId'], $data);
            return Response::json(['data' => $meta]);
        });

        $router->addRoute('GET', '/sitemap.xml', function () {
            $xml = $this->seoService->generateSitemap();
            return new Response(200, ['Content-Type' => 'application/xml'], $xml);
        });

        $router->addRoute('GET', '/robots.txt', function () {
            $txt = $this->seoService->generateRobotsTxt();
            return new Response(200, ['Content-Type' => 'text/plain'], $txt);
        });
    }

    public function registerHooks(): void
    {
        $hooks = ThemeHooks::getInstance();

        $hooks->register('theme:head', function ($data) {
            if (!is_array($data) || empty($data['content'])) {
                return $data;
            }
            $head = $this->onContentHead($data['content']);
            $data['head_extra'] = ($data['head_extra'] ?? '') . $head;
            return $data;
        });
    }

    public function onContentHead(array $content): string
    {
        $seoMeta = $this->seoService->getMetaForContent($content['id'] ?? '');
        $html = '';

        if (!empty($seoMeta['meta_title'])) {
            $html .= '<meta name="title" content="' . htmlspecialchars($seoMeta['meta_title'], ENT_QUOTES) . '">' . "\n";
        }
        if (!empty($seoMeta['meta_description'])) {
            $html .= '<meta name="description" content="' . htmlspecialchars($seoMeta['meta_description'], ENT_QUOTES) . '">' . "\n";
        }
        if (!empty($seoMeta['noindex'])) {
            $html .= '<meta name="robots" content="noindex, nofollow">' . "\n";
        }

        if (!empty($seoMeta['og_title'])) {
            $html .= '<meta property="og:title" content="' . htmlspecialchars($seoMeta['og_title'], ENT_QUOTES) . '">' . "\n";
        }
        if (!empty($seoMeta['og_description'])) {
            $html .= '<meta property="og:description" content="' . htmlspecialchars($seoMeta['og_description'], ENT_QUOTES) . '">' . "\n";
        }
        if (!empty($seoMeta['og_image'])) {
            $html .= '<meta property="og:image" content="' . htmlspecialchars($seoMeta['og_image'], ENT_QUOTES) . '">' . "\n";
        }
        $html .= '<meta property="og:type" content="article">' . "\n";

        $schema = $this->seoService->generateSchema($content, $seoMeta);
        $html .= $schema . "\n";

        return $html;
    }

    public function getSeoService(): SeoService
    {
        return $this->seoService;
    }
}
