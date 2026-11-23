<?php
require_once __DIR__ . '/../includes/admin_auth.php';

$pdo = getDBConnection();

// Status change handler
if (isset($_GET['status_change']) && isset($_GET['id'])) {
    $qId = (int)$_GET['id'];
    $newStatus = sanitizeInput($_GET['status_change']);
    if (in_array($newStatus, ['open', 'in_progress', 'resolved'])) {
        $pdo->prepare("UPDATE queries SET status = ? WHERE id = ?")->execute([$newStatus, $qId]);
        setFlash('success', 'Query status updated to ' . str_replace('_', ' ', $newStatus));
        redirect(BASE_URL . 'admin/queries.php');
    }
}

// Delete handler
if (isset($_GET['delete']) && isset($_GET['id'])) {
    $qId = (int)$_GET['id'];
    $pdo->prepare("DELETE FROM queries WHERE id = ?")->execute([$qId]);
    setFlash('success', 'User query deleted.');
    redirect(BASE_URL . 'admin/queries.php');
}

$statusFilter = sanitizeInput($_GET['status'] ?? '');

$whereSql = "1=1";
$params = [];
if (!empty($statusFilter) && in_array($statusFilter, ['open', 'in_progress', 'resolved'])) {
    $whereSql = "q.status = ?";
    $params[] = $statusFilter;
}

$stmt = $pdo->prepare("
    SELECT q.*, 
           u.name AS user_name, u.email AS user_email,
           emp.name AS employee_name,
           b.title AS blog_title
    FROM queries q
    JOIN users u ON q.user_id = u.id
    LEFT JOIN users emp ON q.employee_id = emp.id
    LEFT JOIN blogs b ON q.blog_id = b.id
    WHERE {$whereSql}
    ORDER BY q.created_at DESC
");
$stmt->execute($params);
$queries = $stmt->fetchAll();

$pageTitle = 'User Queries & Support — BlogSphere Admin';
include __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold mb-0"><i class="bi bi-question-circle text-primary me-2"></i> User Queries &amp; Support Questions</h3>
            <p class="text-muted small mb-0">Manage questions asked by readers regarding published blogs, PDFs, and website support</p>
        </div>
        <a href="<?= BASE_URL ?>admin/dashboard.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i> Back to Dashboard</a>
    </div>

    <!-- Filter Bar -->
    <div class="card border-0 shadow-sm mb-4 rounded-3">
        <div class="card-body p-3">
            <form action="<?= BASE_URL ?>admin/queries.php" method="GET" class="row g-2">
                <div class="col-md-9">
                    <select name="status" class="form-select" onchange="this.form.submit();">
                        <option value="">All Query Statuses</option>
                        <option value="open" <?= $statusFilter === 'open' ? 'selected' : '' ?>>Open Queries</option>
                        <option value="in_progress" <?= $statusFilter === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                        <option value="resolved" <?= $statusFilter === 'resolved' ? 'selected' : '' ?>>Resolved</option>
                    </select>
                </div>
                <div class="col-md-3 d-grid">
                    <a href="<?= BASE_URL ?>admin/queries.php" class="btn btn-secondary">Reset Filter</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Queries Table -->
    <div class="card border-0 shadow-sm rounded-3">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>User (Reader)</th>
                        <th>Subject &amp; Question</th>
                        <th>Associated Blog</th>
                        <th>Assigned Author</th>
                        <th>Status</th>
                        <th>Submitted</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($queries)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="bi bi-chat-check display-4 text-success d-block mb-2"></i>
                                <h5>No user queries found.</h5>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($queries as $q): ?>
                            <tr>
                                <td>
                                    <strong class="d-block text-dark"><?= htmlspecialchars($q['user_name']) ?></strong>
                                    <small class="text-muted"><?= htmlspecialchars($q['user_email']) ?></small>
                                </td>
                                <td style="max-width: 280px;">
                                    <strong class="d-block text-dark"><?= htmlspecialchars($q['subject']) ?></strong>
                                    <small class="text-secondary d-block mt-1 text-truncate" style="max-width: 260px;"><?= htmlspecialchars($q['message']) ?></small>
                                </td>
                                <td>
                                    <?php if ($q['blog_title']): ?>
                                        <a href="<?= BASE_URL ?>user/blog_details.php?id=<?= $q['blog_id'] ?>" class="text-decoration-none fw-semibold text-primary">
                                            <?= htmlspecialchars($q['blog_title']) ?>
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted">General Website Query</span>
                                    <?php endif; ?>
                                </td>
                                <td><span class="badge bg-secondary"><?= htmlspecialchars($q['employee_name'] ?? 'Admin Support') ?></span></td>
                                <td>
                                    <?php if ($q['status'] === 'open'): ?>
                                        <span class="badge bg-danger"><i class="bi bi-exclamation-circle me-1"></i> Open</span>
                                    <?php elseif ($q['status'] === 'in_progress'): ?>
                                        <span class="badge bg-warning text-dark"><i class="bi bi-hourglass-split me-1"></i> In Progress</span>
                                    <?php else: ?>
                                        <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i> Resolved</span>
                                    <?php endif; ?>
                                </td>
                                <td><small class="text-muted"><?= formatDate($q['created_at'], 'M d, Y H:i') ?></small></td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="<?= BASE_URL ?>admin/send_message.php?receiver_id=<?= $q['user_id'] ?>&blog_id=<?= $q['blog_id'] ?>&subject=<?= urlencode('Reply to your query: ' . $q['subject']) ?>" class="btn btn-primary" title="Reply to User"><i class="bi bi-reply"></i> Reply</a>
                                        
                                        <?php if ($q['status'] !== 'resolved'): ?>
                                            <a href="<?= BASE_URL ?>admin/queries.php?status_change=resolved&id=<?= $q['id'] ?>" class="btn btn-success" title="Mark as Resolved"><i class="bi bi-check-lg"></i></a>
                                        <?php else: ?>
                                            <a href="<?= BASE_URL ?>admin/queries.php?status_change=open&id=<?= $q['id'] ?>" class="btn btn-outline-warning text-dark" title="Reopen Query"><i class="bi bi-arrow-counterclockwise"></i></a>
                                        <?php endif; ?>

                                        <a href="<?= BASE_URL ?>admin/queries.php?delete=1&id=<?= $q['id'] ?>" class="btn btn-outline-danger" onclick="return confirm('Delete this query?');" title="Delete"><i class="bi bi-trash"></i></a>
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
