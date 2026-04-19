<?php
if (!defined('BASE_URL')) {
    define('BASE_URL', '/lost_and_found');
}
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$loggedIn = isset($_SESSION['user_id']);
$currentPage = basename($_SERVER['PHP_SELF'] ?? '');
$showAdminLink = !$loggedIn;
$adminLoginUrl = BASE_URL . '/auth/login_admin.php';
$userName = $loggedIn ? htmlspecialchars($_SESSION['user_name']) : '';
$userRole = $_SESSION['user_role'] ?? 'student';

// Load notifications function if user is logged in
$unread_notifications = 0;
if ($loggedIn) {
    require_once __DIR__ . '/notifications.php';
    $unread_notifications = getUnreadNotificationCount($_SESSION['user_id']);
}
?>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover, maximum-scale=5">
    <meta name="description" content="Find your lost items or report found ones at ANU campus">
    <meta name="theme-color" content="#ed1c24">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="ANU L&F">
    <!-- Favicon Links -->
    <link rel="icon" type="image/png" sizes="32x32" href="<?php echo BASE_URL; ?>/assets/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="<?php echo BASE_URL; ?>/assets/favicon.png">
    <link rel="apple-touch-icon" href="<?php echo BASE_URL; ?>/assets/apple-touch-icon.png">
    <link rel="manifest" href="<?php echo BASE_URL; ?>/manifest.json">
    <!-- PWA Metadata -->
    <meta name="application-name" content="ANU Lost & Found">
    <meta name="msapplication-TileImage" content="<?php echo BASE_URL; ?>/assets/icon-192x192.png">
    <meta name="msapplication-TileColor" content="#ed1c24">
    <title>ANU Lost and Found</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Jenna+Sue&display=optional" rel="stylesheet" crossorigin="anonymous">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        /* Fix SweetAlert button visibility on hover */
        .swal2-popup .swal2-actions button:hover {
            background-color: inherit !important;
            color: inherit !important;
        }
        .swal2-confirm:hover {
            background-color: #a6191f !important;
            color: white !important;
        }
        .swal2-deny:hover {
            background-color: #dc3545 !important;
            color: white !important;
        }
        .swal2-cancel:hover {
            background-color: #6c757d !important;
            color: white !important;
        }
        /* Ensure button text is visible */
        .swal2-popup button {
            color: white !important;
            font-weight: 500;
        }
    </style>
    
    <script>
    // Define viewItemDetails EARLY so buttons can call it
    window.viewItemDetails = function(itemId, title, type) {
        if (!itemId) {
            alert('Invalid item ID');
            return;
        }
        
        fetch('<?php echo defined("BASE_URL") ? BASE_URL : "/lost_and_found"; ?>/actions/get_item_details.php?item_id=' + itemId)
            .then(response => {
                if (!response.ok) throw new Error('HTTP error: ' + response.status);
                return response.json();
            })
            .then(data => {
                if (!data.success || !data.item) {
                    alert('Failed to load item: ' + (data.message || 'Unknown error'));
                    return;
                }

                const item = data.item;
                const imageUrl = item.image_path ? '<?php echo defined("BASE_URL") ? BASE_URL : "/lost_and_found"; ?>/' + item.image_path : null;
                
                let html = '<div class="row"><div class="col-md-6">';
                
                if (imageUrl) {
                    html += '<img src="' + imageUrl + '" class="img-fluid rounded" alt="' + item.title + '" style="max-height: 400px; object-fit: cover; width: 100%;">';
                } else {
                    html += '<div class="bg-light p-5 text-center rounded"><i class="bi bi-image fs-1 text-muted"></i><p class="text-muted mt-2">No image available</p></div>';
                }
                
                html += '</div><div class="col-md-6"><h4>' + item.title + '</h4>';
                html += '<p><strong>Type:</strong> <span class="badge ' + (item.type === 'lost' ? 'bg-danger' : 'bg-success') + '">' + item.type.toUpperCase() + '</span></p>';
                html += '<p><strong>Category:</strong> ' + item.category + '</p>';
                html += '<p><strong>Location:</strong> ' + item.location + '</p>';
                html += '<p><strong>Status:</strong> ' + item.status + '</p>';
                html += '<p><strong>Date:</strong> ' + new Date(item.created_at).toLocaleDateString() + '</p>';
                html += '<p><strong>Reported By:</strong> ' + (item.reported_by_name || 'Unknown') + '</p>';
                html += '<hr><p><strong>Description:</strong></p><p>' + item.description + '</p>';
                html += '</div></div>';
                
                const detailsDiv = document.getElementById('itemDetails');
                if (detailsDiv) {
                    detailsDiv.innerHTML = html;
                    const modalEl = document.getElementById('itemModal');
                    if (modalEl) {
                        try {
                            const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
                            modal.show();
                        } catch (e) {
                            alert('Modal display error: ' + e.message);
                        }
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error loading item: ' + error.message);
            });
    };
    
    // Location handler function
    window.handleLocationSelect = function(location, formType) {
        // Skip if we're setting the value programmatically
        if (window.skipLocationHandler) {
            window.skipLocationHandler = false;
            return;
        }
        
        const locationSelect = document.getElementById('location');
        const locationOther = document.getElementById('locationOther');
        
        if (location === 'other') {
            // Show text input for other location
            if (locationOther) {
                locationOther.style.display = 'block';
                locationOther.focus();
            }
            locationSelect.value = '';
        } else if (location === 'Student Quarters') {
            // Show modal to select hostel
            showHostelSelectionModal(location, locationSelect, formType);
        } else {
            // Hide other input if visible
            if (locationOther) {
                locationOther.style.display = 'none';
                locationOther.value = '';
            }
        }
    };
    
    // Show hostel selection modal for Student Quarters
    window.showHostelSelectionModal = function(location, locationSelect, formType) {
        const hostels = ['Casmire', 'Johnson', 'Zanner', 'Crawford'];
        const modal = document.getElementById('hostelSelectionModal');
        
        if (!modal) {
            console.error('Hostel selection modal not found');
            return;
        }
        
        // Update modal title
        const modalTitle = document.getElementById('hostelSelectionTitle');
        if (modalTitle) {
            modalTitle.textContent = 'Select Student Hostel';
        }
        
        // Create hostel selection buttons
        const hostelList = document.getElementById('hostelSelectionList');
        if (hostelList) {
            hostelList.innerHTML = '';
            hostels.forEach(hostel => {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'btn btn-outline-primary w-100 mb-2';
                btn.textContent = hostel;
                btn.onclick = function() {
                    // Set flag to prevent onchange handler from firing
                    window.skipLocationHandler = true;
                    locationSelect.value = location + ', ' + hostel;
                    
                    // Hide modal
                    const bsModal = bootstrap.Modal.getInstance(modal);
                    if (bsModal) bsModal.hide();
                    else new bootstrap.Modal(modal).hide();
                };
                hostelList.appendChild(btn);
            });
        }
        
        // Show modal
        const bsModal = bootstrap.Modal.getInstance(modal) || new bootstrap.Modal(modal);
        bsModal.show();
    };
    </script>
</head>
<body>
<!-- Main Header -->
    <header class="main-header">
        <div class="container">
            <div class="row align-items-center justify-content-center">
                <div class="col-lg-12">
                    <div class="header-start d-flex align-items-center justify-content-start">
                        <img src="<?php echo BASE_URL; ?>/assets/images/anu-logo.png" alt="ANU Logo" class="logo">
                        <div class="header-text ms-0 ms-sm-2 ms-lg-3">
                            <h1 class="mb-1">ANU Lost and Found</h1>
                            <p class="mb-0">Find your lost items or report found ones</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Top Info Bar -->
    <div class="top-bar">
        <div class="container">
            <div class="row w-100 gx-2">
                <div class="col-12 col-lg-6 d-flex flex-wrap gap-2 justify-content-start align-items-center">
                    <div class="top-bar-left text-white small d-flex flex-wrap gap-1">
                        <span class="d-none d-md-inline"><i class="bi bi-envelope-fill"></i> reports@anu.ac.ke</span>
                        <span class="d-none d-md-inline">|</span>
                        <span class="d-none d-lg-inline"><i class="bi bi-geo-alt-fill"></i> Magadi Rd</span>
                        <span class="d-none d-lg-inline">|</span>
                        <span class="d-none d-md-inline"><i class="bi bi-telephone-fill"></i> +254 703 970 520</span>
                    </div>
                </div>
                <div class="col-12 col-lg-6 d-flex flex-wrap gap-2 justify-content-start justify-content-lg-end align-items-center">
                    <div class="top-bar-right d-flex gap-2 align-items-center flex-wrap">
                        <?php if ($loggedIn): ?>
                            <span class="text-white small d-none d-sm-inline">Welcome, <?php echo $userName; ?></span>
                        <?php else: ?>
                            <?php if (in_array($currentPage, ['index.php', 'login.php', 'login_admin.php'])): ?>
                                <a href="javascript:void(0);" class="btn btn-sm btn-outline-light" onclick="scrollToForm('login')">Login</a>
                                <a href="javascript:void(0);" class="btn btn-sm btn-light text-danger" onclick="scrollToForm('register')">Register</a>
                            <?php else: ?>
                                <a href="<?php echo BASE_URL; ?>/auth/login.php" class="btn btn-sm btn-outline-light">Login</a>
                                <a href="<?php echo BASE_URL; ?>/auth/register.php" class="btn btn-sm btn-light text-danger">Register</a>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark" style="background-color: #1a1a1a; box-shadow: 0 2px 14px rgba(0,0,0,0.14);">
        <div class="container">
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="mainNav">
                <ul class="navbar-nav w-100 justify-content-center">
                    <li class="nav-item">
                        <?php if ($loggedIn): ?>
                            <a class="nav-link active" href="<?php echo BASE_URL; ?>/dashboard.php"><i class="bi bi-speedometer2 me-1"></i>Dashboard</a>
                        <?php else: ?>
                            <a class="nav-link active" href="<?php echo BASE_URL; ?>/index.php"><i class="bi bi-house me-1"></i>Home</a>
                        <?php endif; ?>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo BASE_URL; ?>/search.php"><i class="bi bi-search me-1"></i>Search</a>
                    </li>
                    <?php if ($loggedIn && $userRole === 'student'): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo BASE_URL; ?>/report.php"><i class="bi bi-plus-circle me-1"></i>Report</a>
                    </li>
                    <?php endif; ?>
                    <?php if ($loggedIn): ?>
                    <li class="nav-item">
                        <?php if ($userRole === 'student'): ?>
                            <a class="nav-link" href="<?php echo BASE_URL; ?>/claims.php"><i class="bi bi-clipboard-check me-1"></i>My Claims</a>
                        <?php elseif ($userRole === 'security' || $userRole === 'admin'): ?>
                            <a class="nav-link" href="<?php echo BASE_URL; ?>/admin/claims.php"><i class="bi bi-clipboard-check me-1"></i>Claims</a>
                        <?php endif; ?>
                    </li>
                    <?php if ($loggedIn): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo BASE_URL; ?>/<?php echo ($userRole === 'student' ? 'messages.php' : 'admin/messaging.php'); ?>">
                            <i class="bi bi-chat-dots me-1"></i>Messages
                            <?php 
                            // Get unread message count
                            if (isset($_SESSION['user_id'])) {
                                try {
                                    $stmt_msg = $pdo->prepare("SELECT COUNT(*) as count FROM messages WHERE recipient_id = ? AND is_read = FALSE");
                                    $stmt_msg->execute([$_SESSION['user_id']]);
                                    $result_msg = $stmt_msg->fetch();
                                    $unread_count = $result_msg['count'] ?? 0;
                                    if ($unread_count > 0) {
                                        echo '<span class="badge bg-danger rounded-pill ms-1">' . $unread_count . '</span>';
                                    }
                                } catch (Exception $e) {}
                            }
                            ?>                        
                            </a>
                    </li>
                    <?php endif; ?>
                    <?php if ($userRole === 'student'): ?>
                    <li class="nav-item position-relative">
                        <a class="nav-link" href="<?php echo BASE_URL; ?>/notifications.php" style="position: relative; display: inline-block;">
                            <i class="bi bi-bell" style="font-size: 1.2rem;"></i>
                            <?php if ($unread_notifications > 0): ?>
                                <span id="notificationBadge" class="badge bg-danger rounded-pill" style="position: absolute; top: 0px; right: -5px; font-size: 0.7rem; padding: 2px 6px;"><?php echo $unread_notifications; ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <?php elseif ($userRole === 'security' || $userRole === 'admin'): ?>
                    <li class="nav-item position-relative">
                        <a class="nav-link" href="<?php echo BASE_URL; ?>/notifications.php" style="position: relative; display: inline-block;">
                            <i class="bi bi-bell" style="font-size: 1.2rem;"></i>
                            <?php 
                            if (isset($_SESSION['user_id'])) {
                                try {
                                    require_once __DIR__ . '/notifications.php';
                                    $admin_unread = getUnreadNotificationCount($_SESSION['user_id']);
                                    if ($admin_unread > 0): ?>
                                        <span id="notificationBadge" class="badge bg-danger rounded-pill" style="position: absolute; top: 0px; right: -5px; font-size: 0.7rem; padding: 2px 6px;"><?php echo $admin_unread; ?></span>
                                    <?php endif;
                                } catch (Exception $e) {}
                            }
                            ?>
                        </a>
                    </li>
                            }
                            ?>
                        </a>
                    </li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo BASE_URL; ?>/profile.php"><i class="bi bi-person me-1"></i>Profile</a>
                    </li>
                    <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="adminNav" role="button" data-bs-toggle="dropdown" 
                           style="background: linear-gradient(135deg, #ed1c24 0%, #fac923 100%); border-radius: 8px; padding: 8px 15px !important; margin: 5px; color: black; font-weight: 600;">
                            <i class="bi bi-speedometer2"></i> Admin
                        </a>
                        <ul class="dropdown-menu dropdown-menu-dark" aria-labelledby="adminNav" style="background-color: #2a2a2a; border: 1px solid #ed1c24;">
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/admin/dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/admin/claims.php"><i class="bi bi-file-earmark-check"></i> Review Claims</a></li>
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/admin/reports.php"><i class="bi bi-file-earmark-pdf"></i> Reports</a></li>
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/admin/users.php"><i class="bi bi-people"></i> Manage Users</a></li>
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/admin/managemedia.php"><i class="bi bi-image"></i> Manage Media</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
                        </ul>
                    </li>
                    <?php endif; ?>
                    <?php if (($_SESSION['user_role'] ?? null) !== 'admin'): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo BASE_URL; ?>/logout.php"><i class="bi bi-box-arrow-right me-1"></i>Logout</a>
                    </li>
                    <?php endif; ?>
                    <?php else: ?>
                    <?php if ($showAdminLink): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo $adminLoginUrl; ?>"><i class="bi bi-shield-check me-1"></i>Admin</a>
                    </li>
                    <?php endif; ?>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <?php if (in_array($currentPage, ['index.php', 'login.php', 'login_admin.php']) || !$loggedIn): ?>
    <section class="hero-panel" style="background-image: url('<?php echo BASE_URL; ?>/assets/images/anu_campus.jpg'); background-size: cover; background-position: center; background-attachment: fixed; position: relative;">
        <!-- Dark overlay for better text readability -->
        <div style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0, 0, 0, 0.4); z-index: 1;"></div>
        <div class="container py-5" style="position: relative; z-index: 2;">
            <div class="row align-items-center">
                <div class="col-lg-6 text-white">
                    <span class="hero-badge">Campus Lost & Found</span>
                    <h1 class="hero-title">Recover lost items faster with a modern, secure system.</h1>
                    <p class="hero-text">Report lost or found items, track claim status, and stay connected to ANU security in one polished dashboard.</p>
                    <div class="hero-card shadow-lg bg-white bg-opacity-10 p-4 rounded-4 border border-white border-opacity-25 mt-4">
                        <h4 class="mb-3">Quick Access</h4>
                        <p class="mb-3">Sign in to manage claims, browse found items, or report a lost item in seconds.</p>
                        <div class="d-flex flex-wrap gap-2">
                            <span class="badge bg-danger rounded-pill py-2 px-3">Secure</span>
                            <span class="badge bg-white text-danger rounded-pill py-2 px-3">Fast</span>
                            <span class="badge bg-white text-dark rounded-pill py-2 px-3">Friendly</span>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6 text-white mt-4 mt-lg-0 d-flex align-items-center justify-content-center">
                    <div class="card login-card shadow-lg" style="background-color: #ffffcc; border: 2px solid #ff6600; width: 100%; max-width: 400px;">
                        <!-- Login Form -->
                        <div id="loginForm">
                            <div class="card-header login-card-header text-center" style="background-color: #ffffcc !important; color: #000;">
                                <img src="<?php echo BASE_URL; ?>/assets/images/lostfound.png" alt="ANU Lost and Found" class="login-logo mb-2" style="width: 135px; height: 150px; object-fit: contain;">
                                <h5 class="mb-0">Login to Your Account</h5>
                            </div>
                            <div class="card-body">
                                <?php if (isset($_SESSION['login_error'])): ?>
                                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                        <i class="bi bi-exclamation-circle-fill"></i> <strong>Login Failed:</strong> <?php echo htmlspecialchars($_SESSION['login_error']); unset($_SESSION['login_error']); ?>
                                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                    </div>
                                <?php endif; ?>
                                <form action="<?php echo BASE_URL; ?>/auth/process_login.php" method="POST" autocomplete="off">
                                    <input type="text" name="fakeusernameremembered" value="" style="display:none;">
                                    <input type="password" name="fakepasswordremembered" value="" style="display:none;">
                                    <div class="mb-2">
                                        <label for="email" class="form-label">Email</label>
                                        <input type="email" class="form-control form-control-sm" id="email" name="email" placeholder="Email" autocomplete="off" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="password" class="form-label">Password</label>
                                        <input type="password" class="form-control form-control-sm" id="password" name="password" placeholder="Password" autocomplete="off" required>
                                    </div>
                                    <div class="d-grid">
                                        <button type="submit" class="btn btn-danger btn-sm">Login</button>
                                    </div>
                                </form>
                                <div class="text-center mt-2">
                                    <small class="text-muted">Don't have an account? <a href="javascript:void(0);" class="text-danger fw-bold" onclick="toggleAuthForm()">Register</a></small>
                                </div>
                            </div>
                        </div>

                        <!-- Register Form -->
                        <div id="registerForm" style="display:none;">
                            <div class="card-header login-card-header text-center" style="background-color: #ffffcc !important; color: #000;">
                                <img src="<?php echo BASE_URL; ?>/assets/images/lostfound.png" alt="ANU Lost and Found" class="login-logo mb-2" style="width: 135px; height: 150px; object-fit: contain;">
                                <h5 class="mb-0">Create New Account</h5>
                            </div>
                            <div class="card-body">
                                <form action="<?php echo BASE_URL; ?>/auth/register.php" method="POST" autocomplete="off" id="headerRegisterForm">
                                    <input type="text" name="fakeusernameremembered" value="" style="display:none;">
                                    <input type="password" name="fakepasswordremembered" value="" style="display:none;">
                                    
                                    <!-- Name and Phone -->
                                    <div class="row">
                                        <div class="col-6 mb-2">
                                            <label for="reg_name" class="form-label">Full Name</label>
                                            <input type="text" class="form-control form-control-sm" id="reg_name" name="name" placeholder="Name" autocomplete="off" required>
                                        </div>
                                        <div class="col-6 mb-2">
                                            <label for="reg_phone" class="form-label">Phone</label>
                                            <input type="tel" class="form-control form-control-sm" id="reg_phone" name="phone" placeholder="Phone" autocomplete="off" required>
                                        </div>
                                    </div>
                                    
                                    <!-- Date Joined and Course -->
                                    <div class="row">
                                        <div class="col-6 mb-2">
                                            <label for="reg_date_joined" class="form-label" style="font-size: 0.85rem;">Date Joined</label>
                                            <input type="month" class="form-control form-control-sm" id="reg_date_joined" name="date_joined" required onchange="headerGenerateEmail()">
                                        </div>
                                        <div class="col-6 mb-2">
                                            <label for="reg_course" class="form-label" style="font-size: 0.85rem;">Course</label>
                                            <select class="form-select form-select-sm" id="reg_course" name="course" required onchange="headerGenerateEmail()">
                                                <option value="">-- Select --</option>
                                                <optgroup label="Law">
                                                    <option value="llb">LLB</option>
                                                    <option value="crj">Criminal Justice</option>
                                                </optgroup>
                                                <optgroup label="Business">
                                                    <option value="mba">MBA</option>
                                                    <option value="bca">B.Com Acct</option>
                                                    <option value="bcb">B.Com Finance</option>
                                                    <option value="bcm">B.Com Marketing</option>
                                                    <option value="ibm">Int'l Bus Mgmt</option>
                                                    <option value="hrm">HR Management</option>
                                                    <option value="cbm">Cert Bus Mgmt</option>
                                                    <option value="cps">CPSP-K</option>
                                                </optgroup>
                                                <optgroup label="Science & Tech">
                                                    <option value="abt">BBIT</option>
                                                    <option value="acs">CS</option>
                                                    <option value="ait">MSc AIT</option>
                                                    <option value="erm">MSc ERM</option>
                                                    <option value="psc">Procurement</option>
                                                    <option value="enm">Env Mgmt</option>
                                                    <option value="dit">Dip IT</option>
                                                    <option value="dmo">Dip Mobile</option>
                                                    <option value="iad">Int'l Adv Dip CS</option>
                                                </optgroup>
                                                <optgroup label="Humanities">
                                                    <option value="pcs">Peace Studies</option>
                                                    <option value="mce">Mass Comm Elec</option>
                                                    <option value="mcp">Mass Comm Print</option>
                                                    <option value="edu">Education</option>
                                                </optgroup>
                                                <optgroup label="Religion">
                                                    <option value="mdv">Master Divinity</option>
                                                    <option value="thl">Theology</option>
                                                </optgroup>
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <!-- Auto-generated Email -->
                                    <div class="mb-2">
                                        <label for="reg_email" class="form-label">Email (Auto-generated)</label>
                                        <input type="email" class="form-control form-control-sm" id="reg_email" name="email" placeholder="Will auto-fill" readonly style="background-color: #f0f0f0;">
                                    </div>
                                    
                                    <!-- Hidden Registration ID -->
                                    <input type="hidden" id="reg_registration_id" name="registration_id">
                                    
                                    <!-- Password -->
                                    <div class="row">
                                        <div class="col-6 mb-2">
                                            <label for="reg_password" class="form-label">Password</label>
                                            <input type="password" class="form-control form-control-sm" id="reg_password" name="password" placeholder="Password" autocomplete="off" required>
                                        </div>
                                        <div class="col-6 mb-3">
                                            <label for="reg_confirm_password" class="form-label">Confirm</label>
                                            <input type="password" class="form-control form-control-sm" id="reg_confirm_password" name="confirm_password" placeholder="Confirm" autocomplete="off" required>
                                        </div>
                                    </div>
                                    
                                    <div class="d-grid">
                                        <button type="submit" class="btn btn-danger btn-sm" id="reg_submitBtn" disabled>Register</button>
                                    </div>
                                </form>
                                <div class="text-center mt-2">
                                    <small class="text-muted">Already have an account? <a href="javascript:void(0);" class="text-danger fw-bold" onclick="toggleAuthForm()">Login</a></small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <?php endif; ?>

<script>
function toggleAuthForm() {
    const loginForm = document.getElementById('loginForm');
    const registerForm = document.getElementById('registerForm');
    
    if (loginForm && registerForm) {
        if (loginForm.style.display === 'none') {
            loginForm.style.display = 'block';
            registerForm.style.display = 'none';
        } else {
            loginForm.style.display = 'none';
            registerForm.style.display = 'block';
        }
    }
}

function scrollToForm(formType) {
    // Scroll to the form section
    const heroPanel = document.querySelector('.hero-panel');
    if (heroPanel) {
        heroPanel.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
    
    // Show the appropriate form
    const loginForm = document.getElementById('loginForm');
    const registerForm = document.getElementById('registerForm');
    
    if (formType === 'login') {
        if (loginForm) loginForm.style.display = 'block';
        if (registerForm) registerForm.style.display = 'none';
    } else if (formType === 'register') {
        if (loginForm) loginForm.style.display = 'none';
        if (registerForm) registerForm.style.display = 'block';
    }
}

function headerGenerateEmail() {
    const date_joined = document.getElementById('reg_date_joined').value;
    const course = document.getElementById('reg_course').value;
    const email = document.getElementById('reg_email');
    const registration_id = document.getElementById('reg_registration_id');
    const submitBtn = document.getElementById('reg_submitBtn');
    
    if (!date_joined || !course) {
        email.value = '';
        registration_id.value = '';
        submitBtn.disabled = true;
        return;
    }
    
    // Parse date
    const [year, month] = date_joined.split('-');
    const yearShort = year.slice(2);
    
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
            const regId = yearShort + monthCode + progCode + '001';
            const generatedEmail = regId.toLowerCase() + '@anu.ac.ke';
            email.value = generatedEmail;
            registration_id.value = regId;
            submitBtn.disabled = false;
        });
}
</script>
