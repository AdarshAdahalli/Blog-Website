<?php
require_once __DIR__ . '/../includes/admin_auth.php';

$pdo = getDBConnection();
$blogId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($blogId <= 0) {
    redirect(BASE_URL . 'admin/blogs.php');
}

// Fetch blog
$stmt = $pdo->prepare("
    SELECT b.*, c.name AS category_name, u.name AS employee_name, u.email AS employee_email, rev.name as reviewer_name
    FROM blogs b
    LEFT JOIN categories c ON b.category_id = c.id
    LEFT JOIN users u ON b.employee_id = u.id
    LEFT JOIN users rev ON b.reviewed_by = rev.id
    WHERE b.id = ?
");
$stmt->execute([$blogId]);
$blog = $stmt->fetch();

if (!$blog) {
    header("HTTP/1.1 404 Not Found");
    include ROOT_DIR . '404.php';
    exit();
}

$pageTitle = 'Review Manuscript: ' . htmlspecialchars($blog['title']) . ' — BlogSphere Admin';
include __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
    <!-- Top Action Bar -->
    <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom">
        <div>
            <a href="<?= BASE_URL ?>admin/pending_blogs.php" class="btn btn-outline-secondary btn-sm mb-2"><i class="bi bi-arrow-left me-1"></i> Back to Pending Queue</a>
            <h3 class="fw-bold mb-0"><?= htmlspecialchars($blog['title']) ?></h3>
        </div>
        <div class="d-flex gap-2">
            <?php if ($blog['status'] !== 'approved'): ?>
                <a href="<?= BASE_URL ?>admin/approve_blog.php?id=<?= $blog['id'] ?>" class="btn btn-success fw-bold px-3" onclick="return confirm('Approve and publish this blog manuscript?');">
                    <i class="bi bi-check-circle-fill me-1"></i> Approve &amp; Publish
                </a>
            <?php endif; ?>
            <?php if ($blog['status'] !== 'rejected'): ?>
                <a href="<?= BASE_URL ?>admin/reject_blog.php?id=<?= $blog['id'] ?>" class="btn btn-danger fw-bold px-3">
                    <i class="bi bi-x-circle-fill me-1"></i> Reject Manuscript
                </a>
            <?php endif; ?>
            <a href="<?= BASE_URL ?>admin/delete_blog.php?id=<?= $blog['id'] ?>" class="btn btn-outline-danger fw-bold" onclick="return confirm('Delete this blog permanently?');">
                <i class="bi bi-trash"></i>
            </a>
        </div>
    </div>

    <!-- Editorial Review Card -->
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
                <?php 
                    $imagePath = BASE_URL . 'assets/images/default-blog.jpg';
                    if (!empty($blog['featured_image']) && file_exists(BLOG_IMAGE_UPLOAD_DIR . $blog['featured_image'])) {
                        $imagePath = BASE_URL . 'uploads/blog_images/' . $blog['featured_image'];
                    }
                ?>
                <img src="<?= $imagePath ?>" style="max-height: 350px; object-fit: cover;" class="w-100" alt="<?= htmlspecialchars($blog['title']) ?>">

                <div class="card-body p-4 p-md-5">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <span class="badge bg-primary px-3 py-2 text-uppercase"><?= htmlspecialchars($blog['category_name'] ?? 'General') ?></span>
                        <span class="badge bg-secondary text-capitalize px-3 py-2 fs-6">Status: <?= htmlspecialchars($blog['status']) ?></span>
                    </div>

                    <h2 class="fw-bold mb-3"><?= htmlspecialchars($blog['title']) ?></h2>

                    <?php if (!empty($blog['short_description'])): ?>
                        <p class="lead text-secondary bg-light p-3 rounded border-start border-4 border-primary">
                            <?= htmlspecialchars($blog['short_description']) ?>
                        </p>
                    <?php endif; ?>

                    <?php if (($blog['content_type'] ?? 'text') === 'html'): ?>
                        <div class="alert alert-light border py-2 px-3 small text-muted mb-3 d-flex align-items-center justify-content-between">
                            <span><i class="bi bi-code-slash text-danger me-2"></i> <strong>HTML Code Article:</strong> Showing final rendered visual output.</span>
                            <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="collapse" data-bs-target="#rawHtmlCode"><i class="bi bi-file-earmark-code me-1"></i> View Raw HTML Source</button>
                        </div>
                        <div class="collapse mb-3" id="rawHtmlCode">
                            <pre class="bg-dark text-white p-3 rounded small" style="max-height: 250px; overflow-y: auto;"><code><?= htmlspecialchars($blog['content']) ?></code></pre>
                        </div>
                        <div class="blog-rendered-html-output my-4 border rounded-3 p-4 bg-white shadow-sm">
                            <?= $blog['content'] ?>
                        </div>
                    <?php else: ?>
                        <div class="blog-content-body my-4">
                            <?= nl2br(htmlspecialchars($blog['content'])) ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($blog['downloadable_file'])): ?>
                        <div class="card bg-light border p-3 mt-4">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <strong class="d-block text-dark"><i class="bi bi-file-earmark-text text-primary me-2"></i> Attached Document File</strong>
                                    <small class="text-muted">Filename: <code><?= htmlspecialchars($blog['downloadable_file']) ?></code></small>
                                </div>
                                <a href="<?= BASE_URL ?>user/download.php?id=<?= $blog['id'] ?>" class="btn btn-sm btn-outline-primary fw-bold">Download Attachment</a>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Right Metadata Sidebar -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-3 p-4 mb-4">
                <h5 class="fw-bold mb-3 border-bottom pb-2"><i class="bi bi-info-circle text-primary me-2"></i> Manuscript Metadata</h5>
                
                <div class="mb-3">
                    <label class="text-muted small d-block">Employee Writer</label>
                    <strong class="text-dark d-block"><?= htmlspecialchars($blog['employee_name']) ?></strong>
                    <small class="text-muted"><?= htmlspecialchars($blog['employee_email']) ?></small>
                </div>

                <div class="mb-3">
                    <label class="text-muted small d-block">Submission Date</label>
                    <span class="text-dark fw-bold"><?= formatDate($blog['submitted_at'], 'M d, Y H:i:s') ?></span>
                </div>

                <div class="mb-3">
                    <label class="text-muted small d-block">Current Status</label>
                    <?php if ($blog['status'] === 'approved'): ?>
                        <span class="badge bg-success">Approved / Published</span>
                    <?php elseif ($blog['status'] === 'pending'): ?>
                        <span class="badge bg-warning text-dark">Pending Review</span>
                    <?php elseif ($blog['status'] === 'rejected'): ?>
                        <span class="badge bg-danger">Rejected</span>
                    <?php else: ?>
                        <span class="badge bg-secondary">Draft</span>
                    <?php endif; ?>
                </div>

                <?php if ($blog['reviewed_at']): ?>
                    <div class="mb-3">
                        <label class="text-muted small d-block">Reviewed By</label>
                        <span class="text-dark fw-bold"><?= htmlspecialchars($blog['reviewer_name'] ?? 'Admin') ?></span>
                        <small class="text-muted d-block">Date: <?= formatDate($blog['reviewed_at'], 'M d, Y H:i:s') ?></small>
                    </div>
                <?php endif; ?>

                <?php if ($blog['status'] === 'rejected' && !empty($blog['rejection_reason'])): ?>
                    <div class="alert alert-danger mb-0 small">
                        <strong>Rejection Reason:</strong>
                        <div class="mt-1"><?= htmlspecialchars($blog['rejection_reason']) ?></div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- SEO & Search Engine Optimization Card -->
            <div class="card border-0 shadow-sm rounded-3 p-4 mb-4">
                <h5 class="fw-bold mb-3 border-bottom pb-2">
                    <i class="bi bi-search text-success me-2"></i> SEO &amp; Search Visibility
                </h5>
                <div class="mb-3">
                    <label class="text-muted small d-block">SEO Meta Title</label>
                    <span class="text-dark fw-semibold small d-block">
                        <?= htmlspecialchars(!empty($blog['meta_title']) ? $blog['meta_title'] : $blog['title']) ?>
                    </span>
                    <?php if (empty($blog['meta_title'])): ?>
                        <span class="badge bg-light text-muted border">Auto from Title</span>
                    <?php endif; ?>
                </div>

                <div class="mb-3">
                    <label class="text-muted small d-block">SEO Meta Description</label>
                    <p class="text-dark small mb-0 bg-light p-2 rounded border">
                        <?= htmlspecialchars(!empty($blog['meta_description']) ? $blog['meta_description'] : ($blog['short_description'] ?? 'Auto-generated from content.')) ?>
                    </p>
                </div>

                <?php if (!empty($blog['meta_keywords'])): ?>
                    <div class="mb-3">
                        <label class="text-muted small d-block">Focus Keywords</label>
                        <?php foreach (explode(',', $blog['meta_keywords']) as $kw): ?>
                            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 me-1 mb-1">
                                <?= htmlspecialchars(trim($kw)) ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <div>
                    <label class="text-muted small d-block">Search Engine Indexing</label>
                    <?php if ($blog['status'] === 'approved'): ?>
                        <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i> Indexed &amp; In XML Sitemap</span>
                    <?php else: ?>
                        <span class="badge bg-secondary"><i class="bi bi-hourglass me-1"></i> Will Index Once Approved</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
