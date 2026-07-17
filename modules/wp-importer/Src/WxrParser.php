<?php

declare(strict_types=1);

namespace Monsoon\Modules\WpImporter\Src;

use Monsoon\Kernel\Uuid;

final class WxrParser
{
    public function parse(string $xmlContent): array
    {
        libxml_use_internal_errors(true);
        $doc = new \DOMDocument();
        $doc->loadXML($xmlContent);
        $errors = libxml_get_errors();
        libxml_clear_errors();

        if (!empty($errors)) {
            throw new \RuntimeException('Invalid WXR XML: ' . $errors[0]->message);
        }

        $xpath = new \DOMXPath($doc);
        $xpath->registerNamespace('wp', 'http://wordpress.org/export/1.2/');
        $xpath->registerNamespace('dc', 'http://purl.org/dc/elements/1.1/');
        $xpath->registerNamespace('content', 'http://purl.org/rss/1.0/modules/content/');
        $xpath->registerNamespace('excerpt', 'http://wordpress.org/export/1.2/excerpt/');
        $xpath->registerNamespace('wfw', 'http://wellformedweb.org/CommentAPI/');

        $result = [
            'authors' => [],
            'categories' => [],
            'tags' => [],
            'posts' => [],
            'pages' => [],
            'media' => [],
            'base_url' => '',
        ];

        $channel = $xpath->query('/rss/channel')->item(0);
        if ($channel) {
            $wpBaseUrl = $xpath->query('wp:base_site_url', $channel)->item(0);
            $result['base_url'] = $wpBaseUrl ? $wpBaseUrl->textContent : '';
        }

        foreach ($xpath->query('/rss/channel/wp:author') as $author) {
            $authorId = $xpath->query('wp:author_id', $author)->item(0)?->textContent ?? '';
            $authorLogin = $xpath->query('wp:author_login', $author)->item(0)?->textContent ?? '';
            $authorEmail = $xpath->query('wp:author_email', $author)->item(0)?->textContent ?? '';
            $authorDisplay = $xpath->query('wp:author_display_name', $author)->item(0)?->textContent ?? '';
            $authorFirst = $xpath->query('wp:author_first_name', $author)->item(0)?->textContent ?? '';
            $authorLast = $xpath->query('wp:author_last_name', $author)->item(0)?->textContent ?? '';

            $result['authors'][$authorId] = [
                'wp_id' => $authorId,
                'login' => $authorLogin,
                'email' => $authorEmail,
                'display_name' => $authorDisplay,
                'first_name' => $authorFirst,
                'last_name' => $authorLast,
            ];
        }

        foreach ($xpath->query('/rss/channel/wp:category') as $cat) {
            $termId = $xpath->query('wp:term_id', $cat)->item(0)?->textContent ?? '';
            $slug = $xpath->query('wp:category_nicename', $cat)->item(0)?->textContent ?? '';
            $name = $xpath->query('wp:cat_name', $cat)->item(0)?->textContent ?? '';
            $parent = $xpath->query('wp:category_parent', $cat)->item(0)?->textContent ?? '';

            $result['categories'][$termId] = [
                'wp_id' => $termId,
                'slug' => $slug,
                'name' => $name,
                'parent_wp_id' => $parent,
                'taxonomy' => 'category',
            ];
        }

        foreach ($xpath->query('/rss/channel/wp:tag') as $tag) {
            $termId = $xpath->query('wp:term_id', $tag)->item(0)?->textContent ?? '';
            $slug = $xpath->query('wp:tag_slug', $tag)->item(0)?->textContent ?? '';
            $name = $xpath->query('wp:tag_name', $tag)->item(0)?->textContent ?? '';

            $result['tags'][$termId] = [
                'wp_id' => $termId,
                'slug' => $slug,
                'name' => $name,
                'taxonomy' => 'post_tag',
            ];
        }

        foreach ($xpath->query('/rss/channel/item') as $item) {
            $postType = $xpath->query('wp:post_type', $item)->item(0)?->textContent ?? 'post';
            $status = $xpath->query('wp:status', $item)->item(0)?->textContent ?? 'publish';
            $postId = $xpath->query('wp:post_id', $item)->item(0)?->textContent ?? '';
            $date = $xpath->query('wp:post_date', $item)->item(0)?->textContent ?? '';
            $dateGmt = $xpath->query('wp:post_date_gmt', $item)->item(0)?->textContent ?? '';
            $modified = $xpath->query('wp:post_modified', $item)->item(0)?->textContent ?? '';
            $modifiedGmt = $xpath->query('wp:post_modified_gmt', $item)->item(0)?->textContent ?? '';
            $title = $xpath->query('title', $item)->item(0)?->textContent ?? '';
            $contentEncoded = $xpath->query('content:encoded', $item)->item(0)?->textContent ?? '';
            $excerpt = $xpath->query('excerpt:encoded', $item)->item(0)?->textContent ?? '';
            $link = $xpath->query('link', $item)->item(0)?->textContent ?? '';
            $guid = $xpath->query('guid', $item)->item(0)?->textContent ?? '';
            $postName = $xpath->query('wp:post_name', $item)->item(0)?->textContent ?? '';
            $postParent = $xpath->query('wp:post_parent', $item)->item(0)?->textContent ?? '';
            $menuOrder = $xpath->query('wp:menu_order', $item)->item(0)?->textContent ?? '0';
            $commentStatus = $xpath->query('wp:comment_status', $item)->item(0)?->textContent ?? 'open';
            $pingStatus = $xpath->query('wp:ping_status', $item)->item(0)?->textContent ?? 'open';
            $authorLogin = $xpath->query('dc:creator', $item)->item(0)?->textContent ?? '';

            $categories = [];
            foreach ($xpath->query('category[@domain="category"]', $item) as $cat) {
                $nicename = $cat->getAttribute('nicename');
                if ($nicename) $categories[] = $nicename;
            }

            $tags = [];
            foreach ($xpath->query('category[@domain="post_tag"]', $item) as $tag) {
                $nicename = $tag->getAttribute('nicename');
                if ($nicename) $tags[] = $nicename;
            }

            $meta = [];
            foreach ($xpath->query('wp:postmeta', $item) as $pm) {
                $key = $xpath->query('wp:meta_key', $pm)->item(0)?->textContent ?? '';
                $value = $xpath->query('wp:meta_value', $pm)->item(0)?->textContent ?? '';
                if ($key) $meta[$key] = $value;
            }

            $postData = [
                'wp_id' => $postId,
                'post_type' => $postType,
                'status' => $status,
                'title' => $title,
                'content' => $contentEncoded,
                'excerpt' => $excerpt,
                'slug' => $postName,
                'date' => $date,
                'date_gmt' => $dateGmt,
                'modified' => $modified,
                'modified_gmt' => $modifiedGmt,
                'link' => $link,
                'guid' => $guid,
                'parent_wp_id' => $postParent,
                'menu_order' => (int)$menuOrder,
                'comment_status' => $commentStatus,
                'ping_status' => $pingStatus,
                'author_login' => $authorLogin,
                'categories' => $categories,
                'tags' => $tags,
                'meta' => $meta,
            ];

            if ($postType === 'attachment') {
                $attachmentUrl = $xpath->query('wp:attachment_url', $item)->item(0)?->textContent ?? '';
                $postData['attachment_url'] = $attachmentUrl;
                $result['media'][] = $postData;
            } elseif ($postType === 'post') {
                $result['posts'][] = $postData;
            } elseif ($postType === 'page') {
                $result['pages'][] = $postData;
            }
        }

        return $result;
    }
}