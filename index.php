<?php
require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/includes/functions.php';

$pdo = getDBConnection();

// Fetch approved featured blogs for home page display
$stmt = $pdo->prepare("
    SELECT b.*, c.name AS category_name, u.name AS author_name 
    FROM blogs b
    LEFT JOIN categories c ON b.category_id = c.id
    LEFT JOIN users u ON b.employee_id = u.id
    WHERE b.status = 'approved'
    ORDER BY b.created_at DESC
    LIMIT 6
");
$stmt->execute();
$featuredBlogs = $stmt->fetchAll();

// Fetch Categories with counts
$stmtCat = $pdo->prepare("
    SELECT c.*, COUNT(b.id) as blog_count 
    FROM categories c 
    LEFT JOIN blogs b ON c.id = b.category_id AND b.status = 'approved'
    GROUP BY c.id 
    ORDER BY c.name ASC
");
$stmtCat->execute();
$categories = $stmtCat->fetchAll();

$pageTitle = 'BlogSphere — Discover, Learn, and Share Quality Articles';
$metaDescription = 'BlogSphere is an editorial publishing platform featuring informative tech articles, programming guides, and tutorials from expert writers.';
$canonicalUrl = BASE_URL;

// WebSite Schema with SearchAction for Google Search Box
$schemaJson = [
    '@context' => 'https://schema.org',
    '@type' => 'WebSite',
    'name' => 'BlogSphere',
    'url' => BASE_URL,
    'potentialAction' => [
        '@type' => 'SearchAction',
        'target' => BASE_URL . 'user/blogs.php?search={search_term_string}',
        'query-input' => 'required name=search_term_string'
    ]
];

include __DIR__ . '/includes/header.php';
?>

<!-- Hero Section -->
<section class="hero-section text-center text-white py-5">
    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <span class="badge bg-primary px-3 py-2 rounded-pill text-uppercase mb-3 fs-6">Editorial Publishing Platform</span>
                <h1 class="display-3 fw-bold mb-3">Discover. Learn. Share.</h1>
                <p class="lead text-light opacity-90 mb-4 fs-4">Explore informative articles, tutorials, and tech guides written by our community of expert writers.</p>
                <div class="d-flex justify-content-center gap-3">
                    <a href="<?= BASE_URL ?>user/blogs.php" class="btn btn-primary btn-lg px-4 py-3 fw-bold rounded-3 shadow">
                        <i class="bi bi-compass me-2"></i> Browse Published Blogs
                    </a>
                    <?php if (!isLoggedIn()): ?>
                        <a href="<?= BASE_URL ?>auth/login.php" class="btn btn-outline-light btn-lg px-4 py-3 fw-bold rounded-3">
                            <i class="bi bi-box-arrow-in-right me-2"></i> Writer &amp; Reader Login
                        </a>
                    <?php else: ?>
                        <a href="<?= BASE_URL . $_SESSION['user_role'] ?>/dashboard.php" class="btn btn-outline-light btn-lg px-4 py-3 fw-bold rounded-3">
                            <i class="bi bi-speedometer2 me-2"></i> Go to Dashboard
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Featured Approved Blogs Section -->
<section class="py-5">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold mb-1"><i class="bi bi-stars text-warning me-2"></i> Featured Publications</h2>
                <p class="text-muted mb-0">Reviewed and approved articles written by domain experts</p>
            </div>
            <a href="<?= BASE_URL ?>user/blogs.php" class="btn btn-outline-primary fw-semibold">View All Blogs <i class="bi bi-arrow-right"></i></a>
        </div>

        <div class="row g-4">
            <?php if (empty($featuredBlogs)): ?>
                <div class="col-12 text-center py-5">
                    <i class="bi bi-journal-x display-1 text-muted"></i>
                    <h4 class="mt-3 text-secondary">No approved blogs available yet.</h4>
                    <p class="text-muted">Check back soon for freshly reviewed content!</p>
                </div>
            <?php else: ?>
                <?php foreach ($featuredBlogs as $blog): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card h-100 shadow-sm blog-card border-0 overflow-hidden">
                            <div class="position-relative">
                                <?php if ($blog['category_name']): ?>
                                    <span class="badge bg-primary category-badge position-absolute top-0 start-0 m-3 px-3 py-2 shadow-sm"><?= htmlspecialchars($blog['category_name']) ?></span>
                                <?php endif; ?>
                                
                                <?php 
                                    $defaultImg = BASE_URL . 'assets/images/default-blog.jpg';
                                    $imagePath = $defaultImg;
                                    if (!empty($blog['featured_image']) && file_exists(BLOG_IMAGE_UPLOAD_DIR . $blog['featured_image'])) {
                                        $imagePath = BASE_URL . 'uploads/blog_images/' . $blog['featured_image'];
                                    }
                                ?>
                                <img src="<?= $imagePath ?>" class="card-img-top blog-card-img" alt="" onerror="this.onerror=null; this.src='<?= $defaultImg ?>';">
                            </div>

                            <div class="card-body d-flex flex-column p-4">
                                <h5 class="card-title fw-bold text-dark mb-2">
                                    <a href="<?= BASE_URL ?>user/blog_details.php?id=<?= $blog['id'] ?>" class="text-decoration-none text-dark hover-primary">
                                        <?= htmlspecialchars($blog['title']) ?>
                                    </a>
                                </h5>
                                <p class="card-text text-muted small flex-grow-1">
                                    <?= htmlspecialchars(substr($blog['short_description'] ?? strip_tags($blog['content']), 0, 120)) ?>...
                                </p>
                                
                                <div class="blog-meta-bar mt-3 pt-2 d-flex justify-content-between align-items-center text-muted small">
                                    <span><i class="bi bi-person me-1"></i> <?= htmlspecialchars($blog['author_name'] ?? 'Unknown Author') ?></span>
                                    <span><i class="bi bi-calendar3 me-1"></i> <?= formatDate($blog['created_at']) ?></span>
                                </div>
                            </div>

                            <div class="card-footer bg-white border-top-0 d-flex justify-content-between align-items-center pb-3 px-4">
                                <a href="<?= BASE_URL ?>user/blog_details.php?id=<?= $blog['id'] ?>" class="btn btn-sm btn-outline-primary fw-semibold">
                                    Read More <i class="bi bi-chevron-right"></i>
                                </a>
                                <?php if (!empty($blog['downloadable_file'])): ?>
                                    <a href="<?= BASE_URL ?>user/download.php?id=<?= $blog['id'] ?>" class="btn btn-sm btn-success fw-semibold" title="Download Document">
                                        <i class="bi bi-download"></i> Download
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Category Filter Grid -->
<section id="categories" class="py-5 bg-white border-top border-bottom">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="fw-bold"><i class="bi bi-tags text-primary me-2"></i> Explore Categories</h2>
            <p class="text-muted">Find content categorized by industry topics and technical domains</p>
        </div>

        <div class="row g-3">
            <?php foreach ($categories as $cat): ?>
                <div class="col-6 col-md-4 col-lg-3">
                    <a href="<?= BASE_URL ?>user/blogs.php?category=<?= $cat['id'] ?>" class="text-decoration-none">
                        <div class="card h-100 border-0 shadow-sm text-center py-3 hover-shadow transition">
                            <div class="card-body">
                                <i class="bi bi-folder-fill text-primary display-6 mb-2"></i>
                                <h6 class="fw-bold text-dark mb-1"><?= htmlspecialchars($cat['name']) ?></h6>
                                <span class="badge bg-light text-secondary border"><?= $cat['blog_count'] ?> Published</span>
                            </div>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
