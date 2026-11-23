<?php
/**
 * Helper Functions Library
 * BlogSphere — Blog Management & Publishing System
 */

require_once __DIR__ . '/../config/database.php';

// Sanitize user inputs
function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    return htmlspecialchars(trim((string)$data), ENT_QUOTES, 'UTF-8');
}

// Redirect utility
function redirect($url) {
    header("Location: " . $url);
    exit();
}

// CSRF Token Protection
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCSRFToken($token) {
    if (!isset($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

// Authentication Check Helpers
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    global $pdo;
    $stmt = $pdo->prepare("SELECT id, name, email, role, profile_image, status, created_at FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

function requireLogin() {
    if (!isLoggedIn()) {
        $_SESSION['error'] = "Please log in to access this page.";
        redirect(BASE_URL . 'auth/login.php');
    }
}

function requireAdmin() {
    requireLogin();
    if ($_SESSION['user_role'] !== 'admin') {
        header("HTTP/1.1 403 Forbidden");
        include ROOT_DIR . '403.php';
        exit();
    }
}

function requireEmployee() {
    requireLogin();
    if ($_SESSION['user_role'] !== 'employee') {
        header("HTTP/1.1 403 Forbidden");
        include ROOT_DIR . '403.php';
        exit();
    }
}

function requireUser() {
    requireLogin();
    if ($_SESSION['user_role'] !== 'user') {
        header("HTTP/1.1 403 Forbidden");
        include ROOT_DIR . '403.php';
        exit();
    }
}

// Generate URL Friendly Slug
function generateSlug($title) {
    global $pdo;
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title)));
    $slug = preg_replace('/-+/', '-', $slug);
    $slug = rtrim($slug, '-');
    
    // Ensure slug uniqueness
    $originalSlug = $slug;
    $count = 1;
    while (true) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM blogs WHERE slug = ?");
        $stmt->execute([$slug]);
        if ($stmt->fetchColumn() == 0) {
            break;
        }
        $slug = $originalSlug . '-' . $count;
        $count++;
    }
    return $slug;
}

// Create Notification
function createNotification($userId, $title, $message, $type = 'info', $blogId = null) {
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO notifications (user_id, blog_id, title, message, type, is_read, created_at) VALUES (?, ?, ?, ?, ?, FALSE, NOW())");
    return $stmt->execute([$userId, $blogId, $title, $message, $type]);
}

// Send Message
function sendMessage($senderId, $receiverId, $subject, $message, $blogId = null, $parentId = null) {
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, blog_id, subject, message, parent_message_id, status, created_at) VALUES (?, ?, ?, ?, ?, ?, 'unread', NOW())");
    return $stmt->execute([$senderId, $receiverId, $blogId, $subject, $message, $parentId]);
}

// Unread Counters
function getUnreadMessageCount($userId) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM messages WHERE receiver_id = ? AND status = 'unread'");
    $stmt->execute([$userId]);
    return (int)$stmt->fetchColumn();
}

function getUnreadNotificationCount($userId) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = FALSE");
    $stmt->execute([$userId]);
    return (int)$stmt->fetchColumn();
}

// Admin Blog Approval Function
function approveBlog($blogId, $adminId) {
    global $pdo;
    
    // Verify admin role
    $stmtAdmin = $pdo->prepare("SELECT role FROM users WHERE id = ?");
    $stmtAdmin->execute([$adminId]);
    $admin = $stmtAdmin->fetch();
    if (!$admin || $admin['role'] !== 'admin') {
        return false;
    }
    
    // Get Blog
    $stmtBlog = $pdo->prepare("SELECT id, title, employee_id FROM blogs WHERE id = ?");
    $stmtBlog->execute([$blogId]);
    $blog = $stmtBlog->fetch();
    if (!$blog) {
        return false;
    }
    
    // Update Blog
    $stmt = $pdo->prepare("UPDATE blogs SET status = 'approved', reviewed_by = ?, reviewed_at = NOW(), rejection_reason = NULL WHERE id = ?");
    $result = $stmt->execute([$adminId, $blogId]);
    
    if ($result) {
        createNotification(
            $blog['employee_id'],
            "Blog Approved! 🎉",
            "Your blog '" . $blog['title'] . "' has been approved and published to the user panel.",
            "success",
            $blogId
        );
    }
    return $result;
}

// Admin Blog Rejection Function
function rejectBlog($blogId, $adminId, $reason) {
    global $pdo;
    
    if (empty(trim($reason))) {
        return false;
    }
    
    // Verify admin role
    $stmtAdmin = $pdo->prepare("SELECT role FROM users WHERE id = ?");
    $stmtAdmin->execute([$adminId]);
    $admin = $stmtAdmin->fetch();
    if (!$admin || $admin['role'] !== 'admin') {
        return false;
    }
    
    // Get Blog
    $stmtBlog = $pdo->prepare("SELECT id, title, employee_id FROM blogs WHERE id = ?");
    $stmtBlog->execute([$blogId]);
    $blog = $stmtBlog->fetch();
    if (!$blog) {
        return false;
    }
    
    // Update Blog
    $stmt = $pdo->prepare("UPDATE blogs SET status = 'rejected', rejection_reason = ?, reviewed_by = ?, reviewed_at = NOW() WHERE id = ?");
    $result = $stmt->execute([$reason, $adminId, $blogId]);
    
    if ($result) {
        createNotification(
            $blog['employee_id'],
            "Blog Needs Revisions ⚠️",
            "Your blog '" . $blog['title'] . "' was rejected by the admin. Reason: " . $reason,
            "warning",
            $blogId
        );
    }
    return $result;
}

// Format Date
function formatDate($dateStr, $format = 'M d, Y') {
    if (!$dateStr) return 'N/A';
    $time = strtotime($dateStr);
    return date($format, $time);
}

// Flash Message helper
function setFlash($type, $message) {
    $_SESSION['flash'] = [
        'type' => $type, // success, danger, warning, info
        'message' => $message
    ];
}

function displayFlash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        echo '<div class="alert alert-' . htmlspecialchars($flash['type']) . ' alert-dismissible fade show role="alert">' .
             htmlspecialchars($flash['message']) .
             '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
    }
}
