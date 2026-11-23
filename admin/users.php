<?php
require_once __DIR__ . '/../includes/admin_auth.php';

$pdo = getDBConnection();
$userId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$search = sanitizeInput($_GET['search'] ?? '');

// Handle Status Change
if (isset($_GET['status_change']) && $userId > 0) {
    $newStatus = sanitizeInput($_GET['status_change']);
    if (in_array($newStatus, ['active', 'inactive', 'blocked'])) {
        $pdo->prepare("UPDATE users SET status = ? WHERE id = ? AND role = 'user'")->execute([$newStatus, $userId]);
        setFlash('info', 'User status updated to ' . $newStatus);
        redirect(BASE_URL . 'admin/users.php');
    }
}

// Handle Delete User
if (isset($_GET['delete_user']) && $userId > 0) {
    $pdo->prepare("DELETE FROM users WHERE id = ? AND role = 'user'")->execute([$userId]);
    setFlash('success', 'User account deleted.');
    redirect(BASE_URL . 'admin/users.php');
}

// Fetch Users List
$whereClauses = ["u.role = 'user'"];
$params = [];

if (!empty($search)) {
    $whereClauses[] = "(u.name LIKE ? OR u.email LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}

$whereSql = implode(' AND ', $whereClauses);

$stmt = $pdo->prepare("
    SELECT u.*, COUNT(bd.id) as total_downloads
    FROM users u
    LEFT JOIN blog_downloads bd ON u.id = bd.user_id
    WHERE {$whereSql}
    GROUP BY u.id
    ORDER BY u.created_at DESC
");
$stmt->execute($params);
$usersList = $stmt->fetchAll();

// View Download History Modal / Section
$selectedUserHistory = [];
$selectedUser = null;
if (isset($_GET['view_downloads']) && $userId > 0) {
    $stmtUser = $pdo->prepare("SELECT * FROM users WHERE id = ? AND role = 'user'");
    $stmtUser->execute([$userId]);
    $selectedUser = $stmtUser->fetch();

    if ($selectedUser) {
        $stmtHistory = $pdo->prepare("
            SELECT bd.downloaded_at, b.title, b.id as blog_id, c.name as category_name
            FROM blog_downloads bd
            JOIN blogs b ON bd.blog_id = b.id
            LEFT JOIN categories c ON b.category_id = c.id
            WHERE bd.user_id = ?
            ORDER BY bd.downloaded_at DESC
        ");
        $stmtHistory->execute([$userId]);
        $selectedUserHistory = $stmtHistory->fetchAll();
    }
}

$pageTitle = 'User Management — BlogSphere Admin';
include __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold mb-0"><i class="bi bi-person-lines-fill text-primary me-2"></i> User Account Management</h3>
            <p class="text-muted small mb-0">View registered public users, inspect download logs, and enforce access blocks</p>
        </div>
        <a href="<?= BASE_URL ?>admin/dashboard.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i> Back to Dashboard</a>
    </div>

    <!-- Search Bar -->
    <div class="card border-0 shadow-sm mb-4 rounded-3">
        <div class="card-body p-3">
            <form action="<?= BASE_URL ?>admin/users.php" method="GET" class="row g-2">
                <div class="col-md-9">
                    <input type="text" name="search" class="form-control" placeholder="Search user name or email..." value="<?= htmlspecialchars($search) ?>">
                </div>
                <div class="col-md-3 d-grid">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-search me-1"></i> Search Users</button>
                </div>
            </form>
        </div>
    </div>

    <!-- User Download History View Section -->
    <?php if ($selectedUser): ?>
        <div class="card border-0 shadow-sm mb-4 rounded-3 border-start border-primary border-4">
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                <h5 class="fw-bold mb-0"><i class="bi bi-download text-primary me-2"></i> Download History for <?= htmlspecialchars($selectedUser['name']) ?> (<?= htmlspecialchars($selectedUser['email']) ?>)</h5>
                <a href="<?= BASE_URL ?>admin/users.php" class="btn btn-sm btn-outline-secondary">Close History</a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Downloaded Blog Title</th>
                            <th>Category</th>
                            <th>Downloaded Timestamp</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($selectedUserHistory)): ?>
                            <tr>
                                <td colspan="4" class="text-center py-4 text-muted">This user has not downloaded any blog files yet.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($selectedUserHistory as $dh): ?>
                                <tr>
                                    <td><strong class="text-dark"><?= htmlspecialchars($dh['title']) ?></strong></td>
                                    <td><span class="badge bg-secondary"><?= htmlspecialchars($dh['category_name'] ?? 'General') ?></span></td>
                                    <td><small class="text-muted"><?= formatDate($dh['downloaded_at'], 'M d, Y H:i:s') ?></small></td>
                                    <td><a href="<?= BASE_URL ?>user/blog_details.php?id=<?= $dh['blog_id'] ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye"></i> View Blog</a></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

    <!-- Registered Users List -->
    <div class="card border-0 shadow-sm rounded-3">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>User Name</th>
                        <th>Email Address</th>
                        <th>Account Status</th>
                        <th>Total Downloads</th>
                        <th>Joined Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($usersList)): ?>
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">No registered users found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($usersList as $u): ?>
                            <tr>
                                <td><strong class="text-dark"><?= htmlspecialchars($u['name']) ?></strong></td>
                                <td><span class="text-muted"><?= htmlspecialchars($u['email']) ?></span></td>
                                <td>
                                    <?php if ($u['status'] === 'active'): ?>
                                        <span class="badge bg-success">Active</span>
                                    <?php elseif ($u['status'] === 'inactive'): ?>
                                        <span class="badge bg-warning text-dark">Inactive</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Blocked</span>
                                    <?php endif; ?>
                                </td>
                                <td><span class="badge bg-info text-dark fs-6"><?= (int)$u['total_downloads'] ?></span></td>
                                <td><small class="text-muted"><?= formatDate($u['created_at']) ?></small></td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="<?= BASE_URL ?>admin/users.php?view_downloads=1&id=<?= $u['id'] ?>" class="btn btn-outline-primary" title="View Downloads History"><i class="bi bi-download"></i> Downloads</a>
                                        
                                        <?php if ($u['status'] === 'active'): ?>
                                            <a href="<?= BASE_URL ?>admin/users.php?status_change=blocked&id=<?= $u['id'] ?>" class="btn btn-outline-warning" title="Block User"><i class="bi bi-slash-circle"></i> Block</a>
                                        <?php else: ?>
                                            <a href="<?= BASE_URL ?>admin/users.php?status_change=active&id=<?= $u['id'] ?>" class="btn btn-outline-success" title="Activate User"><i class="bi bi-check-circle"></i> Activate</a>
                                        <?php endif; ?>

                                        <a href="<?= BASE_URL ?>admin/users.php?delete_user=1&id=<?= $u['id'] ?>" class="btn btn-outline-danger" onclick="return confirm('Delete this user account?');" title="Delete User"><i class="bi bi-trash"></i></a>
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
