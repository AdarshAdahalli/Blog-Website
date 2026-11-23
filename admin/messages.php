<?php
require_once __DIR__ . '/../includes/admin_auth.php';

$pdo = getDBConnection();
$adminId = $_SESSION['user_id'];

if (isset($_GET['action']) && $_GET['action'] === 'delete') {
    $msgId = (int)($_GET['msg_id'] ?? 0);
    if ($msgId > 0) {
        $pdo->prepare("DELETE FROM messages WHERE id = ? OR parent_message_id = ?")->execute([$msgId, $msgId]);
        setFlash('success', 'Message thread deleted.');
        redirect(BASE_URL . 'admin/messages.php');
    }
}

// Fetch Thread List
$stmt = $pdo->prepare("
    SELECT m.*, 
           sender.name AS sender_name, sender.role AS sender_role,
           receiver.name AS receiver_name, receiver.role AS receiver_role,
           b.title AS blog_title
    FROM messages m
    JOIN users sender ON m.sender_id = sender.id
    JOIN users receiver ON m.receiver_id = receiver.id
    LEFT JOIN blogs b ON m.blog_id = b.id
    WHERE m.receiver_id = ? OR m.sender_id = ?
    ORDER BY m.created_at DESC
");
$stmt->execute([$adminId, $adminId]);
$messages = $stmt->fetchAll();

// Active Conversation Thread
$activeMsgId = isset($_GET['thread_id']) ? (int)$_GET['thread_id'] : (count($messages) > 0 ? $messages[0]['id'] : 0);
$conversation = [];
$activeMainMsg = null;

if ($activeMsgId > 0) {
    $stmtFindRoot = $pdo->prepare("SELECT * FROM messages WHERE id = ?");
    $stmtFindRoot->execute([$activeMsgId]);
    $activeMainMsg = $stmtFindRoot->fetch();

    if ($activeMainMsg) {
        $rootId = $activeMainMsg['parent_message_id'] ? $activeMainMsg['parent_message_id'] : $activeMainMsg['id'];

        if ($activeMainMsg['receiver_id'] == $adminId && $activeMainMsg['status'] === 'unread') {
            $pdo->prepare("UPDATE messages SET status = 'read' WHERE id = ?")->execute([$activeMainMsg['id']]);
        }

        $stmtThread = $pdo->prepare("
            SELECT m.*, sender.name as sender_name, sender.role as sender_role 
            FROM messages m
            JOIN users sender ON m.sender_id = sender.id
            WHERE m.id = ? OR m.parent_message_id = ? OR m.id = (SELECT parent_message_id FROM messages WHERE id = ?)
            ORDER BY m.created_at ASC
        ");
        $stmtThread->execute([$rootId, $rootId, $rootId]);
        $conversation = $stmtThread->fetchAll();
    }
}

$pageTitle = 'Admin Messages Inbox — BlogSphere';
include __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold mb-0"><i class="bi bi-envelope text-primary me-2"></i> System Communications &amp; Support Inbox</h3>
            <p class="text-muted small mb-0">Reply to employee manuscript queries and public user support questions</p>
        </div>
        <a href="<?= BASE_URL ?>admin/send_message.php" class="btn btn-primary"><i class="bi bi-pencil-square me-1"></i> New Announcement / Message</a>
    </div>

    <div class="row g-4">
        <!-- Thread Selection Sidebar -->
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
                <div class="card-header bg-white py-3 border-bottom">
                    <h6 class="fw-bold mb-0"><i class="bi bi-inbox me-2"></i> Message Threads</h6>
                </div>
                <div class="list-group list-group-flush" style="max-height: 550px; overflow-y: auto;">
                    <?php if (empty($messages)): ?>
                        <div class="text-center py-5 text-muted">
                            <i class="bi bi-chat-square-dots display-4"></i>
                            <p class="mt-2 mb-0">No messages in inbox.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($messages as $msg): ?>
                            <?php 
                                $isUnread = ($msg['receiver_id'] == $adminId && $msg['status'] === 'unread');
                                $isActive = ($msg['id'] == $activeMsgId);
                                $otherPerson = ($msg['sender_id'] == $adminId) ? $msg['receiver_name'] : $msg['sender_name'];
                                $otherRole = ($msg['sender_id'] == $adminId) ? $msg['receiver_role'] : $msg['sender_role'];
                            ?>
                            <a href="<?= BASE_URL ?>admin/messages.php?thread_id=<?= $msg['id'] ?>" class="list-group-item list-group-item-action p-3 <?= $isActive ? 'active' : '' ?> <?= $isUnread ? 'bg-light font-weight-bold' : '' ?>">
                                <div class="d-flex w-100 justify-content-between align-items-center mb-1">
                                    <h6 class="mb-0 text-truncate fw-bold <?= $isActive ? 'text-white' : 'text-dark' ?>">
                                        <?= htmlspecialchars($otherPerson) ?> 
                                        <span class="badge bg-secondary ms-1 text-capitalize" style="font-size: 0.65rem;"><?= htmlspecialchars($otherRole) ?></span>
                                    </h6>
                                    <small class="<?= $isActive ? 'text-white-50' : 'text-muted' ?>"><?= formatDate($msg['created_at'], 'M d') ?></small>
                                </div>
                                <div class="small text-truncate <?= $isActive ? 'text-white-50' : 'text-muted' ?> mb-1">
                                    <?= htmlspecialchars($msg['subject']) ?>
                                </div>
                                <?php if ($msg['blog_title']): ?>
                                    <span class="badge bg-info text-dark text-truncate d-inline-block" style="max-width: 200px;">
                                        Blog: <?= htmlspecialchars($msg['blog_title']) ?>
                                    </span>
                                <?php endif; ?>
                                <?php if ($isUnread): ?>
                                    <span class="badge bg-danger ms-2">Unread</span>
                                <?php endif; ?>
                            </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Thread Messages Box -->
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm rounded-3">
                <?php if ($activeMainMsg && !empty($conversation)): ?>
                    <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="fw-bold mb-0 text-dark"><?= htmlspecialchars($activeMainMsg['subject']) ?></h5>
                            <?php if ($activeMainMsg['blog_id']): ?>
                                <small class="text-muted">Regarding Blog Manuscript ID #<?= $activeMainMsg['blog_id'] ?></small>
                            <?php endif; ?>
                        </div>
                        <a href="<?= BASE_URL ?>admin/messages.php?action=delete&msg_id=<?= $activeMainMsg['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete message thread?');">
                            <i class="bi bi-trash"></i> Delete
                        </a>
                    </div>
                    <div class="card-body p-4" style="max-height: 400px; overflow-y: auto;">
                        <div class="d-flex flex-column gap-3">
                            <?php foreach ($conversation as $cMsg): ?>
                                <?php $isMe = ($cMsg['sender_id'] == $adminId); ?>
                                <div class="p-3 rounded-3 <?= $isMe ? 'bg-primary bg-opacity-10 align-self-end text-end ms-5 border border-primary border-opacity-25' : 'bg-light align-self-start text-start me-5 border' ?>" style="max-width: 85%;">
                                    <div class="d-flex align-items-center justify-content-between mb-1 gap-3">
                                        <strong class="small text-dark"><?= $isMe ? 'You (Admin)' : htmlspecialchars($cMsg['sender_name']) ?> <span class="badge bg-secondary text-capitalize"><?= htmlspecialchars($cMsg['sender_role']) ?></span></strong>
                                        <small class="text-muted" style="font-size: 0.75rem;"><?= formatDate($cMsg['created_at'], 'M d, Y H:i') ?></small>
                                    </div>
                                    <p class="mb-0 text-dark" style="white-space: pre-line;"><?= htmlspecialchars($cMsg['message']) ?></p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Admin Reply Form -->
                    <div class="card-footer bg-white p-3 border-top">
                        <form action="<?= BASE_URL ?>admin/send_message.php" method="POST">
                            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                            <?php 
                                $replyReceiverId = ($activeMainMsg['sender_id'] == $adminId) ? $activeMainMsg['receiver_id'] : $activeMainMsg['sender_id'];
                                $parentMsgId = $activeMainMsg['parent_message_id'] ? $activeMainMsg['parent_message_id'] : $activeMainMsg['id'];
                            ?>
                            <input type="hidden" name="receiver_id" value="<?= $replyReceiverId ?>">
                            <input type="hidden" name="parent_id" value="<?= $parentMsgId ?>">
                            <input type="hidden" name="blog_id" value="<?= $activeMainMsg['blog_id'] ?>">
                            <input type="hidden" name="subject" value="Re: <?= htmlspecialchars($activeMainMsg['subject']) ?>">

                            <div class="input-group">
                                <textarea name="message" class="form-control" rows="2" required placeholder="Type admin response..."></textarea>
                                <button type="submit" class="btn btn-primary px-4"><i class="bi bi-send-fill"></i> Send Reply</button>
                            </div>
                        </form>
                    </div>
                <?php else: ?>
                    <div class="card-body text-center py-5 text-muted">
                        <i class="bi bi-chat-text display-3"></i>
                        <h5 class="mt-3">Select a message thread to view conversation</h5>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
