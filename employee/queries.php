<?php
require_once __DIR__ . '/../includes/employee_auth.php';

$pdo = getDBConnection();
$employeeId = $_SESSION['user_id'];

// Status change handler
if (isset($_GET['status_change']) && isset($_GET['id'])) {
    $qId = (int)$_GET['id'];
    $newStatus = sanitizeInput($_GET['status_change']);
    if (in_array($newStatus, ['open', 'in_progress', 'resolved'])) {
        $pdo->prepare("UPDATE queries SET status = ? WHERE id = ? AND employee_id = ?")->execute([$newStatus, $qId, $employeeId]);
        setFlash('success', 'Query status updated to ' . str_replace('_', ' ', $newStatus));
        redirect(BASE_URL . 'employee/queries.php');
    }
}

$stmt = $pdo->prepare("
    SELECT q.*, 
           u.name AS user_name, u.email AS user_email,
           b.title AS blog_title
    FROM queries q
    JOIN users u ON q.user_id = u.id
    LEFT JOIN blogs b ON q.blog_id = b.id
    WHERE q.employee_id = ?
    ORDER BY q.created_at DESC
");
$stmt->execute([$employeeId]);
$queries = $stmt->fetchAll();

$pageTitle = 'Reader Queries — BlogSphere Employee';
include __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold mb-0"><i class="bi bi-question-circle text-primary me-2"></i> Reader Queries Regarding My Blogs</h3>
            <p class="text-muted small mb-0">Questions submitted by readers on your published articles and downloadable attachments</p>
        </div>
        <a href="<?= BASE_URL ?>employee/dashboard.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i> Back to Dashboard</a>
    </div>

    <div class="card border-0 shadow-sm rounded-3">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Reader Name</th>
                        <th>Question / Subject</th>
                        <th>Article Title</th>
                        <th>Status</th>
                        <th>Submitted</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($queries)): ?>
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                <i class="bi bi-chat-check display-4 text-success d-block mb-2"></i>
                                <h5>No questions asked yet!</h5>
                                <p class="mb-0">When readers submit questions on your published articles, they will appear here.</p>
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
                                    <small class="text-secondary d-block mt-1"><?= htmlspecialchars($q['message']) ?></small>
                                </td>
                                <td>
                                    <?php if ($q['blog_title']): ?>
                                        <a href="<?= BASE_URL ?>user/blog_details.php?id=<?= $q['blog_id'] ?>" class="text-decoration-none fw-semibold text-primary">
                                            <?= htmlspecialchars($q['blog_title']) ?>
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted">General Query</span>
                                    <?php endif; ?>
                                </td>
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
                                        <a href="<?= BASE_URL ?>employee/send_message.php?receiver_id=<?= $q['user_id'] ?>&blog_id=<?= $q['blog_id'] ?>&subject=<?= urlencode('Reply to your question: ' . $q['subject']) ?>" class="btn btn-primary" title="Reply to Reader"><i class="bi bi-reply"></i> Reply Reader</a>
                                        
                                        <?php if ($q['status'] !== 'resolved'): ?>
                                            <a href="<?= BASE_URL ?>employee/queries.php?status_change=resolved&id=<?= $q['id'] ?>" class="btn btn-success" title="Mark Resolved"><i class="bi bi-check-lg"></i></a>
                                        <?php else: ?>
                                            <a href="<?= BASE_URL ?>employee/queries.php?status_change=open&id=<?= $q['id'] ?>" class="btn btn-outline-warning text-dark" title="Reopen"><i class="bi bi-arrow-counterclockwise"></i></a>
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

<?php include __DIR__ . '/../includes/footer.php'; ?>
