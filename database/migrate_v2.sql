-- BlogSphere v2 Migration - Run this if DB already exists
USE blog_platform;

-- Add content_type column to blogs table
ALTER TABLE `blogs` ADD COLUMN IF NOT EXISTS `content_type` ENUM('text','html') DEFAULT 'text' AFTER `content`;

-- Create blog_delete_requests table
CREATE TABLE IF NOT EXISTS `blog_delete_requests` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `blog_id` INT NOT NULL,
  `employee_id` INT NOT NULL,
  `reason` TEXT,
  `status` ENUM('pending','approved','rejected') DEFAULT 'pending',
  `admin_note` TEXT DEFAULT NULL,
  `requested_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `resolved_at` DATETIME DEFAULT NULL,
  FOREIGN KEY (`blog_id`) REFERENCES `blogs`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`employee_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
