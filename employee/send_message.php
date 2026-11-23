<?php
require_once __DIR__ . '/../includes/employee_auth.php';

$pdo = getDBConnection();
$employeeId = $_SESSION['user_id'];
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $receiverId = (int)($_POST['receiver_id'] ?? 0);
    $subject = sanitizeInput($_POST['subject'] ?? '');
    $message = sanitizeInput($_POST['message'] ?? '');
    $blogId = isset($_POST['blog_id']) && $_POST['blog_id'] !== '' ? (int)$_POST['blog_id'] : null;
    $parentId = isset($_POST['parent_id']) && $_POST['parent_id'] !== '' ? (int)$_POST['parent_id'] : null;
    $csrfToken = $_POST['csrf_token'] ?? '';

    if (!verifyCSRFToken($csrfToken)) {
        $error = "Invalid CSRF token.";
    } elseif ($receiverId <= 0 || empty($subject) || empty($message)) {
        $error = "Recipient, subject, and message content are required.";
    } else {
        $result = sendMessage($employeeId, $receiverId, $subject, $message, $blogId, $parentId);
        if ($result) {
            createNotification(
                $receiverId,
                "New Message ✉️",
                "Employee " . $_SESSION['user_name'] . " sent a message: " . $subject,
                "info",
                $blogId
            );
            setFlash('success', 'Message sent successfully.');
            redirect(BASE_URL . 'employee/messages.php');
        } else {
            $error = "Failed to send message. Please try again.";
        }
    }
}

// Prefilled GET parameters
$prefillBlogId = (int)($_GET['blog_id'] ?? 0);
$prefillSubject = sanitizeInput($_GET['subject'] ?? '');

// Fetch list of recipients (Admins and Users)
$recipients = $pdo->query("SELECT id, name, role, email FROM users WHERE id != {$employeeId} AND status = 'active' ORDER BY role ASC, name ASC")->fetchAll();

// Default recipient if asking admin
$adminId = 0;
foreach ($recipients as $r) {
    if ($r['role'] === 'admin') {
        $adminId = $r['id'];
        break;
    }
}

$pageTitle = 'Compose Message — BlogSphere';
include __DIR__ . '/../includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-7 col-lg-6">
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-header bg-white py-3 border-bottom">
                    <h5 class="fw-bold mb-0"><i class="bi bi-pencil-square text-primary me-2"></i> Send Message / Ask Admin</h5>
                </div>
                <div class="card-body p-4">
                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show">
                            <?= htmlspecialchars($error) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <form action="<?= BASE_URL ?>employee/send_message.php" method="POST">
                        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                        <?php if ($prefillBlogId > 0): ?>
                            <input type="hidden" name="blog_id" value="<?= $prefillBlogId ?>">
                        <?php endif; ?>

                        <div class="mb-3">
                            <label for="receiver_id" class="form-label fw-bold">Recipient</label>
                            <select name="receiver_id" id="receiver_id" class="form-select" required>
                                <option value="">-- Choose Recipient --</option>
                                <?php foreach ($recipients as $rec): ?>
                                    <option value="<?= $rec['id'] ?>" <?= ($adminId == $rec['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($rec['name']) ?> (<?= strtoupper($rec['role']) ?> - <?= htmlspecialchars($rec['email']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="subject" class="form-label fw-bold">Subject</label>
                            <input type="text" name="subject" id="subject" class="form-control" required placeholder="e.g. Clarification regarding rejection feedback" value="<?= htmlspecialchars($prefillSubject) ?>">
                        </div>

                        <div class="mb-3">
                            <label for="message" class="form-label fw-bold">Message / Question Details</label>
                            <textarea name="message" id="message" rows="5" class="form-control" required placeholder="Write your question or message for the administrator..."></textarea>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mt-4">
                            <a href="<?= BASE_URL ?>employee/messages.php" class="btn btn-outline-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary px-4 fw-bold"><i class="bi bi-send me-1"></i> Send Message</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
