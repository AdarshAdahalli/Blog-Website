<?php
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/functions.php';

if (isLoggedIn()) {
    $role = $_SESSION['user_role'];
    redirect(BASE_URL . $role . '/dashboard.php');
}

$activePortal = sanitizeInput($_GET['role'] ?? 'user');
if (!in_array($activePortal, ['user', 'employee', 'admin'])) {
    $activePortal = 'user';
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usernameInput = sanitizeInput($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $loginPortal = sanitizeInput($_POST['login_portal'] ?? 'user');
    $csrfToken = $_POST['csrf_token'] ?? '';

    $activePortal = in_array($loginPortal, ['user', 'employee', 'admin']) ? $loginPortal : 'user';

    if (!verifyCSRFToken($csrfToken)) {
        $error = "Invalid CSRF token. Please refresh and try again.";
    } elseif (empty($usernameInput) || empty($password)) {
        $error = "Please fill in all fields.";
    } else {
        $pdo = getDBConnection();
        // Allow logging in via username OR email
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$usernameInput, $usernameInput]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // Check if account role matches the selected login portal panel
            if ($user['role'] !== $activePortal) {
                $error = "Access Denied: You cannot sign in to the " . ucfirst($activePortal) . " Portal using a " . ucfirst($user['role']) . " account.";
            } elseif ($user['status'] === 'blocked') {
                $error = "Your account has been blocked by an Administrator. Please contact support.";
            } elseif ($user['status'] === 'inactive') {
                // Pending Admin Approval rule for employees / inactive accounts
                if ($user['role'] === 'employee') {
                    $error = "Employee Approval Pending: Your Employee Writer account has not been accepted by an Administrator yet. You can only log in after an Admin approves your request.";
                } else {
                    $error = "Your account is currently inactive. Please contact support.";
                }
            } else {
                // Successful Login
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role'] = $user['role'];

                setFlash('success', 'Welcome back, ' . htmlspecialchars($user['name']) . '!');

                // Redirect based on role
                if ($user['role'] === 'admin') {
                    redirect(BASE_URL . 'admin/dashboard.php');
                } elseif ($user['role'] === 'employee') {
                    redirect(BASE_URL . 'employee/dashboard.php');
                } else {
                    redirect(BASE_URL . 'user/dashboard.php');
                }
            }
        } else {
            $error = "Invalid username/email or password.";
        }
    }
}

$portalTitles = [
    'user' => ['title' => 'User Sign In', 'subtitle' => 'Access your reader account & downloads', 'icon' => 'bi-person-circle', 'color' => 'primary', 'btn' => 'btn-primary', 'placeholder' => 'Enter username (e.g. user)'],
    'employee' => ['title' => 'Employee Sign In', 'subtitle' => 'Access writer portal & blog manuscripts', 'icon' => 'bi-pen-fill', 'color' => 'info', 'btn' => 'btn-info text-dark', 'placeholder' => 'Enter username (e.g. employee)'],
    'admin' => ['title' => 'Admin Sign In', 'subtitle' => 'Access system control center & approvals', 'icon' => 'bi-shield-lock-fill', 'color' => 'danger', 'btn' => 'btn-danger', 'placeholder' => 'Enter username (e.g. admin)']
];

$currInfo = $portalTitles[$activePortal];
$pageTitle = $currInfo['title'] . ' — BlogSphere';
include __DIR__ . '/../includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-7 col-lg-5">
            <!-- Role Selection Panel Tabs -->
            <div class="nav nav-pills nav-fill mb-3 bg-white p-2 rounded-3 shadow-sm border" role="tablist">
                <a class="nav-link fw-bold <?= $activePortal === 'user' ? 'active bg-primary' : 'text-dark' ?>" href="<?= BASE_URL ?>auth/login.php?role=user">
                    <i class="bi bi-person me-1"></i> User Portal
                </a>
                <a class="nav-link fw-bold <?= $activePortal === 'employee' ? 'active bg-info text-dark' : 'text-dark' ?>" href="<?= BASE_URL ?>auth/login.php?role=employee">
                    <i class="bi bi-pen me-1"></i> Employee Portal
                </a>
                <a class="nav-link fw-bold <?= $activePortal === 'admin' ? 'active bg-danger' : 'text-dark' ?>" href="<?= BASE_URL ?>auth/login.php?role=admin">
                    <i class="bi bi-shield-lock me-1"></i> Admin Portal
                </a>
            </div>

            <!-- Login Form Card -->
            <div class="card shadow-sm border-0 rounded-3">
                <div class="card-body p-4">
                    <div class="text-center mb-4">
                        <i class="bi <?= $currInfo['icon'] ?> text-<?= $currInfo['color'] ?> display-4"></i>
                        <h4 class="fw-bold mt-2 mb-1"><?= $currInfo['title'] ?></h4>
                        <p class="text-muted small mb-0"><?= $currInfo['subtitle'] ?></p>
                    </div>

                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i> <?= htmlspecialchars($error) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <form action="<?= BASE_URL ?>auth/login.php?role=<?= $activePortal ?>" method="POST">
                        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                        <input type="hidden" name="login_portal" value="<?= $activePortal ?>">

                        <div class="mb-3">
                            <label for="username" class="form-label fw-bold">Username or Email</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-person-fill"></i></span>
                                <input type="text" class="form-control" id="username" name="username" required placeholder="<?= $currInfo['placeholder'] ?>" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label fw-bold">Password</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-key-fill"></i></span>
                                <input type="password" class="form-control" id="password" name="password" required placeholder="••••••••">
                            </div>
                        </div>

                        <div class="d-grid gap-2 mt-4">
                            <button type="submit" class="btn <?= $currInfo['btn'] ?> btn-lg fs-6 fw-bold">
                                Sign In to <?= ucfirst($activePortal) ?> Panel
                            </button>
                        </div>
                    </form>

                    <div class="mt-4 pt-3 border-top text-center text-muted small">
                        Don't have an account? <a href="<?= BASE_URL ?>auth/register.php" class="text-primary fw-semibold">Register / Apply here</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
