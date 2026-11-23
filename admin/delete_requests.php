<?php
require_once __DIR__ . '/../includes/admin_auth.php';

$pdo = getDBConnection();

// Handle Approve & Delete Action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_type'])) {
    $requestId = (int)($_POST['request_id'] ?? 0);
    $actionType = sanitizeInput($_POST['action_type']);
    $csrfToken = $_POST['csrf_token'] ?? '';

    if (!verifyCSRFToken($csrfToken)) {
        setFlash('danger', 'Invalid security token.');
        redirect(BASE_URL . 'admin/delete_requests.php');
    }

    // Fetch the request
    $stmtReq = $pdo->prepare("
        SELECT bdr.*, b.title as blog_title, u.name as employee_name
        FROM blog_delete_requests bdr
        LEFT JOIN blogs b ON bdr.blog_id = b.id
        LEFT JOIN users u ON bdr.employee_id = u.id
        WHERE bdr.id = ?
    ");
    $stmtReq->execute([$requestId]);
    $req = $stmtReq->fetch();

    if (!$req) {
        setFlash('danger', 'Deletion request not found.');
        redirect(BASE_URL . 'admin/delete_requests.php');
    }

    if ($actionType === 'approve') {
        $blogId = $req['blog_id'];
        $blogTitle = $req['blog_title'] ?? 'Requested Blog';
        $employeeId = $req['employee_id'];

        // 1. Delete blog permanently
        $pdo->prepare("DELETE FROM blogs WHERE id = ?")->execute([$blogId]);

        // 2. Mark request as approved
        $pdo->prepare("
            UPDATE blog_delete_requests 
            SET status = 'approved', admin_note = 'Approved and deleted by Admin.', resolved_at = NOW() 
            WHERE id = ?
        ")->execute([$requestId]);

        // 3. Notify Employee
        createNotification(
            $employeeId,
            "Deletion Request Approved ✅",
            "Your request to delete '" . $blogTitle . "' has been approved by the Administrator and the blog was permanently removed.",
            "success"
        );

        setFlash('success', 'Blog "' . htmlspecialchars($blogTitle) . '" has been permanently deleted as requested.');
        redirect(BASE_URL . 'admin/delete_requests.php');

    } elseif ($actionType === 'reject') {
        $adminNote = sanitizeInput($_POST['admin_note'] ?? 'Request rejected by Administrator.');
        $blogTitle = $req['blog_title'] ?? 'Requested Blog';
        $employeeId = $req['employee_id'];

        // Mark request as rejected
        $pdo->prepare("
            UPDATE blog_delete_requests 
            SET status = 'rejected', admin_note = ?, resolved_at = NOW() 
            WHERE id = ?
        ")->execute([$adminNote, $requestId]);

        // Notify Employee
        createNotification(
            $employeeId,
            "Deletion Request Rejected ❌",
            "Your request to delete '" . $blogTitle . "' was rejected by the Administrator. Note: " . $adminNote,
            "warning",
            $req['blog_id']
        );

        setFlash('info', 'Deletion request for "' . htmlspecialchars($blogTitle) . '" was rejected. The blog remains published.');
        redirect(BASE_URL . 'admin/delete_requests.php');
    }
}

// Fetch filter
$statusFilter = sanitizeInput($_GET['status'] ?? 'pending');
$whereSql = "1=1";
$params = [];
if (!empty($statusFilter) && in_array($statusFilter, ['pending', 'approved', 'rejected'])) {
    $whereSql = "bdr.status = ?";
    $params[] = $statusFilter;
}

$stmt = $pdo->prepare("
    SELECT bdr.*, 
           b.title AS blog_title, b.status AS current_blog_status,
           u.name AS employee_name, u.email AS employee_email
    FROM blog_delete_requests bdr
    LEFT JOIN blogs b ON bdr.blog_id = b.id
    LEFT JOIN users u ON bdr.employee_id = u.id
    WHERE {$whereSql}
    ORDER BY bdr.requested_at DESC
");
$stmt->execute($params);
$requests = $stmt->fetchAll();

// Counts for filter pills
$pendingCount = (int)$pdo->query("SELECT COUNT(*) FROM blog_delete_requests WHERE status = 'pending'")->fetchColumn();

$pageTitle = 'Blog Deletion Requests — BlogSphere Admin';
include __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold mb-0"><i class="bi bi-trash text-danger me-2"></i> Employee Blog Deletion Requests</h3>
            <p class="text-muted small mb-0">Review requests from authors asking to delete their published articles</p>
        </div>
        <a href="<?= BASE_URL ?>admin/dashboard.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i> Back to Dashboard</a>
    </div>

    <!-- Filter Pills -->
    <div class="card border-0 shadow-sm mb-4 rounded-3">
        <div class="card-body p-3 d-flex flex-wrap gap-2 align-items-center justify-content-between">
            <div class="btn-group" role="group">
                <a href="<?= BASE_URL ?>admin/delete_requests.php?status=pending" class="btn btn-sm <?= ($statusFilter === 'pending') ? 'btn-warning text-dark fw-bold' : 'btn-outline-secondary' ?>">
                    <i class="bi bi-hourglass-split me-1"></i> Pending Requests
                    <?php if ($pendingCount > 0): ?>
                        <span class="badge bg-danger ms-1"><?= $pendingCount ?></span>
                    <?php endif; ?>
                </a>
                <a href="<?= BASE_URL ?>admin/delete_requests.php?status=approved" class="btn btn-sm <?= ($statusFilter === 'approved') ? 'btn-success fw-bold' : 'btn-outline-secondary' ?>">
                    <i class="bi bi-check-circle me-1"></i> Approved / Deleted
                </a>
                <a href="<?= BASE_URL ?>admin/delete_requests.php?status=rejected" class="btn btn-sm <?= ($statusFilter === 'rejected') ? 'btn-danger fw-bold' : 'btn-outline-secondary' ?>">
                    <i class="bi bi-x-circle me-1"></i> Rejected
                </a>
                <a href="<?= BASE_URL ?>admin/delete_requests.php?status=" class="btn btn-sm <?= empty($statusFilter) ? 'btn-dark fw-bold' : 'btn-outline-secondary' ?>">
                    All Requests
                </a>
            </div>
            <span class="text-muted small">Showing <?= count($requests) ?> request(s)</span>
        </div>
    </div>

    <!-- Requests Table -->
    <div class="card border-0 shadow-sm rounded-3">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 30%;">Article / Blog Title</th>
                        <th>Author (Employee)</th>
                        <th style="width: 30%;">Deletion Reason</th>
                        <th>Requested Date</th>
                        <th>Status</th>
                        <th style="width: 15%;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($requests)): ?>
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                <i class="bi bi-check2-circle display-4 text-success d-block mb-2"></i>
                                <h5>No deletion requests found.</h5>
                                <p class="mb-0">There are no employee deletion requests matching this filter.</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($requests as $r): ?>
                            <tr>
                                <td>
                                    <?php if ($r['blog_title']): ?>
                                        <strong class="d-block text-dark"><?= htmlspecialchars($r['blog_title']) ?></strong>
                                        <?php if ($r['current_blog_status']): ?>
                                            <a href="<?= BASE_URL ?>user/blog_details.php?id=<?= $r['blog_id'] ?>" target="_blank" class="small text-primary text-decoration-none">
                                                <i class="bi bi-box-arrow-up-right me-1"></i> View Article
                                            </a>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-muted fst-italic">[Deleted Article ID #<?= $r['blog_id'] ?>]</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong class="d-block text-dark"><?= htmlspecialchars($r['employee_name'] ?? 'Unknown') ?></strong>
                                    <small class="text-muted"><?= htmlspecialchars($r['employee_email'] ?? '') ?></small>
                                </td>
                                <td>
                                    <div class="p-2 bg-light rounded border small">
                                        <?= nl2br(htmlspecialchars($r['reason'])) ?>
                                    </div>
                                    <?php if (!empty($r['admin_note'])): ?>
                                        <div class="mt-1 small text-secondary">
                                            <strong>Admin Note:</strong> <?= htmlspecialchars($r['admin_note']) ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <small class="text-muted d-block"><?= formatDate($r['requested_at'], 'M d, Y H:i') ?></small>
                                    <?php if ($r['resolved_at']): ?>
                                        <small class="text-muted d-block">Resolved: <?= formatDate($r['resolved_at'], 'M d, H:i') ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($r['status'] === 'pending'): ?>
                                        <span class="badge bg-warning text-dark"><i class="bi bi-hourglass-split me-1"></i> Awaiting Admin</span>
                                    <?php elseif ($r['status'] === 'approved'): ?>
                                        <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i> Deleted</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger"><i class="bi bi-x-circle me-1"></i> Rejected</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($r['status'] === 'pending'): ?>
                                        <div class="btn-group btn-group-sm">
                                            <!-- Approve Button Form -->
                                            <form action="<?= BASE_URL ?>admin/delete_requests.php" method="POST" class="d-inline" onsubmit="return confirm('Permanently delete this published blog? This action cannot be undone.');">
                                                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                                                <input type="hidden" name="request_id" value="<?= $r['id'] ?>">
                                                <input type="hidden" name="action_type" value="approve">
                                                <button type="submit" class="btn btn-danger" title="Approve & Permanently Delete Blog">
                                                    <i class="bi bi-trash me-1"></i> Approve &amp; Delete
                                                </button>
                                            </form>

                                            <!-- Reject Modal Trigger -->
                                            <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#rejectModal<?= $r['id'] ?>" title="Reject Request">
                                                <i class="bi bi-x-lg"></i>
                                            </button>
                                        </div>

                                        <!-- Reject Modal -->
                                        <div class="modal fade" id="rejectModal<?= $r['id'] ?>" tabindex="-1">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <form action="<?= BASE_URL ?>admin/delete_requests.php" method="POST">
                                                        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                                                        <input type="hidden" name="request_id" value="<?= $r['id'] ?>">
                                                        <input type="hidden" name="action_type" value="reject">

                                                        <div class="modal-header bg-dark text-white">
                                                            <h5 class="modal-title">Reject Deletion Request</h5>
                                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <p>You are rejecting the deletion request for <strong><?= htmlspecialchars($r['blog_title'] ?? '') ?></strong>. The blog will remain published.</p>
                                                            <div class="mb-3">
                                                                <label class="form-label fw-bold">Explanation / Note for Author</label>
                                                                <textarea class="form-control" name="admin_note" rows="3" required placeholder="Explain why this blog cannot be deleted..."></textarea>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                            <button type="submit" class="btn btn-danger">Reject Request</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-muted small">Processed</span>
                                    <?php endif; ?>
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
