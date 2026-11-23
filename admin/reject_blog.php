<?php
require_once __DIR__ . '/../includes/admin_auth.php';

$pdo = getDBConnection();
$blogId = isset($_GET['id']) ? (int)$_GET['id'] : (int)($_POST['blog_id'] ?? 0);
$adminId = $_SESSION['user_id'];
$error = '';

if ($blogId <= 0) {
    redirect(BASE_URL . 'admin/pending_blogs.php');
}

// Fetch Blog
$stmt = $pdo->prepare("SELECT b.*, u.name as employee_name FROM blogs b LEFT JOIN users u ON b.employee_id = u.id WHERE b.id = ?");
$stmt->execute([$blogId]);
$blog = $stmt->fetch();

if (!$blog) {
    setFlash('danger', 'Blog manuscript not found.');
    redirect(BASE_URL . 'admin/pending_blogs.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reason = sanitizeInput($_POST['rejection_reason'] ?? '');
    $csrfToken = $_POST['csrf_token'] ?? '';

    if (!verifyCSRFToken($csrfToken)) {
        $error = "Invalid CSRF token.";
    } elseif (empty($reason)) {
        $error = "Please provide a clear rejection reason for the author.";
    } else {
        $result = rejectBlog($blogId, $adminId, $reason);

        if ($result) {
            setFlash('warning', 'Blog manuscript rejected. Rejection reason notified to employee.');
            redirect(BASE_URL . 'admin/pending_blogs.php');
        } else {
            $error = "Failed to reject blog.";
        }
    }
}

$pageTitle = 'Reject Manuscript — BlogSphere Admin';
include __DIR__ . '/../includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-7 col-lg-6">
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-header bg-danger text-white py-3">
                    <h5 class="fw-bold mb-0"><i class="bi bi-x-circle me-2"></i> Reject Manuscript: <?= htmlspecialchars($blog['title']) ?></h5>
                </div>
                <div class="card-body p-4">
                    <p class="text-muted small">
                        Author: <strong><?= htmlspecialchars($blog['employee_name']) ?></strong><br>
                        Please specify constructive feedback and exact reasons for rejecting this submission. The author will receive this message and can edit their draft to resubmit.
                    </p>

                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show">
                            <?= htmlspecialchars($error) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <form action="<?= BASE_URL ?>admin/reject_blog.php" method="POST">
                        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                        <input type="hidden" name="blog_id" value="<?= $blogId ?>">

                        <div class="mb-3">
                            <label for="rejection_reason" class="form-label fw-bold">Rejection Reason / Feedback <span class="text-danger">*</span></label>
                            <textarea name="rejection_reason" id="rejection_reason" rows="5" class="form-control" required placeholder="e.g. Please improve the introduction section and add reliable technical reference links."><?= htmlspecialchars($_POST['rejection_reason'] ?? '') ?></textarea>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mt-4">
                            <a href="<?= BASE_URL ?>admin/view_blog.php?id=<?= $blogId ?>" class="btn btn-outline-secondary">Cancel</a>
                            <button type="submit" class="btn btn-danger px-4 fw-bold"><i class="bi bi-send-x me-1"></i> Submit Rejection</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
