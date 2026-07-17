<?php
/**
 * Starter Theme Header
 * @var array $site  Site settings from database
 * @var string $pageTitle Page title
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo htmlspecialchars($pageTitle ?? $site['site_name'] ?? 'Monsoon CMS', ENT_QUOTES, 'UTF-8'); ?></title>
    <?php
    $assets = $theme['assets'] ?? [];
    foreach (($assets['css'] ?? []) as $css): ?>
        <link rel="stylesheet" href="/themes/starter/<?php echo htmlspecialchars($css, ENT_QUOTES, 'UTF-8'); ?>">
    <?php endforeach; ?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<header class="site-header">
    <div class="container">
        <nav class="navbar navbar-expand-lg">
            <a class="navbar-brand" href="/">
                <?php echo htmlspecialchars($site['site_name'] ?? 'Monsoon CMS', ENT_QUOTES, 'UTF-8'); ?>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <?php foreach (($menus['primary'] ?? []) as $item): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo htmlspecialchars($item['url'] ?? '#', ENT_QUOTES, 'UTF-8'); ?>">
                                <?php echo htmlspecialchars($item['label'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </nav>
    </div>
</header>
<main class="site-main">
