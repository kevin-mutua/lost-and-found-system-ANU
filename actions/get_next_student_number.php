<?php
require_once '../includes/db.php';

header('Content-Type: application/json');

if (!isset($_GET['course']) || !isset($_GET['date'])) {
    echo json_encode(['error' => 'Missing parameters']);
    exit;
}

try {
    $course = $_GET['course'];
    $date_joined = $_GET['date']; // Format: YYYY-MM
    
    // Parse date
    $date_parts = explode('-', $date_joined);
    $year = substr($date_parts[0], 2); // Get last 2 digits
    $month_num = $date_parts[1];
    
    // Map month to code
    $month_map = [
        '01' => 'm', '02' => 'm', '03' => 'm', '04' => 'm', '05' => 'm', '06' => 'j',
        '07' => 'j', '08' => 'j', '09' => 's', '10' => 's', '11' => 's', '12' => 's'
    ];
    $month_code = $month_map[$month_num] ?? 'm';
    
    // Course to code mapping
    $course_codes = [
        'llb' => '02llb', 'crj' => '02crj',
        'mba' => '03mba', 'bca' => '03bca', 'bcb' => '03bcb', 'bcm' => '03bcm', 'ibm' => '03ibm',
        'hrm' => '03hrm', 'cbm' => '03cbm', 'cps' => '03cps',
        'abt' => '01abt', 'acs' => '01acs', 'ait' => '01ait', 'erm' => '01erm', 'psc' => '01psc',
        'enm' => '01enm', 'dit' => '01dit', 'dmo' => '01dmo', 'iad' => '01iad',
        'pcs' => '04pcs', 'mce' => '04mce', 'mcp' => '04mcp', 'edu' => '04edu',
        'mdv' => '05mdv', 'thl' => '05thl'
    ];
    
    $prog_code = $course_codes[$course] ?? '01abt';
    
    // Get count of students with this year/month/program combination
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM users 
                          WHERE registration_id LIKE ? AND role = 'student'");
    $like_pattern = $year . $month_code . $prog_code . '%';
    $stmt->execute([$like_pattern]);
    $result = $stmt->fetch();
    
    $next_number = $result['count'] + 1;
    
    echo json_encode(['next_number' => $next_number]);
    
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>
