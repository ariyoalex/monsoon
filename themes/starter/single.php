<?php
get_template_part('header', ['site' => $site, 'pageTitle' => $content['title'] ?? 'Post', 'theme' => $theme, 'menus' => $menus]);
?>
<div class="container py-5">
    <article class="entry-content">
        <h1 class="entry-title"><?php echo htmlspecialchars($content['title'] ?? '', ENT_QUOTES, 'UTF-8'); ?></h1>
        <div class="entry-meta mb-4">
            <small class="text-muted">
                Published <?php echo htmlspecialchars($content['published_at'] ?? $content['created_at'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
            </small>
        </div>
        <div class="entry-body">
            <?php
            $blocks = json_decode($content['body'] ?? '[]', true);
            if (is_array($blocks)) {
                foreach ($blocks as $block) {
                    echo render_block($block);
                }
            } else {
                echo $content['body'] ?? '';
            }
            ?>
        </div>
    </article>
</div>
<?php get_template_part('footer', ['site' => $site, 'theme' => $theme, 'menus' => $menus]); ?>
