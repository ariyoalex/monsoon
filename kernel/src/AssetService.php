<?php

declare(strict_types=1);

namespace Monsoon\Kernel;

final class AssetService
{
    private string $publicDir;
    private string $cacheDir;
    private string $assetUrlBase;
    private string $version;

    public function __construct(string $publicDir, string $cacheDir, string $assetUrlBase = '/', string $version = '1.0.0')
    {
        $this->publicDir = rtrim($publicDir, '/') . '/';
        $this->cacheDir = rtrim($cacheDir, '/') . '/';
        $this->assetUrlBase = rtrim($assetUrlBase, '/') . '/';
        $this->version = $version;

        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
    }

    public function getCssUrl(array $files): string
    {
        $cacheFile = $this->cacheDir . 'css_' . md5(implode(',', $files)) . '.min.css';
        
        if (!is_file($cacheFile) || $this->isCacheStale($files, $cacheFile)) {
            $this->buildCssBundle($files, $cacheFile);
        }
        
        return $this->assetUrlBase . 'cache/' . basename($cacheFile) . '?v=' . $this->version;
    }

    public function getJsUrl(array $files): string
    {
        $cacheFile = $this->cacheDir . 'js_' . md5(implode(',', $files)) . '.min.js';
        
        if (!is_file($cacheFile) || $this->isCacheStale($files, $cacheFile)) {
            $this->buildJsBundle($files, $cacheFile);
        }
        
        return $this->assetUrlBase . 'cache/' . basename($cacheFile) . '?v=' . $this->version;
    }

    private function isCacheStale(array $files, string $cacheFile): bool
    {
        $cacheTime = filemtime($cacheFile);
        foreach ($files as $file) {
            $fullPath = $this->publicDir . ltrim($file, '/');
            if (is_file($fullPath) && filemtime($fullPath) > $cacheTime) {
                return true;
            }
        }
        return false;
    }

    private function buildCssBundle(array $files, string $outputFile): void
    {
        $content = '';
        foreach ($files as $file) {
            $fullPath = $this->publicDir . ltrim($file, '/');
            if (is_file($fullPath)) {
                $content .= '/* ' . $file . ' */' . "\n";
                $content .= file_get_contents($fullPath) . "\n\n";
            }
        }
        
        $minified = $this->minifyCss($content);
        $tempFile = $outputFile . '.tmp';
        file_put_contents($tempFile, $minified);
        rename($tempFile, $outputFile);
    }

    private function buildJsBundle(array $files, string $outputFile): void
    {
        $content = '';
        foreach ($files as $file) {
            $fullPath = $this->publicDir . ltrim($file, '/');
            if (is_file($fullPath)) {
                $content .= '/* ' . $file . ' */' . "\n";
                $content .= file_get_contents($fullPath) . "\n\n";
            }
        }
        
        $minified = $this->minifyJs($content);
        $tempFile = $outputFile . '.tmp';
        file_put_contents($tempFile, $minified);
        rename($tempFile, $outputFile);
    }

    private function minifyCss(string $css): string
    {
        // Remove comments
        $css = preg_replace('/\/\*[\s\S]*?\*\//', '', $css);
        // Remove whitespace around operators
        $css = preg_replace('/\s*([{}:;,>+~])\s*/', '$1', $css);
        // Remove whitespace around braces
        $css = preg_replace('/\s*{\s*/', '{', $css);
        $css = preg_replace('/\s*}\s*/', '}', $css);
        // Collapse multiple whitespace
        $css = preg_replace('/\s+/', ' ', $css);
        // Remove leading/trailing whitespace
        $css = trim($css);
        // Remove trailing semicolon before }
        $css = preg_replace('/;\s*}/', '}', $css);
        // Remove trailing semicolon before }
        $css = preg_replace('/;\s*}$/', '}', $css);
        return $css;
    }

    private function minifyJs(string $js): string
    {
        // Remove single-line comments (but preserve URLs)
        $js = preg_replace('#//[^\n]*\n#', "\n", $js);
        // Remove multi-line comments
        $js = preg_replace('#/\*[\s\S]*?\*/#', '', $js);
        // Remove whitespace around operators
        $js = preg_replace('#\s*([=+\-*/%&|^<>!]=?)\s*#', '$1', $js);
        // Remove whitespace around punctuation
        $js = preg_replace('#\s*([{}();,.:])\s*#', '$1', $js);
        // Collapse multiple whitespace
        $js = preg_replace('/\s+/', ' ', $js);
        // Remove leading/trailing whitespace
        $js = trim($js);
        return $js;
    }

    public function clearCache(): int
    {
        $deleted = 0;
        $files = glob($this->cacheDir . '*.min.css');
        $files = array_merge($files, glob($this->cacheDir . '*.min.js'));
        foreach ($files as $file) {
            if (@unlink($file)) {
                $deleted++;
            }
        }
        return $deleted;
    }

    public function getCacheStats(): array
    {
        $cssFiles = glob($this->cacheDir . '*.min.css');
        $jsFiles = glob($this->cacheDir . '*.min.js');
        $totalSize = 0;
        foreach ($cssFiles as $f) $totalSize += filesize($f);
        foreach ($jsFiles as $f) $totalSize += filesize($f);
        
        return [
            'css_bundles' => count($cssFiles),
            'js_bundles' => count($jsFiles),
            'total_size' => $totalSize,
            'total_size_human' => $this->formatBytes($totalSize),
        ];
    }

    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }

    public function getStyleHtml(): string
    {
        $cssFiles = [
            '/admin.css',
            '/landing.css',
        ];
        $url = $this->getCssUrl($cssFiles);
        return '<link rel="stylesheet" href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '">';
    }

    public function getScriptHtml(bool $inFooter = true): string
    {
        $jsFiles = [
            '/manage.js',
        ];
        $url = $this->getJsUrl($jsFiles);
        return '<script src="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '"></script>';
    }
}