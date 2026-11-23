<?php
require_once __DIR__ . '/../includes/admin_auth.php';

$pdo = getDBConnection();

// Summary Totals via SQL Aggregations
$totalBlogs = (int)$pdo->query("SELECT COUNT(*) FROM blogs")->fetchColumn();
$approvedBlogs = (int)$pdo->query("SELECT COUNT(*) FROM blogs WHERE status = 'approved'")->fetchColumn();
$rejectedBlogs = (int)$pdo->query("SELECT COUNT(*) FROM blogs WHERE status = 'rejected'")->fetchColumn();
$pendingBlogs = (int)$pdo->query("SELECT COUNT(*) FROM blogs WHERE status = 'pending'")->fetchColumn();
$draftBlogs = (int)$pdo->query("SELECT COUNT(*) FROM blogs WHERE status = 'draft'")->fetchColumn();

$totalUsers = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'user'")->fetchColumn();
$totalEmployees = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'employee'")->fetchColumn();
$totalDownloads = (int)$pdo->query("SELECT COUNT(*) FROM blog_downloads")->fetchColumn();

// Most Downloaded Blogs (Top 5)
$stmtTopDownloaded = $pdo->query("
    SELECT b.id, b.title, c.name as category_name, u.name as author_name, COUNT(bd.id) as download_count
    FROM blogs b
    JOIN blog_downloads bd ON b.id = bd.blog_id
    LEFT JOIN categories c ON b.category_id = c.id
    LEFT JOIN users u ON b.employee_id = u.id
    GROUP BY b.id
    ORDER BY download_count DESC
    LIMIT 5
");
$topDownloadedBlogs = $stmtTopDownloaded->fetchAll();

// Most Active Employees / Authors (Top 5)
$stmtTopEmployees = $pdo->query("
    SELECT u.id, u.name, u.email,
           COUNT(b.id) as total_submitted,
           SUM(CASE WHEN b.status = 'approved' THEN 1 ELSE 0 END) as approved_count,
           SUM(CASE WHEN b.status = 'rejected' THEN 1 ELSE 0 END) as rejected_count
    FROM users u
    JOIN blogs b ON u.id = b.employee_id
    WHERE u.role = 'employee'
    GROUP BY u.id
    ORDER BY approved_count DESC, total_submitted DESC
    LIMIT 5
");
$topEmployees = $stmtTopEmployees->fetchAll();

// Category Distribution Stats
$stmtCatStats = $pdo->query("
    SELECT c.name, COUNT(b.id) as blog_count
    FROM categories c
    LEFT JOIN blogs b ON c.id = b.category_id AND b.status = 'approved'
    GROUP BY c.id
    ORDER BY blog_count DESC
");
$catStats = $stmtCatStats->fetchAll();

$pageTitle = 'Platform Analytics & Statistics — BlogSphere Admin';
include __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold mb-0"><i class="bi bi-graph-up-arrow text-primary me-2"></i> Platform Analytics &amp; Statistics</h3>
            <p class="text-muted small mb-0">System-wide editorial metrics, top performing publications, and author productivity</p>
        </div>
        <a href="<?= BASE_URL ?>admin/dashboard.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i> Back to Dashboard</a>
    </div>

    <!-- Summary Metrics -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm bg-primary text-white p-3 rounded-3 text-center">
                <h2 class="fw-extrabold mb-0"><?= number_format($totalBlogs) ?></h2>
                <span class="small opacity-75">Total Manuscripts Created</span>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm bg-success text-white p-3 rounded-3 text-center">
                <h2 class="fw-extrabold mb-0"><?= number_format($approvedBlogs) ?></h2>
                <span class="small opacity-75">Approved / Published Blogs</span>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm bg-warning text-dark p-3 rounded-3 text-center">
                <h2 class="fw-extrabold mb-0"><?= number_format($pendingBlogs) ?></h2>
                <span class="small">Pending Review</span>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm bg-danger text-white p-3 rounded-3 text-center">
                <h2 class="fw-extrabold mb-0"><?= number_format($rejectedBlogs) ?></h2>
                <span class="small opacity-75">Rejected Submissions</span>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Top Downloaded Blogs Table -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm rounded-3 h-100">
                <div class="card-header bg-white py-3 border-bottom">
                    <h5 class="fw-bold mb-0"><i class="bi bi-download text-success me-2"></i> Top 5 Most Downloaded Blogs</h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Blog Title</th>
                                <th>Author</th>
                                <th>Downloads</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($topDownloadedBlogs)): ?>
                                <tr>
                                    <td colspan="3" class="text-center py-4 text-muted">No downloads recorded yet.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($topDownloadedBlogs as $tb): ?>
                                    <tr>
                                        <td>
                                            <a href="<?= BASE_URL ?>user/blog_details.php?id=<?= $tb['id'] ?>" class="fw-bold text-dark text-decoration-none">
                                                <?= htmlspecialchars($tb['title']) ?>
                                            </a>
                                            <small class="text-muted d-block"><?= htmlspecialchars($tb['category_name'] ?? 'General') ?></small>
                                        </td>
                                        <td><span class="small text-muted"><?= htmlspecialchars($tb['author_name']) ?></span></td>
                                        <td><span class="badge bg-success fs-6"><?= number_format($tb['download_count']) ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Top Active Authors Table -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm rounded-3 h-100">
                <div class="card-header bg-white py-3 border-bottom">
                    <h5 class="fw-bold mb-0"><i class="bi bi-award text-warning me-2"></i> Top 5 Active Employee Authors</h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Employee Author</th>
                                <th>Total Submitted</th>
                                <th>Approved</th>
                                <th>Rejected</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($topEmployees)): ?>
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-muted">No employee statistics available.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($topEmployees as $te): ?>
                                    <tr>
                                        <td>
                                            <strong class="d-block text-dark"><?= htmlspecialchars($te['name']) ?></strong>
                                            <small class="text-muted"><?= htmlspecialchars($te['email']) ?></small>
                                        </td>
                                        <td><span class="fw-bold text-dark"><?= $te['total_submitted'] ?></span></td>
                                        <td><span class="badge bg-success"><?= $te['approved_count'] ?></span></td>
                                        <td><span class="badge bg-danger"><?= $te['rejected_count'] ?></span></td>
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
