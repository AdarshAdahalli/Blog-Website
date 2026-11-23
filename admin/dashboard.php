<?php
require_once __DIR__ . '/../includes/admin_auth.php';

$pdo = getDBConnection();

// Fetch System Statistics
$totalUsers = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'user'")->fetchColumn();
$totalEmployees = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'employee'")->fetchColumn();
$totalBlogs = (int)$pdo->query("SELECT COUNT(*) FROM blogs")->fetchColumn();
$pendingBlogsCount = (int)$pdo->query("SELECT COUNT(*) FROM blogs WHERE status = 'pending'")->fetchColumn();
$approvedBlogsCount = (int)$pdo->query("SELECT COUNT(*) FROM blogs WHERE status = 'approved'")->fetchColumn();
$rejectedBlogsCount = (int)$pdo->query("SELECT COUNT(*) FROM blogs WHERE status = 'rejected'")->fetchColumn();
$totalDownloads = (int)$pdo->query("SELECT COUNT(*) FROM blog_downloads")->fetchColumn();
$unreadMessages = getUnreadMessageCount($_SESSION['user_id']);
$pendingDeleteCount = (int)$pdo->query("SELECT COUNT(*) FROM blog_delete_requests WHERE status = 'pending'")->fetchColumn();

// Fetch Pending Submissions for Review
$stmtPending = $pdo->query("
    SELECT b.*, c.name AS category_name, u.name AS employee_name, u.email AS employee_email
    FROM blogs b
    LEFT JOIN categories c ON b.category_id = c.id
    LEFT JOIN users u ON b.employee_id = u.id
    WHERE b.status = 'pending'
    ORDER BY b.submitted_at ASC
    LIMIT 6
");
$pendingBlogs = $stmtPending->fetchAll();

$pageTitle = 'Admin Dashboard — BlogSphere';
include __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row">
        <!-- Admin Sidebar -->
        <div class="col-lg-2 col-md-3 mb-4">
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-body text-center p-3 border-bottom bg-dark text-white rounded-top">
                    <i class="bi bi-shield-lock-fill display-4 text-primary"></i>
                    <h6 class="fw-bold mt-2 mb-0 text-white"><?= htmlspecialchars($_SESSION['user_name']) ?></h6>
                    <span class="badge bg-danger mt-1">SUPER ADMIN</span>
                </div>
                <div class="list-group list-group-flush p-2 small">
                    <a href="<?= BASE_URL ?>admin/dashboard.php" class="list-group-item list-group-item-action active rounded mb-1">
                        <i class="bi bi-speedometer2 me-2"></i> Dashboard
                    </a>
                    <a href="<?= BASE_URL ?>admin/pending_blogs.php" class="list-group-item list-group-item-action rounded mb-1 d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-clock-history text-warning me-2"></i> Pending Review</span>
                        <?php if ($pendingBlogsCount > 0): ?>
                            <span class="badge bg-warning text-dark rounded-pill"><?= $pendingBlogsCount ?></span>
                        <?php endif; ?>
                    </a>
                    <a href="<?= BASE_URL ?>admin/blogs.php" class="list-group-item list-group-item-action rounded mb-1">
                        <i class="bi bi-collection me-2"></i> All Blogs
                    </a>
                    <a href="<?= BASE_URL ?>admin/employees.php" class="list-group-item list-group-item-action rounded mb-1">
                        <i class="bi bi-people me-2"></i> Employees
                    </a>
                    <a href="<?= BASE_URL ?>admin/users.php" class="list-group-item list-group-item-action rounded mb-1">
                        <i class="bi bi-person-lines-fill me-2"></i> Users
                    </a>
                    <a href="<?= BASE_URL ?>admin/delete_requests.php" class="list-group-item list-group-item-action rounded mb-1 d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-trash text-danger me-2"></i> Delete Requests</span>
                        <?php if ($pendingDeleteCount > 0): ?>
                            <span class="badge bg-danger rounded-pill"><?= $pendingDeleteCount ?></span>
                        <?php endif; ?>
                    </a>
                    <a href="<?= BASE_URL ?>admin/messages.php" class="list-group-item list-group-item-action rounded mb-1 d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-envelope me-2"></i> Messages</span>
                        <?php if ($unreadMessages > 0): ?>
                            <span class="badge bg-danger rounded-pill"><?= $unreadMessages ?></span>
                        <?php endif; ?>
                    </a>
                    <a href="<?= BASE_URL ?>admin/notifications.php" class="list-group-item list-group-item-action rounded mb-1">
                        <i class="bi bi-bell me-2"></i> Notifications
                    </a>
                    <a href="<?= BASE_URL ?>admin/statistics.php" class="list-group-item list-group-item-action rounded mb-1">
                        <i class="bi bi-graph-up-arrow me-2"></i> Statistics
                    </a>
                    <a href="<?= BASE_URL ?>admin/profile.php" class="list-group-item list-group-item-action rounded mb-1">
                        <i class="bi bi-person-gear me-2"></i> Profile
                    </a>
                    <a href="<?= BASE_URL ?>auth/logout.php" class="list-group-item list-group-item-action text-danger rounded">
                        <i class="bi bi-box-arrow-right me-2"></i> Logout
                    </a>
                </div>
            </div>
        </div>

        <!-- Main Dashboard Workspace -->
        <div class="col-lg-10 col-md-9">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h3 class="fw-bold mb-0">System Control Center</h3>
                    <p class="text-muted small mb-0">Editorial review, user management, and platform analytics</p>
                </div>
                <div class="d-flex gap-2">
                    <?php if ($pendingDeleteCount > 0): ?>
                        <a href="<?= BASE_URL ?>admin/delete_requests.php" class="btn btn-danger fw-bold"><i class="bi bi-trash me-1"></i> Delete Requests (<?= $pendingDeleteCount ?>)</a>
                    <?php endif; ?>
                    <a href="<?= BASE_URL ?>admin/pending_blogs.php" class="btn btn-warning fw-bold text-dark"><i class="bi bi-check2-square me-1"></i> Review Pending (<?= $pendingBlogsCount ?>)</a>
                    <a href="<?= BASE_URL ?>admin/employees.php?action=add" class="btn btn-primary fw-bold"><i class="bi bi-person-plus me-1"></i> Add Employee</a>
                </div>
            </div>

            <!-- Stats Overview Cards Grid -->
            <div class="row g-3 mb-4">
                <div class="col-6 col-md-3 col-xl-3">
                    <a href="<?= BASE_URL ?>admin/users.php" class="stat-card-link h-100">
                        <div class="card border-0 shadow-sm bg-primary text-white p-3 rounded-3 h-100">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h2 class="fw-bold mb-0 text-white"><?= $totalUsers ?></h2>
                                    <span class="small text-white font-weight-bold">Registered Users</span>
                                </div>
                                <i class="bi bi-person display-4 text-white opacity-75"></i>
                            </div>
                        </div>
                    </a>
                </div>

                <div class="col-6 col-md-3 col-xl-3">
                    <a href="<?= BASE_URL ?>admin/employees.php" class="stat-card-link h-100">
                        <div class="card border-0 shadow-sm bg-info text-dark p-3 rounded-3 h-100">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h2 class="fw-bold mb-0 text-dark"><?= $totalEmployees ?></h2>
                                    <span class="small text-dark font-weight-bold">Employee Authors</span>
                                </div>
                                <i class="bi bi-pen display-4 text-dark opacity-75"></i>
                            </div>
                        </div>
                    </a>
                </div>

                <div class="col-6 col-md-3 col-xl-3">
                    <a href="<?= BASE_URL ?>admin/blogs.php" class="stat-card-link h-100">
                        <div class="card border-0 shadow-sm bg-dark text-white p-3 rounded-3 h-100">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h2 class="fw-bold mb-0 text-white"><?= $totalBlogs ?></h2>
                                    <span class="small text-white font-weight-bold">Total Manuscripts</span>
                                </div>
                                <i class="bi bi-journal-text display-4 text-white opacity-75"></i>
                            </div>
                        </div>
                    </a>
                </div>

                <div class="col-6 col-md-3 col-xl-3">
                    <a href="<?= BASE_URL ?>admin/pending_blogs.php" class="stat-card-link h-100">
                        <div class="card border-0 shadow-sm bg-warning text-dark p-3 rounded-3 h-100">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h2 class="fw-bold mb-0 text-dark"><?= $pendingBlogsCount ?></h2>
                                    <span class="small text-dark font-weight-bold">Pending Review</span>
                                </div>
                                <i class="bi bi-clock-history display-4 text-dark opacity-75"></i>
                            </div>
                        </div>
                    </a>
                </div>

                <div class="col-6 col-md-3 col-xl-3">
                    <a href="<?= BASE_URL ?>admin/blogs.php?status=approved" class="stat-card-link h-100">
                        <div class="card border-0 shadow-sm bg-success text-white p-3 rounded-3 h-100">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h2 class="fw-bold mb-0 text-white"><?= $approvedBlogsCount ?></h2>
                                    <span class="small text-white font-weight-bold">Approved / Published</span>
                                </div>
                                <i class="bi bi-check-circle display-4 text-white opacity-75"></i>
                            </div>
                        </div>
                    </a>
                </div>

                <div class="col-6 col-md-3 col-xl-3">
                    <a href="<?= BASE_URL ?>admin/blogs.php?status=rejected" class="stat-card-link h-100">
                        <div class="card border-0 shadow-sm bg-danger text-white p-3 rounded-3 h-100">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h2 class="fw-bold mb-0 text-white"><?= $rejectedBlogsCount ?></h2>
                                    <span class="small text-white font-weight-bold">Rejected</span>
                                </div>
                                <i class="bi bi-x-circle display-4 text-white opacity-75"></i>
                            </div>
                        </div>
                    </a>
                </div>

                <div class="col-6 col-md-3 col-xl-3">
                    <a href="<?= BASE_URL ?>admin/statistics.php" class="stat-card-link h-100">
                        <div class="card border-0 shadow-sm bg-secondary text-white p-3 rounded-3 h-100">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h2 class="fw-bold mb-0 text-white"><?= $totalDownloads ?></h2>
                                    <span class="small text-white font-weight-bold">Total File Downloads</span>
                                </div>
                                <i class="bi bi-download display-4 text-white opacity-75"></i>
                            </div>
                        </div>
                    </a>
                </div>

                <div class="col-6 col-md-3 col-xl-3">
                    <a href="<?= BASE_URL ?>admin/messages.php" class="stat-card-link h-100">
                        <div class="card border-0 shadow-sm bg-light text-dark p-3 rounded-3 border h-100">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h2 class="fw-bold mb-0 text-dark"><?= $unreadMessages ?></h2>
                                    <span class="small text-dark font-weight-bold">Unread Messages</span>
                                </div>
                                <i class="bi bi-envelope-exclamation display-4 text-primary opacity-75"></i>
                            </div>
                        </div>
                    </a>
                </div>
            </div>

            <!-- Pending Review Submissions Queue -->
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0"><i class="bi bi-hourglass-split text-warning me-2"></i> Pending Editorial Submissions Queue</h5>
                    <a href="<?= BASE_URL ?>admin/pending_blogs.php" class="btn btn-sm btn-outline-primary">View Full Queue</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Blog Title</th>
                                <th>Employee Author</th>
                                <th>Category</th>
                                <th>Submitted Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($pendingBlogs)): ?>
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">
                                        <i class="bi bi-check-circle-fill text-success fs-3 d-block mb-1"></i>
                                        All clear! No pending manuscripts awaiting review.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($pendingBlogs as $pb): ?>
                                    <tr>
                                        <td>
                                            <strong class="d-block text-dark"><?= htmlspecialchars($pb['title']) ?></strong>
                                            <small class="text-muted"><?= htmlspecialchars(substr($pb['short_description'] ?? strip_tags($pb['content']), 0, 70)) ?>...</small>
                                        </td>
                                        <td>
                                            <span class="fw-semibold text-dark d-block"><?= htmlspecialchars($pb['employee_name']) ?></span>
                                            <small class="text-muted"><?= htmlspecialchars($pb['employee_email']) ?></small>
                                        </td>
                                        <td><span class="badge bg-secondary"><?= htmlspecialchars($pb['category_name'] ?? 'General') ?></span></td>
                                        <td><small class="text-muted"><?= formatDate($pb['submitted_at'], 'M d, Y H:i') ?></small></td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="<?= BASE_URL ?>admin/view_blog.php?id=<?= $pb['id'] ?>" class="btn btn-primary"><i class="bi bi-search"></i> Review</a>
                                                <a href="<?= BASE_URL ?>admin/approve_blog.php?id=<?= $pb['id'] ?>" class="btn btn-success" onclick="return confirm('Approve and publish this blog?');"><i class="bi bi-check-lg"></i> Approve</a>
                                                <a href="<?= BASE_URL ?>admin/reject_blog.php?id=<?= $pb['id'] ?>" class="btn btn-danger"><i class="bi bi-x-lg"></i> Reject</a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
