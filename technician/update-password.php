<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is a technician
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'technician') {
    header('Location: ../index.php');
    exit();
}

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Get technician details
    $stmt = $pdo->prepare("SELECT * FROM technicians WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $technician = $stmt->fetch(PDO::FETCH_ASSOC);

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        // Validate input
        $errors = [];
        
        if (empty($current_password)) {
            $errors[] = "Current password is required";
        }
        
        if (empty($new_password)) {
            $errors[] = "New password is required";
        } elseif (strlen($new_password) < 8) {
            $errors[] = "New password must be at least 8 characters long";
        }
        
        if ($new_password !== $confirm_password) {
            $errors[] = "New passwords do not match";
        }

        // Verify current password
        if (empty($errors)) {
            if (!password_verify($current_password, $technician['password'])) {
                $errors[] = "Current password is incorrect";
            } else {
                // Update password
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE technicians SET password = ? WHERE id = ?");
                
                if ($stmt->execute([$hashed_password, $_SESSION['user_id']])) {
                    $_SESSION['success'] = "Password updated successfully";
                    header('Location: profile.php');
                    exit();
                } else {
                    $errors[] = "Failed to update password";
                }
            }
        }
    }
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Password - Job Order System</title>
    <link rel="icon" href="../images/logo-favicon.ico" type="image/x-icon">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .password-container {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        .form-label {
            font-weight: 500;
            color: #333;
        }

        .form-control {
            border-radius: 8px;
            border: 1px solid #ddd;
            padding: 0.75rem;
        }

        .form-control:focus {
            border-color: var(--primary-blue);
            box-shadow: 0 0 0 0.2rem rgba(26, 35, 126, 0.25);
        }

        .btn-update {
            background-color: var(--primary-blue);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .btn-update:hover {
            background-color: var(--hover-blue);
            transform: translateY(-2px);
            color: white;
        }

        .btn-cancel {
            background-color: #f8f9fa;
            color: #333;
            border: 1px solid #ddd;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .btn-cancel:hover {
            background-color: #e9ecef;
            transform: translateY(-2px);
        }

        .alert {
            border-radius: 8px;
            margin-bottom: 1.5rem;
        }

        .password-toggle {
            cursor: pointer;
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            color: #666;
        }

        .password-field {
            position: relative;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <!-- Sidebar -->
        <nav id="sidebar" class="text-white">
            <div class="sidebar-header">
                <div class="text-center mb-3">
                    <img src="../images/logo.png" alt="Logo" style="width: 70px; height: 70px; margin-bottom: 10px; border-radius: 50%; border: 2px solid #4A90E2; box-shadow: 0 0 10px rgba(74, 144, 226, 0.5); display: block; margin-left: auto; margin-right: auto;">
                </div>
            </div>

            <ul class="list-unstyled components">
                <li>
                    <a href="dashboard.php">
                        <i class="fas fa-tachometer-alt"></i>
                        Dashboard
                    </a>
                </li>
                <li>
                    <a href="#jobOrdersSubmenu" data-bs-toggle="collapse" aria-expanded="false" class="dropdown-toggle">
                        <i class="fas fa-clipboard-list"></i>
                        Job Orders
                    </a>
                    <ul class="collapse list-unstyled" id="jobOrdersSubmenu">
                        <li>
                            <a href="orders.php">
                                <i class="fas fa-file-alt"></i>
                                Orders
                            </a>
                        </li>
                        <li>
                            <a href="archived.php">
                                <i class="fas fa-archive"></i>
                                Archived
                            </a>
                        </li>
                    </ul>
                </li>
                <li class="active">
                    <a href="profile.php">
                        <i class="fas fa-user"></i>
                        Profile
                    </a>
                </li>
                <li>
                    <a href="reports.php">
                        <i class="fas fa-chart-bar"></i>
                        Reports
                    </a>
                </li>
                <li>
                    <a href="settings.php">
                        <i class="fas fa-cog"></i>
                        Settings
                    </a>
                </li>
            </ul>
        </nav>

        <!-- Page Content -->
        <div id="content">
            <nav class="navbar navbar-expand-lg navbar-light bg-white">
                <div class="container-fluid">
                    <button type="button" id="sidebarCollapse" class="btn">
                        <i class="fas fa-bars"></i>
                    </button>
                    <div class="ms-auto d-flex align-items-center">
                        <div class="dropdown">
                            <a class="d-flex align-items-center text-decoration-none dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                                <img src="<?= !empty($technician['profile_picture']) ? '../' . htmlspecialchars($technician['profile_picture']) : 'https://ui-avatars.com/api/?name=' . urlencode($technician['name'] ?: 'Technician') . '&background=1a237e&color=fff' ?>" 
                                     alt="Technician" 
                                     class="rounded-circle me-2" 
                                     width="32" 
                                     height="32"
                                     style="object-fit: cover; border: 2px solid #4A90E2;">
                                <span class="me-3">Welcome, <?= htmlspecialchars($technician['name'] ?: 'Technician') ?></span>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0" style="min-width: 200px;">
                                <li>
                                    <a class="dropdown-item d-flex align-items-center py-2" href="profile.php">
                                        <i class="fas fa-user me-2 text-primary"></i>
                                        <span>Profile</span>
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider my-2"></li>
                                <li>
                                    <a class="dropdown-item d-flex align-items-center py-2 text-danger" href="../logout.php">
                                        <i class="fas fa-sign-out-alt me-2"></i>
                                        <span>Logout</span>
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </nav>

            <div class="container-fluid">
                <div class="row justify-content-center">
                    <div class="col-md-6">
                        <div class="password-container">
                            <h2 class="mb-4">Update Password</h2>
                            
                            <?php if (!empty($errors)): ?>
                                <div class="alert alert-danger">
                                    <ul class="mb-0">
                                        <?php foreach ($errors as $error): ?>
                                            <li><?= htmlspecialchars($error) ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>

                            <form action="" method="POST">
                                <div class="mb-3">
                                    <label for="current_password" class="form-label">Current Password</label>
                                    <div class="password-field">
                                        <input type="password" 
                                               class="form-control" 
                                               id="current_password" 
                                               name="current_password" 
                                               required>
                                        <span class="password-toggle" onclick="togglePassword('current_password')">
                                            <i class="fas fa-eye"></i>
                                        </span>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="new_password" class="form-label">New Password</label>
                                    <div class="password-field">
                                        <input type="password" 
                                               class="form-control" 
                                               id="new_password" 
                                               name="new_password" 
                                               required>
                                        <span class="password-toggle" onclick="togglePassword('new_password')">
                                            <i class="fas fa-eye"></i>
                                        </span>
                                    </div>
                                    <small class="text-muted">Password must be at least 8 characters long</small>
                                </div>

                                <div class="mb-4">
                                    <label for="confirm_password" class="form-label">Confirm New Password</label>
                                    <div class="password-field">
                                        <input type="password" 
                                               class="form-control" 
                                               id="confirm_password" 
                                               name="confirm_password" 
                                               required>
                                        <span class="password-toggle" onclick="togglePassword('confirm_password')">
                                            <i class="fas fa-eye"></i>
                                        </span>
                                    </div>
                                </div>

                                <div class="d-flex justify-content-between">
                                    <a href="profile.php" class="btn btn-cancel">
                                        <i class="fas fa-times me-2"></i>Cancel
                                    </a>
                                    <button type="submit" class="btn btn-update">
                                        <i class="fas fa-key me-2"></i>Update Password
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script src="../js/dashboard.js"></script>
    <script>
        function togglePassword(inputId) {
            const input = document.getElementById(inputId);
            const icon = input.nextElementSibling.querySelector('i');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
    </script>
</body>
</html> 