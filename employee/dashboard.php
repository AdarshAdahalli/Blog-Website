<?php
require_once __DIR__ . '/../includes/employee_auth.php';

$pdo = getDBConnection();
$employeeId = $_SESSION['user_id'];

// Dashboard Cards Statistics
$stmtTotal = $pdo->prepare("SELECT COUNT(*) FROM blogs WHERE employee_id = ?");
$stmtTotal->execute([$employeeId]);
$totalBlogs = (int)$stmtTotal->fetchColumn();

$stmtDraft = $pdo->prepare("SELECT COUNT(*) FROM blogs WHERE employee_id = ? AND status = 'draft'");
$stmtDraft->execute([$employeeId]);
$totalDrafts = (int)$stmtDraft->fetchColumn();

$stmtPending = $pdo->prepare("SELECT COUNT(*) FROM blogs WHERE employee_id = ? AND status = 'pending'");
$stmtPending->execute([$employeeId]);
$totalPending = (int)$stmtPending->fetchColumn();

$stmtApproved = $pdo->prepare("SELECT COUNT(*) FROM blogs WHERE employee_id = ? AND status = 'approved'");
$stmtApproved->execute([$employeeId]);
$totalApproved = (int)$stmtApproved->fetchColumn();

$stmtRejected = $pdo->prepare("SELECT COUNT(*) FROM blogs WHERE employee_id = ? AND status = 'rejected'");
$stmtRejected->execute([$employeeId]);
$totalRejected = (int)$stmtRejected->fetchColumn();

// Fetch Recent Blogs
$stmtRecent = $pdo->prepare("
    SELECT b.*, c.name as category_name
    FROM blogs b
    LEFT JOIN categories c ON b.category_id = c.id
    WHERE b.employee_id = ?
    ORDER BY b.updated_at DESC
    LIMIT 5
");
$stmtRecent->execute([$employeeId]);
$recentBlogs = $stmtRecent->fetchAll();

$pageTitle = 'Employee Dashboard — BlogSphere';
include __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row">
        <!-- Employee Sidebar -->
        <div class="col-lg-3 col-md-4 mb-4">
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-body text-center p-4 border-bottom bg-dark text-white rounded-top">
                    <div class="avatar bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 70px; height: 70px; font-size: 2rem;">
                        <i class="bi bi-pen-fill"></i>
                    </div>
                    <h5 class="fw-bold mb-1 text-white"><?= htmlspecialchars($_SESSION['user_name']) ?></h5>
                    <span class="badge bg-primary">Blog Writer / Author</span>
                </div>
                <div class="list-group list-group-flush p-2">
                    <a href="<?= BASE_URL ?>employee/dashboard.php" class="list-group-item list-group-item-action active rounded mb-1">
                        <i class="bi bi-speedometer2 me-2"></i> Dashboard
                    </a>
                    <a href="<?= BASE_URL ?>employee/add_blog.php" class="list-group-item list-group-item-action rounded mb-1">
                        <i class="bi bi-plus-circle-fill text-success me-2"></i> Add New Blog
                    </a>
                    <a href="<?= BASE_URL ?>employee/my_blogs.php" class="list-group-item list-group-item-action rounded mb-1">
                        <i class="bi bi-journals me-2"></i> My Blogs
                    </a>
                    <a href="<?= BASE_URL ?>employee/my_blogs.php?status=pending" class="list-group-item list-group-item-action rounded mb-1 d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-clock-history me-2"></i> Pending Blogs</span>
                        <span class="badge bg-warning text-dark rounded-pill"><?= $totalPending ?></span>
                    </a>
                    <a href="<?= BASE_URL ?>employee/my_blogs.php?status=approved" class="list-group-item list-group-item-action rounded mb-1 d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-check-circle me-2"></i> Approved Blogs</span>
                        <span class="badge bg-success rounded-pill"><?= $totalApproved ?></span>
                    </a>
                    <a href="<?= BASE_URL ?>employee/my_blogs.php?status=rejected" class="list-group-item list-group-item-action rounded mb-1 d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-x-circle me-2"></i> Rejected Blogs</span>
                        <span class="badge bg-danger rounded-pill"><?= $totalRejected ?></span>
                    </a>
                    <a href="<?= BASE_URL ?>employee/messages.php" class="list-group-item list-group-item-action rounded mb-1">
                        <i class="bi bi-chat-left-text me-2"></i> Messages
                    </a>
                    <a href="<?= BASE_URL ?>employee/notifications.php" class="list-group-item list-group-item-action rounded mb-1">
                        <i class="bi bi-bell me-2"></i> Notifications
                    </a>
                    <a href="<?= BASE_URL ?>employee/profile.php" class="list-group-item list-group-item-action rounded mb-1">
                        <i class="bi bi-person-gear me-2"></i> Profile
                    </a>
                    <a href="<?= BASE_URL ?>auth/logout.php" class="list-group-item list-group-item-action text-danger rounded">
                        <i class="bi bi-box-arrow-right me-2"></i> Logout
                    </a>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="col-lg-9 col-md-8">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h3 class="fw-bold mb-0">Writer Portal — Overview</h3>
                    <p class="text-muted small">Manage your draft manuscripts, submitted articles, and editorial feedback</p>
                </div>
                <a href="<?= BASE_URL ?>employee/add_blog.php" class="btn btn-success fw-bold"><i class="bi bi-plus-lg me-1"></i> Create New Manuscript</a>
            </div>

            <!-- Dashboard Metric Cards -->
            <div class="row g-3 mb-4">
                <div class="col-sm-6 col-xl-2">
                    <a href="<?= BASE_URL ?>employee/my_blogs.php" class="stat-card-link h-100">
                        <div class="card border-0 shadow-sm bg-dark text-white p-3 rounded-3 text-center h-100">
                            <h2 class="fw-bold mb-0 text-white"><?= $totalBlogs ?></h2>
                            <span class="small text-white fw-semibold">Total Blogs</span>
                        </div>
                    </a>
                </div>

                <div class="col-sm-6 col-xl-2">
                    <a href="<?= BASE_URL ?>employee/my_blogs.php?status=draft" class="stat-card-link h-100">
                        <div class="card border-0 shadow-sm bg-secondary text-white p-3 rounded-3 text-center h-100">
                            <h2 class="fw-bold mb-0 text-white"><?= $totalDrafts ?></h2>
                            <span class="small text-white opacity-90">Drafts</span>
                        </div>
                    </a>
                </div>

                <div class="col-sm-6 col-xl-3">
                    <a href="<?= BASE_URL ?>employee/my_blogs.php?status=pending" class="stat-card-link h-100">
                        <div class="card border-0 shadow-sm bg-warning text-dark p-3 rounded-3 text-center h-100">
                            <h2 class="fw-bold mb-0 text-dark"><?= $totalPending ?></h2>
                            <span class="small text-dark fw-semibold">Pending Review</span>
                        </div>
                    </a>
                </div>

                <div class="col-sm-6 col-xl-3">
                    <a href="<?= BASE_URL ?>employee/my_blogs.php?status=approved" class="stat-card-link h-100">
                        <div class="card border-0 shadow-sm bg-success text-white p-3 rounded-3 text-center h-100">
                            <h2 class="fw-bold mb-0 text-white"><?= $totalApproved ?></h2>
                            <span class="small text-white fw-semibold">Approved / Published</span>
                        </div>
                    </a>
                </div>

                <div class="col-sm-6 col-xl-2">
                    <a href="<?= BASE_URL ?>employee/my_blogs.php?status=rejected" class="stat-card-link h-100">
                        <div class="card border-0 shadow-sm bg-danger text-white p-3 rounded-3 text-center h-100">
                            <h2 class="fw-bold mb-0 text-white"><?= $totalRejected ?></h2>
                            <span class="small text-white fw-semibold">Rejected</span>
                        </div>
                    </a>
                </div>
            </div>

            <!-- Recent Manuscripts Table -->
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0"><i class="bi bi-journals text-primary me-2"></i> My Recent Blog Manuscripts</h5>
                    <a href="<?= BASE_URL ?>employee/my_blogs.php" class="btn btn-sm btn-outline-primary">View All Manuscripts</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Blog Title</th>
                                <th>Category</th>
                                <th>Status</th>
                                <th>Last Updated</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recentBlogs)): ?>
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">No manuscripts created yet. Click 'Create New Manuscript' to start writing.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($recentBlogs as $blog): ?>
                                    <tr>
                                        <td>
                                            <strong class="d-block text-dark"><?= htmlspecialchars($blog['title']) ?></strong>
                                            <?php if ($blog['status'] === 'rejected' && !empty($blog['rejection_reason'])): ?>
                                                <small class="text-danger d-block mt-1">
                                                    <i class="bi bi-info-circle me-1"></i> <strong>Reason:</strong> <?= htmlspecialchars($blog['rejection_reason']) ?>
                                                </small>
                                            <?php endif; ?>
                                        </td>
                                        <td><span class="badge bg-secondary"><?= htmlspecialchars($blog['category_name'] ?? 'General') ?></span></td>
                                        <td>
                                            <?php if ($blog['status'] === 'approved'): ?>
                                                <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i> Approved</span>
                                            <?php elseif ($blog['status'] === 'pending'): ?>
                                                <span class="badge bg-warning text-dark"><i class="bi bi-hourglass-split me-1"></i> Pending Admin Review</span>
                                            <?php elseif ($blog['status'] === 'rejected'): ?>
                                                <span class="badge bg-danger"><i class="bi bi-x-circle me-1"></i> Rejected</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary"><i class="bi bi-file-earmark me-1"></i> Draft</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><small class="text-muted"><?= formatDate($blog['updated_at'], 'M d, Y H:i') ?></small></td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="<?= BASE_URL ?>user/blog_details.php?id=<?= $blog['id'] ?>" class="btn btn-outline-secondary" title="Preview"><i class="bi bi-eye"></i></a>
                                                <a href="<?= BASE_URL ?>employee/edit_blog.php?id=<?= $blog['id'] ?>" class="btn btn-outline-primary" title="Edit"><i class="bi bi-pencil"></i></a>
                                                <?php if ($blog['status'] === 'draft'): ?>
                                                    <a href="<?= BASE_URL ?>employee/submit_blog.php?id=<?= $blog['id'] ?>" class="btn btn-outline-success" title="Submit for Approval"><i class="bi bi-send"></i> Submit</a>
                                                    <a href="<?= BASE_URL ?>employee/delete_blog.php?id=<?= $blog['id'] ?>" class="btn btn-outline-danger" onclick="return confirm('Delete draft manuscript?');" title="Delete Draft"><i class="bi bi-trash"></i></a>
                                                <?php endif; ?>
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
