<?php
require_once __DIR__ . '/../includes/admin_auth.php';

$blogId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$adminId = $_SESSION['user_id'];

if ($blogId <= 0) {
    setFlash('danger', 'Invalid blog ID.');
    redirect(BASE_URL . 'admin/pending_blogs.php');
}

$result = approveBlog($blogId, $adminId);

if ($result) {
    setFlash('success', 'Blog manuscript successfully approved and published to the user portal!');
} else {
    setFlash('danger', 'Failed to approve blog manuscript.');
}

redirect(BASE_URL . 'admin/pending_blogs.php');
