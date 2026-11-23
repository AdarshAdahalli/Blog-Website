<?php
require_once __DIR__ . '/../includes/admin_auth.php';

$pdo = getDBConnection();
$blogId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($blogId <= 0) {
    setFlash('danger', 'Invalid blog ID.');
    redirect(BASE_URL . 'admin/blogs.php');
}

// Fetch blog metadata to remove files if existing
$stmt = $pdo->prepare("SELECT featured_image, downloadable_file FROM blogs WHERE id = ?");
$stmt->execute([$blogId]);
$blog = $stmt->fetch();

if ($blog) {
    if (!empty($blog['featured_image']) && file_exists(BLOG_IMAGE_UPLOAD_DIR . $blog['featured_image'])) {
        @unlink(BLOG_IMAGE_UPLOAD_DIR . $blog['featured_image']);
    }
    if (!empty($blog['downloadable_file']) && file_exists(BLOG_FILE_UPLOAD_DIR . $blog['downloadable_file'])) {
        @unlink(BLOG_FILE_UPLOAD_DIR . $blog['downloadable_file']);
    }

    $stmtDelete = $pdo->prepare("DELETE FROM blogs WHERE id = ?");
    if ($stmtDelete->execute([$blogId])) {
        setFlash('success', 'Blog permanently deleted.');
    } else {
        setFlash('danger', 'Failed to delete blog.');
    }
} else {
    setFlash('danger', 'Blog not found.');
}

redirect(BASE_URL . 'admin/blogs.php');
