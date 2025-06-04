<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: index.php');
    exit();
}

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Get all technicians with their order counts
    $stmt = $pdo->query("
        SELECT 
            t.*,
            COUNT(DISTINCT jo.id) as total_orders,
            SUM(CASE WHEN jo.status = 'completed' THEN 1 ELSE 0 END) as completed_orders,
            SUM(CASE WHEN jo.status = 'in_progress' THEN 1 ELSE 0 END) as in_progress_orders,
            SUM(CASE WHEN jo.status = 'pending' THEN 1 ELSE 0 END) as pending_orders
        FROM technicians t
        LEFT JOIN job_orders jo ON t.id = jo.assigned_technician_id
        GROUP BY t.id
        ORDER BY t.name ASC
    ");
    $technicians = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Technicians - Job Order System</title>
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
        #sidebar .components li a[aria-expanded="true"] {
            background: rgba(255, 255, 255, 0.1);
        }
        #sidebar .components li .collapse {
            padding-left: 1rem;
        }
        #sidebar .components li .collapse a {
            padding: 0.5rem 1rem;
            font-size: 0.9rem;
        }
        #sidebar .components li .collapse a:hover {
            background: rgba(255, 255, 255, 0.1);
        }
        .technician-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
        }
        .status-badge {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 6px;
        }
        .status-active {
            background-color: #4caf50;
        }
        .status-inactive {
            background-color: #f44336;
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
                    <a href="technicians.php">
                        <i class="fas fa-users-cog"></i>
                        Technicians
                    </a>
                </li>
                <li>
                    <a href="reports.php">
                        <i class="fas fa-chart-bar"></i>
                        Reports
                    </a>
                </li>
                <li>
                    <a href="#settingsSubmenu" data-bs-toggle="collapse" aria-expanded="false" class="dropdown-toggle">
                        <i class="fas fa-cog"></i>
                        Settings
                    </a>
                    <ul class="collapse list-unstyled" id="settingsSubmenu">
                        <li>
                            <a href="settings/index.php">
                                <i class="fas fa-user-shield"></i>
                                Admin Settings
                            </a>
                        </li>
                        <li>
                            <a href="aircon_models.php">
                                <i class="fas fa-snowflake"></i>
                                Aircon Models
                            </a>
                        </li>
                    </ul>
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
                                <img src="https://ui-avatars.com/api/?name=<?= urlencode($_SESSION['username']) ?>&background=1a237e&color=fff" alt="Admin" class="rounded-circle me-2" width="32" height="32">
                                <span class="me-3">Welcome, <?= htmlspecialchars($_SESSION['username']) ?></span>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user me-2"></i>Profile</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </nav>

            <div class="container-fluid">
                <!-- Header -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h4 class="mb-0">Technicians</h4>
                        <p class="text-muted mb-0">Manage your service technicians</p>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTechnicianModal">
                            <i class="fas fa-plus me-2"></i>Add Technician
                        </button>
                        <button type="button" class="btn btn-outline-primary" onclick="window.print()">
                            <i class="fas fa-print me-2"></i>Print
                        </button>
                    </div>
                </div>

                <!-- Alert Messages -->
                <?php if (isset($_SESSION['success_message'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($_SESSION['success_message']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['success_message']); endif; ?>

                <?php if (isset($_SESSION['error_message'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($_SESSION['error_message']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['error_message']); endif; ?>

                <!-- Technicians Table -->
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Technician</th>
                                        <th>Contact</th>
                                        <th>Status</th>
                                        <th>Orders</th>
                                        <th>Performance</th>
                                        <th class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($technicians as $tech): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <img src="https://ui-avatars.com/api/?name=<?= urlencode($tech['name']) ?>&background=1a237e&color=fff" 
                                                     alt="<?= htmlspecialchars($tech['name']) ?>" 
                                                     class="technician-avatar me-3">
                                                <div>
                                                    <h6 class="mb-0"><?= htmlspecialchars($tech['name']) ?></h6>
                                                    <small class="text-muted">@<?= htmlspecialchars($tech['username']) ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex flex-column">
                                                <span class="text-muted">
                                                    <i class="fas fa-phone me-2"></i><?= htmlspecialchars($tech['phone']) ?>
                                                </span>
                                                <small class="text-muted">
                                                    <i class="fas fa-calendar me-2"></i>Joined <?= date('M d, Y', strtotime($tech['created_at'])) ?>
                                                </small>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="status-badge status-active"></span>
                                            <span class="text-success">Active</span>
                                        </td>
                                        <td>
                                            <div class="d-flex gap-3">
                                                <div class="text-center">
                                                    <h6 class="mb-0"><?= $tech['total_orders'] ?></h6>
                                                    <small class="text-muted">Total</small>
                                                </div>
                                                <div class="text-center">
                                                    <h6 class="mb-0 text-success"><?= $tech['completed_orders'] ?></h6>
                                                    <small class="text-muted">Completed</small>
                                                </div>
                                                <div class="text-center">
                                                    <h6 class="mb-0 text-primary"><?= $tech['in_progress_orders'] ?></h6>
                                                    <small class="text-muted">In Progress</small>
                                                </div>
                                                <div class="text-center">
                                                    <h6 class="mb-0 text-warning"><?= $tech['pending_orders'] ?></h6>
                                                    <small class="text-muted">Pending</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <?php
                                            $completion_rate = $tech['total_orders'] > 0 
                                                ? round(($tech['completed_orders'] / $tech['total_orders']) * 100) 
                                                : 0;
                                            ?>
                                            <div class="d-flex align-items-center">
                                                <div class="progress flex-grow-1" style="height: 6px;">
                                                    <div class="progress-bar bg-success" role="progressbar" 
                                                         style="width: <?= $completion_rate ?>%"></div>
                                                </div>
                                                <span class="ms-2"><?= $completion_rate ?>%</span>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex justify-content-center gap-2">
                                                <a href="technician-orders.php?id=<?= $tech['id'] ?>" 
                                                   class="btn btn-sm btn-light" 
                                                   data-bs-toggle="tooltip" 
                                                   title="View Orders">
                                                    <i class="fas fa-clipboard-list text-primary"></i>
                                                </a>
                                                <button type="button" 
                                                        class="btn btn-sm btn-light edit-technician-btn" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#editTechnicianModal"
                                                        data-id="<?= $tech['id'] ?>"
                                                        data-name="<?= htmlspecialchars($tech['name']) ?>"
                                                        data-username="<?= htmlspecialchars($tech['username']) ?>"
                                                        data-phone="<?= htmlspecialchars($tech['phone']) ?>"
                                                        title="Edit Technician">
                                                    <i class="fas fa-edit text-warning"></i>
                                                </button>
                                                <button type="button" 
                                                        class="btn btn-sm btn-light delete-technician-btn" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#deleteTechnicianModal"
                                                        data-id="<?= $tech['id'] ?>"
                                                        data-name="<?= htmlspecialchars($tech['name']) ?>"
                                                        title="Delete Technician">
                                                    <i class="fas fa-trash text-danger"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Technician Modal -->
    <div class="modal fade" id="addTechnicianModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Technician</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="addTechnicianForm" action="add_technician.php" method="POST">
                        <div class="mb-3">
                            <label class="form-label">Full Name</label>
                            <input type="text" class="form-control" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Username</label>
                            <input type="text" class="form-control" name="username" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Phone Number</label>
                            <input type="tel" class="form-control" name="phone" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" class="form-control" name="password" required minlength="6">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Confirm Password</label>
                            <input type="password" class="form-control" name="confirm_password" required minlength="6">
                        </div>
                        <div class="modal-footer px-0 pb-0">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Add Technician</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Technician Modal -->
    <div class="modal fade" id="editTechnicianModal" tabindex="-1" aria-labelledby="editTechnicianModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editTechnicianModalLabel">Edit Technician</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editTechnicianForm" action="controller/edit_technician.php" method="POST">
                        <input type="hidden" name="technician_id" id="edit_technician_id">
                        <div class="mb-3">
                            <label class="form-label">Full Name</label>
                            <input type="text" class="form-control" name="name" id="edit_technician_name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Username</label>
                            <input type="text" class="form-control" name="username" id="edit_technician_username" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Phone Number</label>
                            <input type="tel" class="form-control" name="phone" id="edit_technician_phone" required>
                        </div>
                        <!-- Password fields are typically handled separately for security -->
                        <!-- Or you can add them here if needed, but require current password -->
                        <div class="modal-footer px-0 pb-0">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Technician Modal -->
    <div class="modal fade" id="deleteTechnicianModal" tabindex="-1" aria-labelledby="deleteTechnicianModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteTechnicianModalLabel">Confirm Deletion</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete technician <strong id="delete_technician_name_placeholder"></strong>?</p>
                    <p class="text-danger"><small>This action cannot be undone.</small></p>
                    <form id="deleteTechnicianForm" action="controller/delete_technician.php" method="POST">
                        <input type="hidden" name="technician_id" id="delete_technician_id">
                        <div class="modal-footer px-0 pb-0">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-danger">Delete</button>
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
        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        })

        document.addEventListener('DOMContentLoaded', function() {
            const editModal = document.getElementById('editTechnicianModal');
            const deleteModal = document.getElementById('deleteTechnicianModal');

            editModal.addEventListener('show.bs.modal', function (event) {
                const button = event.relatedTarget; // Button that triggered the modal
                const technicianId = button.getAttribute('data-id');
                const technicianName = button.getAttribute('data-name');
                const technicianUsername = button.getAttribute('data-username');
                const technicianPhone = button.getAttribute('data-phone');

                // Populate the modal's form fields
                editModal.querySelector('#edit_technician_id').value = technicianId;
                editModal.querySelector('#edit_technician_name').value = technicianName;
                editModal.querySelector('#edit_technician_username').value = technicianUsername;
                editModal.querySelector('#edit_technician_phone').value = technicianPhone;
            });

            deleteModal.addEventListener('show.bs.modal', function (event) {
                const button = event.relatedTarget; // Button that triggered the modal
                const technicianId = button.getAttribute('data-id');
                const technicianName = button.getAttribute('data-name');

                // Populate the modal content
                deleteModal.querySelector('#delete_technician_id').value = technicianId;
                deleteModal.querySelector('#delete_technician_name_placeholder').textContent = technicianName;
            });
        });
    </script>
</body>
</html> 