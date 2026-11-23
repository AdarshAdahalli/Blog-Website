<?php
require_once __DIR__ . '/functions.php';

$pageTitle = $pageTitle ?? 'BlogSphere — Discover. Learn. Share.';
$metaDescription = $metaDescription ?? 'Explore expert blogs, articles, tutorials, and tech guides written by our community of writers on BlogSphere.';
$metaKeywords = $metaKeywords ?? 'blog, tech guides, programming, articles, tutorials, BlogSphere';
$metaAuthor = $metaAuthor ?? 'BlogSphere Editorial Team';

$user = isLoggedIn() ? getCurrentUser() : null;
$role = $_SESSION['user_role'] ?? 'guest';
$unreadMessages = $user ? getUnreadMessageCount($user['id']) : 0;
$unreadNotifications = $user ? getUnreadNotificationCount($user['id']) : 0;

// Determine Canonical URL
if (!isset($canonicalUrl)) {
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
    $queryString = !empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '';
    $canonicalUrl = rtrim(BASE_URL, '/') . $scriptName . $queryString;
}

// Robots directive: private dashboards and auth pages shouldn't be indexed
$scriptPath = $_SERVER['SCRIPT_NAME'] ?? '';
$isPrivateArea = (strpos($scriptPath, '/admin/') !== false) || 
                 (strpos($scriptPath, '/employee/') !== false) || 
                 (strpos($scriptPath, '/auth/') !== false);
$metaRobots = $metaRobots ?? ($isPrivateArea ? 'noindex, nofollow, noarchive' : 'index, follow, max-snippet:-1, max-image-preview:large, max-video-preview:-1');

// Open Graph & Social Cards
$ogType = $ogType ?? 'website';
$ogTitle = $ogTitle ?? $pageTitle;
$ogDescription = $ogDescription ?? $metaDescription;
$ogUrl = $ogUrl ?? $canonicalUrl;
$ogImage = $ogImage ?? (BASE_URL . 'assets/images/default-blog.jpg');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>

    <!-- SEO Meta Tags -->
    <meta name="description" content="<?= htmlspecialchars($metaDescription) ?>">
    <meta name="keywords" content="<?= htmlspecialchars($metaKeywords) ?>">
    <meta name="author" content="<?= htmlspecialchars($metaAuthor) ?>">
    <meta name="robots" content="<?= htmlspecialchars($metaRobots) ?>">
    <link rel="canonical" href="<?= htmlspecialchars($canonicalUrl) ?>">

    <!-- Open Graph (Facebook, LinkedIn, WhatsApp) -->
    <meta property="og:site_name" content="BlogSphere">
    <meta property="og:type" content="<?= htmlspecialchars($ogType) ?>">
    <meta property="og:title" content="<?= htmlspecialchars($ogTitle) ?>">
    <meta property="og:description" content="<?= htmlspecialchars($ogDescription) ?>">
    <meta property="og:url" content="<?= htmlspecialchars($ogUrl) ?>">
    <meta property="og:image" content="<?= htmlspecialchars($ogImage) ?>">
    <meta property="og:image:alt" content="<?= htmlspecialchars($ogTitle) ?>">
    <?php if ($ogType === 'article' && !empty($articlePublishedTime)): ?>
        <meta property="article:published_time" content="<?= htmlspecialchars($articlePublishedTime) ?>">
    <?php endif; ?>
    <?php if ($ogType === 'article' && !empty($articleModifiedTime)): ?>
        <meta property="article:modified_time" content="<?= htmlspecialchars($articleModifiedTime) ?>">
    <?php endif; ?>
    <?php if ($ogType === 'article' && !empty($articleAuthor)): ?>
        <meta property="article:author" content="<?= htmlspecialchars($articleAuthor) ?>">
    <?php endif; ?>
    <?php if ($ogType === 'article' && !empty($articleSection)): ?>
        <meta property="article:section" content="<?= htmlspecialchars($articleSection) ?>">
    <?php endif; ?>

    <!-- Twitter / X Cards -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= htmlspecialchars($ogTitle) ?>">
    <meta name="twitter:description" content="<?= htmlspecialchars($ogDescription) ?>">
    <meta name="twitter:image" content="<?= htmlspecialchars($ogImage) ?>">

    <!-- JSON-LD Structured Data Schema (Schema.org) -->
    <?php if (!empty($schemaJson)): ?>
        <script type="application/ld+json">
<?= json_encode($schemaJson, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) ?>
        </script>
    <?php endif; ?>
    
    <!-- Local & CDN Bootstrap 5 CSS -->
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    
    <!-- Local & CDN Bootstrap Icons -->
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Custom CSS Files -->
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
    <?php if ($role === 'admin'): ?>
        <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/admin.css">
    <?php elseif ($role === 'employee'): ?>
        <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/employee.css">
    <?php elseif ($role === 'user'): ?>
        <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/user.css">
    <?php endif; ?>

    <!-- Embedded Fail-Safe CSS Fallback -->
    <style>
        body { font-family: 'Inter', -apple-system, sans-serif; background-color: #f8f9fa; color: #2b2d42; margin: 0; padding: 0; }
        .hero-section { background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); color: #fff; padding: 4rem 1rem; text-center: center; }
        .blog-card { border: none; border-radius: 0.75rem; background: #fff; transition: transform 0.2s; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        .blog-card:hover { transform: translateY(-4px); }
        .blog-card-img { height: 200px; object-fit: cover; width: 100%; border-top-left-radius: 0.75rem; border-top-right-radius: 0.75rem; }
        .category-badge { position: absolute; top: 12px; left: 12px; z-index: 10; font-size: 0.75rem; }
    </style>
</head>
<body class="d-flex flex-column min-vh-100 bg-light">
<?php include __DIR__ . '/navbar.php'; ?>
<main class="flex-grow-1">
<?php displayFlash(); ?>
