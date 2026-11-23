<?php
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/functions.php';

// Security Check 1: User Must Be Logged In
requireLogin();

$blogId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($blogId <= 0) {
    setFlash('danger', 'Invalid blog ID for download.');
    redirect(BASE_URL . 'user/blogs.php');
}

$pdo = getDBConnection();

// Fetch Blog metadata from DB
$stmt = $pdo->prepare("SELECT id, title, downloadable_file, status, employee_id FROM blogs WHERE id = ?");
$stmt->execute([$blogId]);
$blog = $stmt->fetch();

if (!$blog) {
    setFlash('danger', 'The requested blog does not exist.');
    redirect(BASE_URL . 'user/blogs.php');
}

// Security Check 2: Blog Status Must Be Approved (unless Admin or the Blog Author Employee)
$isAuthor = ($_SESSION['user_id'] == $blog['employee_id']);
$isAdmin = ($_SESSION['user_role'] === 'admin');

if ($blog['status'] !== 'approved' && !$isAuthor && !$isAdmin) {
    setFlash('danger', 'Downloading files from unapproved or draft blogs is strictly forbidden.');
    redirect(BASE_URL . 'user/blogs.php');
}

if (empty($blog['downloadable_file'])) {
    setFlash('warning', 'No downloadable document attached to this blog.');
    redirect(BASE_URL . 'user/blog_details.php?id=' . $blogId);
}

// Security Check 3: Sanitize filename to prevent Path Traversal Attacks
$filename = basename($blog['downloadable_file']);
$filePath = BLOG_FILE_UPLOAD_DIR . $filename;

// Security Check 4: Check if file physically exists on disk and is within allowed directory
if (!file_exists($filePath) || !is_file($filePath)) {
    setFlash('danger', 'The requested file could not be found on the server storage.');
    redirect(BASE_URL . 'user/blog_details.php?id=' . $blogId);
}

// Record Download Statistics in database
$userId = $_SESSION['user_id'];
$stmtTrack = $pdo->prepare("INSERT INTO blog_downloads (blog_id, user_id, downloaded_at) VALUES (?, ?, NOW())");
$stmtTrack->execute([$blogId, $userId]);

// Force Download Headers
$fileExt = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
$contentTypes = [
    'pdf' => 'application/pdf',
    'doc' => 'application/msword',
    'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'txt' => 'text/plain'
];
$mimeType = $contentTypes[$fileExt] ?? 'application/octet-stream';

// Clear output buffers
if (ob_get_level()) {
    ob_end_clean();
}

header('Content-Description: File Transfer');
header('Content-Type: ' . $mimeType);
header('Content-Disposition: attachment; filename="' . rawurlencode($filename) . '"');
header('Content-Transfer-Encoding: binary');
header('Expires: 0');
header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
header('Pragma: public');
header('Content-Length: ' . filesize($filePath));

readfile($filePath);
exit();
