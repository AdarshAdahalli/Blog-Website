<?php
require_once __DIR__ . '/../includes/user_auth.php';

$pdo = getDBConnection();
$userId = $_SESSION['user_id'];
$error = '';
$success = '';

$user = getCurrentUser();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitizeInput($_POST['name'] ?? '');
    $username = sanitizeInput($_POST['username'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $csrfToken = $_POST['csrf_token'] ?? '';

    if (!verifyCSRFToken($csrfToken)) {
        $error = "Invalid CSRF token.";
    } elseif (empty($name) || empty($username) || empty($email)) {
        $error = "Name, Username, and Email are required.";
    } else {
        // Uniqueness check for username & email
        $stmtCheck = $pdo->prepare("SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?");
        $stmtCheck->execute([$username, $email, $userId]);
        if ($stmtCheck->fetch()) {
            $error = "The username or email address is already taken by another account.";
        } else {
            $profileImage = $user['profile_image'];

            // Handle Profile Image Upload
            if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
                $imgName = $_FILES['profile_image']['name'];
                $imgTmp = $_FILES['profile_image']['tmp_name'];
                $imgSize = $_FILES['profile_image']['size'];
                $imgExt = strtolower(pathinfo($imgName, PATHINFO_EXTENSION));

                if ($imgSize > MAX_FILE_SIZE) {
                    $error = "Profile image size exceeds 5MB limit.";
                } elseif (!in_array($imgExt, ALLOWED_IMAGE_EXT)) {
                    $error = "Invalid image extension. Allowed: jpg, jpeg, png, webp.";
                } else {
                    $newImgName = 'avatar_' . $userId . '_' . time() . '.' . $imgExt;
                    move_uploaded_file($imgTmp, BLOG_IMAGE_UPLOAD_DIR . $newImgName);
                    $profileImage = $newImgName;
                }
            }

            if (empty($error)) {
                // If changing password, verify current password
                if (!empty($newPassword)) {
                    // Fetch current stored password
                    $stmtPass = $pdo->prepare("SELECT password FROM users WHERE id = ?");
                    $stmtPass->execute([$userId]);
                    $storedPass = $stmtPass->fetchColumn();

                    if (empty($currentPassword) || !password_verify($currentPassword, $storedPass)) {
                        $error = "Incorrect Current Password. Please enter your existing password to authorize password change.";
                    } elseif ($newPassword !== $confirmPassword) {
                        $error = "New Password and Confirm Password do not match.";
                    } elseif (strlen($newPassword) < 6) {
                        $error = "New Password must be at least 6 characters long.";
                    } else {
                        $hashed = password_hash($newPassword, PASSWORD_DEFAULT);
                        $stmtUpd = $pdo->prepare("UPDATE users SET name = ?, username = ?, email = ?, password = ?, profile_image = ? WHERE id = ?");
                        $stmtUpd->execute([$name, $username, $email, $hashed, $profileImage, $userId]);
                        $success = "Profile and password updated successfully!";
                    }
                } else {
                    $stmtUpd = $pdo->prepare("UPDATE users SET name = ?, username = ?, email = ?, profile_image = ? WHERE id = ?");
                    $stmtUpd->execute([$name, $username, $email, $profileImage, $userId]);
                    $success = "Profile details updated successfully!";
                }

                if (empty($error)) {
                    $_SESSION['user_name'] = $name;
                    $_SESSION['user_email'] = $email;
                    setFlash('success', $success);
                    redirect(BASE_URL . 'user/profile.php');
                }
            }
        }
    }
}

$user = getCurrentUser();
$pageTitle = 'Edit Profile — User Panel';
include __DIR__ . '/../includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-7">
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0"><i class="bi bi-person-gear text-primary me-2"></i> Edit User Profile</h5>
                    <span class="badge bg-primary px-3 py-2 text-uppercase">Role: User</span>
                </div>
                <div class="card-body p-4">
                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i> <?= htmlspecialchars($error) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <form action="<?= BASE_URL ?>user/profile.php" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">

                        <!-- Profile Avatar Section -->
                        <div class="text-center mb-4 pb-3 border-bottom">
                            <?php if (!empty($user['profile_image']) && file_exists(BLOG_IMAGE_UPLOAD_DIR . $user['profile_image'])): ?>
                                <img src="<?= BASE_URL ?>uploads/blog_images/<?= htmlspecialchars($user['profile_image']) ?>" class="rounded-circle img-thumbnail mb-2" style="width: 100px; height: 100px; object-fit: cover;">
                            <?php else: ?>
                                <div class="avatar bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-2" style="width: 100px; height: 100px; font-size: 2.5rem;">
                                    <i class="bi bi-person-fill"></i>
                                </div>
                            <?php endif; ?>
                            <div class="mt-2">
                                <label for="profile_image" class="form-label fw-bold small text-muted">Change Avatar Image</label>
                                <input type="file" name="profile_image" id="profile_image" class="form-control form-control-sm w-75 mx-auto" accept=".jpg,.jpeg,.png,.webp">
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label for="name" class="form-label fw-bold">Full Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" id="name" class="form-control" required value="<?= htmlspecialchars($_POST['name'] ?? $user['name']) ?>">
                            </div>
                            <div class="col-md-6">
                                <label for="username" class="form-label fw-bold">Username <span class="text-danger">*</span></label>
                                <input type="text" name="username" id="username" class="form-control" required value="<?= htmlspecialchars($_POST['username'] ?? ($user['username'] ?? 'user')) ?>">
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="email" class="form-label fw-bold">Email Address <span class="text-danger">*</span></label>
                            <input type="email" name="email" id="email" class="form-control" required value="<?= htmlspecialchars($_POST['email'] ?? $user['email']) ?>">
                        </div>

                        <!-- Change Password Accordion -->
                        <div class="border rounded-3 p-3 bg-light mb-4">
                            <h6 class="fw-bold mb-3"><i class="bi bi-shield-lock text-primary me-2"></i> Security &amp; Password Update (Optional)</h6>
                            
                            <div class="mb-3">
                                <label for="current_password" class="form-label small fw-bold">Current Password</label>
                                <input type="password" name="current_password" id="current_password" class="form-control" placeholder="Required only if changing password">
                            </div>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="new_password" class="form-label small fw-bold">New Password</label>
                                    <input type="password" name="new_password" id="new_password" class="form-control" placeholder="••••••••">
                                </div>
                                <div class="col-md-6">
                                    <label for="confirm_password" class="form-label small fw-bold">Confirm New Password</label>
                                    <input type="password" name="confirm_password" id="confirm_password" class="form-control" placeholder="••••••••">
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center">
                            <a href="<?= BASE_URL ?>user/dashboard.php" class="btn btn-outline-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary fw-bold px-4"><i class="bi bi-save me-1"></i> Save Profile Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
