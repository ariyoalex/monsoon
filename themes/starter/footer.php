<?php
/**
 * Starter Theme Footer
 * @var array $site Site settings
 */
?>
</main>
<footer class="site-footer">
    <div class="container py-4">
        <div class="row">
            <div class="col-md-6">
                <p>&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($site['site_name'] ?? 'Monsoon CMS', ENT_QUOTES, 'UTF-8'); ?>. All rights reserved.</p>
            </div>
            <div class="col-md-6 text-md-end">
                <ul class="list-inline mb-0">
                    <?php foreach (($menus['footer'] ?? []) as $item): ?>
                        <li class="list-inline-item">
                            <a href="<?php echo htmlspecialchars($item['url'] ?? '#', ENT_QUOTES, 'UTF-8'); ?>">
                                <?php echo htmlspecialchars($item['label'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
</footer>
<?php foreach (($assets['js'] ?? []) as $js): ?>
    <script src="/themes/starter/<?php echo htmlspecialchars($js, ENT_QUOTES, 'UTF-8'); ?>"></script>
<?php endforeach; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
