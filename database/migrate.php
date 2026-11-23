<?php
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';

$pdo = getDBConnection();

try {
    // Add content_type column if not exists
    $columns = $pdo->query("SHOW COLUMNS FROM blogs LIKE 'content_type'")->fetchAll();
    if (empty($columns)) {
        $pdo->exec("ALTER TABLE blogs ADD COLUMN content_type ENUM('text','html') DEFAULT 'text' AFTER content");
        echo "Added content_type column to blogs.\n";
    } else {
        echo "content_type column already exists.\n";
    }

    // Add SEO columns to blogs if not exists
    $seoColumns = [
        'meta_title' => "ALTER TABLE blogs ADD COLUMN meta_title VARCHAR(255) DEFAULT NULL AFTER short_description",
        'meta_description' => "ALTER TABLE blogs ADD COLUMN meta_description VARCHAR(500) DEFAULT NULL AFTER meta_title",
        'meta_keywords' => "ALTER TABLE blogs ADD COLUMN meta_keywords VARCHAR(255) DEFAULT NULL AFTER meta_description",
        'canonical_url' => "ALTER TABLE blogs ADD COLUMN canonical_url VARCHAR(255) DEFAULT NULL AFTER meta_keywords"
    ];

    foreach ($seoColumns as $col => $sql) {
        $check = $pdo->query("SHOW COLUMNS FROM blogs LIKE '{$col}'")->fetchAll();
        if (empty($check)) {
            $pdo->exec($sql);
            echo "Added {$col} column to blogs.\n";
        } else {
            echo "{$col} column already exists.\n";
        }
    }
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS blog_delete_requests (
            id INT AUTO_INCREMENT PRIMARY KEY,
            blog_id INT NOT NULL,
            employee_id INT NOT NULL,
            reason TEXT,
            status ENUM('pending','approved','rejected') DEFAULT 'pending',
            admin_note TEXT DEFAULT NULL,
            requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            resolved_at DATETIME DEFAULT NULL,
            FOREIGN KEY (blog_id) REFERENCES blogs(id) ON DELETE CASCADE,
            FOREIGN KEY (employee_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
    echo "blog_delete_requests table ready.\n";

} catch (Exception $e) {
    echo "Migration error: " . $e->getMessage() . "\n";
}
