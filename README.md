# BlogSphere — Blog Management & Publishing System

BlogSphere is a full-featured, multi-panel Blog Management and Publishing System built with the pure **LAMP stack** (Linux, Apache, MySQL, PHP 8.2+, HTML5, CSS3, JavaScript, and Bootstrap 5). 

It enforces a complete editorial review workflow across three distinct panels: **User Panel**, **Employee Panel (Writers)**, and **Admin Panel (Editors & Managers)**.

---

## 🚀 Technology Requirements & Stack
- **Backend:** PHP 8.2+ (PDO for secure database transactions with prepared statements)
- **Database:** MySQL / MariaDB (`blog_platform`)
- **Web Server:** Apache (with `mod_rewrite` support)
- **Frontend:** HTML5, CSS3, JavaScript (Vanilla ES6), Bootstrap 5, Bootstrap Icons
- **Security:** Session management, Role-Based Access Control (RBAC), `password_hash()` / `password_verify()`, CSRF Protection tokens, XSS HTML escaping (`htmlspecialchars`), Path Traversal Protection for file downloads, Strict MIME/Extension upload validation.

---

## 📁 Project Directory Structure

```text
blog-platform/
├── config/
│   ├── constants.php        # System constants, BASE_URL, directory paths
│   └── database.php         # PDO Database connection handler
├── includes/
│   ├── header.php           # Global HTML header with Bootstrap & CSS links
│   ├── footer.php           # Global HTML footer & JS script loaders
│   ├── navbar.php           # Role-based dynamic navigation bar
│   ├── functions.php        # Core helper functions, notifications, messages & approval logic
│   ├── auth_check.php       # Generic login verification check
│   ├── admin_auth.php      # Admin role authorization check
│   ├── employee_auth.php   # Employee role authorization check
│   └── user_auth.php        # User role authorization check
├── assets/
│   ├── css/
│   │   ├── style.css        # Base global layout & typography styles
│   │   ├── admin.css        # Admin panel specific styling
│   │   ├── employee.css     # Employee panel styling & editor toolbar
│   │   └── user.css         # User panel article reader styling
│   ├── js/
│   │   ├── main.js          # Auto-dismiss alerts, tooltips, global UI helpers
│   │   ├── validation.js    # Client-side form & file extension validation
│   │   ├── admin.js         # Admin modal confirmation scripts
│   │   ├── employee.js      # Text formatting editor toolbar script
│   │   └── user.js          # Interactive user features
│   └── images/
│       ├── logo.png         # Brand logo
│       └── default-blog.jpg # Default thumbnail image
├── uploads/
│   ├── blog_images/         # Uploaded featured article images
│   └── blog_files/          # Uploaded attachment documents (PDF, DOC, TXT)
├── auth/
│   ├── login.php            # Unified login controller & view
│   ├── register.php         # User public registration handler
│   └── logout.php           # Session destruction handler
├── index.php                # Public homepage with featured approved blogs
├── 404.php                  # Page not found error view
├── 403.php                  # Access forbidden error view
├── 500.php                  # Internal server error view
├── user/
│   ├── dashboard.php        # User portal dashboard
│   ├── blogs.php            # Approved published blogs catalog & search
│   ├── blog_details.php     # Article reader & author query form
│   ├── download.php         # Secure download controller with download tracking
│   ├── messages.php         # User message inbox & conversation threads
│   ├── send_message.php     # User query & message composer
│   ├── profile.php          # User profile settings
│   └── notifications.php    # User notifications list
├── employee/
│   ├── dashboard.php        # Employee writer portal dashboard
│   ├── add_blog.php         # Create blog manuscript form (Draft or Submit)
│   ├── edit_blog.php        # Edit manuscript (reverts rejected blogs to Draft)
│   ├── delete_blog.php      # Delete draft manuscript handler
│   ├── my_blogs.php         # Filterable list of employee's manuscripts
│   ├── submit_blog.php      # Transition draft manuscript to pending approval
│   ├── messages.php         # Employee inbox & rejection clarification threads
│   ├── send_message.php     # Employee message composer (Ask Admin)
│   ├── notifications.php    # Employee notifications list
│   └── profile.php          # Employee profile settings
├── admin/
│   ├── dashboard.php        # Admin overview control center
│   ├── pending_blogs.php    # Pending manuscript review queue
│   ├── view_blog.php        # Complete manuscript review interface
│   ├── approve_blog.php     # Approve manuscript processor
│   ├── reject_blog.php      # Reject manuscript processor with required reason
│   ├── blogs.php            # System-wide blog management & filters
│   ├── delete_blog.php      # Admin blog deletion processor
│   ├── employees.php        # Employee management & productivity stats
│   ├── users.php            # User account management & download history
│   ├── messages.php         # Admin message inbox & support threads
│   ├── send_message.php     # Admin message composer
│   ├── notifications.php    # Admin notification list
│   ├── statistics.php       # Platform analytics & SQL aggregations
│   └── profile.php          # Admin profile settings
├── database/
│   └── blog_platform.sql    # Complete SQL database schema & seed data
└── README.md                # Project documentation
```

---

## 🔑 Default Test Accounts

All account passwords are standardly hashed using `password_hash()` in the database schema.

| Role | Name | Email Address | Password | Panel Access URL |
| :--- | :--- | :--- | :--- | :--- |
| **ADMIN** | System Admin | `admin@blogsphere.com` | `Admin@123` | `/blog-platform/admin/dashboard.php` |
| **EMPLOYEE** | John Writer | `employee@blogsphere.com` | `Employee@123` | `/blog-platform/employee/dashboard.php` |
| **USER** | Demo User | `user@blogsphere.com` | `User@123` | `/blog-platform/user/dashboard.php` |

---

## 💻 Installation Guide

### 1. Running on XAMPP (Windows)

1. **Copy Project Directory:**
   Copy the `blog-platform` folder into your XAMPP `htdocs` directory:
   ```text
   C:\xampp\htdocs\blog-platform
   ```

2. **Start Services:**
   Open the XAMPP Control Panel and start **Apache** and **MySQL**.

3. **Import Database:**
   - Open phpMyAdmin in your browser: `http://localhost/phpmyadmin/`
   - Create a database named `blog_platform`.
   - Click on the **Import** tab, choose `blog-platform/database/blog_platform.sql`, and click **Go**.
   - Alternatively, import using MySQL CLI:
     ```bash
     C:\xampp\mysql\bin\mysql.exe -u root -p blog_platform < "C:\xampp\htdocs\blog-platform\database\blog_platform.sql"
     ```

4. **Access the Website:**
   Open your browser and navigate to:
   ```text
   http://localhost/blog-platform/
   ```

---

### 2. Running on Linux / Ubuntu (LAMP Stack)

1. **Update and Install LAMP Components:**
   ```bash
   sudo apt update
   sudo apt install -y apache2 mysql-server php8.2 libapache2-mod-php php8.2-mysql php8.2-pdo
   sudo systemctl enable --now apache2 mysql
   ```

2. **Deploy Application Directory:**
   Copy the `blog-platform` project to `/var/www/html/`:
   ```bash
   sudo cp -r blog-platform /var/www/html/blog-platform
   ```

3. **Set File & Upload Directory Permissions:**
   ```bash
   sudo chown -R www-data:www-data /var/www/html/blog-platform
   sudo chmod -R 755 /var/www/html/blog-platform
   sudo chmod -R 777 /var/www/html/blog-platform/uploads
   ```

4. **Import Database Schema:**
   ```bash
   sudo mysql -u root -e "CREATE DATABASE IF NOT EXISTS blog_platform;"
   sudo mysql -u root blog_platform < /var/www/html/blog-platform/database/blog_platform.sql
   ```

5. **Enable Apache Rewrite Module (Optional):**
   ```bash
   sudo a2enmod rewrite
   sudo systemctl restart apache2
   ```

6. **Open in Browser:**
   ```text
   http://YOUR_SERVER_IP/blog-platform/
   ```

---

## 🔄 Editorial Workflow & Business Rules

1. **Manuscrip Creation:** Employees write articles using the formatting toolbar (`add_blog.php`). Status defaults to `draft`.
2. **Submission:** Employee clicks *Submit for Approval*. Status becomes `pending`. An automated notification is sent to Admin.
3. **Admin Review:** Admin opens `admin/pending_blogs.php` and reviews manuscript text and attachments.
4. **Approval Option:** Admin clicks *Approve*. Status becomes `approved`, `reviewed_by` and `reviewed_at` are stamped. The article becomes publicly visible on the home page and User Panel.
5. **Rejection Option:** Admin clicks *Reject*. Admin must fill in a rejection feedback reason. Status becomes `rejected`. The employee receives a notification with the reason.
6. **Revision & Re-submission:** Employee views the rejection note on `my_blogs.php` or asks Admin clarification via the messaging system. When the employee edits the rejected blog, status converts back to `draft`. Once edited, the employee submits again (`pending`).
7. **Secure Downloads:** Logged-in users can download attachments attached to approved blogs. Downloads are tracked in `blog_downloads`. Path traversal attacks (e.g. `?file=../../config/database.php`) are strictly prevented.

---

## 🧪 Comprehensive Verification Checklist

To verify system functionality, test the following sequence:

- [x] **TEST 1 (Employee Draft & Submit):** Log in as Employee (`employee@blogsphere.com`). Create a new blog, save draft. Verify status = `draft`. Click *Submit for Approval*. Verify status = `pending`.
- [x] **TEST 2 (Admin Review & Approval):** Log in as Admin (`admin@blogsphere.com`). Open *Pending Approvals*. Approve the submitted blog. Verify notification sent to Employee. Verify blog is visible on public index and User Panel.
- [x] **TEST 3 (Admin Rejection with Reason):** Log in as Employee. Create another blog and submit it. Log in as Admin, click *Reject*, enter reason "Please expand introduction". Confirm Employee receives rejection notification and reason text.
- [x] **TEST 4 (Employee Rejection Inquiry & Edit):** Log in as Employee. Open rejected blog, click *Ask Admin Why*. Send message. Admin receives inquiry and replies. Employee edits manuscript; status reverts to `draft` and is resubmitted to `pending`.
- [x] **TEST 5 (User Download & Tracking):** Log in as User (`user@blogsphere.com`). Open an approved blog and click *Download*. File downloads with forced attachment headers. Verify download entry is recorded in `blog_downloads` and stats dashboard.
- [x] **TEST 6 (User Question / Query System):** User submits a question regarding an article. Employee receives notification and direct message.
- [x] **TEST 7 (Access Control Enforcement):** Attempt accessing `/admin/dashboard.php` or `/employee/dashboard.php` while logged in as a normal User. System returns `403 Access Forbidden`.
- [x] **TEST 8 (Download Protection):** Attempt downloading an unapproved or draft blog via URL manipulation. Access denied.
- [x] **TEST 9 (Path Traversal Prevention):** Attempt calling `user/download.php?file=../../config/database.php`. System rejects the request using sanitized blog IDs.

---

## 🛠️ Troubleshooting

- **Database Connection Failed:** Verify MySQL is running and credentials in `config/database.php` match your host, username, and password.
- **Upload Permission Denied:** Ensure `uploads/blog_images` and `uploads/blog_files` directories exist and have write permissions (`chmod 777` on Linux).
- **CSS / JS Assets Not Loading:** Check `BASE_URL` in `config/constants.php` or verify virtual host path settings.
