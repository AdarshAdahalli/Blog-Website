<?php
$role = $_SESSION['user_role'] ?? 'guest';
$user = isLoggedIn() ? getCurrentUser() : null;
$unreadMessages = $user ? getUnreadMessageCount($user['id']) : 0;
$unreadNotifications = $user ? getUnreadNotificationCount($user['id']) : 0;
$pendingDeleteRequestsCount = 0;
if ($role === 'admin') {
    try {
        $pdoNav = getDBConnection();
        $pendingDeleteRequestsCount = (int)$pdoNav->query("SELECT COUNT(*) FROM blog_delete_requests WHERE status = 'pending'")->fetchColumn();
    } catch (Exception $e) {}
}
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top shadow-sm">
    <div class="container-fluid px-4">
        <a class="navbar-brand d-flex align-items-center fw-bold fs-4 text-gradient" href="<?= BASE_URL ?>index.php">
            <i class="bi bi-journal-richtext text-primary me-2"></i>
            <span>BlogSphere</span>
        </a>
        
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarMain">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link" href="<?= BASE_URL ?>index.php"><i class="bi bi-house-door me-1"></i> Home</a>
                </li>
                
                <?php if ($role === 'guest'): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= BASE_URL ?>user/blogs.php"><i class="bi bi-grid me-1"></i> Blogs</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= BASE_URL ?>user/blogs.php#categories"><i class="bi bi-tags me-1"></i> Categories</a>
                    </li>
                <?php elseif ($role === 'user'): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= BASE_URL ?>user/dashboard.php"><i class="bi bi-speedometer2 me-1"></i> Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= BASE_URL ?>user/blogs.php"><i class="bi bi-search me-1"></i> Browse Blogs</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= BASE_URL ?>user/messages.php">
                            <i class="bi bi-chat-left-dots me-1"></i> Messages
                            <?php if ($unreadMessages > 0): ?>
                                <span class="badge bg-danger rounded-pill"><?= $unreadMessages ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= BASE_URL ?>user/notifications.php">
                            <i class="bi bi-bell me-1"></i> Notifications
                            <?php if ($unreadNotifications > 0): ?>
                                <span class="badge bg-warning text-dark rounded-pill"><?= $unreadNotifications ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                <?php elseif ($role === 'employee'): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= BASE_URL ?>employee/dashboard.php"><i class="bi bi-speedometer2 me-1"></i> Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= BASE_URL ?>employee/add_blog.php"><i class="bi bi-plus-circle me-1"></i> Add Blog</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= BASE_URL ?>employee/my_blogs.php"><i class="bi bi-journals me-1"></i> My Blogs</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= BASE_URL ?>employee/queries.php"><i class="bi bi-question-circle me-1"></i> Reader Queries</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= BASE_URL ?>employee/messages.php">
                            <i class="bi bi-chat-left-text me-1"></i> Messages
                            <?php if ($unreadMessages > 0): ?>
                                <span class="badge bg-danger rounded-pill"><?= $unreadMessages ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                <?php elseif ($role === 'admin'): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= BASE_URL ?>admin/dashboard.php"><i class="bi bi-speedometer2 me-1"></i> Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= BASE_URL ?>admin/pending_blogs.php">
                            <i class="bi bi-clock-history me-1"></i> Pending Approvals
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= BASE_URL ?>admin/blogs.php"><i class="bi bi-collection me-1"></i> All Blogs</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= BASE_URL ?>admin/employees.php"><i class="bi bi-people me-1"></i> Employees</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= BASE_URL ?>admin/users.php"><i class="bi bi-person-lines-fill me-1"></i> Users</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= BASE_URL ?>admin/delete_requests.php">
                            <i class="bi bi-trash me-1"></i> Delete Requests
                            <?php if ($pendingDeleteRequestsCount > 0): ?>
                                <span class="badge bg-danger rounded-pill"><?= $pendingDeleteRequestsCount ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= BASE_URL ?>admin/queries.php"><i class="bi bi-question-circle me-1"></i> User Queries</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= BASE_URL ?>admin/messages.php">
                            <i class="bi bi-envelope me-1"></i> Messages
                            <?php if ($unreadMessages > 0): ?>
                                <span class="badge bg-danger rounded-pill"><?= $unreadMessages ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= BASE_URL ?>admin/statistics.php"><i class="bi bi-graph-up-arrow me-1"></i> Statistics</a>
                    </li>
                <?php endif; ?>
            </ul>

            <div class="d-flex align-items-center gap-2">
                <?php if ($role === 'guest'): ?>
                    <a href="<?= BASE_URL ?>auth/login.php" class="btn btn-outline-light btn-sm px-3"><i class="bi bi-box-arrow-in-right me-1"></i> Login</a>
                    <a href="<?= BASE_URL ?>auth/register.php" class="btn btn-primary btn-sm px-3"><i class="bi bi-person-plus me-1"></i> Register</a>
                <?php else: ?>
                    <div class="dropdown position-relative" id="userAccountDropdown">
                        <button class="btn btn-light btn-sm dropdown-toggle d-flex align-items-center gap-2 px-3 py-2 fw-semibold shadow-sm border" type="button" id="dropdownUserMenu" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-person-circle fs-6 text-primary"></i>
                            <span class="text-dark"><?= htmlspecialchars($_SESSION['user_name'] ?? 'Account') ?></span>
                            <span class="badge bg-info text-dark text-capitalize ms-1"><?= htmlspecialchars($role) ?></span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow-lg py-2 mt-2 border-0 rounded-3" aria-labelledby="dropdownUserMenu">
                            <li class="px-3 py-2 border-bottom bg-light">
                                <strong class="d-block text-dark small"><?= htmlspecialchars($_SESSION['user_name'] ?? '') ?></strong>
                                <small class="text-muted d-block"><?= htmlspecialchars($_SESSION['user_email'] ?? '') ?></small>
                                <span class="badge bg-secondary text-uppercase mt-1" style="font-size: 0.65rem;"><?= htmlspecialchars($role) ?> Account</span>
                            </li>
                            <li>
                                <a class="dropdown-item py-2" href="<?= BASE_URL . $role ?>/dashboard.php">
                                    <i class="bi bi-speedometer2 text-primary me-2"></i> Dashboard
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item py-2" href="<?= BASE_URL . $role ?>/profile.php">
                                    <i class="bi bi-person-gear text-info me-2"></i> Edit Profile
                                </a>
                            </li>
                            <li><hr class="dropdown-divider my-1"></li>
                            <li>
                                <a class="dropdown-item py-2 text-danger fw-bold" href="<?= BASE_URL ?>auth/logout.php">
                                    <i class="bi bi-box-arrow-right text-danger me-2"></i> Logout
                                </a>
                            </li>
                        </ul>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>
