<?php
require_once __DIR__ . '/../includes/admin_auth.php';

$pdo = getDBConnection();
$adminId = $_SESSION['user_id'];
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
        $result = sendMessage($adminId, $receiverId, $subject, $message, $blogId, $parentId);
        if ($result) {
            createNotification(
                $receiverId,
                "Message from Administrator 🛡️",
                "Admin sent you a message: " . $subject,
                "info",
                $blogId
            );
            setFlash('success', 'Admin message delivered successfully.');
            redirect(BASE_URL . 'admin/messages.php');
        } else {
            $error = "Failed to deliver message.";
        }
    }
}

// Fetch list of recipients (Employees and Users)
$recipients = $pdo->query("SELECT id, name, role, email FROM users WHERE id != {$adminId} AND status = 'active' ORDER BY role ASC, name ASC")->fetchAll();

$pageTitle = 'Compose Admin Message — BlogSphere';
include __DIR__ . '/../includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-7 col-lg-6">
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-header bg-dark text-white py-3">
                    <h5 class="fw-bold mb-0"><i class="bi bi-pencil-square me-2"></i> Send Admin Communication</h5>
                </div>
                <div class="card-body p-4">
                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show">
                            <?= htmlspecialchars($error) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <form action="<?= BASE_URL ?>admin/send_message.php" method="POST">
                        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">

                        <div class="mb-3">
                            <label for="receiver_id" class="form-label fw-bold">Select Recipient (Employee or User)</label>
                            <select name="receiver_id" id="receiver_id" class="form-select" required>
                                <option value="">-- Choose Recipient --</option>
                                <?php foreach ($recipients as $rec): ?>
                                    <option value="<?= $rec['id'] ?>">
                                        <?= htmlspecialchars($rec['name']) ?> (<?= strtoupper($rec['role']) ?> - <?= htmlspecialchars($rec['email']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="subject" class="form-label fw-bold">Subject</label>
                            <input type="text" name="subject" id="subject" class="form-control" required placeholder="e.g. Manuscript Submission Policy Update">
                        </div>

                        <div class="mb-3">
                            <label for="message" class="form-label fw-bold">Message Content</label>
                            <textarea name="message" id="message" rows="5" class="form-control" required placeholder="Type administrator message here..."></textarea>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mt-4">
                            <a href="<?= BASE_URL ?>admin/messages.php" class="btn btn-outline-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary px-4 fw-bold"><i class="bi bi-send me-1"></i> Deliver Message</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
