<?php
/**
 * System Constants & Configuration Settings
 * BlogSphere — Blog Management & Publishing System
 */

// Application Info
define('APP_NAME', 'BlogSphere');
define('APP_VERSION', '1.0.0');

// Base URLs
if (!defined('BASE_URL')) {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    // Dynamically calculate subfolder if needed
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    $basePath = preg_replace('#/(auth|user|employee|admin)$#', '', $scriptDir);
    $basePath = rtrim($basePath, '/');
    if (!empty($basePath) && $basePath[0] !== '/') {
        $basePath = '/' . $basePath;
    }
    define('BASE_URL', $protocol . "://" . $host . ($basePath ? $basePath : '') . '/');
}

// System Directories
define('ROOT_DIR', dirname(__DIR__) . '/');
define('UPLOAD_DIR', ROOT_DIR . 'uploads/');
define('BLOG_IMAGE_UPLOAD_DIR', UPLOAD_DIR . 'blog_images/');
define('BLOG_FILE_UPLOAD_DIR', UPLOAD_DIR . 'blog_files/');

// Upload Constraints
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5 MB
define('ALLOWED_IMAGE_EXT', ['jpg', 'jpeg', 'png', 'webp']);
define('ALLOWED_DOC_EXT', ['pdf', 'doc', 'docx', 'txt']);
define('DISALLOWED_EXT', ['php', 'php3', 'php4', 'php5', 'phtml', 'exe', 'sh', 'js', 'html', 'pl', 'cgi']);

// Session settings
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    session_start();
}

// Timezone
date_default_timezone_set('UTC');
