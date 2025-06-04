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

    // Set default values for missing fields
    $technician['email'] = $technician['email'] ?? '';
    $technician['phone'] = $technician['phone'] ?? '';
    $technician['name'] = $technician['name'] ?? '';
    $technician['username'] = $technician['username'] ?? '';
    $technician['profile_picture'] = $technician['profile_picture'] ?? '';
    $technician['created_at'] = $technician['created_at'] ?? date('Y-m-d H:i:s');
    $technician['updated_at'] = $technician['updated_at'] ?? date('Y-m-d H:i:s');

    // Get technician's performance statistics
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_orders,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_orders,
            AVG(CASE 
                WHEN status = 'completed' 
                THEN TIMESTAMPDIFF(HOUR, created_at, completed_at)
                ELSE NULL 
            END) as avg_completion_time
        FROM job_orders 
        WHERE assigned_technician_id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - Job Order System</title>
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
        .profile-header {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        .profile-picture {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid var(--primary-blue);
            box-shadow: 0 4px 20px rgba(26, 35, 126, 0.2);
        }

        .profile-stats {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }

        .profile-stats:hover {
            transform: translateY(-5px);
        }

        .profile-stats i {
            font-size: 2rem;
            color: var(--primary-blue);
            margin-bottom: 1rem;
        }

        .profile-stats h3 {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
            color: var(--primary-blue);
        }

        .profile-stats p {
            color: #666;
            margin-bottom: 0;
        }

        .profile-details {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        .detail-item {
            margin-bottom: 1.5rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid #eee;
        }

        .detail-item:last-child {
            margin-bottom: 0;
            padding-bottom: 0;
            border-bottom: none;
        }

        .detail-label {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }

        .detail-value {
            color: #333;
            font-weight: 500;
        }

        .edit-profile-btn {
            background-color: var(--primary-blue);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .edit-profile-btn:hover {
            background-color: var(--hover-blue);
            transform: translateY(-2px);
            color: white;
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
                                    <a class="dropdown-item d-flex align-items-center py-2 text-danger" href="../admin/logout.php">
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
                <!-- Header -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h4 class="mb-0">Technician Profile</h4>
                        <p class="text-muted mb-0">View and manage your account details</p>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#editProfileModal">
                            <i class="fas fa-edit me-2"></i>Edit Profile
                        </button>
                        <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#changePasswordModal">
                            <i class="fas fa-key me-2"></i>Change Password
                        </button>
                    </div>
                </div>

                <!-- Alert Messages -->
                <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?= $_SESSION['success'] ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['success']); endif; ?>

                <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= $_SESSION['error'] ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['error']); endif; ?>

                <!-- Profile Content -->
                <div class="row">
                    <div class="col-md-4">
                        <div class="card shadow-sm h-100">
                            <div class="card-body text-center d-flex flex-column align-items-center justify-content-center py-5">
                                <img src="<?= !empty($technician['profile_picture']) ? '../' . htmlspecialchars($technician['profile_picture']) : 'https://ui-avatars.com/api/?name=' . urlencode($technician['name'] ?: 'Technician') . '&background=1a237e&color=fff' ?>" 
                                     alt="Profile" 
                                     class="rounded-circle mb-4" 
                                     style="width: 150px; height: 150px; object-fit: cover; border: 4px solid #4A90E2; box-shadow: 0 0 10px rgba(74, 144, 226, 0.3);">
                                <h5 class="card-title mb-2"><?= htmlspecialchars($technician['name'] ?: 'Technician') ?></h5>
                                <p class="text-muted mb-0">Technician</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-8">
                        <div class="card shadow-sm h-100">
                            <div class="card-body">
                                <h5 class="card-title mb-4">Account Information</h5>
                                <div class="row mb-3">
                                    <div class="col-sm-3">
                                        <p class="mb-0 text-muted">Full Name</p>
                                    </div>
                                    <div class="col-sm-9">
                                        <p class="mb-0"><?= htmlspecialchars($technician['name'] ?: 'N/A') ?></p>
                                    </div>
                                </div>
                                <hr>
                                <div class="row mb-3">
                                    <div class="col-sm-3">
                                        <p class="mb-0 text-muted">Username</p>
                                    </div>
                                    <div class="col-sm-9">
                                        <p class="mb-0"><?= htmlspecialchars($technician['username'] ?: 'N/A') ?></p>
                                    </div>
                                </div>
                                <hr>
                                <div class="row mb-3">
                                    <div class="col-sm-3">
                                        <p class="mb-0 text-muted">Email</p>
                                    </div>
                                    <div class="col-sm-9">
                                        <p class="mb-0"><?= htmlspecialchars($technician['email'] ?: 'N/A') ?></p>
                                    </div>
                                </div>
                                <hr>
                                <div class="row mb-3">
                                    <div class="col-sm-3">
                                        <p class="mb-0 text-muted">Phone</p>
                                    </div>
                                    <div class="col-sm-9">
                                        <p class="mb-0"><?= htmlspecialchars($technician['phone'] ?: 'N/A') ?></p>
                                    </div>
                                </div>
                                <hr>
                                <div class="row mb-3">
                                    <div class="col-sm-3">
                                        <p class="mb-0 text-muted">Member Since</p>
                                    </div>
                                    <div class="col-sm-9">
                                        <p class="mb-0"><?= date('F d, Y', strtotime($technician['created_at'])) ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Profile Modal -->
    <div class="modal fade" id="editProfileModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Profile</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form action="edit-profile.php" method="POST" enctype="multipart/form-data">
                        <div class="text-center mb-4">
                            <img src="<?= !empty($technician['profile_picture']) ? '../' . htmlspecialchars($technician['profile_picture']) : 'https://ui-avatars.com/api/?name=' . urlencode($technician['name'] ?: 'Technician') . '&background=1a237e&color=fff' ?>" 
                                 alt="Profile" 
                                 class="rounded-circle mb-3" 
                                 style="width: 150px; height: 150px; object-fit: cover; border: 4px solid #4A90E2; box-shadow: 0 0 10px rgba(74, 144, 226, 0.3);"
                                 id="profilePreview">
                            <div class="mb-3">
                                <label class="form-label">Profile Picture</label>
                                <input type="file" class="form-control" name="profile_picture" accept="image/jpeg,image/png,image/gif">
                                <small class="text-muted">Max file size: 5MB. Allowed formats: JPG, PNG, GIF</small>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Full Name</label>
                            <input type="text" class="form-control" name="name" value="<?= htmlspecialchars($technician['name'] ?? '') ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Username</label>
                            <input type="text" class="form-control" name="username" value="<?= htmlspecialchars($technician['username'] ?? '') ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" value="<?= htmlspecialchars($technician['email'] ?? '') ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Phone</label>
                            <input type="tel" class="form-control" name="phone" value="<?= htmlspecialchars($technician['phone'] ?? '') ?>" required>
                        </div>
                        <div class="modal-footer px-0 pb-0">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Change Password Modal -->
    <div class="modal fade" id="changePasswordModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Change Password</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form action="update-password.php" method="POST">
                        <div class="mb-3">
                            <label class="form-label">Current Password</label>
                            <div class="password-field">
                                <input type="password" class="form-control" name="current_password" required>
                                <span class="password-toggle" onclick="togglePassword(this.previousElementSibling)">
                                    <i class="fas fa-eye"></i>
                                </span>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">New Password</label>
                            <div class="password-field">
                                <input type="password" class="form-control" name="new_password" required minlength="8">
                                <span class="password-toggle" onclick="togglePassword(this.previousElementSibling)">
                                    <i class="fas fa-eye"></i>
                                </span>
                            </div>
                            <small class="text-muted">Password must be at least 8 characters long</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Confirm New Password</label>
                            <div class="password-field">
                                <input type="password" class="form-control" name="confirm_password" required minlength="8">
                                <span class="password-toggle" onclick="togglePassword(this.previousElementSibling)">
                                    <i class="fas fa-eye"></i>
                                </span>
                            </div>
                        </div>
                        <div class="modal-footer px-0 pb-0">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Change Password</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script src="../js/dashboard.js"></script>
    <script>
        function togglePassword(input) {
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

        // Profile picture preview
        document.querySelector('input[name="profile_picture"]').addEventListener('change', function(e) {
            if (e.target.files && e.target.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('profilePreview').src = e.target.result;
                }
                reader.readAsDataURL(e.target.files[0]);
            }
        });
    </script>
</body>
</html> 