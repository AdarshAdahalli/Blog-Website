<?php
require_once __DIR__ . '/../includes/admin_auth.php';

$pdo = getDBConnection();

$statusFilter = sanitizeInput($_GET['status'] ?? '');
$categoryFilter = (int)($_GET['category'] ?? 0);
$employeeFilter = (int)($_GET['employee'] ?? 0);
$search = sanitizeInput($_GET['search'] ?? '');

$whereClauses = ["1=1"];
$params = [];

if (!empty($statusFilter)) {
    $whereClauses[] = "b.status = ?";
    $params[] = $statusFilter;
}
if ($categoryFilter > 0) {
    $whereClauses[] = "b.category_id = ?";
    $params[] = $categoryFilter;
}
if ($employeeFilter > 0) {
    $whereClauses[] = "b.employee_id = ?";
    $params[] = $employeeFilter;
}
if (!empty($search)) {
    $whereClauses[] = "(b.title LIKE ? OR b.content LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}

$whereSql = implode(' AND ', $whereClauses);

$stmt = $pdo->prepare("
    SELECT b.*, c.name AS category_name, u.name AS employee_name
    FROM blogs b
    LEFT JOIN categories c ON b.category_id = c.id
    LEFT JOIN users u ON b.employee_id = u.id
    WHERE {$whereSql}
    ORDER BY b.updated_at DESC
");
$stmt->execute($params);
$blogs = $stmt->fetchAll();

// Categories & Employees for Filter Dropdowns
$categories = $pdo->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();
$employees = $pdo->query("SELECT id, name FROM users WHERE role = 'employee' ORDER BY name ASC")->fetchAll();
$pendingDeletes = $pdo->query("SELECT blog_id FROM blog_delete_requests WHERE status = 'pending'")->fetchAll(PDO::FETCH_COLUMN);

$pageTitle = 'All Blogs Management — BlogSphere Admin';
include __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold mb-0"><i class="bi bi-collection text-primary me-2"></i> All Manuscripts Management</h3>
            <p class="text-muted small mb-0">View, filter, edit, approve, reject, or delete any blog across the system</p>
        </div>
        <a href="<?= BASE_URL ?>admin/dashboard.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i> Back to Dashboard</a>
    </div>

    <!-- Filter Bar -->
    <div class="card border-0 shadow-sm mb-4 rounded-3">
        <div class="card-body p-3">
            <form action="<?= BASE_URL ?>admin/blogs.php" method="GET" class="row g-2">
                <div class="col-md-3">
                    <input type="text" name="search" class="form-control" placeholder="Search by title..." value="<?= htmlspecialchars($search) ?>">
                </div>
                <div class="col-md-3">
                    <select name="status" class="form-select">
                        <option value="">All Statuses</option>
                        <option value="draft" <?= $statusFilter === 'draft' ? 'selected' : '' ?>>Draft</option>
                        <option value="pending" <?= $statusFilter === 'pending' ? 'selected' : '' ?>>Pending Approval</option>
                        <option value="approved" <?= $statusFilter === 'approved' ? 'selected' : '' ?>>Approved / Published</option>
                        <option value="rejected" <?= $statusFilter === 'rejected' ? 'selected' : '' ?>>Rejected</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="category" class="form-select">
                        <option value="0">All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= $categoryFilter == $cat['id'] ? 'selected' : '' ?>><?= htmlspecialchars($cat['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3 d-grid">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-funnel me-1"></i> Filter Manuscripts</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Table -->
    <div class="card border-0 shadow-sm rounded-3">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 30%;">Title</th>
                        <th>Author (Employee)</th>
                        <th>Category</th>
                        <th>Status</th>
                        <th>Date Updated</th>
                        <th style="width: 20%;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($blogs)): ?>
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">No manuscripts found matching filter criteria.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($blogs as $b): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-1 mb-1">
                                        <strong class="text-dark"><?= htmlspecialchars($b['title']) ?></strong>
                                        <?php if (($b['content_type'] ?? 'text') === 'html'): ?>
                                            <span class="badge bg-danger" title="HTML Code Article"><i class="bi bi-code-slash me-1"></i> HTML</span>
                                        <?php endif; ?>
                                    </div>
                                    <?php if (in_array($b['id'], $pendingDeletes)): ?>
                                        <a href="<?= BASE_URL ?>admin/delete_requests.php" class="badge bg-warning text-dark text-decoration-none d-inline-block">
                                            <i class="bi bi-trash me-1"></i> Author Requested Deletion
                                        </a>
                                    <?php endif; ?>
                                    <?php if ($b['status'] === 'rejected' && !empty($b['rejection_reason'])): ?>
                                        <small class="text-danger d-block">Reason: <?= htmlspecialchars($b['rejection_reason']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><span class="fw-semibold text-dark"><?= htmlspecialchars($b['employee_name'] ?? 'Unknown') ?></span></td>
                                <td><span class="badge bg-secondary"><?= htmlspecialchars($b['category_name'] ?? 'General') ?></span></td>
                                <td>
                                    <?php if ($b['status'] === 'approved'): ?>
                                        <span class="badge bg-success">Approved</span>
                                    <?php elseif ($b['status'] === 'pending'): ?>
                                        <span class="badge bg-warning text-dark">Pending Review</span>
                                    <?php elseif ($b['status'] === 'rejected'): ?>
                                        <span class="badge bg-danger">Rejected</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Draft</span>
                                    <?php endif; ?>
                                </td>
                                <td><small class="text-muted"><?= formatDate($b['updated_at'], 'M d, Y H:i') ?></small></td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="<?= BASE_URL ?>admin/view_blog.php?id=<?= $b['id'] ?>" class="btn btn-outline-secondary" title="View Details"><i class="bi bi-eye"></i> View</a>
                                        <?php if ($b['status'] !== 'approved'): ?>
                                            <a href="<?= BASE_URL ?>admin/approve_blog.php?id=<?= $b['id'] ?>" class="btn btn-outline-success" onclick="return confirm('Approve blog?');" title="Approve"><i class="bi bi-check-lg"></i></a>
                                        <?php endif; ?>
                                        <?php if ($b['status'] !== 'rejected'): ?>
                                            <a href="<?= BASE_URL ?>admin/reject_blog.php?id=<?= $b['id'] ?>" class="btn btn-outline-warning text-dark" title="Reject"><i class="bi bi-x-lg"></i></a>
                                        <?php endif; ?>
                                        <a href="<?= BASE_URL ?>admin/delete_blog.php?id=<?= $b['id'] ?>" class="btn btn-outline-danger" onclick="return confirm('Permanently delete this blog?');" title="Delete"><i class="bi bi-trash"></i></a>
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

<?php include __DIR__ . '/../includes/footer.php'; ?>
