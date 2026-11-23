<?php
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/functions.php';

$pdo = getDBConnection();
$blogId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($blogId <= 0) {
    redirect(BASE_URL . 'user/blogs.php');
}

// Fetch blog details
$stmt = $pdo->prepare("
    SELECT b.*, c.name AS category_name, u.name AS author_name, u.email AS author_email
    FROM blogs b
    LEFT JOIN categories c ON b.category_id = c.id
    LEFT JOIN users u ON b.employee_id = u.id
    WHERE b.id = ?
");
$stmt->execute([$blogId]);
$blog = $stmt->fetch();

if (!$blog) {
    header("HTTP/1.1 404 Not Found");
    include ROOT_DIR . '404.php';
    exit();
}

// Check visibility: public users can only see 'approved' blogs.
// Admins or the authoring Employee can preview draft/pending/rejected blogs.
$isAuthor = isLoggedIn() && ($_SESSION['user_id'] == $blog['employee_id']);
$isAdmin = isLoggedIn() && ($_SESSION['user_role'] === 'admin');

if ($blog['status'] !== 'approved' && !$isAuthor && !$isAdmin) {
    header("HTTP/1.1 403 Forbidden");
    include ROOT_DIR . '403.php';
    exit();
}

// Handle Query Submission from User
$querySuccess = '';
$queryError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_query'])) {
    if (!isLoggedIn()) {
        setFlash('warning', 'Please login to submit a question about this article.');
        redirect(BASE_URL . 'auth/login.php');
    }

    $subject = sanitizeInput($_POST['subject'] ?? '');
    $message = sanitizeInput($_POST['message'] ?? '');
    $csrfToken = $_POST['csrf_token'] ?? '';

    if (!verifyCSRFToken($csrfToken)) {
        $queryError = "Invalid CSRF token. Please try again.";
    } elseif (empty($subject) || empty($message)) {
        $queryError = "Subject and Message fields are required.";
    } else {
        $userId = $_SESSION['user_id'];
        $employeeId = $blog['employee_id'];

        // Insert query into queries table
        $stmtQuery = $pdo->prepare("
            INSERT INTO queries (user_id, employee_id, blog_id, subject, message, status, created_at)
            VALUES (?, ?, ?, ?, ?, 'open', NOW())
        ");
        $stmtQuery->execute([$userId, $employeeId, $blogId, $subject, $message]);

        // Send direct message to author employee
        sendMessage($userId, $employeeId, $subject, $message, $blogId);

        // Notify author employee
        createNotification(
            $employeeId,
            "New Reader Query 📩",
            "User " . $_SESSION['user_name'] . " asked a question about '" . $blog['title'] . "': " . $subject,
            "info",
            $blogId
        );

        $querySuccess = "Your question has been sent to the article author! You can track replies in your Messages section.";
    }
}

// Fetch Related Approved Blogs
$stmtRelated = $pdo->prepare("
    SELECT b.id, b.title, b.created_at, c.name as category_name
    FROM blogs b
    LEFT JOIN categories c ON b.category_id = c.id
    WHERE b.status = 'approved' AND b.category_id = ? AND b.id != ?
    ORDER BY b.created_at DESC
    LIMIT 3
");
$stmtRelated->execute([$blog['category_id'], $blogId]);
$relatedBlogs = $stmtRelated->fetchAll();

// Dynamic SEO Configurations
$imagePath = BASE_URL . 'assets/images/default-blog.jpg';
if (!empty($blog['featured_image']) && file_exists(BLOG_IMAGE_UPLOAD_DIR . $blog['featured_image'])) {
    $imagePath = BASE_URL . 'uploads/blog_images/' . $blog['featured_image'];
}

$pageTitle = (!empty($blog['meta_title']) ? $blog['meta_title'] : $blog['title']) . ' — BlogSphere';
$metaDescription = !empty($blog['meta_description']) 
    ? $blog['meta_description'] 
    : (!empty($blog['short_description']) ? $blog['short_description'] : substr(strip_tags($blog['content']), 0, 160));
$metaKeywords = !empty($blog['meta_keywords']) 
    ? $blog['meta_keywords'] 
    : (($blog['category_name'] ?? 'blog') . ', ' . $blog['title'] . ', BlogSphere');
$metaAuthor = $blog['author_name'] ?? 'BlogSphere Writer';
$canonicalUrl = !empty($blog['canonical_url']) ? $blog['canonical_url'] : (BASE_URL . 'user/blog_details.php?id=' . $blog['id']);

$ogType = 'article';
$ogTitle = !empty($blog['meta_title']) ? $blog['meta_title'] : $blog['title'];
$ogDescription = $metaDescription;
$ogUrl = $canonicalUrl;
$ogImage = $imagePath;
$articlePublishedTime = date('c', strtotime($blog['created_at']));
$articleModifiedTime = date('c', strtotime($blog['updated_at']));
$articleAuthor = $blog['author_name'] ?? '';
$articleSection = $blog['category_name'] ?? 'General';

// Schema.org Structured Data (JSON-LD) for Search Engine Rich Snippets
$schemaJson = [
    '@context' => 'https://schema.org',
    '@type' => 'BlogPosting',
    'mainEntityOfPage' => [
        '@type' => 'WebPage',
        '@id' => $canonicalUrl
    ],
    'headline' => $blog['title'],
    'description' => $metaDescription,
    'image' => [$imagePath],
    'datePublished' => $articlePublishedTime,
    'dateModified' => $articleModifiedTime,
    'author' => [
        '@type' => 'Person',
        'name' => $blog['author_name'] ?? 'BlogSphere Writer'
    ],
    'publisher' => [
        '@type' => 'Organization',
        'name' => 'BlogSphere',
        'logo' => [
            '@type' => 'ImageObject',
            'url' => BASE_URL . 'assets/images/default-blog.jpg'
        ]
    ]
];
if (!empty($blog['category_name'])) {
    $schemaJson['articleSection'] = $blog['category_name'];
}

include __DIR__ . '/../includes/header.php';
?>

<div class="container py-5">
    <div class="row g-4">
        <!-- Main Article Container -->
        <div class="col-lg-8">
            <article class="card border-0 shadow-sm rounded-3 overflow-hidden">
                <?php 
                    $imagePath = BASE_URL . 'assets/images/default-blog.jpg';
                    if (!empty($blog['featured_image']) && file_exists(BLOG_IMAGE_UPLOAD_DIR . $blog['featured_image'])) {
                        $imagePath = BASE_URL . 'uploads/blog_images/' . $blog['featured_image'];
                    }
                ?>
                <img src="<?= $imagePath ?>" class="w-100" style="max-height: 420px; object-fit: cover;" alt="<?= htmlspecialchars($blog['title']) ?>">

                <div class="card-body p-4 p-md-5">
                    <?php if ($blog['category_name']): ?>
                        <span class="badge bg-primary mb-3 px-3 py-2 text-uppercase fs-7"><?= htmlspecialchars($blog['category_name']) ?></span>
                    <?php endif; ?>

                    <h1 class="fw-extrabold display-5 text-dark mb-3"><?= htmlspecialchars($blog['title']) ?></h1>

                    <div class="d-flex flex-wrap align-items-center justify-content-between text-muted border-top border-bottom py-3 mb-4">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-person-circle fs-4 text-primary"></i>
                            <div>
                                <strong class="text-dark d-block"><?= htmlspecialchars($blog['author_name'] ?? 'Author') ?></strong>
                                <small class="text-muted">Blog Author &amp; Technical Writer</small>
                            </div>
                        </div>
                        <div class="text-end">
                            <small class="d-block"><i class="bi bi-calendar-event me-1"></i> Published: <?= formatDate($blog['created_at']) ?></small>
                            <span class="badge bg-success text-capitalize"><i class="bi bi-check-circle me-1"></i> <?= htmlspecialchars($blog['status']) ?></span>
                        </div>
                    </div>

                    <?php if (!empty($blog['short_description'])): ?>
                        <div class="lead bg-light p-3 rounded border-start border-primary border-4 mb-4 text-secondary italic">
                            <?= htmlspecialchars($blog['short_description']) ?>
                        </div>
                    <?php endif; ?>

                    <?php if (($blog['content_type'] ?? 'text') === 'html'): ?>
                        <div class="blog-rendered-html-output my-4">
                            <?= $blog['content'] ?>
                        </div>
                    <?php else: ?>
                        <div class="blog-content-body my-4">
                            <?= nl2br(htmlspecialchars($blog['content'])) ?>
                        </div>
                    <?php endif; ?>

                    <!-- Download Section -->
                    <?php if (!empty($blog['downloadable_file'])): ?>
                        <div class="card bg-primary bg-opacity-10 border-primary border-opacity-25 my-5">
                            <div class="card-body p-4 d-flex flex-column flex-sm-row justify-content-between align-items-center gap-3">
                                <div>
                                    <h5 class="fw-bold text-primary mb-1"><i class="bi bi-file-earmark-arrow-down me-2"></i> Downloadable Resource Available</h5>
                                    <p class="text-muted small mb-0">Download the reference PDF / document attached to this publication.</p>
                                </div>
                                <?php if (isLoggedIn()): ?>
                                    <a href="<?= BASE_URL ?>user/download.php?id=<?= $blog['id'] ?>" class="btn btn-success btn-lg fw-bold px-4">
                                        <i class="bi bi-download me-2"></i> Download File
                                    </a>
                                <?php else: ?>
                                    <a href="<?= BASE_URL ?>auth/login.php" class="btn btn-warning fw-bold">
                                        <i class="bi bi-lock me-1"></i> Login to Download
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <hr class="my-5">

                    <!-- Question / Query Form Section -->
                    <div id="query-form-section" class="bg-light p-4 rounded-3 border">
                        <h4 class="fw-bold mb-2"><i class="bi bi-question-circle text-primary me-2"></i> Ask About This Blog</h4>
                        <p class="text-muted small mb-4">Have questions, need clarification, or facing issues with downloadable code/PDFs? Send a direct message to the author.</p>

                        <?php if ($querySuccess): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="bi bi-check-circle-fill me-2"></i> <?= htmlspecialchars($querySuccess) ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <?php if ($queryError): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <?= htmlspecialchars($queryError) ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <?php if (isLoggedIn()): ?>
                            <form action="<?= BASE_URL ?>user/blog_details.php?id=<?= $blog['id'] ?>#query-form-section" method="POST">
                                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                                <input type="hidden" name="submit_query" value="1">

                                <div class="mb-3">
                                    <label for="subject" class="form-label fw-semibold">Subject / Question Summary</label>
                                    <input type="text" class="form-control" id="subject" name="subject" required placeholder="e.g. Question regarding code snippet on line 42" value="<?= htmlspecialchars($_POST['subject'] ?? '') ?>">
                                </div>

                                <div class="mb-3">
                                    <label for="message" class="form-label fw-semibold">Your Question / Clarification Request</label>
                                    <textarea class="form-control" id="message" name="message" rows="4" required placeholder="Describe what you would like the author to clarify..."><?= htmlspecialchars($_POST['message'] ?? '') ?></textarea>
                                </div>

                                <button type="submit" class="btn btn-primary fw-bold px-4">
                                    <i class="bi bi-send me-1"></i> Send Question to Author
                                </button>
                            </form>
                        <?php else: ?>
                            <div class="text-center py-3">
                                <p class="text-muted mb-2">You must be signed in to submit questions regarding this blog.</p>
                                <a href="<?= BASE_URL ?>auth/login.php" class="btn btn-outline-primary"><i class="bi bi-box-arrow-in-right me-1"></i> Log In to Ask Question</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </article>
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            <!-- Author Card -->
            <div class="card border-0 shadow-sm mb-4 rounded-3 text-center p-4">
                <i class="bi bi-person-badge display-3 text-primary mb-2"></i>
                <h5 class="fw-bold mb-1"><?= htmlspecialchars($blog['author_name'] ?? 'Author') ?></h5>
                <p class="text-muted small mb-3">BlogSphere Staff Writer</p>
                <button id="btn-ask-question" class="btn btn-outline-primary btn-sm rounded-pill w-100">
                    <i class="bi bi-chat-text me-1"></i> Ask Author a Question
                </button>
            </div>

            <!-- Related Articles -->
            <?php if (!empty($relatedBlogs)): ?>
                <div class="card border-0 shadow-sm rounded-3">
                    <div class="card-header bg-white py-3 border-bottom">
                        <h5 class="fw-bold mb-0"><i class="bi bi-journals text-primary me-2"></i> Related Articles</h5>
                    </div>
                    <div class="list-group list-group-flush">
                        <?php foreach ($relatedBlogs as $rel): ?>
                            <a href="<?= BASE_URL ?>user/blog_details.php?id=<?= $rel['id'] ?>" class="list-group-item list-group-item-action p-3">
                                <span class="badge bg-secondary mb-1"><?= htmlspecialchars($rel['category_name']) ?></span>
                                <h6 class="fw-bold mb-1 text-dark"><?= htmlspecialchars($rel['title']) ?></h6>
                                <small class="text-muted"><i class="bi bi-calendar3 me-1"></i> <?= formatDate($rel['created_at']) ?></small>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
