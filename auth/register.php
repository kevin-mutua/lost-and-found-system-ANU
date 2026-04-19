<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/auth_check.php';

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    // Redirect based on user role
    if (hasRole('admin')) {
        header('Location: ' . BASE_URL . '/admin.php');
    } elseif (hasRole('security')) {
        header('Location: ' . BASE_URL . '/dashboard.php');
    } else {
        header('Location: ' . BASE_URL . '/dashboard.php');
    }
    exit();
}

$error = '';
$success = '';

// Handle registration form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name']);
    $email = sanitize($_POST['email']);
    $phone = sanitize($_POST['phone']);
    $course = sanitize($_POST['course']);
    $date_joined = sanitize($_POST['date_joined']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Validation
    if (empty($name) || empty($email) || empty($phone) || empty($course) || empty($date_joined) || empty($password)) {
        $error = 'All fields are required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long';
    } else {
        try {
            // Generate registration ID based on course and date_joined
            // Format: YYmCODEnnn (e.g., 18m01abt045)
            
            // Parse date_joined (format: 2018-05 or similar)
            $date_parts = explode('-', $date_joined);
            $year = substr($date_parts[0], 2); // Get last 2 digits of year
            $month_num = $date_parts[1]; // Get month number
            
            // Map month to month code
            $month_map = ['01' => 'm', '02' => 'm', '03' => 'm', '04' => 'm', '05' => 'm', '06' => 'j', 
                          '07' => 'j', '08' => 'j', '09' => 's', '10' => 's', '11' => 's', '12' => 's'];
            $month_code = $month_map[$month_num] ?? 'm';
            
            // Course to school+program code mapping
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
            
            // Get next student number for this year/month/program
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM users WHERE registration_id LIKE ? AND role = 'student'");
            $like_pattern = $year . $month_code . $prog_code . '%';
            $stmt->execute([$like_pattern]);
            $result = $stmt->fetch();
            $next_num = str_pad(($result['count'] + 1), 3, '0', STR_PAD_LEFT);
            
            // Build final registration ID
            $registration_id = $year . $month_code . $prog_code . $next_num;
            
            // Check if email or registration ID already exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? OR registration_id = ?");
            $stmt->execute([$email, $registration_id]);
            if ($stmt->fetch()) {
                $error = 'Email or Registration ID already exists';
            } else {
                // Calculate valid_till - 4 years from now
                $valid_till = date('Y-m-d', strtotime('+4 years'));
                
                // Parse date_joined for created_at
                $created_at = $date_joined . '-01 00:00:00';
                
                // Insert new user with default 'student' role
                $stmt = $pdo->prepare("INSERT INTO users (registration_id, name, email, phone, password, role, valid_till, created_at, course) 
                                      VALUES (?, ?, ?, ?, ?, 'student', ?, ?, ?)");
                
                // Map course code back to full course name
                $course_name_map = [
                    'llb' => 'Bachelor of Laws (LLB)',
                    'crj' => 'Bachelor of Arts in Criminal Justice Security Studies',
                    'mba' => 'Master of Business Administration (MBA)',
                    'bca' => 'Bachelor of Commerce (Accounting)',
                    'bcb' => 'Bachelor of Commerce (Banking and Finance)',
                    'bcm' => 'Bachelor of Commerce (Marketing Management)',
                    'ibm' => 'Bachelor of Science in International Business Management',
                    'hrm' => 'Bachelor of Human Resource Management',
                    'cbm' => 'Certificate in Business Management',
                    'cps' => 'Certified Procurement and Supply Professional of Kenya (CPSP-K)',
                    'abt' => 'Bachelor of Business and Information Technology',
                    'acs' => 'Bachelor of Science in Computer Science',
                    'ait' => 'Master of Science in Applied Information Technology (MSc. AIT)',
                    'erm' => 'Master of Science in Environmental Resource Management (MSc. ERM)',
                    'psc' => 'Bachelor of Science in Procurement and Supply Chain Management',
                    'enm' => 'Bachelor of Science in Environmental and Natural Resource Management',
                    'dit' => 'Diploma in Information Technology',
                    'dmo' => 'Diploma in Mobile Computing',
                    'iad' => 'International Advanced Diploma in Computer Studies (IADCS)',
                    'pcs' => 'Bachelor of Arts in Peace and Conflict Studies',
                    'mce' => 'Bachelor of Mass Communication (Electronic)',
                    'mcp' => 'Bachelor of Mass Communication (Print Media)',
                    'edu' => 'Bachelor of Arts in Education',
                    'mdv' => 'Master of Divinity',
                    'thl' => 'Bachelor of Arts in Theology'
                ];
                
                $full_course = $course_name_map[$course] ?? 'N/A';
                
                $stmt->execute([$registration_id, $name, $email, $phone, password_hash($password, PASSWORD_DEFAULT), $valid_till, $created_at, $full_course]);

                // Get the newly created user ID
                $user_id = $pdo->lastInsertId();
                
                // Auto-login the user
                $_SESSION['user_id'] = $user_id;
                $_SESSION['user_name'] = $name;
                $_SESSION['user_role'] = 'student';
                $_SESSION['user_email'] = $email;
                $_SESSION['registration_id'] = $registration_id;
                $_SESSION['registration_success'] = true;
                
                // Redirect to dashboard
                header('Location: ' . BASE_URL . '/dashboard.php');
                exit();
            }
        } catch(PDOException $e) {
            $error = 'Registration failed. Please try again.';
        }
    }
}

require_once '../includes/header.php';
?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card login-card mt-5">
                <div class="card-header text-center" style="background-color: #ed1c24; color: white; border: none;">
                    <img src="<?php echo BASE_URL; ?>/assets/images/lostfound.png" alt="ANU Lost and Found" class="login-logo mb-3">
                    <p class="mb-0">Create an Account</p>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger" role="alert">
                            <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                        <div class="alert alert-success" role="alert">
                            <?php echo htmlspecialchars($success); ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" autocomplete="off" id="registerForm">
                        <input type="text" name="fakeusernameremembered" value="" style="display:none;">
                        <input type="password" name="fakepasswordremembered" value="" style="display:none;">
                        
                        <!-- Name and Phone -->
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="name" class="form-label">Full Name</label>
                                <input type="text" class="form-control" id="name" name="name" placeholder="Your full name" autocomplete="off" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="phone" class="form-label">Phone Number</label>
                                <input type="tel" class="form-control" id="phone" name="phone" placeholder="+254 7XXXXXXXX" autocomplete="off" required>
                            </div>
                        </div>
                        
                        <!-- Date Joined and Course -->
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="date_joined" class="form-label">Date Joined (Year-Month)</label>
                                <input type="month" class="form-control" id="date_joined" name="date_joined" required onchange="generateEmail()">
                                <small class="text-muted">e.g., 2018-05 (May 2018)</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="course" class="form-label">Course</label>
                                <select class="form-select" id="course" name="course" required onchange="generateEmail()">
                                    <option value="">-- Select Course --</option>
                                    <!-- School of Law -->
                                    <optgroup label="School of Law">
                                        <option value="llb">Bachelor of Laws (LLB)</option>
                                        <option value="crj">Criminal Justice Security Studies</option>
                                    </optgroup>
                                    <!-- School of Business -->
                                    <optgroup label="School of Business">
                                        <option value="mba">MBA</option>
                                        <option value="bca">B.Com Accounting</option>
                                        <option value="bcb">B.Com Banking & Finance</option>
                                        <option value="bcm">B.Com Marketing</option>
                                        <option value="ibm">Int'l Business Management</option>
                                        <option value="hrm">Human Resource Management</option>
                                        <option value="cbm">Certificate in Business Management</option>
                                        <option value="cps">CPSP-K</option>
                                    </optgroup>
                                    <!-- School of Science & Technology -->
                                    <optgroup label="School of Science & Technology">
                                        <option value="abt">Bachelor of Business and Information Technology (BBIT)</option>
                                        <option value="acs">Bachelor of Science in Computer Science</option>
                                        <option value="ait">MSc Applied Information Technology</option>
                                        <option value="erm">MSc Environmental Resource Management</option>
                                        <option value="psc">Procurement & Supply Chain Management</option>
                                        <option value="enm">Environmental & Natural Resource Mgmt</option>
                                        <option value="dit">Diploma in Information Technology</option>
                                        <option value="dmo">Diploma in Mobile Computing</option>
                                        <option value="iad">International Advanced Diploma in CS</option>
                                    </optgroup>
                                    <!-- School of Humanities & Social Sciences -->
                                    <optgroup label="School of Humanities & Social Sciences">
                                        <option value="pcs">Peace and Conflict Studies</option>
                                        <option value="mce">Mass Communication (Electronic)</option>
                                        <option value="mcp">Mass Communication (Print)</option>
                                        <option value="edu">Education</option>
                                    </optgroup>
                                    <!-- School of Religion & Christian Ministry -->
                                    <optgroup label="School of Religion & Christian Ministry">
                                        <option value="mdv">Master of Divinity</option>
                                        <option value="thl">Bachelor of Arts in Theology</option>
                                    </optgroup>
                                </select>
                            </div>
                        </div>
                        
                        <!-- Auto-generated Email -->
                        <div class="mb-3">
                            <label for="email" class="form-label">Email (Auto-generated)</label>
                            <input type="email" class="form-control" id="email" name="email" placeholder="Will auto-fill" disabled style="background-color: #f0f0f0;">
                            <small class="text-muted">Automatically generated from your registration ID</small>
                        </div>
                        
                        <!-- Registration ID (hidden but sent to backend) -->
                        <input type="hidden" id="registration_id" name="registration_id">
                        
                        <!-- Password -->
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control" id="password" name="password" placeholder="Create your password" autocomplete="off" required>
                                <small class="text-muted">Minimum 6 characters</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="confirm_password" class="form-label">Confirm Password</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Confirm password" autocomplete="off" required>
                            </div>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-danger" id="submitBtn" disabled>Register</button>
                        </div>
                    </form>
                    
                    <script>
                    function generateEmail() {
                        const date_joined = document.getElementById('date_joined').value;
                        const course = document.getElementById('course').value;
                        const email = document.getElementById('email');
                        const registration_id = document.getElementById('registration_id');
                        const submitBtn = document.getElementById('submitBtn');
                        
                        if (!date_joined || !course) {
                            email.value = '';
                            registration_id.value = '';
                            submitBtn.disabled = true;
                            return;
                        }
                        
                        // Parse date
                        const [year, month] = date_joined.split('-');
                        const yearShort = year.slice(2); // Last 2 digits
                        
                        // Map month to code
                        const monthMap = {
                            '01': 'm', '02': 'm', '03': 'm', '04': 'm', '05': 'm', '06': 'j',
                            '07': 'j', '08': 'j', '09': 's', '10': 's', '11': 's', '12': 's'
                        };
                        const monthCode = monthMap[month];
                        
                        // Course to school+program code
                        const courseCodeMap = {
                            'llb': '02llb', 'crj': '02crj',
                            'mba': '03mba', 'bca': '03bca', 'bcb': '03bcb', 'bcm': '03bcm', 'ibm': '03ibm',
                            'hrm': '03hrm', 'cbm': '03cbm', 'cps': '03cps',
                            'abt': '01abt', 'acs': '01acs', 'ait': '01ait', 'erm': '01erm', 'psc': '01psc',
                            'enm': '01enm', 'dit': '01dit', 'dmo': '01dmo', 'iad': '01iad',
                            'pcs': '04pcs', 'mce': '04mce', 'mcp': '04mcp', 'edu': '04edu',
                            'mdv': '05mdv', 'thl': '05thl'
                        };
                        
                        const progCode = courseCodeMap[course];
                        
                        // This would fetch the next student number from backend
                        // For now, we'll use a simple counter
                        fetch('<?php echo BASE_URL; ?>/actions/get_next_student_number.php?course=' + course + '&date=' + date_joined)
                            .then(response => response.json())
                            .then(data => {
                                const studentNum = String(data.next_number).padStart(3, '0');
                                const regId = yearShort + monthCode + progCode + studentNum;
                                const generatedEmail = regId.toLowerCase() + '@anu.ac.ke';
                                
                                email.value = generatedEmail;
                                registration_id.value = regId;
                                submitBtn.disabled = false;
                            })
                            .catch(error => {
                                console.error('Error:', error);
                                // Fallback to 001 if backend fails
                                const regId = yearShort + monthCode + progCode + '001';
                                const generatedEmail = regId.toLowerCase() + '@anu.ac.ke';
                                email.value = generatedEmail;
                                registration_id.value = regId;
                                submitBtn.disabled = false;
                            });
                    }
                    </script>
                    <div class="text-center mt-3">
                        <p class="mb-0">Already have an account?</p>
                        <a href="<?php echo BASE_URL; ?>/auth/login.php" class="btn btn-link">Login Here</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
require_once '../includes/footer.php';
?>