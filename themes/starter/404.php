<?php
get_template_part('header', ['site' => $site, 'pageTitle' => 'Page Not Found', 'theme' => $theme, 'menus' => $menus]);
?>
<div class="container py-5 text-center">
    <h1>404 — Page Not Found</h1>
    <p>The page you're looking for doesn't exist.</p>
    <a href="/" class="btn btn-primary">Go Home</a>
</div>
<?php get_template_part('footer', ['site' => $site, 'theme' => $theme, 'menus' => $menus]); ?>
