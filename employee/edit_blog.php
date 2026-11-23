<?php
require_once __DIR__ . '/../includes/employee_auth.php';

$pdo = getDBConnection();
$employeeId = $_SESSION['user_id'];
$blogId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$error = '';

if ($blogId <= 0) {
    redirect(BASE_URL . 'employee/my_blogs.php');
}

// Fetch existing blog details
$stmt = $pdo->prepare("SELECT * FROM blogs WHERE id = ? AND employee_id = ?");
$stmt->execute([$blogId, $employeeId]);
$blog = $stmt->fetch();

if (!$blog) {
    setFlash('danger', 'Blog manuscript not found or permission denied.');
    redirect(BASE_URL . 'employee/my_blogs.php');
}

// Disallow editing approved blogs: once approved, it cannot be edited
if ($blog['status'] === 'approved') {
    setFlash('warning', 'Approved and published blogs cannot be modified directly. If you need to remove it, you can submit a deletion request to the Administrator.');
    redirect(BASE_URL . 'employee/my_blogs.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = sanitizeInput($_POST['title'] ?? '');
    $categoryId = isset($_POST['category_id']) ? (int)$_POST['category_id'] : 0;
    $shortDesc = sanitizeInput($_POST['short_description'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $contentType = in_array($_POST['content_type'] ?? 'text', ['text', 'html']) ? $_POST['content_type'] : 'text';
    $metaTitle = sanitizeInput($_POST['meta_title'] ?? '');
    $metaDescription = sanitizeInput($_POST['meta_description'] ?? '');
    $metaKeywords = sanitizeInput($_POST['meta_keywords'] ?? '');
    $action = $_POST['action'] ?? 'save_draft';
    $csrfToken = $_POST['csrf_token'] ?? '';

    if (!verifyCSRFToken($csrfToken)) {
        $error = "Invalid CSRF token.";
    } elseif (empty($title) || $categoryId <= 0 || empty($content)) {
        $error = "Title, Category, and Content are required.";
    } else {
        $featuredImage = $blog['featured_image'];
        $downloadableFile = $blog['downloadable_file'];

        // Optional image update
        if (isset($_FILES['featured_image']) && $_FILES['featured_image']['error'] === UPLOAD_ERR_OK) {
            $imgName = $_FILES['featured_image']['name'];
            $imgTmp = $_FILES['featured_image']['tmp_name'];
            $imgSize = $_FILES['featured_image']['size'];
            $imgExt = strtolower(pathinfo($imgName, PATHINFO_EXTENSION));

            if ($imgSize > MAX_FILE_SIZE) {
                $error = "Featured Image size exceeds 5MB.";
            } elseif (!in_array($imgExt, ALLOWED_IMAGE_EXT) || in_array($imgExt, DISALLOWED_EXT)) {
                $error = "Invalid image extension.";
            } else {
                $newImgName = 'img_' . uniqid() . '_' . time() . '.' . $imgExt;
                move_uploaded_file($imgTmp, BLOG_IMAGE_UPLOAD_DIR . $newImgName);
                $featuredImage = $newImgName;
            }
        }

        // Optional document update
        if (empty($error) && isset($_FILES['downloadable_file']) && $_FILES['downloadable_file']['error'] === UPLOAD_ERR_OK) {
            $docName = $_FILES['downloadable_file']['name'];
            $docTmp = $_FILES['downloadable_file']['tmp_name'];
            $docSize = $_FILES['downloadable_file']['size'];
            $docExt = strtolower(pathinfo($docName, PATHINFO_EXTENSION));

            if ($docSize > MAX_FILE_SIZE) {
                $error = "Document size exceeds 5MB.";
            } elseif (!in_array($docExt, ALLOWED_DOC_EXT) || in_array($docExt, DISALLOWED_EXT)) {
                $error = "Invalid document extension.";
            } else {
                $newDocName = 'doc_' . uniqid() . '_' . time() . '.' . $docExt;
                move_uploaded_file($docTmp, BLOG_FILE_UPLOAD_DIR . $newDocName);
                $downloadableFile = $newDocName;
            }
        }

        if (empty($error)) {
            // Determine new status based on current status and action:
            // 1) If current is 'pending':
            //    - action 'update_pending': stays 'pending' (content updated, admin notified)
            //    - action 'save_draft': moved to 'draft'
            // 2) If current is 'rejected':
            //    - action 'submit_approval': becomes 'pending', submitted_at = NOW()
            //    - action 'save_draft': becomes 'draft'
            // 3) If current is 'draft':
            //    - action 'submit_approval': becomes 'pending', submitted_at = NOW()
            //    - action 'save_draft': stays 'draft'
            if ($blog['status'] === 'pending') {
                if ($action === 'save_draft') {
                    $newStatus = 'draft';
                    $submittedAt = null;
                } else {
                    $newStatus = 'pending';
                    $submittedAt = $blog['submitted_at'] ?? date('Y-m-d H:i:s');
                }
            } elseif ($blog['status'] === 'rejected') {
                $newStatus = ($action === 'submit_approval') ? 'pending' : 'draft';
                $submittedAt = ($action === 'submit_approval') ? date('Y-m-d H:i:s') : null;
            } else {
                // draft
                $newStatus = ($action === 'submit_approval') ? 'pending' : 'draft';
                $submittedAt = ($action === 'submit_approval') ? date('Y-m-d H:i:s') : $blog['submitted_at'];
            }

            $stmtUpdate = $pdo->prepare("
                UPDATE blogs 
                SET category_id = ?, title = ?, short_description = ?, meta_title = ?, meta_description = ?, meta_keywords = ?, content = ?, content_type = ?,
                    featured_image = ?, downloadable_file = ?, status = ?, 
                    submitted_at = ?, updated_at = NOW()
                WHERE id = ? AND employee_id = ?
            ");
            $result = $stmtUpdate->execute([$categoryId, $title, $shortDesc, $metaTitle, $metaDescription, $metaKeywords, $content, $contentType, $featuredImage, $downloadableFile, $newStatus, $submittedAt, $blogId, $employeeId]);

            if ($result) {
                if ($newStatus === 'pending') {
                    // Notify admins
                    $isUpdateNotice = ($blog['status'] === 'pending');
                    $msgTitle = $isUpdateNotice ? "Pending Blog Updated ✏️" : "Blog Resubmitted for Approval 🔄";
                    $msgDesc = $isUpdateNotice
                        ? "Employee " . $_SESSION['user_name'] . " updated pending blog: '" . $title . "' (" . strtoupper($contentType) . " mode)"
                        : "Employee " . $_SESSION['user_name'] . " resubmitted manuscript: '" . $title . "'";

                    $stmtAdmins = $pdo->query("SELECT id FROM users WHERE role = 'admin'");
                    while ($admin = $stmtAdmins->fetch()) {
                        createNotification(
                            $admin['id'],
                            $msgTitle,
                            $msgDesc,
                            "info",
                            $blogId
                        );
                    }

                    if ($isUpdateNotice) {
                        setFlash('success', 'Pending blog manuscript updated! It remains in the Admin review queue with your latest content.');
                    } else {
                        setFlash('success', 'Manuscript updated and submitted for Admin approval!');
                    }
                } else {
                    setFlash('success', 'Manuscript changes saved to Draft.');
                }
                redirect(BASE_URL . 'employee/my_blogs.php');
            } else {
                $error = "Failed to update blog manuscript.";
            }
        }
    }
}

// Fetch categories
$categories = $pdo->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();
$currentContentType = $_POST['content_type'] ?? ($blog['content_type'] ?? 'text');

$pageTitle = 'Edit Manuscript — BlogSphere';
include __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-9">

            <!-- Informational Banner for Pending Status -->
            <?php if ($blog['status'] === 'pending'): ?>
                <div class="alert alert-warning shadow-sm border-start border-warning border-4 mb-4">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-hourglass-split display-6 text-warning me-3"></i>
                        <div>
                            <h5 class="fw-bold mb-1">Editing a Pending Review Manuscript</h5>
                            <p class="mb-0 text-dark">This manuscript is currently awaiting Administrator approval. You can update the title, content, HTML code, or attachments. Click <strong>"Update Pending Submission"</strong> to keep it in the review queue with your latest edits, or <strong>"Move to Draft"</strong> to pull it back.</p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Informational Banner for Rejected Status -->
            <?php if ($blog['status'] === 'rejected' && !empty($blog['rejection_reason'])): ?>
                <div class="alert alert-danger shadow-sm border-start border-danger border-4 mb-4">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h5 class="fw-bold mb-1"><i class="bi bi-exclamation-triangle-fill me-2"></i> Editorial Revision Request</h5>
                            <p class="mb-0"><strong>Rejection Reason:</strong> <?= htmlspecialchars($blog['rejection_reason']) ?></p>
                            <small class="text-muted d-block mt-1">Make the requested corrections and click "Resubmit for Approval".</small>
                        </div>
                        <a href="<?= BASE_URL ?>employee/send_message.php?ask_admin=1&blog_id=<?= $blog['id'] ?>&subject=<?= urlencode('Question regarding rejection of ' . $blog['title']) ?>" class="btn btn-outline-dark btn-sm fw-bold ms-3 text-nowrap">
                            <i class="bi bi-question-circle me-1"></i> Ask Admin
                        </a>
                    </div>
                </div>
            <?php endif; ?>

            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0">
                        <i class="bi bi-pencil-square text-primary me-2"></i> Edit Blog Manuscript
                        <?php if ($blog['status'] === 'pending'): ?>
                            <span class="badge bg-warning text-dark ms-2"><i class="bi bi-hourglass-split me-1"></i> Status: Pending Review</span>
                        <?php elseif ($blog['status'] === 'draft'): ?>
                            <span class="badge bg-secondary ms-2">Status: Draft</span>
                        <?php elseif ($blog['status'] === 'rejected'): ?>
                            <span class="badge bg-danger ms-2">Status: Rejected</span>
                        <?php endif; ?>
                    </h5>
                    <a href="<?= BASE_URL ?>employee/my_blogs.php" class="btn btn-sm btn-outline-secondary">Cancel</a>
                </div>
                <div class="card-body p-4">
                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?= htmlspecialchars($error) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <form action="<?= BASE_URL ?>employee/edit_blog.php?id=<?= $blogId ?>" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">

                        <div class="row g-3 mb-3">
                            <div class="col-md-8">
                                <label for="title" class="form-label fw-bold">Blog Title <span class="text-danger">*</span></label>
                                <input type="text" class="form-control form-control-lg" id="title" name="title" required value="<?= htmlspecialchars($_POST['title'] ?? $blog['title']) ?>">
                            </div>
                            <div class="col-md-4">
                                <label for="category_id" class="form-label fw-bold">Category <span class="text-danger">*</span></label>
                                <select name="category_id" id="category_id" class="form-select form-select-lg" required>
                                    <option value="">-- Select Category --</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?= $cat['id'] ?>" <?= (($_POST['category_id'] ?? $blog['category_id']) == $cat['id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($cat['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="short_description" class="form-label fw-bold">Short Description / Summary</label>
                            <textarea name="short_description" id="short_description" rows="2" class="form-control"><?= htmlspecialchars($_POST['short_description'] ?? $blog['short_description']) ?></textarea>
                        </div>

                        <!-- Content Mode Toggle (Plain Text vs HTML Code) -->
                        <div class="card bg-light border-0 p-3 mb-3 rounded-3">
                            <div class="d-flex flex-wrap justify-content-between align-items-center">
                                <div>
                                    <label class="form-label fw-bold mb-1">Content Format Mode <span class="text-danger">*</span></label>
                                    <p class="text-muted small mb-0">Switch between Plain Text or Raw HTML Code mode</p>
                                </div>
                                <div class="btn-group" role="group">
                                    <input type="radio" class="btn-check" name="content_type" id="mode_text" value="text" autocomplete="off" <?= ($currentContentType === 'text') ? 'checked' : '' ?> onchange="switchContentMode('text')">
                                    <label class="btn btn-outline-primary" for="mode_text"><i class="bi bi-fonts me-1"></i> Plain Text</label>

                                    <input type="radio" class="btn-check" name="content_type" id="mode_html" value="html" autocomplete="off" <?= ($currentContentType === 'html') ? 'checked' : '' ?> onchange="switchContentMode('html')">
                                    <label class="btn btn-outline-danger" for="mode_html"><i class="bi bi-code-slash me-1"></i> HTML Code Mode</label>
                                </div>
                            </div>
                            <div id="html_mode_notice" class="alert alert-info py-2 px-3 mt-3 mb-0 small <?= ($currentContentType === 'text') ? 'd-none' : '' ?>">
                                <i class="bi bi-info-circle-fill me-1"></i> <strong>HTML Code Mode Enabled:</strong> Admin and Readers will see the <strong>final rendered outcome</strong> — not the raw code! Click <strong>"Preview Live Output"</strong> below to view the rendered preview.
                            </div>
                        </div>

                        <!-- Content Editor area with formatting toolbar & Preview switcher -->
                        <div class="mb-4">
                            <div class="d-flex flex-wrap justify-content-between align-items-center mb-2 gap-2">
                                <label for="content" class="form-label fw-bold mb-0">
                                    Article Content <span class="text-danger">*</span>
                                </label>
                                
                                <div class="d-flex flex-wrap align-items-center gap-2">
                                    <!-- HTML File Import / Preview Trigger -->
                                    <input type="file" id="html_file_input" accept=".html,.htm" class="d-none" onchange="handleHtmlFileUpload(this)">
                                    <button type="button" class="btn btn-sm btn-outline-danger fw-semibold" onclick="document.getElementById('html_file_input').click()" title="Select an HTML file from your computer to import and preview its rendered outcome">
                                        <i class="bi bi-filetype-html me-1"></i> Upload &amp; Preview HTML File
                                    </button>

                                    <!-- View Switcher Tabs -->
                                    <div class="btn-group btn-group-sm" role="group" aria-label="Editor Views">
                                        <button type="button" class="btn btn-primary active" id="btn_view_code" onclick="switchEditorView('code')">
                                            <i class="bi bi-code-slash me-1"></i> Editor
                                        </button>
                                        <button type="button" class="btn btn-outline-primary" id="btn_view_preview" onclick="switchEditorView('preview')">
                                            <i class="bi bi-eye-fill me-1"></i> Preview HTML Output
                                        </button>
                                        <button type="button" class="btn btn-outline-primary" id="btn_view_split" onclick="switchEditorView('split')">
                                            <i class="bi bi-layout-split me-1"></i> Split Screen
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Plain text toolbar -->
                            <div id="toolbar_text" class="editor-toolbar d-flex flex-wrap gap-1 mb-2 <?= ($currentContentType === 'html') ? 'd-none' : '' ?>">
                                <button type="button" class="btn btn-sm btn-light border" onclick="applyFormat('bold')" title="Bold"><i class="bi bi-type-bold"></i></button>
                                <button type="button" class="btn btn-sm btn-light border" onclick="applyFormat('italic')" title="Italic"><i class="bi bi-type-italic"></i></button>
                                <button type="button" class="btn btn-sm btn-light border" onclick="applyFormat('heading')" title="Heading"><i class="bi bi-type-h3"></i></button>
                                <button type="button" class="btn btn-sm btn-light border" onclick="applyFormat('ul')" title="Bullet List"><i class="bi bi-list-ul"></i></button>
                                <button type="button" class="btn btn-sm btn-light border" onclick="applyFormat('link')" title="Insert Link"><i class="bi bi-link-45deg"></i></button>
                            </div>

                            <!-- HTML code toolbar -->
                            <div id="toolbar_html" class="editor-toolbar d-flex flex-wrap gap-1 mb-2 <?= ($currentContentType === 'text') ? 'd-none' : '' ?>">
                                <button type="button" class="btn btn-sm btn-light border" onclick="insertHtmlTag('<h2>', '</h2>')" title="Heading 2"><strong>&lt;h2&gt;</strong></button>
                                <button type="button" class="btn btn-sm btn-light border" onclick="insertHtmlTag('<h3>', '</h3>')" title="Heading 3"><strong>&lt;h3&gt;</strong></button>
                                <button type="button" class="btn btn-sm btn-light border" onclick="insertHtmlTag('<p>', '</p>')" title="Paragraph">&lt;p&gt;</button>
                                <button type="button" class="btn btn-sm btn-light border" onclick="insertHtmlTag('<strong>', '</strong>')" title="Bold">&lt;strong&gt;</button>
                                <button type="button" class="btn btn-sm btn-light border" onclick="insertHtmlTag('<em>', '</em>')" title="Italic">&lt;em&gt;</button>
                                <button type="button" class="btn btn-sm btn-light border" onclick="insertHtmlSnippet('<ul>\n  <li>Item 1</li>\n  <li>Item 2</li>\n</ul>')" title="List">&lt;ul&gt;&lt;li&gt;</button>
                                <button type="button" class="btn btn-sm btn-light border" onclick="insertHtmlSnippet('<pre class=\"bg-dark text-white p-3 rounded\"><code>\n// Your code here\n</code></pre>')" title="Code Block">&lt;pre&gt;&lt;code&gt;</button>
                                <button type="button" class="btn btn-sm btn-light border" onclick="insertHtmlSnippet('<div class=\"alert alert-primary p-3 rounded\">\n  <h5 class=\"alert-heading\">Note</h5>\n  <p class=\"mb-0\">Important message here</p>\n</div>')" title="Alert Box">&lt;alert&gt;</button>
                                <button type="button" class="btn btn-sm btn-light border" onclick="insertHtmlSnippet('<table class=\"table table-bordered table-striped\">\n  <thead>\n    <tr><th>Header 1</th><th>Header 2</th></tr>\n  </thead>\n  <tbody>\n    <tr><td>Data 1</td><td>Data 2</td></tr>\n  </tbody>\n</table>')" title="Table">&lt;table&gt;</button>
                                <button type="button" class="btn btn-sm btn-light border" onclick="insertHtmlTag('<a href=\"https://example.com\" target=\"_blank\">', '</a>')" title="Link">&lt;a&gt;</button>
                            </div>

                            <!-- Workspace Container (Supports Editor, Preview, and Side-by-Side Split View) -->
                            <div class="row g-3" id="editor_workspace">
                                <div class="col-12" id="col_code">
                                    <textarea name="content" id="content" rows="15" class="form-control editor-textarea font-monospace" required><?= htmlspecialchars($_POST['content'] ?? $blog['content']) ?></textarea>
                                </div>
                                <div class="col-12 d-none" id="col_preview">
                                    <div class="p-4 border rounded-3 bg-white shadow-sm h-100" style="min-height: 380px;">
                                        <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
                                            <span class="badge bg-success py-1 px-2">
                                                <i class="bi bi-display me-1"></i> Live Rendered Outcome (Admin &amp; User View)
                                            </span>
                                            <small class="text-muted"><i class="bi bi-info-circle me-1"></i> Renders headings, cards, tables, styles, and links</small>
                                        </div>
                                        <div id="preview_output" class="blog-rendered-html-output"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label for="featured_image" class="form-label fw-bold">Featured Image</label>
                                <?php if (!empty($blog['featured_image'])): ?>
                                    <div class="mb-2">
                                        <small class="text-muted d-block mb-1">Current Image:</small>
                                        <img src="<?= BASE_URL ?>uploads/blog_images/<?= $blog['featured_image'] ?>" style="height: 60px; object-fit: cover;" class="rounded border">
                                    </div>
                                <?php endif; ?>
                                <input type="file" name="featured_image" id="featured_image" class="form-control" accept=".jpg,.jpeg,.png,.webp">
                                <small class="text-muted">Upload to replace existing image (Max 5MB)</small>
                            </div>
                            <div class="col-md-6">
                                <label for="downloadable_file" class="form-label fw-bold">Downloadable File</label>
                                <?php if (!empty($blog['downloadable_file'])): ?>
                                    <div class="mb-2">
                                        <small class="text-muted d-block mb-1">Current File: <code><?= htmlspecialchars($blog['downloadable_file']) ?></code></small>
                                    </div>
                                <?php endif; ?>
                                <input type="file" name="downloadable_file" id="downloadable_file" class="form-control" accept=".pdf,.doc,.docx,.txt">
                                <small class="text-muted">Upload to replace existing document (Max 5MB)</small>
                            </div>
                        </div>

                        <!-- Search Engine Optimization (SEO) & Social Sharing Card -->
                        <div class="card border border-primary border-opacity-25 rounded-3 mb-4 shadow-sm bg-white">
                            <div class="card-header bg-light py-3 d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="fw-bold mb-0 text-primary">
                                        <i class="bi bi-search me-2"></i> Search Engine Optimization (SEO) &amp; Social Sharing
                                    </h6>
                                    <small class="text-muted">Optimize how your article ranks and appears on Google, Bing, Facebook, and Twitter</small>
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-primary" onclick="autoFillSeo()">
                                    <i class="bi bi-magic me-1"></i> Auto-fill from Title &amp; Summary
                                </button>
                            </div>
                            <div class="card-body p-3 p-md-4">
                                <div class="row g-3 mb-3">
                                    <div class="col-md-6">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <label for="meta_title" class="form-label fw-bold mb-0">SEO Meta Title</label>
                                            <span id="title_counter" class="badge bg-secondary font-monospace">0/60</span>
                                        </div>
                                        <input type="text" class="form-control" id="meta_title" name="meta_title" maxlength="70" placeholder="e.g. 10 Essential Web Dev Tips for 2026 — BlogSphere" value="<?= htmlspecialchars($_POST['meta_title'] ?? ($blog['meta_title'] ?? '')) ?>" oninput="updateSeoPreview()">
                                        <small class="text-muted">Optimal length: 50–60 characters. Defaults to article title if left empty.</small>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="meta_keywords" class="form-label fw-bold mb-1">Focus Keywords / Tags</label>
                                        <input type="text" class="form-control" id="meta_keywords" name="meta_keywords" placeholder="e.g. web development, php, tutorials, tips" value="<?= htmlspecialchars($_POST['meta_keywords'] ?? ($blog['meta_keywords'] ?? '')) ?>">
                                        <small class="text-muted">Comma-separated target search keywords for search ranking.</small>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <label for="meta_description" class="form-label fw-bold mb-0">SEO Meta Description</label>
                                        <span id="desc_counter" class="badge bg-secondary font-monospace">0/160</span>
                                    </div>
                                    <textarea class="form-control" id="meta_description" name="meta_description" rows="2" maxlength="170" placeholder="A compelling 1-2 sentence overview to convince readers to click from Google search results..." oninput="updateSeoPreview()"><?= htmlspecialchars($_POST['meta_description'] ?? ($blog['meta_description'] ?? '')) ?></textarea>
                                    <small class="text-muted">Optimal length: 120–160 characters. Defaults to article summary if left empty.</small>
                                </div>

                                <!-- Live Google Search Result Preview -->
                                <div class="p-3 bg-light rounded-3 border">
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <span class="fw-bold small text-muted"><i class="bi bi-google me-1"></i> Live Google Search Result Preview:</span>
                                        <span id="seo_score_badge" class="badge bg-success">Optimal SEO Snippet</span>
                                    </div>
                                    <div class="google-preview-box bg-white p-3 rounded border">
                                        <div class="d-flex align-items-center gap-2 mb-1">
                                            <div class="rounded-circle bg-light border d-flex align-items-center justify-content-center" style="width: 26px; height: 26px;">
                                                <i class="bi bi-journal-richtext text-primary" style="font-size: 14px;"></i>
                                            </div>
                                            <div class="lh-1">
                                                <div class="text-dark fw-semibold" style="font-size: 13px;">BlogSphere</div>
                                                <div class="text-muted font-monospace" style="font-size: 11px;" id="preview_url"><?= BASE_URL ?>user/blog_details.php?id=<?= $blog['id'] ?></div>
                                            </div>
                                        </div>
                                        <h5 class="text-primary mb-1 text-decoration-underline" style="cursor: pointer; font-size: 18px;" id="preview_title">
                                            <?= htmlspecialchars(!empty($blog['meta_title']) ? $blog['meta_title'] : $blog['title']) ?>
                                        </h5>
                                        <p class="text-muted mb-0 small" style="line-height: 1.4;" id="preview_description">
                                            <?= htmlspecialchars(!empty($blog['meta_description']) ? $blog['meta_description'] : ($blog['short_description'] ?? 'Your meta description preview will appear here in Google search engine result snippets.')) ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-3 pt-3 border-top">
                            <?php if ($blog['status'] === 'pending'): ?>
                                <button type="submit" name="action" value="save_draft" class="btn btn-outline-secondary px-4 fw-bold">
                                    <i class="bi bi-arrow-down-left-square me-1"></i> Move to Draft
                                </button>
                                <button type="submit" name="action" value="update_pending" class="btn btn-warning px-4 fw-bold text-dark shadow-sm">
                                    <i class="bi bi-arrow-repeat me-1"></i> Update Pending Submission
                                </button>
                            <?php elseif ($blog['status'] === 'rejected'): ?>
                                <button type="submit" name="action" value="save_draft" class="btn btn-secondary px-4 fw-bold">
                                    <i class="bi bi-file-earmark me-1"></i> Save Draft
                                </button>
                                <button type="submit" name="action" value="submit_approval" class="btn btn-primary px-4 fw-bold shadow-sm">
                                    <i class="bi bi-send-check me-1"></i> Resubmit for Approval
                                </button>
                            <?php else: ?>
                                <button type="submit" name="action" value="save_draft" class="btn btn-secondary px-4 fw-bold">
                                    <i class="bi bi-file-earmark me-1"></i> Save Draft
                                </button>
                                <button type="submit" name="action" value="submit_approval" class="btn btn-primary px-4 fw-bold shadow-sm">
                                    <i class="bi bi-send-check me-1"></i> Submit for Approval
                                </button>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function switchContentMode(mode) {
    const textToolbar = document.getElementById('toolbar_text');
    const htmlToolbar = document.getElementById('toolbar_html');
    const notice = document.getElementById('html_mode_notice');
    const textarea = document.getElementById('content');

    if (mode === 'html') {
        textToolbar.classList.add('d-none');
        htmlToolbar.classList.remove('d-none');
        notice.classList.remove('d-none');
    } else {
        htmlToolbar.classList.add('d-none');
        textToolbar.classList.remove('d-none');
        notice.classList.add('d-none');
    }
}

function insertHtmlTag(openTag, closeTag) {
    const textarea = document.getElementById('content');
    const start = textarea.selectionStart;
    const end = textarea.selectionEnd;
    const selected = textarea.value.substring(start, end);
    const replacement = openTag + (selected || 'Sample Text') + closeTag;
    textarea.value = textarea.value.substring(0, start) + replacement + textarea.value.substring(end);
    textarea.focus();
    textarea.setSelectionRange(start + openTag.length, start + openTag.length + (selected ? selected.length : 11));
    updatePreviewIfOpen();
}

function insertHtmlSnippet(snippet) {
    const textarea = document.getElementById('content');
    const start = textarea.selectionStart;
    const end = textarea.selectionEnd;
    textarea.value = textarea.value.substring(0, start) + snippet + textarea.value.substring(end);
    textarea.focus();
    updatePreviewIfOpen();
}

function switchEditorView(view) {
    const colCode = document.getElementById('col_code');
    const colPreview = document.getElementById('col_preview');
    const btnCode = document.getElementById('btn_view_code');
    const btnPreview = document.getElementById('btn_view_preview');
    const btnSplit = document.getElementById('btn_view_split');

    // Reset buttons
    btnCode.className = 'btn btn-outline-primary';
    btnPreview.className = 'btn btn-outline-primary';
    btnSplit.className = 'btn btn-outline-primary';

    updateRenderedPreview();

    if (view === 'code') {
        btnCode.className = 'btn btn-primary active';
        colCode.className = 'col-12';
        colCode.classList.remove('d-none');
        colPreview.classList.add('d-none');
    } else if (view === 'preview') {
        btnPreview.className = 'btn btn-primary active';
        colCode.classList.add('d-none');
        colPreview.className = 'col-12';
        colPreview.classList.remove('d-none');
    } else if (view === 'split') {
        btnSplit.className = 'btn btn-primary active';
        colCode.className = 'col-lg-6';
        colCode.classList.remove('d-none');
        colPreview.className = 'col-lg-6';
        colPreview.classList.remove('d-none');
    }
}

function updateRenderedPreview() {
    const output = document.getElementById('preview_output');
    const content = document.getElementById('content').value;
    const isHtmlMode = document.getElementById('mode_html').checked;

    if (!content.trim()) {
        output.innerHTML = '<div class="text-center py-5 text-muted"><i class="bi bi-file-code display-4 d-block mb-2"></i><em>No content to preview yet. Start typing or upload an HTML file.</em></div>';
        return;
    }

    if (isHtmlMode) {
        output.innerHTML = content;
    } else {
        output.innerHTML = content.replace(/\n/g, '<br>');
    }
}

function handleHtmlFileUpload(input) {
    if (input.files && input.files[0]) {
        const file = input.files[0];
        const reader = new FileReader();
        reader.onload = function(e) {
            const htmlContent = e.target.result;
            document.getElementById('content').value = htmlContent;
            
            // Auto switch to HTML Code mode
            document.getElementById('mode_html').checked = true;
            switchContentMode('html');

            // Switch to Split View so the author sees both code & preview instantly!
            switchEditorView('split');

            // If title is empty, try extracting <title> or <h1> from the HTML file
            const titleInput = document.getElementById('title');
            if (!titleInput.value.trim()) {
                const titleMatch = htmlContent.match(/<title[^>]*>(.*?)<\/title>/i) || htmlContent.match(/<h1[^>]*>(.*?)<\/h1>/i);
                if (titleMatch && titleMatch[1]) {
                    titleInput.value = titleMatch[1].replace(/<[^>]+>/g, '').trim();
                    updateSeoPreview();
                }
            }
        };
        reader.readAsText(file);
    }
}

document.getElementById('content').addEventListener('input', function() {
    updateRenderedPreview();
});

// Real-Time SEO & SERP Snippet Preview Logic
function updateSeoPreview() {
    const rawTitle = document.getElementById('title').value.trim();
    const metaTitle = document.getElementById('meta_title').value.trim();
    const rawDesc = document.getElementById('short_description').value.trim();
    const metaDesc = document.getElementById('meta_description').value.trim();

    const displayTitle = metaTitle || (rawTitle ? rawTitle + ' — BlogSphere' : 'Your Article Title Appears Here — BlogSphere');
    const displayDesc = metaDesc || (rawDesc || 'Your meta description preview will appear here in Google search engine result snippets.');
    
    document.getElementById('preview_title').innerText = displayTitle;
    document.getElementById('preview_description').innerText = displayDesc;

    // Character Counters
    const titleLen = metaTitle.length;
    const descLen = metaDesc.length;
    
    const titleCounter = document.getElementById('title_counter');
    titleCounter.innerText = titleLen + '/60';
    if (titleLen > 60) {
        titleCounter.className = 'badge bg-danger font-monospace';
    } else if (titleLen >= 40) {
        titleCounter.className = 'badge bg-success font-monospace';
    } else {
        titleCounter.className = 'badge bg-secondary font-monospace';
    }

    const descCounter = document.getElementById('desc_counter');
    descCounter.innerText = descLen + '/160';
    if (descLen > 160) {
        descCounter.className = 'badge bg-danger font-monospace';
    } else if (descLen >= 120) {
        descCounter.className = 'badge bg-success font-monospace';
    } else {
        descCounter.className = 'badge bg-secondary font-monospace';
    }
}

function autoFillSeo() {
    const rawTitle = document.getElementById('title').value.trim();
    const rawDesc = document.getElementById('short_description').value.trim();

    if (rawTitle) {
        document.getElementById('meta_title').value = rawTitle.length > 50 ? rawTitle.substring(0, 57) + '...' : rawTitle + ' — BlogSphere';
    }
    if (rawDesc) {
        document.getElementById('meta_description').value = rawDesc.length > 155 ? rawDesc.substring(0, 155) + '...' : rawDesc;
    }
    updateSeoPreview();
}

document.getElementById('title').addEventListener('input', updateSeoPreview);
document.getElementById('short_description').addEventListener('input', updateSeoPreview);
updateSeoPreview();
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
