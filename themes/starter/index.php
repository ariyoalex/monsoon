<?php
/**
 * Starter Theme - Index Template
 * @var array $site Site settings
 * @var array $content Current content item (if singular) or null
 * @var array $posts Array of content items (if archive) or null
 * @var array $menus Menu arrays
 * @var array $theme Theme config from theme.json
 */
get_template_part('header', ['site' => $site, 'pageTitle' => $content['title'] ?? 'Home', 'theme' => $theme, 'menus' => $menus]);
?>

<div class="container py-5">
    <?php if (!empty($content)): ?>
        <article class="entry-content">
            <h1 class="entry-title"><?php echo htmlspecialchars($content['title'] ?? '', ENT_QUOTES, 'UTF-8'); ?></h1>
            <?php if ($content['type'] === 'post'): ?>
                <div class="entry-meta mb-4">
                    <small class="text-muted">
                        Published <?php echo htmlspecialchars($content['published_at'] ?? $content['created_at'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
                    </small>
                </div>
            <?php endif; ?>
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

    <?php elseif (!empty($posts)): ?>
        <div class="row">
            <?php foreach ($posts as $post): ?>
                <div class="col-md-6 col-lg-4 mb-4">
                    <article class="card h-100">
                        <div class="card-body">
                            <h2 class="card-title h5">
                                <a href="/<?php echo htmlspecialchars($post['slug'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" class="text-decoration-none text-dark">
                                    <?php echo htmlspecialchars($post['title'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
                                </a>
                            </h2>
                            <p class="card-text text-muted">
                                <?php echo htmlspecialchars(mb_strimwidth(strip_tags($post['body'] ?? ''), 0, 120, '...'), ENT_QUOTES, 'UTF-8'); ?>
                            </p>
                        </div>
                        <div class="card-footer bg-transparent">
                            <small class="text-muted">
                                <?php echo htmlspecialchars($post['created_at'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
                            </small>
                        </div>
                    </article>
                </div>
            <?php endforeach; ?>
        </div>

    <?php else: ?>
        <div class="text-center py-5">
            <h1>Welcome to <?php echo htmlspecialchars($site['site_name'] ?? 'Monsoon CMS', ENT_QUOTES, 'UTF-8'); ?></h1>
            <p class="lead">No content yet.</p>
        </div>
    <?php endif; ?>
</div>

<?php get_template_part('footer', ['site' => $site, 'theme' => $theme, 'menus' => $menus]); ?>
