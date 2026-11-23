<?php
require_once __DIR__ . '/../includes/user_auth.php';

$pdo = getDBConnection();
$userId = $_SESSION['user_id'];

// Stats
$stmtBlogs = $pdo->query("SELECT COUNT(*) FROM blogs WHERE status = 'approved'");
$totalApprovedBlogs = (int)$stmtBlogs->fetchColumn();

$stmtCats = $pdo->query("SELECT COUNT(*) FROM categories");
$totalCategories = (int)$stmtCats->fetchColumn();

$stmtDownloads = $pdo->prepare("SELECT COUNT(*) FROM blog_downloads WHERE user_id = ?");
$stmtDownloads->execute([$userId]);
$myDownloads = (int)$stmtDownloads->fetchColumn();

$unreadMessages = getUnreadMessageCount($userId);

// Fetch recent user downloads
$stmtRecentDownloads = $pdo->prepare("
    SELECT bd.downloaded_at, b.id as blog_id, b.title, c.name as category_name
    FROM blog_downloads bd
    JOIN blogs b ON bd.blog_id = b.id
    LEFT JOIN categories c ON b.category_id = c.id
    WHERE bd.user_id = ?
    ORDER BY bd.downloaded_at DESC
    LIMIT 5
");
$stmtRecentDownloads->execute([$userId]);
$recentDownloads = $stmtRecentDownloads->fetchAll();

// Fetch latest approved blogs
$stmtLatest = $pdo->query("
    SELECT b.*, c.name as category_name, u.name as author_name 
    FROM blogs b
    LEFT JOIN categories c ON b.category_id = c.id
    LEFT JOIN users u ON b.employee_id = u.id
    WHERE b.status = 'approved'
    ORDER BY b.created_at DESC
    LIMIT 4
");
$latestBlogs = $stmtLatest->fetchAll();

$pageTitle = 'User Dashboard — BlogSphere';
include __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row">
        <!-- User Sidebar -->
        <div class="col-lg-3 col-md-4 mb-4">
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-body text-center p-4 border-bottom">
                    <div class="avatar bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 70px; height: 70px; font-size: 2rem;">
                        <i class="bi bi-person-fill"></i>
                    </div>
                    <h5 class="fw-bold mb-1"><?= htmlspecialchars($_SESSION['user_name']) ?></h5>
                    <span class="badge bg-info text-dark">Registered User</span>
                </div>
                <div class="list-group list-group-flush p-2">
                    <a href="<?= BASE_URL ?>user/dashboard.php" class="list-group-item list-group-item-action active rounded mb-1">
                        <i class="bi bi-speedometer2 me-2"></i> Dashboard
                    </a>
                    <a href="<?= BASE_URL ?>user/blogs.php" class="list-group-item list-group-item-action rounded mb-1">
                        <i class="bi bi-journal-text me-2"></i> Browse Blogs
                    </a>
                    <a href="<?= BASE_URL ?>user/blogs.php#categories" class="list-group-item list-group-item-action rounded mb-1">
                        <i class="bi bi-tags me-2"></i> Categories
                    </a>
                    <a href="<?= BASE_URL ?>user/messages.php" class="list-group-item list-group-item-action rounded mb-1 d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-chat-dots me-2"></i> Messages</span>
                        <?php if ($unreadMessages > 0): ?>
                            <span class="badge bg-danger rounded-pill"><?= $unreadMessages ?></span>
                        <?php endif; ?>
                    </a>
                    <a href="<?= BASE_URL ?>user/notifications.php" class="list-group-item list-group-item-action rounded mb-1">
                        <i class="bi bi-bell me-2"></i> Notifications
                    </a>
                    <a href="<?= BASE_URL ?>user/profile.php" class="list-group-item list-group-item-action rounded mb-1">
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
                    <h3 class="fw-bold mb-0">Welcome back, <?= htmlspecialchars($_SESSION['user_name']) ?>!</h3>
                    <p class="text-muted small">Access published articles, technical resources, and your message center</p>
                </div>
                <a href="<?= BASE_URL ?>user/blogs.php" class="btn btn-primary"><i class="bi bi-search me-1"></i> Explore Articles</a>
            </div>

            <!-- Dashboard Cards -->
            <div class="row g-3 mb-4">
                <div class="col-sm-6 col-xl-3">
                    <a href="<?= BASE_URL ?>user/blogs.php" class="stat-card-link h-100">
                        <div class="card border-0 shadow-sm bg-primary text-white p-3 rounded-3 h-100">
                            <div class="d-flex align-items-center">
                                <div class="fs-1 me-3 opacity-75"><i class="bi bi-journal-check"></i></div>
                                <div>
                                    <h3 class="fw-bold mb-0 text-white"><?= $totalApprovedBlogs ?></h3>
                                    <span class="small text-white">Published Blogs</span>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>

                <div class="col-sm-6 col-xl-3">
                    <a href="<?= BASE_URL ?>user/blogs.php#categories" class="stat-card-link h-100">
                        <div class="card border-0 shadow-sm bg-success text-white p-3 rounded-3 h-100">
                            <div class="d-flex align-items-center">
                                <div class="fs-1 me-3 opacity-75"><i class="bi bi-tags"></i></div>
                                <div>
                                    <h3 class="fw-bold mb-0 text-white"><?= $totalCategories ?></h3>
                                    <span class="small text-white">Available Categories</span>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>

                <div class="col-sm-6 col-xl-3">
                    <a href="#recent-downloads" class="stat-card-link h-100">
                        <div class="card border-0 shadow-sm bg-warning text-dark p-3 rounded-3 h-100">
                            <div class="d-flex align-items-center">
                                <div class="fs-1 me-3 opacity-75"><i class="bi bi-file-earmark-arrow-down"></i></div>
                                <div>
                                    <h3 class="fw-bold mb-0 text-dark"><?= $myDownloads ?></h3>
                                    <span class="small text-dark">My Downloads</span>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>

                <div class="col-sm-6 col-xl-3">
                    <a href="<?= BASE_URL ?>user/messages.php" class="stat-card-link h-100">
                        <div class="card border-0 shadow-sm bg-danger text-white p-3 rounded-3 h-100">
                            <div class="d-flex align-items-center">
                                <div class="fs-1 me-3 opacity-75"><i class="bi bi-envelope"></i></div>
                                <div>
                                    <h3 class="fw-bold mb-0 text-white"><?= $unreadMessages ?></h3>
                                    <span class="small text-white">Unread Messages</span>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
            </div>

            <!-- Recent Activity & Downloads -->
            <div class="row g-4">
                <div class="col-lg-7">
                    <div class="card border-0 shadow-sm rounded-3">
                        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                            <h5 class="fw-bold mb-0"><i class="bi bi-stars text-warning me-2"></i> Recently Published Blogs</h5>
                            <a href="<?= BASE_URL ?>user/blogs.php" class="btn btn-sm btn-link text-decoration-none">View All</a>
                        </div>
                        <div class="card-body p-0">
                            <div class="list-group list-group-flush">
                                <?php foreach ($latestBlogs as $b): ?>
                                    <div class="list-group-item p-3 d-flex justify-content-between align-items-center">
                                        <div>
                                            <span class="badge bg-secondary mb-1"><?= htmlspecialchars($b['category_name'] ?? 'General') ?></span>
                                            <h6 class="fw-bold mb-1">
                                                <a href="<?= BASE_URL ?>user/blog_details.php?id=<?= $b['id'] ?>" class="text-decoration-none text-dark">
                                                    <?= htmlspecialchars($b['title']) ?>
                                                </a>
                                            </h6>
                                            <small class="text-muted"><i class="bi bi-person me-1"></i> <?= htmlspecialchars($b['author_name']) ?> &bull; <?= formatDate($b['created_at']) ?></small>
                                        </div>
                                        <a href="<?= BASE_URL ?>user/blog_details.php?id=<?= $b['id'] ?>" class="btn btn-sm btn-outline-primary">Read</a>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-5" id="recent-downloads">
                    <div class="card border-0 shadow-sm rounded-3">
                        <div class="card-header bg-white py-3 border-bottom">
                            <h5 class="fw-bold mb-0"><i class="bi bi-download text-success me-2"></i> Recent File Downloads</h5>
                        </div>
                        <div class="card-body p-0">
                            <?php if (empty($recentDownloads)): ?>
                                <div class="text-center py-4 text-muted small">
                                    <i class="bi bi-inbox fs-2"></i>
                                    <p class="mt-2 mb-0">No files downloaded yet.</p>
                                </div>
                            <?php else: ?>
                                <div class="list-group list-group-flush">
                                    <?php foreach ($recentDownloads as $d): ?>
                                        <div class="list-group-item p-3">
                                            <h6 class="fw-bold mb-1 text-truncate">
                                                <a href="<?= BASE_URL ?>user/blog_details.php?id=<?= $d['blog_id'] ?>" class="text-decoration-none text-dark">
                                                    <?= htmlspecialchars($d['title']) ?>
                                                </a>
                                            </h6>
                                            <small class="text-muted d-block"><i class="bi bi-clock me-1"></i> Downloaded: <?= formatDate($d['downloaded_at'], 'M d, Y H:i') ?></small>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
