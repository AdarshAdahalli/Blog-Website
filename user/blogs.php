<?php
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/functions.php';

$pdo = getDBConnection();

// Search & Filter parameters
$search = sanitizeInput($_GET['search'] ?? '');
$categoryId = isset($_GET['category']) ? (int)$_GET['category'] : 0;

// Pagination setup
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 6;
$offset = ($page - 1) * $limit;

// SQL Query Construction - Strictly Approved Blogs ONLY
$whereClauses = ["b.status = 'approved'"];
$params = [];

if (!empty($search)) {
    $whereClauses[] = "(b.title LIKE ? OR b.short_description LIKE ? OR b.content LIKE ? OR u.name LIKE ? OR c.name LIKE ?)";
    $searchTerm = "%{$search}%";
    $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm]);
}

if ($categoryId > 0) {
    $whereClauses[] = "b.category_id = ?";
    $params[] = $categoryId;
}

$whereSql = implode(' AND ', $whereClauses);

// Count Total Approved Blogs matching filter
$countSql = "SELECT COUNT(*) FROM blogs b LEFT JOIN users u ON b.employee_id = u.id LEFT JOIN categories c ON b.category_id = c.id WHERE {$whereSql}";
$stmtCount = $pdo->prepare($countSql);
$stmtCount->execute($params);
$totalBlogs = (int)$stmtCount->fetchColumn();
$totalPages = ceil($totalBlogs / $limit);

// Fetch Approved Blogs
$sql = "
    SELECT b.*, c.name AS category_name, u.name AS author_name 
    FROM blogs b
    LEFT JOIN categories c ON b.category_id = c.id
    LEFT JOIN users u ON b.employee_id = u.id
    WHERE {$whereSql}
    ORDER BY b.created_at DESC
    LIMIT {$limit} OFFSET {$offset}
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$blogs = $stmt->fetchAll();

// Categories for Filter
$categories = $pdo->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();

$pageTitle = 'Browse Approved Blogs & Articles — BlogSphere';
$metaDescription = 'Explore our library of approved articles, tech guides, and downloadable resources across various categories on BlogSphere.';
$canonicalUrl = BASE_URL . 'user/blogs.php' . (!empty($_GET['category']) ? '?category=' . (int)$_GET['category'] : '');

include __DIR__ . '/../includes/header.php';
?>

<div class="container py-5">
    <div class="row mb-4 align-items-center">
        <div class="col-md-6">
            <h2 class="fw-bold mb-1"><i class="bi bi-grid-3x3-gap-fill text-primary me-2"></i> Published Articles</h2>
            <p class="text-muted mb-0">Showing <?= $totalBlogs ?> approved articles published by our editorial team</p>
        </div>
    </div>

    <!-- Search & Filter Bar -->
    <div class="card border-0 shadow-sm mb-4 rounded-3">
        <div class="card-body p-3">
            <form action="<?= BASE_URL ?>user/blogs.php" method="GET" class="row g-3">
                <div class="col-md-6">
                    <div class="input-group">
                        <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" name="search" class="form-control border-start-0" placeholder="Search by title, description, content, or author..." value="<?= htmlspecialchars($search) ?>">
                    </div>
                </div>
                <div class="col-md-4">
                    <select name="category" class="form-select">
                        <option value="0">All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= $categoryId == $cat['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2 d-grid">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-funnel me-1"></i> Filter</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Blog Grid -->
    <div class="row g-4">
        <?php if (empty($blogs)): ?>
            <div class="col-12 text-center py-5">
                <i class="bi bi-search-heart display-1 text-muted"></i>
                <h4 class="mt-3 text-secondary">No approved blogs match your criteria.</h4>
                <p class="text-muted">Try clearing your filters or search terms.</p>
                <a href="<?= BASE_URL ?>user/blogs.php" class="btn btn-outline-primary">Reset Filters</a>
            </div>
        <?php else: ?>
            <?php foreach ($blogs as $blog): ?>
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
                                <a href="<?= BASE_URL ?>user/blog_details.php?id=<?= $blog['id'] ?>" class="text-decoration-none text-dark">
                                    <?= htmlspecialchars($blog['title']) ?>
                                </a>
                            </h5>
                            <p class="card-text text-muted small flex-grow-1">
                                <?= htmlspecialchars(substr($blog['short_description'] ?? strip_tags($blog['content']), 0, 120)) ?>...
                            </p>
                            
                            <div class="blog-meta-bar mt-3 pt-2 d-flex justify-content-between align-items-center text-muted small">
                                <span><i class="bi bi-person me-1"></i> <?= htmlspecialchars($blog['author_name'] ?? 'Author') ?></span>
                                <span><i class="bi bi-calendar3 me-1"></i> <?= formatDate($blog['created_at']) ?></span>
                            </div>
                        </div>

                        <div class="card-footer bg-white border-top-0 d-flex justify-content-between align-items-center pb-3 px-4">
                            <a href="<?= BASE_URL ?>user/blog_details.php?id=<?= $blog['id'] ?>" class="btn btn-sm btn-outline-primary fw-semibold">
                                Read More <i class="bi bi-chevron-right"></i>
                            </a>
                            <?php if (!empty($blog['downloadable_file'])): ?>
                                <a href="<?= BASE_URL ?>user/download.php?id=<?= $blog['id'] ?>" class="btn btn-sm btn-success fw-semibold" title="Download Resource File">
                                    <i class="bi bi-download"></i> Download
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
        <nav class="mt-5" aria-label="Blog Pagination">
            <ul class="pagination justify-content-center">
                <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                    <a class="page-link" href="<?= BASE_URL ?>user/blogs.php?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&category=<?= $categoryId ?>">Previous</a>
                </li>
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                        <a class="page-link" href="<?= BASE_URL ?>user/blogs.php?page=<?= $i ?>&search=<?= urlencode($search) ?>&category=<?= $categoryId ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>
                <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                    <a class="page-link" href="<?= BASE_URL ?>user/blogs.php?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&category=<?= $categoryId ?>">Next</a>
                </li>
            </ul>
        </nav>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
