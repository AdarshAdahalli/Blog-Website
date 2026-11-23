<?php
require_once __DIR__ . '/../includes/employee_auth.php';

$pdo = getDBConnection();
$employeeId = $_SESSION['user_id'];

$statusFilter = sanitizeInput($_GET['status'] ?? '');
$search = sanitizeInput($_GET['search'] ?? '');

$whereClauses = ["b.employee_id = ?"];
$params = [$employeeId];

if (!empty($statusFilter) && in_array($statusFilter, ['draft', 'pending', 'approved', 'rejected'])) {
    $whereClauses[] = "b.status = ?";
    $params[] = $statusFilter;
}

if (!empty($search)) {
    $whereClauses[] = "(b.title LIKE ? OR b.short_description LIKE ?)";
    $searchTerm = "%{$search}%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

$whereSql = implode(' AND ', $whereClauses);

$stmt = $pdo->prepare("
    SELECT b.*, c.name AS category_name, rev.name as reviewer_name
    FROM blogs b
    LEFT JOIN categories c ON b.category_id = c.id
    LEFT JOIN users rev ON b.reviewed_by = rev.id
    WHERE {$whereSql}
    ORDER BY b.updated_at DESC
");
$stmt->execute($params);
$blogs = $stmt->fetchAll();

// Fetch active delete requests for this employee
$stmtDelReqs = $pdo->prepare("SELECT blog_id, status FROM blog_delete_requests WHERE employee_id = ?");
$stmtDelReqs->execute([$employeeId]);
$delRequests = [];
while ($dr = $stmtDelReqs->fetch()) {
    $delRequests[$dr['blog_id']] = $dr['status'];
}

$pageTitle = 'My Manuscripts — BlogSphere';
include __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold mb-0"><i class="bi bi-journals text-primary me-2"></i> My Manuscripts</h3>
            <p class="text-muted small mb-0">Manage your written articles, update pending reviews, or request deletion of published blogs</p>
        </div>
        <a href="<?= BASE_URL ?>employee/add_blog.php" class="btn btn-primary fw-bold"><i class="bi bi-plus-lg me-1"></i> New Manuscript</a>
    </div>

    <!-- Filter & Search Bar -->
    <div class="card border-0 shadow-sm mb-4 rounded-3">
        <div class="card-body p-3">
            <form action="<?= BASE_URL ?>employee/my_blogs.php" method="GET" class="row g-2">
                <div class="col-md-5">
                    <input type="text" name="search" class="form-control" placeholder="Search manuscript title..." value="<?= htmlspecialchars($search) ?>">
                </div>
                <div class="col-md-4">
                    <select name="status" class="form-select">
                        <option value="">All Statuses</option>
                        <option value="draft" <?= $statusFilter === 'draft' ? 'selected' : '' ?>>Drafts</option>
                        <option value="pending" <?= $statusFilter === 'pending' ? 'selected' : '' ?>>Pending Approval (Editable)</option>
                        <option value="approved" <?= $statusFilter === 'approved' ? 'selected' : '' ?>>Approved / Published</option>
                        <option value="rejected" <?= $statusFilter === 'rejected' ? 'selected' : '' ?>>Rejected / Revision Required</option>
                    </select>
                </div>
                <div class="col-md-3 d-grid">
                    <button type="submit" class="btn btn-secondary"><i class="bi bi-funnel me-1"></i> Filter Manuscripts</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Manuscripts List -->
    <div class="card border-0 shadow-sm rounded-3">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 35%;">Title &amp; Format</th>
                        <th>Category</th>
                        <th>Status</th>
                        <th>Submitted / Reviewed</th>
                        <th style="width: 25%;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($blogs)): ?>
                        <tr>
                            <td colspan="5" class="text-center py-5 text-muted">
                                <i class="bi bi-journal-x display-4"></i>
                                <p class="mt-2 mb-0">No manuscripts found matching your criteria.</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($blogs as $b): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-2 mb-1">
                                        <strong class="text-dark fs-6"><?= htmlspecialchars($b['title']) ?></strong>
                                        <?php if (($b['content_type'] ?? 'text') === 'html'): ?>
                                            <span class="badge bg-danger" title="Created with HTML Code"><i class="bi bi-code-slash me-1"></i> HTML</span>
                                        <?php endif; ?>
                                    </div>

                                    <?php if ($b['status'] === 'rejected' && !empty($b['rejection_reason'])): ?>
                                        <div class="alert alert-danger py-2 px-3 mt-2 mb-1 small rounded">
                                            <strong><i class="bi bi-exclamation-octagon me-1"></i> Rejection Reason:</strong>
                                            <div class="mt-1"><?= htmlspecialchars($b['rejection_reason']) ?></div>
                                            <div class="mt-2 pt-1 border-top border-danger border-opacity-25 d-flex gap-2">
                                                <a href="<?= BASE_URL ?>employee/send_message.php?blog_id=<?= $b['id'] ?>&subject=<?= urlencode('Question regarding rejection: ' . $b['title']) ?>" class="btn btn-sm btn-danger py-0 px-2 font-size-xs fw-bold">
                                                    <i class="bi bi-chat-text me-1"></i> Ask Admin Why
                                                </a>
                                                <a href="<?= BASE_URL ?>employee/edit_blog.php?id=<?= $b['id'] ?>" class="btn btn-sm btn-outline-dark py-0 px-2 font-size-xs">
                                                    <i class="bi bi-pencil me-1"></i> Edit Manuscript
                                                </a>
                                            </div>
                                        </div>
                                    <?php endif; ?>

                                    <?php if (isset($delRequests[$b['id']]) && $delRequests[$b['id']] === 'pending'): ?>
                                        <span class="badge bg-warning text-dark mt-1">
                                            <i class="bi bi-hourglass-split me-1"></i> Deletion Request Pending Admin Review
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td><span class="badge bg-secondary"><?= htmlspecialchars($b['category_name'] ?? 'General') ?></span></td>
                                <td>
                                    <?php if ($b['status'] === 'approved'): ?>
                                        <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i> Approved</span>
                                    <?php elseif ($b['status'] === 'pending'): ?>
                                        <span class="badge bg-warning text-dark"><i class="bi bi-hourglass-split me-1"></i> Pending Review</span>
                                    <?php elseif ($b['status'] === 'rejected'): ?>
                                        <span class="badge bg-danger"><i class="bi bi-x-circle me-1"></i> Rejected</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary"><i class="bi bi-file-earmark me-1"></i> Draft</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <small class="text-muted d-block">Created: <?= formatDate($b['created_at']) ?></small>
                                    <?php if ($b['submitted_at']): ?>
                                        <small class="text-muted d-block">Submitted: <?= formatDate($b['submitted_at'], 'M d, Y H:i') ?></small>
                                    <?php endif; ?>
                                    <?php if ($b['reviewed_at']): ?>
                                        <small class="text-muted d-block">Reviewed: <?= formatDate($b['reviewed_at'], 'M d, Y H:i') ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="d-flex flex-wrap gap-1 align-items-center">
                                        <!-- View / Preview -->
                                        <a href="<?= BASE_URL ?>user/blog_details.php?id=<?= $b['id'] ?>" class="btn btn-sm btn-outline-secondary" title="Preview"><i class="bi bi-eye"></i> View</a>

                                        <!-- Pending Status: Allow editing while in pending review! -->
                                        <?php if ($b['status'] === 'pending'): ?>
                                            <a href="<?= BASE_URL ?>employee/edit_blog.php?id=<?= $b['id'] ?>" class="btn btn-sm btn-warning text-dark fw-bold" title="Update Manuscript while in Pending Review">
                                                <i class="bi bi-pencil-square me-1"></i> Edit Pending
                                            </a>
                                        <?php elseif ($b['status'] === 'draft' || $b['status'] === 'rejected'): ?>
                                            <!-- Draft or Rejected: Allow Editing -->
                                            <a href="<?= BASE_URL ?>employee/edit_blog.php?id=<?= $b['id'] ?>" class="btn btn-sm btn-outline-primary" title="Edit Manuscript">
                                                <i class="bi bi-pencil"></i> Edit
                                            </a>
                                        <?php endif; ?>

                                        <!-- Draft Status: Submit or Direct Delete -->
                                        <?php if ($b['status'] === 'draft'): ?>
                                            <a href="<?= BASE_URL ?>employee/submit_blog.php?id=<?= $b['id'] ?>" class="btn btn-sm btn-success" title="Submit for Approval"><i class="bi bi-send"></i> Submit</a>
                                            <a href="<?= BASE_URL ?>employee/delete_blog.php?id=<?= $b['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this draft manuscript?');" title="Delete Draft"><i class="bi bi-trash"></i> Delete</a>
                                        <?php endif; ?>

                                        <!-- Approved Status: Employee CANNOT edit directly; must send Deletion Request to Admin -->
                                        <?php if ($b['status'] === 'approved'): ?>
                                            <?php if (isset($delRequests[$b['id']]) && $delRequests[$b['id']] === 'pending'): ?>
                                                <button type="button" class="btn btn-sm btn-secondary" disabled title="Deletion Request is awaiting Admin response">
                                                    <i class="bi bi-clock me-1"></i> Delete Pending
                                                </button>
                                            <?php else: ?>
                                                <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteRequestModal<?= $b['id'] ?>" title="Request Admin to delete this published blog">
                                                    <i class="bi bi-trash me-1"></i> Request Delete
                                                </button>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Deletion Request Modal for Approved Blogs -->
                                    <?php if ($b['status'] === 'approved'): ?>
                                        <div class="modal fade" id="deleteRequestModal<?= $b['id'] ?>" tabindex="-1" aria-labelledby="modalLabel<?= $b['id'] ?>" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <form action="<?= BASE_URL ?>employee/request_delete.php" method="POST">
                                                        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                                                        <input type="hidden" name="blog_id" value="<?= $b['id'] ?>">

                                                        <div class="modal-header bg-danger text-white">
                                                            <h5 class="modal-title" id="modalLabel<?= $b['id'] ?>"><i class="bi bi-exclamation-triangle me-2"></i> Request Blog Deletion</h5>
                                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <p>You are requesting the Administrator to permanently remove your published blog:</p>
                                                            <div class="alert alert-light border fw-bold text-dark mb-3">
                                                                <?= htmlspecialchars($b['title']) ?>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label for="reason_<?= $b['id'] ?>" class="form-label fw-bold">Reason for Deletion <span class="text-danger">*</span></label>
                                                                <textarea class="form-control" id="reason_<?= $b['id'] ?>" name="reason" rows="4" required placeholder="Explain why this published blog needs to be removed (e.g. outdated technical info, replaced by new version, copyright request)..."></textarea>
                                                            </div>
                                                            <small class="text-muted d-block">
                                                                <i class="bi bi-info-circle me-1"></i> As per editorial policy, published blogs require Admin authorization to delete. The admin will review your request.
                                                            </small>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                            <button type="submit" class="btn btn-danger fw-bold"><i class="bi bi-send me-1"></i> Send Deletion Request</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
