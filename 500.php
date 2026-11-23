<?php
require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/includes/functions.php';
$pageTitle = '500 - Server Error';
include __DIR__ . '/includes/header.php';
?>
<div class="container text-center py-5 my-5">
    <i class="bi bi-bug text-danger display-1"></i>
    <h1 class="display-4 fw-bold mt-3">500 - Internal Server Error</h1>
    <p class="lead text-muted">Something went wrong on our server. Please try again later.</p>
    <a href="<?= BASE_URL ?>index.php" class="btn btn-primary btn-lg mt-3"><i class="bi bi-house me-2"></i> Return to Homepage</a>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
