<?php
/**
 * Dynamic XML Sitemap Generator
 * BlogSphere — Blog Management & Publishing System
 */

require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/config/database.php';

// Set proper XML headers
header("Content-Type: application/xml; charset=utf-8");
header("X-Robots-Tag: noindex, follow");

$pdo = getDBConnection();

// Fetch all approved blogs
$stmt = $pdo->query("
    SELECT id, title, slug, created_at, updated_at 
    FROM blogs 
    WHERE status = 'approved' 
    ORDER BY updated_at DESC
");
$blogs = $stmt->fetchAll();

// Fetch categories
$categories = $pdo->query("SELECT id, name FROM categories ORDER BY name ASC")->fetchAll();

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <!-- Homepage -->
    <url>
        <loc><?= htmlspecialchars(rtrim(BASE_URL, '/') . '/') ?></loc>
        <lastmod><?= date('Y-m-d') ?></lastmod>
        <changefreq>daily</changefreq>
        <priority>1.0</priority>
    </url>

    <!-- Published Blogs Index -->
    <url>
        <loc><?= htmlspecialchars(BASE_URL . 'user/blogs.php') ?></loc>
        <lastmod><?= date('Y-m-d') ?></lastmod>
        <changefreq>daily</changefreq>
        <priority>0.8</priority>
    </url>

    <!-- Categories -->
    <?php foreach ($categories as $cat): ?>
    <url>
        <loc><?= htmlspecialchars(BASE_URL . 'user/blogs.php?category=' . $cat['id']) ?></loc>
        <changefreq>weekly</changefreq>
        <priority>0.6</priority>
    </url>
    <?php endforeach; ?>

    <!-- Individual Approved Blog Articles -->
    <?php foreach ($blogs as $b): ?>
    <url>
        <loc><?= htmlspecialchars(BASE_URL . 'user/blog_details.php?id=' . $b['id']) ?></loc>
        <lastmod><?= date('Y-m-d', strtotime($b['updated_at'] ?? $b['created_at'])) ?></lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.7</priority>
    </url>
    <?php endforeach; ?>
</urlset>
