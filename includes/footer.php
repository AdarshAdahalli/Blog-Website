</main>
<footer class="bg-dark text-white pt-5 pb-4 mt-5 border-top border-secondary">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-4 col-md-6">
                <h5 class="text-primary fw-bold mb-3 d-flex align-items-center">
                    <i class="bi bi-journal-richtext fs-4 me-2"></i> BlogSphere
                </h5>
                <p class="text-light opacity-75 small leading-relaxed">
                    A professional blog writing, editorial review, and publishing platform built with standard LAMP stack. Empowering writers, editors, and readers.
                </p>
            </div>
            
            <div class="col-lg-4 col-md-6">
                <h6 class="text-white fw-bold mb-3">Quick Navigation</h6>
                <ul class="list-unstyled mb-0">
                    <li class="mb-2">
                        <a href="<?= BASE_URL ?>index.php" class="text-light text-decoration-none small d-inline-flex align-items-center">
                            <i class="bi bi-chevron-right text-primary me-2"></i> Home
                        </a>
                    </li>
                    <li class="mb-2">
                        <a href="<?= BASE_URL ?>user/blogs.php" class="text-light text-decoration-none small d-inline-flex align-items-center">
                            <i class="bi bi-chevron-right text-primary me-2"></i> Published Articles
                        </a>
                    </li>
                    <li class="mb-2">
                        <a href="<?= BASE_URL ?>auth/login.php" class="text-light text-decoration-none small d-inline-flex align-items-center">
                            <i class="bi bi-chevron-right text-primary me-2"></i> Writer &amp; Admin Portal
                        </a>
                    </li>
                </ul>
            </div>
            
            <div class="col-lg-4 col-md-12">
                <h6 class="text-white fw-bold mb-3">System Architecture</h6>
                <div class="small text-light opacity-90">
                    <p class="mb-2 d-flex align-items-center"><i class="bi bi-cpu text-info fs-5 me-2"></i> LAMP (Linux, Apache, MySQL, PHP 8.2+)</p>
                    <p class="mb-2 d-flex align-items-center"><i class="bi bi-shield-check text-success fs-5 me-2"></i> PDO Prepared Statements &amp; BCRYPT Security</p>
                    <p class="mb-0 d-flex align-items-center"><i class="bi bi-clock-history text-warning fs-5 me-2"></i> UTC Server Time: <?= date('Y-m-d H:i:s') ?></p>
                </div>
            </div>
        </div>

        <hr class="border-secondary my-4">

        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-center text-light opacity-75 small">
            <p class="mb-2 mb-sm-0">&copy; <?= date('Y') ?> <strong>BlogSphere Platform</strong>. All Rights Reserved.</p>
            <p class="mb-0">Engineered with Bootstrap 5 &amp; PHP 8.2</p>
        </div>
    </div>
</footer>

<!-- Local & CDN Bootstrap 5 JS Bundle -->
<script src="<?= BASE_URL ?>assets/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<!-- Custom JavaScript -->
<script src="<?= BASE_URL ?>assets/js/main.js"></script>
<script src="<?= BASE_URL ?>assets/js/validation.js"></script>
<?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
    <script src="<?= BASE_URL ?>assets/js/admin.js"></script>
<?php elseif (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'employee'): ?>
    <script src="<?= BASE_URL ?>assets/js/employee.js"></script>
<?php elseif (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'user'): ?>
    <script src="<?= BASE_URL ?>assets/js/user.js"></script>
<?php endif; ?>
</body>
</html>
