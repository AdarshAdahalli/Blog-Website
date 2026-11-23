-- ====================================================================
-- BlogSphere — Blog Management & Publishing System Database Schema
-- Target Engine: MySQL / MariaDB (PHP PDO & Prepared Statements)
-- ====================================================================

CREATE DATABASE IF NOT EXISTS `blog_platform` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `blog_platform`;

-- --------------------------------------------------------------------
-- 1. USERS TABLE
-- --------------------------------------------------------------------
DROP TABLE IF EXISTS `queries`;
DROP TABLE IF EXISTS `notifications`;
DROP TABLE IF EXISTS `messages`;
DROP TABLE IF EXISTS `blog_downloads`;
DROP TABLE IF EXISTS `blogs`;
DROP TABLE IF EXISTS `categories`;
DROP TABLE IF EXISTS `users`;

CREATE TABLE `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(100) UNIQUE NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) UNIQUE NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('admin','employee','user') NOT NULL DEFAULT 'user',
  `profile_image` VARCHAR(255) DEFAULT NULL,
  `status` ENUM('active','inactive','blocked') DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------------------
-- 2. CATEGORIES TABLE
-- --------------------------------------------------------------------
CREATE TABLE `categories` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `description` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------------------
-- 3. BLOGS TABLE
-- --------------------------------------------------------------------
CREATE TABLE `blogs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `employee_id` INT NOT NULL,
  `category_id` INT DEFAULT NULL,
  `title` VARCHAR(255) NOT NULL,
  `slug` VARCHAR(255) UNIQUE,
  `short_description` TEXT,
  `meta_title` VARCHAR(255) DEFAULT NULL,
  `meta_description` VARCHAR(500) DEFAULT NULL,
  `meta_keywords` VARCHAR(255) DEFAULT NULL,
  `canonical_url` VARCHAR(255) DEFAULT NULL,
  `content` LONGTEXT NOT NULL,
  `content_type` ENUM('text','html') DEFAULT 'text',
  `featured_image` VARCHAR(255) DEFAULT NULL,
  `downloadable_file` VARCHAR(255) DEFAULT NULL,
  `status` ENUM('draft', 'pending', 'approved', 'rejected') DEFAULT 'draft',
  `rejection_reason` TEXT DEFAULT NULL,
  `submitted_at` DATETIME DEFAULT NULL,
  `reviewed_at` DATETIME DEFAULT NULL,
  `reviewed_by` INT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`employee_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`reviewed_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------------------
-- 4. BLOG DOWNLOADS TABLE
-- --------------------------------------------------------------------
CREATE TABLE `blog_downloads` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `blog_id` INT NOT NULL,
  `user_id` INT NOT NULL,
  `downloaded_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`blog_id`) REFERENCES `blogs`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------------------
-- 5. MESSAGES TABLE
-- --------------------------------------------------------------------
CREATE TABLE `messages` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `sender_id` INT NOT NULL,
  `receiver_id` INT NOT NULL,
  `blog_id` INT DEFAULT NULL,
  `subject` VARCHAR(255),
  `message` TEXT NOT NULL,
  `parent_message_id` INT DEFAULT NULL,
  `status` ENUM('unread','read') DEFAULT 'unread',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`sender_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`receiver_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`blog_id`) REFERENCES `blogs`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`parent_message_id`) REFERENCES `messages`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------------------
-- 6. NOTIFICATIONS TABLE
-- --------------------------------------------------------------------
CREATE TABLE `notifications` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `blog_id` INT DEFAULT NULL,
  `title` VARCHAR(255),
  `message` TEXT NOT NULL,
  `type` VARCHAR(50) DEFAULT 'info',
  `is_read` BOOLEAN DEFAULT FALSE,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`blog_id`) REFERENCES `blogs`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------------------
-- 7. CONTACT / QUERY TABLE
-- --------------------------------------------------------------------
CREATE TABLE `queries` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `employee_id` INT DEFAULT NULL,
  `blog_id` INT DEFAULT NULL,
  `subject` VARCHAR(255),
  `message` TEXT NOT NULL,
  `status` ENUM('open', 'in_progress', 'resolved') DEFAULT 'open',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`employee_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`blog_id`) REFERENCES `blogs`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------------------
-- 8. BLOG DELETE REQUESTS TABLE
-- --------------------------------------------------------------------
CREATE TABLE `blog_delete_requests` (
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

-- ====================================================================
-- SEED DATA INSERTS
-- ====================================================================

-- 1. Insert Standard Categories
INSERT INTO `categories` (`id`, `name`, `description`) VALUES
(1, 'Technology', 'Trends and innovations in modern computer technology.'),
(2, 'Programming', 'Software engineering, syntax, and coding practices.'),
(3, 'Cloud Computing', 'AWS, Azure, Google Cloud, and distributed architectures.'),
(4, 'Artificial Intelligence', 'Machine learning, neural networks, and LLMs.'),
(5, 'Cyber Security', 'Network defense, encryption, and threat mitigation.'),
(6, 'Database', 'SQL, NoSQL, relational modeling, and query optimization.'),
(7, 'Web Development', 'Frontend and backend web development technologies.'),
(8, 'Linux', 'Operating system administration, shell scripting, and server setup.'),
(9, 'Networking', 'TCP/IP protocols, routers, firewalls, and infrastructure.'),
(10, 'General', 'General technical topics and industry news.');

-- 2. Insert Core Test Accounts with Usernames & Passwords
-- Admin: username = admin, password = Admin@123
-- Employee: username = employee, password = Employee@123
-- User: username = user, password = User@123
INSERT INTO `users` (`id`, `username`, `name`, `email`, `password`, `role`, `status`) VALUES
(1, 'admin', 'System Admin', 'admin@blogsphere.com', '$2y$12$NRrl5GFnw5J56JfJC7xyGO87PsI2IxyKryZEpDDFKB2efhXU/PA9a', 'admin', 'active'),
(2, 'employee', 'John Writer', 'employee@blogsphere.com', '$2y$12$AI4eWj6quU9uROTQsqQMYOEzjejOGG7WPetUrhvKjze6t4/EGdLL2', 'employee', 'active'),
(3, 'user', 'Demo User', 'user@blogsphere.com', '$2y$12$NlWtlEx/1FtJuxPcwyeWyus6CnIVU4PnjAAPAVT3L1eJHw.wC4wl.', 'user', 'active');

-- 3. Insert Sample Blogs Across Various Statuses
INSERT INTO `blogs` (`id`, `employee_id`, `category_id`, `title`, `slug`, `short_description`, `content`, `featured_image`, `downloadable_file`, `status`, `rejection_reason`, `submitted_at`, `reviewed_at`, `reviewed_by`) VALUES
(1, 2, 3, 'Introduction to Cloud Computing', 'introduction-to-cloud-computing', 'An overview of cloud infrastructure models including IaaS, PaaS, and SaaS for scalable deployments.', 'Cloud computing enables organizations to rent virtualized IT infrastructure instead of owning physical servers. This comprehensive overview covers Infrastructure as a Service (IaaS), Platform as a Service (PaaS), and Software as a Service (SaaS).\n\nKey Advantages:\n- Elastic Scalability\n- Pay-as-you-go Pricing Model\n- Global Accessibility', 'sample-cloud.jpg', 'sample-guide.pdf', 'approved', NULL, '2026-09-20 10:00:00', '2026-09-20 14:00:00', 1),
(2, 2, 8, 'Linux Commands Every Beginner Should Know', 'linux-commands-every-beginner-should-know', 'Essential CLI commands for navigating directories, setting file permissions, and managing processes in Linux.', 'Command Line Interface (CLI) mastery is indispensable for system administrators and software engineers operating in Linux environments.\n\nTop Commands:\n1. ls -la (List detailed directory contents)\n2. grep (Search text patterns in files)\n3. chmod & chown (Manage permissions and ownership)\n4. htop / ps aux (Process monitoring)', 'sample-cloud.jpg', 'sample-guide.txt', 'approved', NULL, '2026-09-22 09:30:00', '2026-09-22 11:15:00', 1),
(3, 2, 6, 'Understanding Database Normalization', 'understanding-database-normalization', 'Learn how First, Second, and Third Normal Forms (1NF, 2NF, 3NF) eliminate redundancy in relational databases.', 'Database normalization is a systematic approach of decomposing tables to eliminate data redundancy and undesirable anomalies such as Insert, Update, and Delete anomalies.\n\nNormal Forms Summary:\n- 1NF: Atomic values per cell.\n- 2NF: No partial dependency on composite keys.\n- 3NF: No transitive dependency on non-key columns.', 'sample-cloud.jpg', 'sample-guide.pdf', 'approved', NULL, '2026-09-24 16:00:00', '2026-09-25 08:45:00', 1),
(4, 2, 5, 'Introduction to Cyber Security Threats', 'introduction-to-cyber-security-threats', 'An analysis of common security vectors including Phishing, SQL Injection, Cross-Site Scripting (XSS), and Ransomware.', 'Modern web applications face continuous automated attack vectors. This manuscript explores defensive techniques for protecting user data and preventing SQL Injection, XSS, and CSRF vulnerabilities.\n\nBest practices include using PDO prepared statements, input sanitization, and strict Content Security Policies.', 'sample-cloud.jpg', 'sample-guide.pdf', 'pending', NULL, '2026-09-27 11:00:00', NULL, NULL),
(5, 2, 2, 'Getting Started with Modern Python 3.12', 'getting-started-with-modern-python-3-12', 'Exploring modern Python features, type hint syntax, and performance improvements.', 'Python 3.12 introduces substantial performance improvements and cleaner syntax for error messages. Please expand this manuscript before resubmission.', 'sample-cloud.jpg', NULL, 'rejected', 'Please improve the introduction and add explicit code examples for type hinting.', '2026-09-25 10:00:00', '2026-09-25 15:30:00', 1),
(6, 2, 4, 'Artificial Intelligence & Neural Networks Draft', 'artificial-intelligence-neural-networks-draft', 'Draft exploration of deep learning architectures and transformer models.', 'Draft content working title for AI transformer architectures...', NULL, NULL, 'draft', NULL, NULL, NULL, NULL);

-- 4. Insert Sample Download History
INSERT INTO `blog_downloads` (`id`, `blog_id`, `user_id`, `downloaded_at`) VALUES
(1, 1, 3, '2026-09-26 12:30:00'),
(2, 2, 3, '2026-09-27 15:45:00');

-- 5. Insert Sample Message Thread
INSERT INTO `messages` (`id`, `sender_id`, `receiver_id`, `blog_id`, `subject`, `message`, `parent_message_id`, `status`, `created_at`) VALUES
(1, 2, 1, 5, 'Question regarding rejection of Getting Started with Modern Python', 'Could you please explain which part of the introduction needs improvement?', NULL, 'read', '2026-09-25 16:00:00'),
(2, 1, 2, 5, 'Re: Question regarding rejection of Getting Started with Modern Python', 'Please add at least three reliable sources and concrete code snippets for type annotations in Python 3.12.', 1, 'unread', '2026-09-25 16:30:00');

-- 6. Insert Sample Notifications
INSERT INTO `notifications` (`id`, `user_id`, `blog_id`, `title`, `message`, `type`, `is_read`, `created_at`) VALUES
(1, 2, 1, 'Blog Approved! 🎉', 'Your blog ''Introduction to Cloud Computing'' has been approved and published.', 'success', 1, '2026-09-20 14:00:00'),
(2, 2, 5, 'Blog Needs Revisions ⚠️', 'Your blog ''Getting Started with Modern Python 3.12'' was rejected by admin. Reason: Please improve the introduction and add explicit code examples.', 'warning', 0, '2026-09-25 15:30:00'),
(3, 1, 4, 'New Blog Pending Review 📝', 'Employee John Writer submitted manuscript: ''Introduction to Cyber Security Threats''', 'info', 0, '2026-09-27 11:00:00');
