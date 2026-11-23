<?php
require_once __DIR__ . '/../includes/employee_auth.php';

$pdo = getDBConnection();
$employeeId = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(BASE_URL . 'employee/my_blogs.php');
}

$blogId = isset($_POST['blog_id']) ? (int)$_POST['blog_id'] : 0;
$reason = sanitizeInput($_POST['reason'] ?? '');
$csrfToken = $_POST['csrf_token'] ?? '';

if (!verifyCSRFToken($csrfToken)) {
    setFlash('danger', 'Invalid security token.');
    redirect(BASE_URL . 'employee/my_blogs.php');
}

if ($blogId <= 0 || empty($reason)) {
    setFlash('danger', 'Please provide a valid reason for requesting blog deletion.');
    redirect(BASE_URL . 'employee/my_blogs.php');
}

// Check that the blog belongs to this employee and is approved
$stmt = $pdo->prepare("SELECT * FROM blogs WHERE id = ? AND employee_id = ?");
$stmt->execute([$blogId, $employeeId]);
$blog = $stmt->fetch();

if (!$blog) {
    setFlash('danger', 'Blog not found or access denied.');
    redirect(BASE_URL . 'employee/my_blogs.php');
}

if ($blog['status'] !== 'approved') {
    setFlash('warning', 'Deletion requests are only needed for approved/published blogs. Drafts can be deleted directly.');
    redirect(BASE_URL . 'employee/my_blogs.php');
}

// Check if a pending delete request already exists
$stmtCheck = $pdo->prepare("SELECT id FROM blog_delete_requests WHERE blog_id = ? AND status = 'pending'");
$stmtCheck->execute([$blogId]);
if ($stmtCheck->fetch()) {
    setFlash('info', 'A deletion request for this blog is already pending Admin review.');
    redirect(BASE_URL . 'employee/my_blogs.php');
}

// Insert deletion request
$stmtInsert = $pdo->prepare("
    INSERT INTO blog_delete_requests (blog_id, employee_id, reason, status, requested_at)
    VALUES (?, ?, ?, 'pending', NOW())
");
$result = $stmtInsert->execute([$blogId, $employeeId, $reason]);

if ($result) {
    // Notify all admins
    $stmtAdmins = $pdo->query("SELECT id FROM users WHERE role = 'admin'");
    while ($admin = $stmtAdmins->fetch()) {
        createNotification(
            $admin['id'],
            "Blog Deletion Request 🗑️",
            "Employee " . $_SESSION['user_name'] . " requested deletion of published blog: '" . $blog['title'] . "'. Reason: " . $reason,
            "warning",
            $blogId
        );
    }
    setFlash('success', 'Deletion request for "' . htmlspecialchars($blog['title']) . '" submitted to Administrator for review.');
} else {
    setFlash('danger', 'Failed to submit deletion request.');
}

redirect(BASE_URL . 'employee/my_blogs.php');
