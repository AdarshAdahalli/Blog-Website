<?php
require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/includes/functions.php';
$pageTitle = '404 - Page Not Found';
include __DIR__ . '/includes/header.php';
?>
<div class="container text-center py-5 my-5">
    <i class="bi bi-exclamation-triangle text-warning display-1"></i>
    <h1 class="display-4 fw-bold mt-3">404 - Page Not Found</h1>
    <p class="lead text-muted">The page or blog resource you requested could not be found or has been removed.</p>
    <a href="<?= BASE_URL ?>index.php" class="btn btn-primary btn-lg mt-3"><i class="bi bi-house me-2"></i> Return to Homepage</a>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
