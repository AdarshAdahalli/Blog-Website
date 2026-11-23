<?php
require_once __DIR__ . '/../includes/admin_auth.php';

$pdo = getDBConnection();

$stmt = $pdo->query("
    SELECT b.*, c.name AS category_name, u.name AS employee_name, u.email AS employee_email
    FROM blogs b
    LEFT JOIN categories c ON b.category_id = c.id
    LEFT JOIN users u ON b.employee_id = u.id
    WHERE b.status = 'pending'
    ORDER BY b.submitted_at ASC
");
$pendingBlogs = $stmt->fetchAll();

$pageTitle = 'Pending Approvals — BlogSphere Admin';
include __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold mb-0"><i class="bi bi-clock-history text-warning me-2"></i> Pending Editorial Approvals</h3>
            <p class="text-muted small mb-0">Review manuscripts submitted by employees before publishing to users</p>
        </div>
        <a href="<?= BASE_URL ?>admin/dashboard.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i> Back to Dashboard</a>
    </div>

    <div class="card border-0 shadow-sm rounded-3">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 35%;">Blog Title</th>
                        <th>Author (Employee)</th>
                        <th>Category</th>
                        <th>Submitted Date</th>
                        <th>Status</th>
                        <th style="width: 20%;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($pendingBlogs)): ?>
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                <i class="bi bi-check2-circle display-3 text-success d-block mb-2"></i>
                                <h5>No pending blogs!</h5>
                                <p class="mb-0">All submitted manuscripts have been reviewed.</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($pendingBlogs as $b): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-1 mb-1">
                                        <strong class="text-dark"><?= htmlspecialchars($b['title']) ?></strong>
                                        <?php if (($b['content_type'] ?? 'text') === 'html'): ?>
                                            <span class="badge bg-danger" title="HTML Code Article"><i class="bi bi-code-slash me-1"></i> HTML</span>
                                        <?php endif; ?>
                                    </div>
                                    <small class="text-muted"><?= htmlspecialchars(substr($b['short_description'] ?? strip_tags($b['content']), 0, 80)) ?>...</small>
                                </td>
                                <td>
                                    <strong class="d-block text-dark"><?= htmlspecialchars($b['employee_name']) ?></strong>
                                    <small class="text-muted"><?= htmlspecialchars($b['employee_email']) ?></small>
                                </td>
                                <td><span class="badge bg-secondary"><?= htmlspecialchars($b['category_name'] ?? 'General') ?></span></td>
                                <td><small class="text-muted"><?= formatDate($b['submitted_at'], 'M d, Y H:i') ?></small></td>
                                <td><span class="badge bg-warning text-dark"><i class="bi bi-hourglass-split me-1"></i> Pending Review</span></td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="<?= BASE_URL ?>admin/view_blog.php?id=<?= $b['id'] ?>" class="btn btn-primary" title="Review Complete Manuscript"><i class="bi bi-eye"></i> View</a>
                                        <a href="<?= BASE_URL ?>admin/approve_blog.php?id=<?= $b['id'] ?>" class="btn btn-success" onclick="return confirm('Approve and publish this blog?');" title="Approve Blog"><i class="bi bi-check-lg"></i> Approve</a>
                                        <a href="<?= BASE_URL ?>admin/reject_blog.php?id=<?= $b['id'] ?>" class="btn btn-danger" title="Reject with Reason"><i class="bi bi-x-lg"></i> Reject</a>
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
