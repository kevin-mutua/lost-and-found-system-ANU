<?php
session_start();
define('BASE_URL', '/lost_and_found');
require_once '../includes/db.php';

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    // Redirect based on user role
    $role = isset($_SESSION['role']) ? $_SESSION['role'] : '';
    if ($role === 'admin') {
        header('Location: ' . BASE_URL . '/admin/dashboard.php');
    } elseif ($role === 'security') {
        header('Location: ' . BASE_URL . '/dashboard.php');
    } else {
        header('Location: ' . BASE_URL . '/dashboard.php');
    }
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - ANU Lost & Found</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?php echo BASE_URL; ?>/assets/css/style.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: linear-gradient(135deg, #ed1c24 0%, #fac923 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .admin-login-container {
            width: 100%;
            max-width: 450px;
            padding: 20px;
        }

        .admin-card {
            background: white;
            border: none;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            border-radius: 16px;
            overflow: hidden;
        }

        .admin-card-header {
            background: linear-gradient(135deg, #000 0%, #1a1a1a 100%);
            color: white;
            padding: 2rem;
            text-align: center;
        }

        .admin-card-header h3 {
            font-weight: 700;
            margin-bottom: 0.5rem;
            font-size: 1.8rem;
        }

        .admin-card-header p {
            opacity: 0.9;
            margin: 0;
            font-size: 0.95rem;
        }

        .admin-card-body {
            padding: 2.5rem 2rem;
        }

        .form-label {
            color: #000;
            font-weight: 600;
            margin-bottom: 0.75rem;
            display: block;
        }

        .form-control {
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            padding: 12px 15px;
            font-size: 1rem;
            transition: all 0.3s;
            margin-bottom: 1.5rem;
        }

        .form-control:focus {
            border-color: #ed1c24;
            box-shadow: 0 0 0 0.2rem rgba(237,28,36,0.1);
            outline: none;
        }

        .admin-login-btn {
            background: linear-gradient(135deg, #ed1c24 0%, #fac923 100%);
            color: #000;
            font-weight: 700;
            border: none;
            border-radius: 8px;
            padding: 14px 20px;
            font-size: 1rem;
            width: 100%;
            cursor: pointer;
            transition: all 0.3s;
            margin-bottom: 1.5rem;
        }

        .admin-login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(237,28,36,0.4);
        }

        .admin-login-btn:active {
            transform: translateY(0);
        }

        .alert {
            border-radius: 8px;
            border: none;
            margin-bottom: 1.5rem;
            padding: 12px 15px;
        }

        .back-link-container {
            text-align: center;
            padding-top: 1rem;
            border-top: 1px solid #e0e0e0;
        }

        .back-link {
            color: #666;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.95rem;
            transition: all 0.3s;
        }

        .back-link:hover {
            color: #ed1c24;
        }

        .admin-badge {
            display: inline-block;
            background: linear-gradient(135deg, #fac923 0%, #ed1c24 100%);
            color: #000;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <div class="admin-login-container">
        <div class="admin-card">
            <div class="admin-card-header">
                <span class="admin-badge">Admin Portal</span>
                <h3>Admin Access</h3>
                <p>Manage claims, verify items, and oversee campus recovery</p>
            </div>
            <div class="admin-card-body">
                <?php if (isset($_SESSION['login_error'])): ?>
                    <div class="alert alert-danger" role="alert">
                        <?php echo htmlspecialchars($_SESSION['login_error']); unset($_SESSION['login_error']); ?>
                    </div>
                <?php endif; ?>
                <form action="<?php echo BASE_URL; ?>/auth/process_login.php" method="POST" autocomplete="off">
                    <input type="text" name="fakeusernameremembered" value="" style="display:none;">
                    <input type="password" name="fakepasswordremembered" value="" style="display:none;">
                    
                    <label for="admin_email" class="form-label">Admin Email</label>
                    <input type="email" class="form-control" id="admin_email" name="email" placeholder="Enter admin email" autocomplete="off" required>
                    
                    <label for="admin_password" class="form-label">Password</label>
                    <input type="password" class="form-control" id="admin_password" name="password" placeholder="Enter password" autocomplete="off" required>
                    
                    <button type="submit" class="admin-login-btn">Admin Login</button>
                </form>
                <div class="back-link-container">
                    <a href="<?php echo BASE_URL; ?>/index.php" class="back-link">← Back to Home</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
