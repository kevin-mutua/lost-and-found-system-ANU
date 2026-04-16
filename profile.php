<?php
session_start();

// Prevent browser caching for this dynamic page
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

require_once 'includes/db.php';
require_once 'includes/auth_check.php';
require_once 'includes/functions.php';
requireLogin();
require_once 'includes/header.php';

$error = '';
$success = '';
$user = null;

// Get user data and statistics from database
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    
    if ($user) {
        // Set session variables for consistency
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['registration_id'] = $user['registration_id'];
        $_SESSION['phone'] = $user['phone'];
        $_SESSION['created_at'] = $user['created_at'];
    }
}

// Handle case when user is not found
if (!$user) {
    $_SESSION['login_error'] = 'User not found. Please log in again.';
    header('Location: ' . BASE_URL . '/auth/login.php');
    exit();
}

// Calculate user statistics
$stats = [
    'items_lost' => 0,
    'items_recovered' => 0,
    'points_earned' => 0
];

if ($_SESSION['user_role'] === 'student') {
    // Count items lost
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM items WHERE user_id = ? AND type = 'lost'");
    $stmt->execute([$_SESSION['user_id']]);
    $result = $stmt->fetch();
    $stats['items_lost'] = $result['count'] ?? 0;
    
    // Count items recovered (lost items with status 'recovered')
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM items WHERE user_id = ? AND type = 'lost' AND status = 'recovered'");
    $stmt->execute([$_SESSION['user_id']]);
    $result = $stmt->fetch();
    $stats['items_recovered'] = $result['count'] ?? 0;
    
    // Points earned: 1 point per recovered item
    $stats['points_earned'] = $stats['items_recovered'];
}

// Determine user level based on points
function getUserLevel($points) {
    if ($points >= 251) return ['name' => 'Legend', 'color' => '#FFD700', 'icon' => '⭐'];
    if ($points >= 101) return ['name' => 'Hero', 'color' => '#FF6B6B', 'icon' => '🦸'];
    if ($points >= 51) return ['name' => 'Knight', 'color' => '#4ECDC4', 'icon' => '⚔'];
    if ($points >= 26) return ['name' => 'Guardian', 'color' => '#95E1D3', 'icon' => '🛡'];
    if ($points >= 11) return ['name' => 'Volunteer', 'color' => '#A8D8EA', 'icon' => '🤝'];
    return ['name' => 'Rookie', 'color' => '#D3D3D3', 'icon' => '🌱'];
}

$level = getUserLevel($stats['points_earned']);
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <h2 class="mt-4 mb-4">My Profile</h2>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-6 col-md-8 col-sm-12 mx-auto" style="max-width: 700px;">
            <!-- Profile Card -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body p-4">
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

                    <div class="row" style="align-items: center;">
                        <div class="col-md-3 text-center">
                            <div class="profile-avatar mb-3">
                                <div style="width: 100px; height: 100px; border-radius: 15px; background: linear-gradient(135deg, #ed1c24 0%, #000000 100%); display: flex; align-items: center; justify-content: center; box-shadow: 0 8px 20px rgba(237, 28, 36, 0.25); margin: 0 auto;">
                                    <i class="bi bi-person-circle" style="font-size: 3.5rem; color: #fac923;"></i>
                                </div>
                            </div>
                            <h4 style="margin: 15px 0 5px 0; color: #000; font-weight: 700; font-size: 1.3rem;"><?php echo htmlspecialchars($user['name']); ?></h4>
                            <p style="margin: 0; color: #ed1c24; font-weight: 600; font-size: 13px; text-transform: uppercase; letter-spacing: 1px;"><?php echo getRoleName(); ?></p>
                        </div>
                        <div class="col-md-9">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label style="font-size: 11px; color: #666; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; display: block; margin-bottom: 8px;">Registration ID</label>
                                    <div style="background: linear-gradient(135deg, rgba(237,28,36,0.05) 0%, rgba(250,201,35,0.05) 100%); border: 2px solid rgba(237,28,36,0.2); border-radius: 10px; padding: 12px 15px; color: #000; font-weight: 600; font-size: 14px;"><?php echo htmlspecialchars($user['registration_id']); ?></div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label style="font-size: 11px; color: #666; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; display: block; margin-bottom: 8px;">Email</label>
                                    <div style="background: linear-gradient(135deg, rgba(250,201,35,0.05) 0%, rgba(237,28,36,0.05) 100%); border: 2px solid rgba(250,201,35,0.2); border-radius: 10px; padding: 12px 15px; color: #000; font-weight: 600; font-size: 14px;"><?php echo htmlspecialchars($user['email']); ?></div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-0">
                                    <label style="font-size: 11px; color: #666; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; display: block; margin-bottom: 8px;">Phone</label>
                                    <div style="background: linear-gradient(135deg, rgba(237,28,36,0.05) 0%, rgba(250,201,35,0.05) 100%); border: 2px solid rgba(237,28,36,0.2); border-radius: 10px; padding: 12px 15px; color: #000; font-weight: 600; font-size: 14px;"><?php echo htmlspecialchars($user['phone'] ?? 'Not provided'); ?></div>
                                </div>
                                <div class="col-md-6 mb-0">
                                    <label style="font-size: 11px; color: #666; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; display: block; margin-bottom: 8px;">Member Since</label>
                                    <div style="background: linear-gradient(135deg, rgba(250,201,35,0.05) 0%, rgba(237,28,36,0.05) 100%); border: 2px solid rgba(250,201,35,0.2); border-radius: 10px; padding: 12px 15px; color: #000; font-weight: 600; font-size: 14px;"><?php echo date('M j, Y', strtotime($user['created_at'])); ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Gamification Stats Card (only for students) -->
            <?php if ($_SESSION['user_role'] === 'student'): ?>
            <div class="card border-0 mb-4" style="background: linear-gradient(135deg, #fdf8e8 0%, #fff9f0 50%, #ffe8d6 100%); overflow: hidden; box-shadow: 0 8px 24px rgba(250, 201, 35, 0.25);">
                <!-- Header with decorative background -->
                <div style="background: linear-gradient(90deg, #fac923 0%, #ff6b6b 100%); padding: 16px 0; position: relative; overflow: hidden;">
                    <h5 class="mb-0" style="color: #fff; padding: 0 20px; font-size: 18px; font-weight: 700; position: relative; z-index: 2;">
                        <i class="bi bi-trophy" style="color: #fff; margin-right: 8px;"></i> Your Achievement Progress
                    </h5>
                </div>

                <div class="card-body p-4">
                    <!-- Level Badge - Compact -->
                    <div class="text-center mb-2">
                        <div style="background: linear-gradient(135deg, #fef5d4 0%, #fef9e7 100%); border: 3px solid #fac923; border-radius: 16px; padding: 16px 20px; display: inline-block; box-shadow: 0 8px 20px rgba(250, 201, 35, 0.35);">
                            <svg width="60" height="60" viewBox="0 0 80 80" style="margin-bottom: 6px; filter: drop-shadow(0 3px 6px rgba(237, 28, 36, 0.2));">
                                <!-- Level Badge Background -->
                                <circle cx="40" cy="40" r="38" fill="<?php echo $level['color']; ?>" opacity="0.2" stroke="<?php echo $level['color']; ?>" stroke-width="2"/>
                                <circle cx="40" cy="40" r="35" fill="#fef5d4" stroke="<?php echo $level['color']; ?>" stroke-width="2"/>
                                
                                <!-- Star for Legend -->
                                <?php if ($level['name'] === 'Legend'): ?>
                                <polygon points="40,10 48,30 70,30 52,45 58,65 40,50 22,65 28,45 10,30 32,30" fill="#fac923" filter="drop-shadow(0 2px 4px rgba(0,0,0,0.15))"/>
                                <!-- Fire for Hero -->
                                <?php elseif ($level['name'] === 'Hero'): ?>
                                <path d="M 40 15 Q 35 25 35 35 Q 35 50 40 60 Q 45 50 45 35 Q 45 25 40 15 Z" fill="#ff6b6b" filter="drop-shadow(0 2px 4px rgba(0,0,0,0.15))"/>
                                <path d="M 40 25 Q 37 30 37 37 Q 37 45 40 52 Q 43 45 43 37 Q 43 30 40 25 Z" fill="#fac923"/>
                                <!-- Shield for Knight -->
                                <?php elseif ($level['name'] === 'Knight'): ?>
                                <path d="M 40 12 L 28 22 L 28 38 Q 28 52 40 65 Q 52 52 52 38 L 52 22 Z" fill="#4ECDC4" stroke="#4ECDC4" stroke-width="1" filter="drop-shadow(0 2px 4px rgba(0,0,0,0.15))"/>
                                <path d="M 40 20 L 32 27 L 32 38 Q 32 50 40 60 Q 48 50 48 38 L 48 27 Z" fill="#fac923"/>
                                <!-- Heart for Guardian -->
                                <?php elseif ($level['name'] === 'Guardian'): ?>
                                <path d="M 40 65 L 20 45 Q 15 40 15 32 Q 15 22 25 22 Q 32 22 40 30 Q 48 22 55 22 Q 65 22 65 32 Q 65 40 40 65 Z" fill="#95E1D3" filter="drop-shadow(0 2px 4px rgba(0,0,0,0.15))"/>
                                <!-- Handshake for Volunteer -->
                                <?php elseif ($level['name'] === 'Volunteer'): ?>
                                <circle cx="30" cy="35" r="8" fill="#A8D8EA" filter="drop-shadow(0 2px 4px rgba(0,0,0,0.15))"/>
                                <circle cx="50" cy="35" r="8" fill="#A8D8EA" filter="drop-shadow(0 2px 4px rgba(0,0,0,0.15))"/>
                                <rect x="25" y="40" width="30" height="8" fill="#A8D8EA" filter="drop-shadow(0 2px 4px rgba(0,0,0,0.15))"/>
                                <!-- Sprout for Rookie -->
                                <?php else: ?>
                                <path d="M 40 65 Q 35 55 35 45 Q 35 35 40 25 Q 45 35 45 45 Q 45 55 40 65 Z" fill="#fac923" filter="drop-shadow(0 2px 4px rgba(0,0,0,0.15))"/>
                                <ellipse cx="32" cy="40" rx="4" ry="8" fill="#fac923" transform="rotate(-20 32 40)" filter="drop-shadow(0 2px 4px rgba(0,0,0,0.15))"/>
                                <ellipse cx="48" cy="40" rx="4" ry="8" fill="#fac923" transform="rotate(20 48 40)" filter="drop-shadow(0 2px 4px rgba(0,0,0,0.15))"/>
                                <?php endif; ?>
                            </svg>
                            <h4 style="margin: 0 0 2px 0; color: #ed1c24; font-weight: 700; font-size: 16px;"><?php echo $level['name']; ?></h4>
                            <p style="margin: 0; color: #666; font-size: 12px;"><?php echo $stats['points_earned']; ?> <span style="color: #fac923; font-weight: 700;">PTS</span></p>
                        </div>
                    </div>

                    <!-- Progress Bar - Compact -->
                    <div class="mb-2">
                        <label style="font-size: 0.75rem; color: #666; font-weight: 600; display: block; margin-bottom: 0.3rem;">Level Progress</label>
                        <div style="background: rgba(250,201,35,0.1); border-radius: 8px; height: 10px; overflow: hidden; border: 1px solid rgba(250,201,35,0.4);">
                            <div style="background: linear-gradient(90deg, #fac923 0%, #ed1c24 100%); height: 100%; width: <?php echo min(100, ($stats['points_earned'] / 251) * 100); ?>%; border-radius: 7px; transition: width 0.3s ease; box-shadow: 0 2px 4px rgba(237, 28, 36, 0.3);" class="progress-bar"></div>
                        </div>
                    </div>

                    <!-- Stats Grid - Compact -->
                    <div class="row mb-2">
                        <div class="col-md-4 mb-0">
                            <div style="background: linear-gradient(135deg, #ffe8d6 0%, #ffdbce 100%); border: 1.5px solid #ed1c24; border-radius: 10px; padding: 12px; box-shadow: 0 4px 12px rgba(237,28,36,0.15); text-align: center; position: relative; overflow: hidden;">
                                <i class="bi bi-search" style="font-size: 18px; color: #ed1c24; display: block; margin-bottom: 4px;"></i>
                                <h4 style="color: #ed1c24; margin: 0; font-weight: 700; font-size: 20px;"><?php echo $stats['items_lost']; ?></h4>
                                <p style="margin: 2px 0 0 0; color: #666; font-size: 11px; font-weight: 600;">Lost Items</p>
                            </div>
                        </div>
                        <div class="col-md-4 mb-0">
                            <div style="background: linear-gradient(135deg, #d4f0e8 0%, #c9ebe1 100%); border: 1.5px solid #4ECDC4; border-radius: 10px; padding: 12px; box-shadow: 0 4px 12px rgba(78,205,196,0.15); text-align: center; position: relative; overflow: hidden;">
                                <i class="bi bi-check-circle" style="font-size: 18px; color: #4ECDC4; display: block; margin-bottom: 4px;"></i>
                                <h4 style="color: #4ECDC4; margin: 0; font-weight: 700; font-size: 20px;"><?php echo $stats['items_recovered']; ?></h4>
                                <p style="margin: 2px 0 0 0; color: #666; font-size: 11px; font-weight: 600;">Recovered</p>
                            </div>
                        </div>
                        <div class="col-md-4 mb-0">
                            <div style="background: linear-gradient(135deg, #fff4d6 0%, #fff0ca 100%); border: 1.5px solid #fac923; border-radius: 10px; padding: 12px; box-shadow: 0 4px 12px rgba(250,201,35,0.25); text-align: center; position: relative; overflow: hidden;">
                                <i class="bi bi-star" style="font-size: 18px; color: #fac923; display: block; margin-bottom: 4px;"></i>
                                <h4 style="color: #fac923; margin: 0; font-weight: 700; font-size: 20px;"><?php echo $stats['points_earned']; ?></h4>
                                <p style="margin: 2px 0 0 0; color: #666; font-size: 11px; font-weight: 600;">Points Earned</p>
                            </div>
                        </div>
                    </div>

                    <!-- Claim Points Button -->
                    <div class="text-center mb-2">
                        <?php if ($stats['points_earned'] >= 50): ?>
                            <button class="btn" style="background: linear-gradient(135deg, #fac923 0%, #ed1c24 100%); color: #fff; padding: 10px 25px; border-radius: 10px; border: none; font-weight: 700; cursor: pointer; font-size: 13px; box-shadow: 0 6px 14px rgba(237,28,36,0.25); transition: transform 0.2s, box-shadow 0.2s;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 8px 18px rgba(237,28,36,0.35)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 6px 14px rgba(237,28,36,0.25)';">
                                <i class="bi bi-award"></i> Claim Certificate
                            </button>
                            <p style="margin-top: 8px; color: #fac923; font-size: 12px; font-weight: 600;">
                                <i class="bi bi-info-circle"></i> Visit security office for your achievement certificate!
                            </p>
                        <?php else: ?>
                            <button class="btn" style="background: rgba(250,201,35,0.15); color: #fac923; padding: 10px 25px; border-radius: 10px; border: 2px solid #fac923; font-weight: 700; cursor: not-allowed; font-size: 13px;" disabled>
                                <i class="bi bi-lock"></i> Unlock rewards at 50pts
                            </button>
                            <p style="margin-top: 6px; color: #666; font-size: 11px;">
                                Earn <strong style="color: #fac923;"><?php echo 50 - $stats['points_earned']; ?></strong> more points for rewards
                            </p>
                        <?php endif; ?>
                    </div>

                    <!-- Thank You Message - Compact -->
                    <div style="background: linear-gradient(135deg, rgba(250,201,35,0.12) 0%, rgba(237,28,36,0.08) 100%); border-left: 4px solid #fac923; border-radius: 10px; padding: 12px 14px; text-align: center;">
                        <h6 style="margin: 0 0 4px 0; color: #ed1c24; font-weight: 700; font-size: 11px; text-transform: uppercase; letter-spacing: 1px;">🙏 Thank You</h6>
                        <p style="margin: 0; color: #666; font-size: 11px; line-height: 1.4; font-weight: 500;">
                            Thank you for helping ANU! Your contributions make a real difference.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Password Change Card -->
            <div class="card border-0 mb-4" style="background: #fdfda8; border-left: 5px solid #ed1c24;">
                <div class="card-header" style="background: transparent; padding: 1rem; border-bottom: 2px solid rgba(237, 28, 36, 0.1);">
                    <h5 class="mb-0" style="color: #ed1c24; font-weight: 700; font-size: 1.1rem;"><i class="bi bi-shield-lock"></i> Change Password</h5>
                    <small style="color: #120203; display: block; margin-top: 0.2rem; font-size: 0.8rem;">Secure your account</small>
                </div>
                <div class="card-body p-3">
                    <form id="changePasswordForm" method="POST" action="actions/change_password.php">
                        <div class="mb-2">
                            <label for="currentPassword" class="form-label" style="color: #120203; font-weight: 600; font-size: 0.9rem; margin-bottom: 0.3rem;">Current Password</label>
                            <input type="password" class="form-control" id="currentPassword" name="current_password" required style="border: 2px solid #ed1c24; border-radius: 8px; font-size: 0.9rem; padding: 0.5rem;" onkeyup="validateFormState()">
                            <small style="color: #666; font-size: 0.75rem;">Verify your identity</small>
                        </div>

                        <div class="mb-2">
                            <label for="newPassword" class="form-label" style="color: #120203; font-weight: 600; font-size: 0.9rem; margin-bottom: 0.3rem;">New Password</label>
                            <input type="password" class="form-control" id="newPassword" name="new_password" required style="border: 2px solid #fac923; border-radius: 8px; font-size: 0.9rem; padding: 0.5rem;" onkeyup="validatePassword(this.value)">
                            <small style="color: #666; display: block; margin-top: 0.2rem; font-size: 0.75rem;">
                                <i class="bi bi-info-circle"></i> 8+ chars, letters & numbers only
                            </small>

                            <!-- Password Strength Meter -->
                            <div style="margin-top: 0.5rem;">
                                <label style="font-size: 0.75rem; color: #666; font-weight: 600; display: block; margin-bottom: 0.2rem;">Strength:</label>
                                <div style="display: flex; gap: 4px; margin-bottom: 0.3rem;">
                                    <div id="strength1" style="flex: 1; height: 4px; background: #ddd; border-radius: 2px; transition: all 0.3s;"></div>
                                    <div id="strength2" style="flex: 1; height: 4px; background: #ddd; border-radius: 2px; transition: all 0.3s;"></div>
                                    <div id="strength3" style="flex: 1; height: 4px; background: #ddd; border-radius: 2px; transition: all 0.3s;"></div>
                                    <div id="strength4" style="flex: 1; height: 4px; background: #ddd; border-radius: 2px; transition: all 0.3s;"></div>
                                </div>
                                <div style="display: flex; justify-content: space-between; font-size: 0.7rem; color: #999;">
                                    <span>Weak</span>
                                    <span id="strengthText" style="font-weight: 600; color: #999;">-</span>
                                    <span>Strong</span>
                                </div>
                            </div>

                            <!-- Password Requirements -->
                            <div style="background: rgba(237, 28, 36, 0.05); border: 1px solid rgba(237, 28, 36, 0.2); border-radius: 6px; padding: 0.5rem; margin-top: 0.5rem;">
                                <p style="margin: 0 0 0.3rem 0; font-size: 0.75rem; color: #120203; font-weight: 600;">Requirements:</p>
                                <div style="font-size: 0.7rem; color: #666;">
                                    <div id="reqLength" style="margin-bottom: 0.2rem;">
                                        <i class="bi bi-circle" style="color: #ddd; margin-right: 0.2rem; font-size: 0.5rem;"></i>
                                        <span>8 characters</span>
                                    </div>
                                    <div id="reqLetter" style="margin-bottom: 0.2rem;">
                                        <i class="bi bi-circle" style="color: #ddd; margin-right: 0.2rem; font-size: 0.5rem;"></i>
                                        <span>Letters (A-Z)</span>
                                    </div>
                                    <div id="reqNumber">
                                        <i class="bi bi-circle" style="color: #ddd; margin-right: 0.2rem; font-size: 0.5rem;"></i>
                                        <span>Numbers (0-9)</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-2">
                            <label for="confirmPassword" class="form-label" style="color: #120203; font-weight: 600; font-size: 0.9rem; margin-bottom: 0.3rem;">Confirm Password</label>
                            <input type="password" class="form-control" id="confirmPassword" name="confirm_password" required style="border: 2px solid #fac923; border-radius: 8px; font-size: 0.9rem; padding: 0.5rem;" onkeyup="validatePasswordMatch()">
                            <small id="matchError" style="color: #d32f2f; display: none; margin-top: 0.2rem; font-size: 0.75rem;">
                                <i class="bi bi-exclamation-circle"></i> Passwords don't match
                            </small>
                        </div>

                        <div class="d-flex gap-2 mt-3">
                            <button type="submit" id="submitBtn" class="btn btn-sm btn-danger" style="font-weight: 600; font-size: 0.85rem;" disabled>
                                <i class="bi bi-shield-check"></i> Update
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="resetPasswordForm()" style="font-weight: 600; font-size: 0.85rem;">
                                <i class="bi bi-arrow-counterclockwise"></i> Reset
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
// Password validation function
function validatePassword(password) {
    const minLength = 8;
    const hasLetter = /[a-zA-Z]/.test(password);
    const hasNumber = /[0-9]/.test(password);
    const validFormat = /^[a-zA-Z0-9]*$/.test(password); // Only letters and numbers
    
    // Update requirements display
    const reqLength = document.getElementById('reqLength');
    const reqLetter = document.getElementById('reqLetter');
    const reqNumber = document.getElementById('reqNumber');
    
    if (password.length >= minLength) {
        reqLength.innerHTML = '<i class="bi bi-check-circle-fill" style="color: #4caf50; margin-right: 0.3rem;"></i><span style="color: #4caf50;">At least 8 characters</span>';
    } else {
        reqLength.innerHTML = '<i class="bi bi-circle" style="color: #ddd; margin-right: 0.3rem;"></i><span>At least 8 characters</span>';
    }
    
    if (hasLetter) {
        reqLetter.innerHTML = '<i class="bi bi-check-circle-fill" style="color: #4caf50; margin-right: 0.3rem;"></i><span style="color: #4caf50;">Contains letters (A-Z, a-z)</span>';
    } else {
        reqLetter.innerHTML = '<i class="bi bi-circle" style="color: #ddd; margin-right: 0.3rem;"></i><span>Contains letters (A-Z, a-z)</span>';
    }
    
    if (hasNumber) {
        reqNumber.innerHTML = '<i class="bi bi-check-circle-fill" style="color: #4caf50; margin-right: 0.3rem;"></i><span style="color: #4caf50;">Contains numbers (0-9)</span>';
    } else {
        reqNumber.innerHTML = '<i class="bi bi-circle" style="color: #ddd; margin-right: 0.3rem;"></i><span>Contains numbers (0-9)</span>';
    }
    
    // Update strength meter
    let strength = 0;
    if (password.length >= minLength) strength++;
    if (hasLetter) strength++;
    if (hasNumber) strength++;
    if (password.length >= 12) strength++;
    
    const strengthBars = ['strength1', 'strength2', 'strength3', 'strength4'];
    const strengthText = document.getElementById('strengthText');
    const strengthColors = ['#d32f2f', '#ff9800', '#fac923', '#4caf50'];
    const strengthLabels = ['Very Weak', 'Weak', 'Good', 'Strong'];
    
    strengthBars.forEach((bar, index) => {
        const element = document.getElementById(bar);
        if (index < strength) {
            element.style.background = strengthColors[strength - 1];
        } else {
            element.style.background = '#ddd';
        }
    });
    
    if (strength > 0) {
        strengthText.textContent = strengthLabels[strength - 1];
        strengthText.style.color = strengthColors[strength - 1];
    } else {
        strengthText.textContent = '-';
        strengthText.style.color = '#999';
    }
    
    // Validate password format and enable/disable submit button
    validatePasswordMatch();
    validateFormState();
}

function validatePasswordMatch() {
    const newPass = document.getElementById('newPassword').value;
    const confirmPass = document.getElementById('confirmPassword').value;
    const matchError = document.getElementById('matchError');
    
    if (confirmPass && newPass !== confirmPass) {
        matchError.style.display = 'block';
    } else {
        matchError.style.display = 'none';
    }
    
    validateFormState();
}

function validateFormState() {
    const currentPass = document.getElementById('currentPassword').value;
    const newPass = document.getElementById('newPassword').value;
    const confirmPass = document.getElementById('confirmPassword').value;
    const submitBtn = document.getElementById('submitBtn');
    
    const minLength = 8;
    const hasLetter = /[a-zA-Z]/.test(newPass);
    const hasNumber = /[0-9]/.test(newPass);
    const validFormat = /^[a-zA-Z0-9]*$/.test(newPass);
    const passwordsMatch = newPass === confirmPass && confirmPass !== '';
    
    const isValid = currentPass && 
                    newPass.length >= minLength && 
                    hasLetter && 
                    hasNumber && 
                    validFormat && 
                    passwordsMatch;
    
    submitBtn.disabled = !isValid;
}

function resetPasswordForm() {
    document.getElementById('changePasswordForm').reset();
    document.getElementById('strengthText').textContent = '-';
    document.getElementById('strengthText').style.color = '#999';
    ['strength1', 'strength2', 'strength3', 'strength4'].forEach(id => {
        document.getElementById(id).style.background = '#ddd';
    });
    document.getElementById('matchError').style.display = 'none';
    document.getElementById('submitBtn').disabled = true;
}

// Handle form submission
document.getElementById('changePasswordForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    fetch('<?php echo BASE_URL; ?>/actions/change_password.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: 'Password Changed Successfully!',
                html: '<strong>Your password has been updated.</strong><br><br>You will be logged out for security purposes.<br>Please log in with your new password.',
                confirmButtonColor: '#ed1c24',
                confirmButtonText: 'Login Again',
                allowOutsideClick: false,
                didOpen: () => {
                    // Automatically redirect after 3 seconds
                    setTimeout(() => {
                        window.location.href = '<?php echo BASE_URL; ?>/auth/login.php';
                    }, 3000);
                }
            }).then(result => {
                if (result.isConfirmed) {
                    window.location.href = '<?php echo BASE_URL; ?>/auth/login.php';
                }
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: data.message || 'Failed to change password',
                confirmButtonColor: '#ed1c24'
            });
        }
    })
    .catch(error => {
        console.error('Error:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'An error occurred while changing password',
            confirmButtonColor: '#ed1c24'
        });
    });
});
</script>

<?php
require_once 'includes/footer.php';
?>