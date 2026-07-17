<?php
declare(strict_types=1);
namespace Monsoon\Kernel;

final class TemplateFunctions
{
    public static function renderBlock(array $block): string
    {
        $type = $block['type'] ?? '';
        $data = $block['data'] ?? [];
        switch ($type) {
            case 'heading':
                $level = (int)($data['level'] ?? 2);
                $level = max(1, min(6, $level));
                return '<h' . $level . '>' . htmlspecialchars($data['text'] ?? '', ENT_QUOTES, 'UTF-8') . '</h' . $level . '>';
            case 'paragraph':
                return '<p>' . ($data['text'] ?? '') . '</p>';
            case 'image':
                $alt = htmlspecialchars($data['alt'] ?? '', ENT_QUOTES, 'UTF-8');
                $src = htmlspecialchars($data['src'] ?? '', ENT_QUOTES, 'UTF-8');
                $caption = htmlspecialchars($data['caption'] ?? '', ENT_QUOTES, 'UTF-8');
                $align = htmlspecialchars($data['align'] ?? 'center', ENT_QUOTES, 'UTF-8');
                $html = '<figure class="wp-block-image align-' . $align . '">';
                $html .= '<img src="' . $src . '" alt="' . $alt . '">';
                if ($caption !== '') {
                    $html .= '<figcaption>' . $caption . '</figcaption>';
                }
                $html .= '</figure>';
                return $html;
            case 'list':
                $tag = !empty($data['ordered']) ? 'ol' : 'ul';
                $items = $data['items'] ?? [];
                $html = '<' . $tag . '>';
                foreach ($items as $item) {
                    $html .= '<li>' . htmlspecialchars($item, ENT_QUOTES, 'UTF-8') . '</li>';
                }
                $html .= '</' . $tag . '>';
                return $html;
            case 'quote':
                $html = '<blockquote>';
                $html .= '<p>' . htmlspecialchars($data['text'] ?? '', ENT_QUOTES, 'UTF-8') . '</p>';
                if (!empty($data['attribution'])) {
                    $html .= '<footer>' . htmlspecialchars($data['attribution'], ENT_QUOTES, 'UTF-8') . '</footer>';
                }
                $html .= '</blockquote>';
                return $html;
            case 'separator':
                return '<hr class="wp-block-separator">';
            case 'button':
                $text = htmlspecialchars($data['text'] ?? 'Click here', ENT_QUOTES, 'UTF-8');
                $url = htmlspecialchars($data['url'] ?? '#', ENT_QUOTES, 'UTF-8');
                $style = htmlspecialchars($data['style'] ?? 'primary', ENT_QUOTES, 'UTF-8');
                return '<div class="wp-block-button"><a class="wp-block-button__link btn btn-' . $style . '" href="' . $url . '">' . $text . '</a></div>';
            default:
                return '<!-- Unknown block type: ' . htmlspecialchars($type, ENT_QUOTES, 'UTF-8') . ' -->';
        }
    }
}

// Global functions for use in templates
function render_block(array $block): string
{
    return TemplateFunctions::renderBlock($block);
}

function get_template_part(string $slug, array $data = []): void
{
    $loader = new ThemeLoader(dirname(__DIR__, 2) . '/themes');
    $theme = $loader->getActiveTheme();
    if ($theme === null) return;
    $path = $theme['_path'] . '/' . $slug . '.php';
    if (is_file($path)) {
        extract($data);
        include $path;
    }
}

function bloginfo(string $key): void
{
    $settings = [
        'name' => 'Monsoon CMS',
        'charset' => 'UTF-8',
    ];
    echo htmlspecialchars($settings[$key] ?? '', ENT_QUOTES, 'UTF-8');
}

function home_url(string $path = ''): string
{
    return $path ?: '/';
}

function esc_url(string $url): string
{
    return htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
}

function has_custom_logo(): bool
{
    return false;
}

function body_class(): void
{
    echo 'class="home"';
}

function wp_head(): void {}
function wp_body_open(): void {}
function wp_nav_menu(array $args = []): void {}
