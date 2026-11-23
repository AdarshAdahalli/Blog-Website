<?php
require_once __DIR__ . '/../includes/admin_auth.php';

$pdo = getDBConnection();
$error = '';
$action = $_GET['action'] ?? 'list';
$empId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Handle Employee Request Approval / Acceptance GET Actions
if (isset($_GET['accept_employee']) && $empId > 0) {
    $stmtAcc = $pdo->prepare("UPDATE users SET status = 'active' WHERE id = ? AND role = 'employee'");
    if ($stmtAcc->execute([$empId])) {
        // Fetch employee details
        $stmtEmpInfo = $pdo->prepare("SELECT name FROM users WHERE id = ?");
        $stmtEmpInfo->execute([$empId]);
        $empName = $stmtEmpInfo->fetchColumn();

        createNotification(
            $empId,
            "Employee Account Approved! 🎉",
            "Congratulations " . $empName . "! Your Employee Writer registration request has been accepted by the Administrator. You can now log in.",
            "success"
        );
        setFlash('success', 'Employee registration request accepted! Account is now active.');
    } else {
        setFlash('danger', 'Failed to accept employee request.');
    }
    redirect(BASE_URL . 'admin/employees.php');
}

if (isset($_GET['reject_employee']) && $empId > 0) {
    $pdo->prepare("UPDATE users SET status = 'blocked' WHERE id = ? AND role = 'employee'")->execute([$empId]);
    setFlash('warning', 'Employee registration request rejected and account blocked.');
    redirect(BASE_URL . 'admin/employees.php');
}

// Handle Add / Edit Employee Post Form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitizeInput($_POST['name'] ?? '');
    $username = sanitizeInput($_POST['username'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $status = sanitizeInput($_POST['status'] ?? 'active');
    $formAction = $_POST['form_action'] ?? 'add';
    $targetEmpId = (int)($_POST['emp_id'] ?? 0);
    $csrfToken = $_POST['csrf_token'] ?? '';

    if (!verifyCSRFToken($csrfToken)) {
        $error = "Invalid CSRF token.";
    } elseif (empty($name) || empty($username) || empty($email)) {
        $error = "Name, Username, and Email are required.";
    } else {
        if ($formAction === 'add') {
            if (empty($password)) {
                $error = "Password is required when creating a new employee account.";
            } else {
                $stmtCheck = $pdo->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
                $stmtCheck->execute([$email, $username]);
                if ($stmtCheck->fetch()) {
                    $error = "An account with this email address or username already exists.";
                } else {
                    $hashedPass = password_hash($password, PASSWORD_DEFAULT);
                    $stmtIns = $pdo->prepare("INSERT INTO users (username, name, email, password, role, status, created_at) VALUES (?, ?, ?, ?, 'employee', ?, NOW())");
                    if ($stmtIns->execute([$username, $name, $email, $hashedPass, $status])) {
                        setFlash('success', 'Employee account created successfully!');
                        redirect(BASE_URL . 'admin/employees.php');
                    } else {
                        $error = "Failed to create employee.";
                    }
                }
            }
        } elseif ($formAction === 'edit' && $targetEmpId > 0) {
            $stmtCheck = $pdo->prepare("SELECT id FROM users WHERE (email = ? OR username = ?) AND id != ?");
            $stmtCheck->execute([$email, $username, $targetEmpId]);
            if ($stmtCheck->fetch()) {
                $error = "Email address or username is used by another account.";
            } else {
                if (!empty($password)) {
                    $hashedPass = password_hash($password, PASSWORD_DEFAULT);
                    $stmtUpd = $pdo->prepare("UPDATE users SET name = ?, username = ?, email = ?, password = ?, status = ? WHERE id = ? AND role = 'employee'");
                    $stmtUpd->execute([$name, $username, $email, $hashedPass, $status, $targetEmpId]);
                } else {
                    $stmtUpd = $pdo->prepare("UPDATE users SET name = ?, username = ?, email = ?, status = ? WHERE id = ? AND role = 'employee'");
                    $stmtUpd->execute([$name, $username, $email, $status, $targetEmpId]);
                }
                setFlash('success', 'Employee details updated successfully.');
                redirect(BASE_URL . 'admin/employees.php');
            }
        }
    }
}

// Handle Status Toggle / Delete via GET
if (isset($_GET['status_change'])) {
    $newStatus = sanitizeInput($_GET['status_change']);
    if (in_array($newStatus, ['active', 'inactive', 'blocked']) && $empId > 0) {
        $pdo->prepare("UPDATE users SET status = ? WHERE id = ? AND role = 'employee'")->execute([$newStatus, $empId]);
        setFlash('info', 'Employee status updated to ' . $newStatus);
        redirect(BASE_URL . 'admin/employees.php');
    }
}

if (isset($_GET['delete_emp']) && $empId > 0) {
    $pdo->prepare("DELETE FROM users WHERE id = ? AND role = 'employee'")->execute([$empId]);
    setFlash('success', 'Employee account deleted.');
    redirect(BASE_URL . 'admin/employees.php');
}

// Fetch Pending Employee Requests (status = 'inactive')
$stmtPendingEmps = $pdo->query("SELECT * FROM users WHERE role = 'employee' AND status = 'inactive' ORDER BY created_at DESC");
$pendingEmployees = $stmtPendingEmps->fetchAll();

// Fetch Active & Blocked Employees with Statistics Aggregation
$stmtEmployees = $pdo->query("
    SELECT u.*,
           COUNT(DISTINCT b.id) as total_blogs,
           SUM(CASE WHEN b.status = 'approved' THEN 1 ELSE 0 END) as approved_blogs,
           SUM(CASE WHEN b.status = 'pending' THEN 1 ELSE 0 END) as pending_blogs,
           SUM(CASE WHEN b.status = 'rejected' THEN 1 ELSE 0 END) as rejected_blogs,
           COUNT(DISTINCT bd.id) as total_downloads
    FROM users u
    LEFT JOIN blogs b ON u.id = b.employee_id
    LEFT JOIN blog_downloads bd ON b.id = bd.blog_id
    WHERE u.role = 'employee' AND u.status != 'inactive'
    GROUP BY u.id
    ORDER BY u.created_at DESC
");
$employeesList = $stmtEmployees->fetchAll();

// Edit Employee Object
$editEmp = null;
if ($action === 'edit' && $empId > 0) {
    $stmtEdit = $pdo->prepare("SELECT * FROM users WHERE id = ? AND role = 'employee'");
    $stmtEdit->execute([$empId]);
    $editEmp = $stmtEdit->fetch();
}

$pageTitle = 'Employee Management — BlogSphere Admin';
include __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold mb-0"><i class="bi bi-people text-primary me-2"></i> Employee Management &amp; Approvals</h3>
            <p class="text-muted small mb-0">Accept employee registration requests, monitor productivity, and manage accounts</p>
        </div>
        <?php if ($action !== 'add'): ?>
            <a href="<?= BASE_URL ?>admin/employees.php?action=add" class="btn btn-primary fw-bold"><i class="bi bi-person-plus me-1"></i> Add New Employee</a>
        <?php else: ?>
            <a href="<?= BASE_URL ?>admin/employees.php" class="btn btn-outline-secondary">Back to Employee List</a>
        <?php endif; ?>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <?= htmlspecialchars($error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Pending Employee Approval Requests Section -->
    <?php if (!empty($pendingEmployees)): ?>
        <div class="card border-0 shadow-sm mb-4 rounded-3 border-start border-warning border-4">
            <div class="card-header bg-warning bg-opacity-10 py-3 border-bottom d-flex justify-content-between align-items-center">
                <h5 class="fw-bold mb-0 text-dark">
                    <i class="bi bi-clock-history me-2"></i> Pending Employee Approval Requests 
                    <span class="badge bg-warning text-dark ms-2"><?= count($pendingEmployees) ?> Awaiting Acceptance</span>
                </h5>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Applicant Name</th>
                            <th>Username</th>
                            <th>Email Address</th>
                            <th>Registration Date</th>
                            <th>Decision Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pendingEmployees as $pEmp): ?>
                            <tr>
                                <td><strong class="text-dark"><?= htmlspecialchars($pEmp['name']) ?></strong></td>
                                <td><code class="text-primary"><?= htmlspecialchars($pEmp['username']) ?></code></td>
                                <td><?= htmlspecialchars($pEmp['email']) ?></td>
                                <td><small class="text-muted"><?= formatDate($pEmp['created_at'], 'M d, Y H:i') ?></small></td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="<?= BASE_URL ?>admin/employees.php?accept_employee=1&id=<?= $pEmp['id'] ?>" class="btn btn-success fw-bold" onclick="return confirm('Accept and activate this employee writer account?');">
                                            <i class="bi bi-check-circle me-1"></i> Accept &amp; Activate
                                        </a>
                                        <a href="<?= BASE_URL ?>admin/employees.php?reject_employee=1&id=<?= $pEmp['id'] ?>" class="btn btn-outline-danger" onclick="return confirm('Reject and block this registration request?');">
                                            <i class="bi bi-x-circle me-1"></i> Reject
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

    <!-- Add / Edit Employee Form Section -->
    <?php if ($action === 'add' || ($action === 'edit' && $editEmp)): ?>
        <div class="card border-0 shadow-sm mb-4 rounded-3">
            <div class="card-header bg-white py-3 border-bottom">
                <h5 class="fw-bold mb-0"><?= $action === 'add' ? '<i class="bi bi-person-plus text-success me-2"></i> Create Employee Account' : '<i class="bi bi-pencil text-primary me-2"></i> Edit Employee Account' ?></h5>
            </div>
            <div class="card-body p-4">
                <form action="<?= BASE_URL ?>admin/employees.php" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    <input type="hidden" name="form_action" value="<?= $action ?>">
                    <?php if ($action === 'edit'): ?>
                        <input type="hidden" name="emp_id" value="<?= $editEmp['id'] ?>">
                    <?php endif; ?>

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label for="name" class="form-label fw-bold">Full Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="name" class="form-control" required placeholder="John Writer" value="<?= htmlspecialchars($_POST['name'] ?? ($editEmp['name'] ?? '')) ?>">
                        </div>
                        <div class="col-md-4">
                            <label for="username" class="form-label fw-bold">Username <span class="text-danger">*</span></label>
                            <input type="text" name="username" id="username" class="form-control" required placeholder="johnwriter" value="<?= htmlspecialchars($_POST['username'] ?? ($editEmp['username'] ?? '')) ?>">
                        </div>
                        <div class="col-md-4">
                            <label for="email" class="form-label fw-bold">Email Address <span class="text-danger">*</span></label>
                            <input type="email" name="email" id="email" class="form-control" required placeholder="writer@blogsphere.com" value="<?= htmlspecialchars($_POST['email'] ?? ($editEmp['email'] ?? '')) ?>">
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label for="password" class="form-label fw-bold">Password <?= $action === 'edit' ? '(leave blank to keep current)' : '<span class="text-danger">*</span>' ?></label>
                            <input type="password" name="password" id="password" class="form-control" <?= $action === 'add' ? 'required' : '' ?> placeholder="••••••••">
                        </div>
                        <div class="col-md-6">
                            <label for="status" class="form-label fw-bold">Account Status</label>
                            <select name="status" id="status" class="form-select">
                                <option value="active" <?= (($_POST['status'] ?? ($editEmp['status'] ?? '')) === 'active') ? 'selected' : '' ?>>Active (Can Login)</option>
                                <option value="inactive" <?= (($_POST['status'] ?? ($editEmp['status'] ?? '')) === 'inactive') ? 'selected' : '' ?>>Inactive (Pending Approval)</option>
                                <option value="blocked" <?= (($_POST['status'] ?? ($editEmp['status'] ?? '')) === 'blocked') ? 'selected' : '' ?>>Blocked</option>
                            </select>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <a href="<?= BASE_URL ?>admin/employees.php" class="btn btn-outline-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary px-4 fw-bold"><?= $action === 'add' ? 'Create Account' : 'Save Changes' ?></button>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <!-- Active & Blocked Employees List Table -->
    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-header bg-white py-3 border-bottom">
            <h5 class="fw-bold mb-0"><i class="bi bi-people-fill text-primary me-2"></i> Employee Roster &amp; Productivity</h5>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Employee Writer</th>
                        <th>Username</th>
                        <th>Status</th>
                        <th>Total Blogs</th>
                        <th>Approved</th>
                        <th>Pending</th>
                        <th>Rejected</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($employeesList)): ?>
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">No active employees found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($employeesList as $emp): ?>
                            <tr>
                                <td>
                                    <strong class="d-block text-dark"><?= htmlspecialchars($emp['name']) ?></strong>
                                    <small class="text-muted"><?= htmlspecialchars($emp['email']) ?></small>
                                </td>
                                <td><code class="text-primary"><?= htmlspecialchars($emp['username']) ?></code></td>
                                <td>
                                    <?php if ($emp['status'] === 'active'): ?>
                                        <span class="badge bg-success">Active</span>
                                    <?php elseif ($emp['status'] === 'inactive'): ?>
                                        <span class="badge bg-warning text-dark">Pending Approval</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Blocked</span>
                                    <?php endif; ?>
                                </td>
                                <td><span class="fw-bold"><?= (int)$emp['total_blogs'] ?></span></td>
                                <td><span class="badge bg-success"><?= (int)$emp['approved_blogs'] ?></span></td>
                                <td><span class="badge bg-warning text-dark"><?= (int)$emp['pending_blogs'] ?></span></td>
                                <td><span class="badge bg-danger"><?= (int)$emp['rejected_blogs'] ?></span></td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="<?= BASE_URL ?>admin/employees.php?action=edit&id=<?= $emp['id'] ?>" class="btn btn-outline-primary" title="Edit Employee"><i class="bi bi-pencil"></i></a>
                                        <a href="<?= BASE_URL ?>admin/blogs.php?employee=<?= $emp['id'] ?>" class="btn btn-outline-secondary" title="View Employee Blogs"><i class="bi bi-journals"></i> Blogs</a>
                                        
                                        <?php if ($emp['status'] === 'active'): ?>
                                            <a href="<?= BASE_URL ?>admin/employees.php?status_change=blocked&id=<?= $emp['id'] ?>" class="btn btn-outline-warning" title="Block Employee"><i class="bi bi-slash-circle"></i> Block</a>
                                        <?php else: ?>
                                            <a href="<?= BASE_URL ?>admin/employees.php?status_change=active&id=<?= $emp['id'] ?>" class="btn btn-outline-success" title="Activate Employee"><i class="bi bi-check-circle"></i> Accept</a>
                                        <?php endif; ?>
                                        
                                        <a href="<?= BASE_URL ?>admin/employees.php?delete_emp=1&id=<?= $emp['id'] ?>" class="btn btn-outline-danger" onclick="return confirm('Delete this employee account and all their manuscripts?');" title="Delete Employee"><i class="bi bi-trash"></i></a>
                                    </div>
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
