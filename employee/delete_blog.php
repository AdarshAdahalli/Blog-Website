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
    setFlash('danger', 'Blog manuscript not found or access denied.');
    redirect(BASE_URL . 'employee/my_blogs.php');
}

// Strictly enforce Rule 55: Employee can ONLY delete draft blogs!
if ($blog['status'] !== 'draft') {
    setFlash('danger', 'Access Denied: Employees are only allowed to delete draft manuscripts. You cannot delete pending, approved, or rejected blogs.');
    redirect(BASE_URL . 'employee/my_blogs.php');
}

// Perform deletion
$stmtDelete = $pdo->prepare("DELETE FROM blogs WHERE id = ? AND employee_id = ?");
if ($stmtDelete->execute([$blogId, $employeeId])) {
    setFlash('success', 'Draft manuscript deleted successfully.');
} else {
    setFlash('danger', 'Failed to delete manuscript.');
}

redirect(BASE_URL . 'employee/my_blogs.php');
