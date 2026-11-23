<?php
require_once __DIR__ . '/../includes/employee_auth.php';

$pdo = getDBConnection();
$employeeId = $_SESSION['user_id'];
$blogId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($blogId <= 0) {
    setFlash('danger', 'Invalid blog ID.');
    redirect(BASE_URL . 'employee/my_blogs.php');
}

// Fetch blog
$stmt = $pdo->prepare("SELECT * FROM blogs WHERE id = ? AND employee_id = ?");
$stmt->execute([$blogId, $employeeId]);
$blog = $stmt->fetch();

if (!$blog) {
    setFlash('danger', 'Blog manuscript not found.');
    redirect(BASE_URL . 'employee/my_blogs.php');
}

if ($blog['status'] !== 'draft' && $blog['status'] !== 'rejected') {
    setFlash('warning', 'This manuscript is already pending review or approved.');
    redirect(BASE_URL . 'employee/my_blogs.php');
}

// Update status to pending
$stmtUpdate = $pdo->prepare("UPDATE blogs SET status = 'pending', submitted_at = NOW(), updated_at = NOW() WHERE id = ?");
if ($stmtUpdate->execute([$blogId])) {
    // Notify all admins
    $stmtAdmins = $pdo->query("SELECT id FROM users WHERE role = 'admin'");
    while ($admin = $stmtAdmins->fetch()) {
        createNotification(
            $admin['id'],
            "New Blog Pending Review 📝",
            "Employee " . $_SESSION['user_name'] . " submitted manuscript: '" . $blog['title'] . "' for approval.",
            "info",
            $blogId
        );
    }
    setFlash('success', 'Manuscript successfully submitted for Admin approval!');
} else {
    setFlash('danger', 'Failed to submit manuscript.');
}

redirect(BASE_URL . 'employee/my_blogs.php');
