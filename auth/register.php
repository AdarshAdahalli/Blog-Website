<?php
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/functions.php';

if (isLoggedIn()) {
    $role = $_SESSION['user_role'];
    redirect(BASE_URL . $role . '/dashboard.php');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitizeInput($_POST['name'] ?? '');
    $username = sanitizeInput($_POST['username'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $accountType = sanitizeInput($_POST['account_type'] ?? 'user');
    $csrfToken = $_POST['csrf_token'] ?? '';

    if (!verifyCSRFToken($csrfToken)) {
        $error = "Invalid CSRF token. Please try again.";
    } elseif (empty($name) || empty($username) || empty($email) || empty($password) || empty($confirmPassword)) {
        $error = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } elseif ($password !== $confirmPassword) {
        $error = "Passwords do not match.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long.";
    } else {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
        $stmt->execute([$email, $username]);
        if ($stmt->fetch()) {
            $error = "An account with this email address or username already exists.";
        } else {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            if ($accountType === 'employee') {
                $role = 'employee';
                $status = 'inactive'; // Pending Admin acceptance/approval
            } else {
                $role = 'user';
                $status = 'active';
            }

            $stmt = $pdo->prepare("INSERT INTO users (username, name, email, password, role, status, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
            if ($stmt->execute([$username, $name, $email, $hashedPassword, $role, $status])) {
                if ($role === 'employee') {
                    // Notify all Admins of new Employee request
                    $stmtAdmins = $pdo->query("SELECT id FROM users WHERE role = 'admin'");
                    while ($admin = $stmtAdmins->fetch()) {
                        createNotification(
                            $admin['id'],
                            "New Employee Approval Request ✍️",
                            "Applicant " . $name . " (" . $username . ") requested Employee Writer access and requires your approval.",
                            "warning"
                        );
                    }
                    setFlash('warning', 'Employee registration request submitted! Your account is pending Admin approval. You can only log in after an Administrator accepts your request.');
                    redirect(BASE_URL . 'auth/login.php?role=employee');
                } else {
                    setFlash('success', 'Registration successful! You can now log in using your username.');
                    redirect(BASE_URL . 'auth/login.php?role=user');
                }
            } else {
                $error = "Registration failed. Please try again later.";
            }
        }
    }
}

$pageTitle = 'Register Account — BlogSphere';
include __DIR__ . '/../includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card shadow-sm border-0 rounded-3">
                <div class="card-body p-4">
                    <div class="text-center mb-4">
                        <i class="bi bi-person-plus text-primary display-4"></i>
                        <h4 class="fw-bold mt-2">Create Account</h4>
                        <p class="text-muted small">Join BlogSphere as a Reader or apply as an Employee Writer</p>
                    </div>

                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i> <?= htmlspecialchars($error) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <form action="<?= BASE_URL ?>auth/register.php" method="POST" onsubmit="return validateRegisterForm();">
                        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">

                        <div class="mb-3">
                            <label for="account_type" class="form-label fw-bold">Registering As</label>
                            <select name="account_type" id="account_type" class="form-select border-primary" onchange="toggleAccountNote(this.value);">
                                <option value="user" selected>User (Reader / Downloader)</option>
                                <option value="employee">Employee (Blog Writer / Author - Requires Admin Approval)</option>
                            </select>
                            <div id="employee-note" class="alert alert-warning mt-2 mb-0 py-2 small d-none">
                                <i class="bi bi-info-circle-fill me-1"></i> <strong>Employee Accounts:</strong> Requires Admin acceptance before login is permitted.
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="name" class="form-label fw-bold">Full Name</label>
                            <input type="text" class="form-control" id="name" name="name" required placeholder="John Doe" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
                        </div>

                        <div class="mb-3">
                            <label for="username" class="form-label fw-bold">Username</label>
                            <input type="text" class="form-control" id="username" name="username" required placeholder="johndoe" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label fw-bold">Email Address</label>
                            <input type="email" class="form-control" id="email" name="email" required placeholder="name@example.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="password" class="form-label fw-bold">Password</label>
                                <input type="password" class="form-control" id="password" name="password" required placeholder="••••••••">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="confirm_password" class="form-label fw-bold">Confirm Password</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required placeholder="••••••••">
                            </div>
                        </div>

                        <div class="d-grid gap-2 mt-4">
                            <button type="submit" class="btn btn-primary btn-lg fs-6 fw-bold">Submit Registration</button>
                        </div>
                    </form>

                    <div class="mt-4 pt-3 border-top text-center text-muted small">
                        Already have an account? <a href="<?= BASE_URL ?>auth/login.php" class="text-primary fw-semibold">Sign in here</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function toggleAccountNote(type) {
    const note = document.getElementById('employee-note');
    if (type === 'employee') {
        note.classList.remove('d-none');
    } else {
        note.classList.add('d-none');
    }
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
