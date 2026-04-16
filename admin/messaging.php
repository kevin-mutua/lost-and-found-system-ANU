<?php
ob_start();
session_start();
define('BASE_URL', '/lost_and_found');
require_once '../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = 'Please login first.';
    header('Location: ' . BASE_URL . '/auth/login.php');
    exit();
}

if (($_SESSION['user_role'] ?? null) !== 'admin') {
    $_SESSION['error'] = 'Admin access required.';
    header('Location: ' . BASE_URL . '/index.php');
    exit();
}

require_once '../includes/functions.php';
require_once '../includes/admin_functions.php';

$securityPersonnel = getSecurityPersonnel($pdo);

// Get conversations for current admin user
$stmt = $pdo->prepare("
    SELECT DISTINCT 
        CASE 
            WHEN m.sender_id = ? THEN m.recipient_id 
            ELSE m.sender_id 
        END as other_user_id,
        u.name as other_user_name,
        u.email as other_user_email,
        MAX(m.created_at) as last_message_time,
        (SELECT body FROM messages 
         WHERE ((sender_id = ? AND recipient_id = CASE WHEN m.sender_id = ? THEN m.recipient_id ELSE m.sender_id END) 
            OR (sender_id = CASE WHEN m.sender_id = ? THEN m.recipient_id ELSE m.sender_id END AND recipient_id = ?))
         ORDER BY created_at DESC LIMIT 1) as last_message_preview,
        COUNT(CASE WHEN m.is_read = 0 AND m.recipient_id = ? THEN 1 END) as unread_count
    FROM messages m
    LEFT JOIN users u ON (
        (m.sender_id = ? AND u.id = m.recipient_id) OR 
        (m.recipient_id = ? AND u.id = m.sender_id)
    )
    WHERE (m.sender_id = ? OR m.recipient_id = ?)
    GROUP BY other_user_id
    ORDER BY last_message_time DESC
");
$stmt->execute([
    $_SESSION['user_id'], 
    $_SESSION['user_id'], $_SESSION['user_id'],
    $_SESSION['user_id'], $_SESSION['user_id'],
    $_SESSION['user_id'],
    $_SESSION['user_id'], $_SESSION['user_id'],
    $_SESSION['user_id'], $_SESSION['user_id']
]);
$conversations = $stmt->fetchAll();

$selected_user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : (count($conversations) > 0 ? $conversations[0]['other_user_id'] : 0);

$selected_user = null;
$messages = [];

if ($selected_user_id > 0) {
    // Get user details
    $stmt = $pdo->prepare("SELECT id, name, email FROM users WHERE id = ?");
    $stmt->execute([$selected_user_id]);
    $selected_user = $stmt->fetch();
    
    if ($selected_user) {
        // Get all messages in this conversation
        $stmt = $pdo->prepare("
            SELECT m.*, 
                   u.name as sender_name,
                   CASE WHEN m.sender_id = ? AND m.recipient_id = ? THEN 1 
                        WHEN m.sender_id = ? AND m.recipient_id = ? THEN 0 
                   END as is_sent
            FROM messages m
            LEFT JOIN users u ON m.sender_id = u.id
            WHERE (m.sender_id = ? AND m.recipient_id = ?) 
               OR (m.sender_id = ? AND m.recipient_id = ?)
            ORDER BY m.created_at ASC
        ");
        $stmt->execute([
            $_SESSION['user_id'], $selected_user_id,
            $selected_user_id, $_SESSION['user_id'],
            $_SESSION['user_id'], $selected_user_id,
            $selected_user_id, $_SESSION['user_id']
        ]);
        $messages = $stmt->fetchAll();
        
        // Mark messages as read
        $stmt = $pdo->prepare("
            UPDATE messages 
            SET is_read = 1 
            WHERE recipient_id = ? AND sender_id = ? AND is_read = 0
        ");
        $stmt->execute([$_SESSION['user_id'], $selected_user_id]);
    }
}

require_once '../includes/header.php';
?>

<style>
    .messaging-container {
        height: calc(100vh - 120px);
        display: flex;
        background: #fff;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }

    .conversations-panel {
        width: 320px;
        border-right: 1px solid #e0e0e0;
        display: flex;
        flex-direction: column;
        background: #fff;
    }

    .conversations-header {
        padding: 16px;
        border-bottom: 1px solid #e0e0e0;
        background: linear-gradient(135deg, #000000 0%, #ed1c24 100%);
        color: white;
    }

    .conversations-list {
        flex: 1;
        overflow-y: auto;
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .conversation-item {
        padding: 12px 16px;
        border-bottom: 1px solid #f0f0f0;
        cursor: pointer;
        transition: background 0.2s;
        position: relative;
    }

    .conversation-item:hover {
        background: #f5f5f5;
    }

    .conversation-item.active {
        background: linear-gradient(135deg, rgba(237,28,36,0.1) 0%, rgba(0,0,0,0.05) 100%);
        border-left: 4px solid #ed1c24;
        padding-left: 12px;
    }

    .conversation-header-info {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 4px;
    }

    .conversation-name {
        font-weight: 600;
        font-size: 14px;
        color: #000;
    }

    .unread-badge {
        background: #ed1c24;
        color: white;
        border-radius: 50%;
        width: 20px;
        height: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
        font-weight: bold;
    }

    .conversation-preview {
        font-size: 13px;
        color: #888;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .conversation-time {
        font-size: 12px;
        color: #aaa;
    }

    .chat-panel {
        flex: 1;
        display: flex;
        flex-direction: column;
        background: #fff;
    }

    .chat-header {
        padding: 16px;
        border-bottom: 1px solid #e0e0e0;
        background: linear-gradient(135deg, #000000 0%, #ed1c24 100%);
        color: white;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .chat-header-info h5 {
        margin: 0;
        font-size: 16px;
        font-weight: 600;
    }

    .chat-header-info small {
        display: block;
        opacity: 0.9;
        font-size: 12px;
    }

    .messages-container {
        flex: 1;
        overflow-y: auto;
        padding: 20px;
        background: #fff;
        display: flex;
        flex-direction: column;
    }

    .empty-chat {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        height: 100%;
        color: #999;
    }

    .empty-chat i {
        font-size: 48px;
        margin-bottom: 16px;
        opacity: 0.3;
    }

    .message-bubble {
        display: flex;
        margin-bottom: 12px;
        align-items: flex-end;
        animation: fadeIn 0.3s ease-in;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .message-bubble.sent {
        justify-content: flex-end;
    }

    .message-bubble.received {
        justify-content: flex-start;
    }

    .bubble-content {
        max-width: 60%;
        word-wrap: break-word;
    }

    .bubble-text {
        padding: 10px 14px;
        border-radius: 18px;
        font-size: 14px;
        line-height: 1.4;
    }

    .sent .bubble-text {
        background: linear-gradient(135deg, #000000 0%, #ed1c24 100%);
        color: white;
        border-bottom-right-radius: 4px;
    }

    .received .bubble-text {
        background: #f0f0f0;
        color: #333;
        border-bottom-left-radius: 4px;
    }

    .message-time {
        font-size: 11px;
        color: #999;
        margin: 0 8px;
        white-space: nowrap;
    }

    .message-attachment {
        padding: 12px;
        background: #f9f9f9;
        border: 1px solid #ddd;
        border-radius: 8px;
        font-size: 12px;
        color: #666;
        margin-top: 4px;
    }

    .message-attachment a {
        color: #ed1c24;
        text-decoration: none;
        font-weight: 600;
    }

    .message-attachment a:hover {
        text-decoration: underline;
    }

    .compose-panel {
        padding: 16px;
        background: #fff;
        border-top: 1px solid #e0e0e0;
        display: flex;
        gap: 8px;
        align-items: flex-end;
    }

    .compose-input-group {
        flex: 1;
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .compose-input-group textarea {
        resize: none;
        max-height: 100px;
        padding: 10px 12px;
        border: 1px solid #e0e0e0;
        border-radius: 20px;
        font-family: inherit;
        font-size: 14px;
    }

    .compose-input-group textarea:focus {
        outline: none;
        border-color: #ed1c24;
        box-shadow: 0 0 0 2px rgba(237,28,36,0.1);
    }

    .compose-actions {
        display: flex;
        gap: 8px;
        margin-top: auto;
    }

    .btn-attach {
        background: #f0f0f0;
        border: none;
        border-radius: 50%;
        width: 36px;
        height: 36px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        color: #666;
        transition: all 0.2s;
    }

    .btn-attach:hover {
        background: #e0e0e0;
        color: #ed1c24;
    }

    .btn-send {
        background: linear-gradient(135deg, #000000 0%, #ed1c24 100%);
        color: white;
        border: none;
        border-radius: 50%;
        width: 36px;
        height: 36px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: transform 0.2s;
        font-size: 16px;
    }

    .btn-send:hover {
        transform: scale(1.05);
    }

    .btn-send:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }

    .no-conversation {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        height: 100%;
        color: #999;
    }

    .no-conversation i {
        font-size: 64px;
        margin-bottom: 16px;
        opacity: 0.2;
    }

    .file-input-hidden {
        display: none;
    }

    .attachment-preview {
        background: #f5f5f5;
        padding: 8px 12px;
        border-radius: 8px;
        font-size: 12px;
        color: #666;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .attachment-preview .remove-btn {
        background: none;
        border: none;
        color: #999;
        cursor: pointer;
        font-size: 16px;
    }
</style>

<div class="container-fluid py-3" style="height: 100%; padding: 0 !important; max-width: 1100px; margin: 0 auto;">
    <div class="row" style="height: calc(100vh - 120px); margin: 0;">
        <div class="col-12" style="padding: 0;">
            <div class="messaging-container">
                <!-- Conversations Panel -->
                <div class="conversations-panel">
                    <div class="conversations-header">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <h5 style="margin: 0; font-size: 18px; font-weight: 600;">
                                <i class="bi bi-chat-left-dots"></i> Messages
                            </h5>
                        </div>
                    </div>

                    <div class="conversations-list">
                        <?php if (!empty($conversations)): ?>
                            <?php foreach ($conversations as $conv): ?>
                                <div class="conversation-item <?php echo $selected_user_id === $conv['other_user_id'] ? 'active' : ''; ?>" 
                                     onclick="selectConversation(<?php echo $conv['other_user_id']; ?>)">
                                    <div class="conversation-header-info">
                                        <span class="conversation-name"><?php echo htmlspecialchars($conv['other_user_name']); ?></span>
                                        <?php if ($conv['unread_count'] > 0): ?>
                                            <span class="unread-badge"><?php echo $conv['unread_count']; ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <?php if (!empty($conv['last_message_preview'])): ?>
                                        <div class="conversation-preview"><?php echo htmlspecialchars(substr($conv['last_message_preview'], 0, 50)); ?></div>
                                    <?php endif; ?>
                                    <div class="conversation-time"><?php echo date('M d, H:i', strtotime($conv['last_message_time'])); ?></div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div style="padding: 20px; text-align: center; color: #999;">
                                <i class="bi bi-inbox" style="font-size: 24px; display: block; margin-bottom: 8px;"></i>
                                <small>No conversations yet</small>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Chat Panel -->
                <div class="chat-panel">
                    <?php if ($selected_user): ?>
                        <!-- Chat Header -->
                        <div class="chat-header">
                            <div class="chat-header-info">
                                <h5><?php echo htmlspecialchars($selected_user['name']); ?></h5>
                                <small><?php echo htmlspecialchars($selected_user['email']); ?></small>
                            </div>
                        </div>

                        <!-- Messages Container -->
                        <div class="messages-container" id="messagesContainer">
                            <?php if (!empty($messages)): ?>
                                <?php foreach ($messages as $msg): ?>
                                    <div class="message-bubble <?php echo $msg['is_sent'] ? 'sent' : 'received'; ?>">
                                        <div class="bubble-content">
                                            <div class="bubble-text">
                                                <?php echo htmlspecialchars($msg['body']); ?>
                                            </div>
                                            <?php if (!empty($msg['attachment_path'])): ?>
                                                <div class="message-attachment">
                                                    <i class="bi bi-paperclip"></i>
                                                    <a href="<?php echo BASE_URL . '/' . htmlspecialchars($msg['attachment_path']); ?>" download>
                                                        <?php echo htmlspecialchars($msg['attachment_name']); ?>
                                                    </a>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="message-time"><?php echo date('H:i', strtotime($msg['created_at'])); ?></div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="empty-chat">
                                    <i class="bi bi-chat-dots"></i>
                                    <p>No messages yet. Start the conversation!</p>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Compose Panel -->
                        <div class="compose-panel">
                            <div class="compose-input-group" style="width: 100%;">
                                <div id="attachmentPreview"></div>
                                <textarea id="messageInput" placeholder="Type a message..." rows="1"></textarea>
                            </div>
                            <div class="compose-actions">
                                <button class="btn-attach" onclick="document.getElementById('fileInput').click()" title="Attach file">
                                    <i class="bi bi-paperclip"></i>
                                </button>
                                <button class="btn-send" id="sendBtn" onclick="sendMessage()" title="Send message">
                                    <i class="bi bi-send-fill"></i>
                                </button>
                            </div>
                            <input type="file" id="fileInput" class="file-input-hidden" accept="*/*">
                        </div>
                    <?php else: ?>
                        <div class="no-conversation">
                            <i class="bi bi-chat-dots"></i>
                            <p>Select a conversation to start messaging</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    const selectedUserId = <?php echo $selected_user_id; ?>;

    function selectConversation(userId) {
        window.location.href = '<?php echo BASE_URL; ?>/admin/messaging.php?user_id=' + userId;
    }

    function sendMessage() {
        const messageInput = document.getElementById('messageInput');
        const fileInput = document.getElementById('fileInput');
        const message = messageInput.value.trim();

        if (!message && !fileInput.files.length) {
            return;
        }

        const formData = new FormData();
        formData.append('recipient_id', selectedUserId);
        formData.append('message', message);
        if (fileInput.files.length) {
            formData.append('attachment', fileInput.files[0]);
        }

        fetch('<?php echo BASE_URL; ?>/actions/send_message.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                messageInput.value = '';
                fileInput.value = '';
                document.getElementById('attachmentPreview').innerHTML = '';
                // Refresh messages
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Failed to send message');
        });
    }

    // Auto-expand textarea
    const textarea = document.getElementById('messageInput');
    if (textarea) {
        textarea.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = Math.min(this.scrollHeight, 100) + 'px';
        });

        textarea.addEventListener('keypress', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
        });
    }

    // File attachment handling
    const fileInput = document.getElementById('fileInput');
    if (fileInput) {
        fileInput.addEventListener('change', function() {
            const preview = document.getElementById('attachmentPreview');
            if (this.files.length) {
                const file = this.files[0];
                preview.innerHTML = `
                    <div class="attachment-preview">
                        <span><i class="bi bi-file-earmark"></i> ${file.name}</span>
                        <button type="button" class="remove-btn" onclick="document.getElementById('fileInput').value=''; document.getElementById('attachmentPreview').innerHTML='';">
                            ✕
                        </button>
                    </div>
                `;
            } else {
                preview.innerHTML = '';
            }
        });
    }

    // Scroll to bottom on load
    const messagesContainer = document.getElementById('messagesContainer');
    if (messagesContainer) {
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }
</script>

<?php require_once '../includes/footer.php'; ?>
