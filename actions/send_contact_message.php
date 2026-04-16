<?php
require_once '../includes/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

try {
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $subject = isset($_POST['subject']) ? trim($_POST['subject']) : '';
    $message = isset($_POST['message']) ? trim($_POST['message']) : '';

    // Validation
    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        echo json_encode(['success' => false, 'message' => 'All fields are required']);
        exit;
    }

    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Invalid email address']);
        exit;
    }

    // Limit message length
    if (strlen($message) > 5000) {
        echo json_encode(['success' => false, 'message' => 'Message is too long (max 5000 characters)']);
        exit;
    }

    // Check if user exists
    $user_id = null;
    if (isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];
    }

    // Store message in database for audit trail
    $stmt = $pdo->prepare("
        INSERT INTO contact_messages (user_id, name, email, subject, message, created_at)
        VALUES (?, ?, ?, ?, ?, NOW())
    ");
    $stmt->execute([$user_id, $name, $email, $subject, $message]);

    // Send email to Lost and Found office
    $to = 'reports@anu.ac.ke';
    $email_subject = 'ANU Lost & Found - Contact Form Submission: ' . htmlspecialchars($subject);
    
    $email_body = "
    <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; }
                .container { max-width: 600px; margin: 0 auto; }
                .header { background: #dc3545; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; background: #f8f9fa; }
                .field { margin-bottom: 15px; }
                .label { font-weight: bold; color: #333; }
                .footer { background: #333; color: white; padding: 10px; text-align: center; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>Lost & Found Contact Message</h2>
                </div>
                <div class='content'>
                    <div class='field'>
                        <span class='label'>From:</span> " . htmlspecialchars($name) . " (" . htmlspecialchars($email) . ")
                    </div>
                    <div class='field'>
                        <span class='label'>Subject:</span> " . htmlspecialchars($subject) . "
                    </div>
                    <div class='field'>
                        <span class='label'>Message:</span>
                        <div style='background: white; padding: 10px; border-left: 3px solid #dc3545; margin-top: 5px;'>
                            " . nl2br(htmlspecialchars($message)) . "
                        </div>
                    </div>
                    <div style='border-top: 1px solid #ddd; padding-top: 15px; margin-top: 15px; font-size: 12px; color: #666;'>
                        <p><strong>Received:</strong> " . date('F j, Y \a\t g:i A') . "</p>
                        <p><strong>Submitted from:</strong> ANU Lost & Found System</p>
                    </div>
                </div>
                <div class='footer'>
                    <p>&copy; " . date('Y') . " African Nazarene University Lost & Found System</p>
                </div>
            </div>
        </body>
    </html>
    ";

    // Send email with headers
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    $headers .= "From: noreply@anu.ac.ke\r\n";
    $headers .= "Reply-To: " . $email . "\r\n";

    mail($to, $email_subject, $email_body, $headers);

    // Send confirmation to user
    $user_email_subject = 'ANU Lost & Found - We Received Your Message';
    $user_email_body = "
    <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; }
                .container { max-width: 600px; margin: 0 auto; }
                .header { background: #28a745; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; background: #f8f9fa; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>Message Received</h2>
                </div>
                <div class='content'>
                    <p>Hello " . htmlspecialchars($name) . ",</p>
                    <p>Thank you for contacting the ANU Lost & Found office. We have received your message and will respond as soon as possible.</p>
                    <p><strong>Expected Response Time:</strong> Within 24 business hours</p>
                    <p>If your issue is urgent, please call us at <strong>+254 703 970 520</strong> during office hours (Mon-Fri, 8:00 AM - 5:00 PM).</p>
                    <p style='margin-top: 30px; font-size: 12px; color: #666;'>
                        Best regards,<br>
                        ANU Lost & Found Team
                    </p>
                </div>
            </div>
        </body>
    </html>
    ";

    mail($email, $user_email_subject, $user_email_body, $headers);

    echo json_encode(['success' => true, 'message' => 'Message sent successfully']);

} catch (Exception $e) {
    error_log('Contact form error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred. Please try again later.']);
}
