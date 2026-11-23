<?php
require_once __DIR__ . '/../includes/employee_auth.php';

$pdo = getDBConnection();
$employeeId = $_SESSION['user_id'];

if (isset($_GET['action']) && $_GET['action'] === 'read_all') {
    $pdo->prepare("UPDATE notifications SET is_read = TRUE WHERE user_id = ?")->execute([$employeeId]);
    setFlash('success', 'All notifications marked as read.');
    redirect(BASE_URL . 'employee/notifications.php');
}

if (isset($_GET['read_id'])) {
    $readId = (int)$_GET['read_id'];
    $pdo->prepare("UPDATE notifications SET is_read = TRUE WHERE id = ? AND user_id = ?")->execute([$readId, $employeeId]);
    redirect(BASE_URL . 'employee/notifications.php');
}

$stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 30");
$stmt->execute([$employeeId]);
$notifications = $stmt->fetchAll();

$pageTitle = 'Notifications — BlogSphere';
include __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h3 class="fw-bold mb-0"><i class="bi bi-bell text-warning me-2"></i> Employee Notifications</h3>
                    <p class="text-muted small mb-0">Approval alerts, rejection feedback, and system messages</p>
                </div>
                <?php if (!empty($notifications)): ?>
                    <a href="<?= BASE_URL ?>employee/notifications.php?action=read_all" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-check2-all me-1"></i> Mark All as Read
                    </a>
                <?php endif; ?>
            </div>

            <div class="card border-0 shadow-sm rounded-3">
                <div class="list-group list-group-flush">
                    <?php if (empty($notifications)): ?>
                        <div class="text-center py-5 text-muted">
                            <i class="bi bi-bell-slash display-3"></i>
                            <h5 class="mt-3">No notifications yet.</h5>
                        </div>
                    <?php else: ?>
                        <?php foreach ($notifications as $n): ?>
                            <div class="list-group-item p-3 d-flex justify-content-between align-items-start <?= !$n['is_read'] ? 'bg-light font-weight-bold border-start border-primary border-4' : '' ?>">
                                <div>
                                    <div class="d-flex align-items-center mb-1 gap-2">
                                        <h6 class="fw-bold mb-0 text-dark"><?= htmlspecialchars($n['title']) ?></h6>
                                        <?php if (!$n['is_read']): ?>
                                            <span class="badge bg-danger">New</span>
                                        <?php endif; ?>
                                    </div>
                                    <p class="mb-1 text-secondary small"><?= htmlspecialchars($n['message']) ?></p>
                                    <small class="text-muted"><i class="bi bi-clock me-1"></i> <?= formatDate($n['created_at'], 'M d, Y H:i') ?></small>
                                </div>
                                <?php if (!$n['is_read']): ?>
                                    <a href="<?= BASE_URL ?>employee/notifications.php?read_id=<?= $n['id'] ?>" class="btn btn-sm btn-link text-decoration-none">Mark Read</a>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
