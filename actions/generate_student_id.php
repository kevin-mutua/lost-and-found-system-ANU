<?php
session_start();
require_once '../includes/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('HTTP/1.0 403 Forbidden');
    exit('Access denied');
}

// Get user ID from query or session
$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : $_SESSION['user_id'];

// Fetch user data
$stmt = $pdo->prepare("SELECT name, registration_id, course, valid_till, created_at FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    header('HTTP/1.0 404 Not Found');
    exit('User not found');
}

// Calculate valid_till if not set (4 years from join date)
if (empty($user['valid_till'])) {
    $join_date = new DateTime($user['created_at'] ?? date('Y-m-d'));
    $valid_date = $join_date->add(new DateInterval('P4Y'));
    $valid_till_formatted = $valid_date->format('F Y'); // e.g., December 2030
} else {
    $valid_date = new DateTime($user['valid_till']);
    $valid_till_formatted = $valid_date->format('F Y');
}

// Load the template image
$template_path = '../assets/images/student-id.png';
if (!file_exists($template_path)) {
    header('HTTP/1.0 404 Not Found');
    exit('Template image not found');
}

// Create image from template
$image = imagecreatefrompng($template_path);
if (!$image) {
    header('HTTP/1.0 500 Internal Server Error');
    exit('Failed to load template image');
}

// Define colors
$white = imagecolorallocate($image, 255, 255, 255);
$dark_red = imagecolorallocate($image, 237, 28, 36);  // #ed1c24
$dark_gray = imagecolorallocate($image, 85, 85, 85);  // #555555
$light_gray = imagecolorallocate($image, 153, 153, 153);  // #999999
$blue = imagecolorallocate($image, 0, 102, 204);  // #0066cc - for Valid Till

// Load fonts (using Inter OTF font)
$name = strtoupper(htmlspecialchars($user['name']));
$student_id = strtoupper(htmlspecialchars($user['registration_id']));
$course = strtoupper(htmlspecialchars($user['course'] ?? 'N/A'));
$valid_till_upper = strtoupper($valid_till_formatted);

$font_path = '../assets/fonts/Inter-Regular.otf';
$font_size = 11;

// Get image dimensions
$width = imagesx($image);
$height = imagesy($image);

// Calculate positioning for white areas
$left_col_x = 176;     // Left column for name/course (moved to 11rem)
$right_col_x = 328;    // Right column for "Valid Till" (3.5rem away from student ID)

// Student Name - using Inter font
imagettftext($image, $font_size, 0, $left_col_x, 100, $dark_gray, $font_path, $name);

// Course - slightly smaller (reduced by 10%)
imagettftext($image, 8, 0, $left_col_x, 120, $light_gray, $font_path, $course);

// Student ID - in red
imagettftext($image, $font_size, 0, $left_col_x, 164, $dark_red, $font_path, $student_id);

// Valid Till date - vertically centered with Student ID, in blue
imagettftext($image, 9, 0, $right_col_x, 164, $blue, $font_path, $valid_till_upper);

// Set header and output image
header('Content-Type: image/png');
header('Content-Disposition: inline; filename="student-id-' . $user_id . '.png"');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

imagepng($image);
imagedestroy($image);
?>
