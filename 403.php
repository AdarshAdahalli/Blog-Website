<?php
require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/includes/functions.php';
$pageTitle = '403 - Access Forbidden';
include __DIR__ . '/includes/header.php';
?>
<div class="container text-center py-5 my-5">
    <i class="bi bi-shield-x text-danger display-1"></i>
    <h1 class="display-4 fw-bold mt-3">403 - Access Forbidden</h1>
    <p class="lead text-muted">You do not have administrative or panel permissions to access this requested area.</p>
    <a href="<?= BASE_URL ?>index.php" class="btn btn-primary btn-lg mt-3"><i class="bi bi-house me-2"></i> Return to Safety</a>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
