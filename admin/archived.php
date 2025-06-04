<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: index.php');
    exit();
}

// Get filter parameters from the request
$search_customer = $_GET['search_customer'] ?? '';
$filter_service = $_GET['filter_service'] ?? '';
$filter_technician = $_GET['filter_technician'] ?? '';


try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Get completed and cancelled job orders
    $sql = "
        SELECT 
            jo.*,
            COALESCE(am.model_name, 'Not Specified') as model_name,
            t.name as technician_name,
            CASE 
                WHEN jo.status = 'completed' THEN COALESCE(jo.completed_at, jo.updated_at)
                ELSE COALESCE(jo.updated_at, jo.created_at)
            END as status_date
        FROM job_orders jo 
        LEFT JOIN aircon_models am ON jo.aircon_model_id = am.id 
        LEFT JOIN technicians t ON jo.assigned_technician_id = t.id
        WHERE jo.status IN ('completed', 'cancelled')
    ";

    $params = [];

    if (!empty($search_customer)) {
        $sql .= " AND jo.customer_name LIKE ?";
        $params[] = '%' . $search_customer . '%';
    }

    if (!empty($filter_service)) {
        $sql .= " AND jo.service_type = ?";
        $params[] = $filter_service;
    }

    if (!empty($filter_technician)) {
        $sql .= " AND jo.assigned_technician_id = ?";
        $params[] = $filter_technician;
    }

    if (!empty($start_date)) {
        $sql .= " AND (jo.completed_at >= ? OR jo.updated_at >= ?)";
        $params[] = $start_date . ' 00:00:00';
        $params[] = $start_date . ' 00:00:00';
    }

    if (!empty($end_date)) {
        $sql .= " AND (jo.completed_at <= ? OR jo.updated_at <= ?)";
        $params[] = $end_date . ' 23:59:59';
        $params[] = $end_date . ' 23:59:59';
    }

    $sql .= "
        ORDER BY 
            CASE 
                WHEN jo.status = 'completed' THEN 1
                WHEN jo.status = 'cancelled' THEN 2
            END,
            status_date DESC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get technicians for dropdown
    $stmt = $pdo->query("SELECT id, name FROM technicians");
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
    <title>Archived Orders - Job Order System</title>
    <link rel="icon" href="../images/logo-favicon.ico" type="image/x-icon">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../css/style.css">
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
                <li class="active">
                    <a href="#jobOrdersSubmenu" data-bs-toggle="collapse" aria-expanded="true" class="dropdown-toggle">
                        <i class="fas fa-clipboard-list"></i>
                        Job Orders
                    </a>
                    <ul class="collapse show list-unstyled" id="jobOrdersSubmenu">
                        <li>
                            <a href="orders.php">
                                <i class="fas fa-file-alt"></i>
                                Orders
                            </a>
                        </li>
                        <li class="active">
                            <a href="archived.php">
                                <i class="fas fa-archive"></i>
                                Archived
                            </a>
                        </li>
                    </ul>
                </li>
                <li>
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
                    <h4 class="mb-0">Archived Orders</h4>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-outline-primary" onclick="window.print()">
                            <i class="fas fa-print me-2"></i>Print
                        </button>
                        <button type="button" class="btn btn-outline-primary">
                            <i class="fas fa-file-export me-2"></i>Export
                        </button>
                    </div>
                </div>

                <!-- Search and Filter Form -->
                <form method="GET" action="" class="mb-4">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-3">
                            <label for="search_customer" class="form-label">Search Customer</label>
                            <input type="text" class="form-control" id="search_customer" name="search_customer" value="<?= htmlspecialchars($search_customer) ?>" placeholder="Enter customer name">
                        </div>
                        <div class="col-md-3">
                            <label for="filter_service" class="form-label">Service Type</label>
                            <select class="form-select" id="filter_service" name="filter_service">
                                <option value="">All Service Types</option>
                                <option value="installation" <?= $filter_service === 'installation' ? 'selected' : '' ?>>Installation</option>
                                <option value="repair" <?= $filter_service === 'repair' ? 'selected' : '' ?>>Repair</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="filter_technician" class="form-label">Technician</label>
                            <select class="form-select" id="filter_technician" name="filter_technician">
                                <option value="">All Technicians</option>
                                <?php foreach ($technicians as $tech): ?>
                                    <option value="<?= $tech['id'] ?>" <?= (string)$filter_technician === (string)$tech['id'] ? 'selected' : '' ?>><?= htmlspecialchars($tech['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-secondary w-100">Apply Filters</button>
                        </div>
                    </div>
                </form>

                <!-- Orders Table -->
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Ticket Number</th>
                                        <th>Customer</th>
                                        <th>Service Type</th>
                                        <th>Model</th>
                                        <th>Technician</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                        <th>Price</th>
                                        <th class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($orders as $order): ?>
                                    <tr>
                                        <td>
                                            <span class="fw-semibold"><?= htmlspecialchars($order['job_order_number']) ?></span>
                                        </td>
                                        <td>
                                            <div class="fw-medium"><?= htmlspecialchars($order['customer_name']) ?></div>
                                            <small class="text-muted d-block"><?= htmlspecialchars($order['customer_phone']) ?></small>
                                            <small class="text-muted d-block text-truncate" style="max-width: 200px;"><?= htmlspecialchars($order['customer_address']) ?></small>
                                        </td>
                                        <td>
                                            <span class="badge bg-info bg-opacity-10 text-info">
                                                <?= ucfirst(htmlspecialchars($order['service_type'])) ?>
                                            </span>
                                        </td>
                                        <td><?= htmlspecialchars($order['model_name']) ?></td>
                                        <td>
                                            <?php if ($order['technician_name']): ?>
                                                <div class="d-flex align-items-center">
                                                    <img src="https://ui-avatars.com/api/?name=<?= urlencode($order['technician_name']) ?>&background=1a237e&color=fff" 
                                                         alt="<?= htmlspecialchars($order['technician_name']) ?>" 
                                                         class="rounded-circle me-2" 
                                                         width="24" height="24">
                                                    <?= htmlspecialchars($order['technician_name']) ?>
                                                </div>
                                            <?php else: ?>
                                                <span class="text-muted">Not Assigned</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?= $order['status'] === 'completed' ? 'success' : 'danger' ?>">
                                                <?= ucfirst($order['status']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="fw-medium">
                                                <?php 
                                                if (!empty($order['status_date'])) {
                                                    echo date('M d, Y', strtotime($order['status_date']));
                                                } else {
                                                    echo 'N/A';
                                                }
                                                ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="fw-semibold">₱<?= number_format($order['price'], 2) ?></div>
                                        </td>
                                        <td>
                                            <div class="d-flex justify-content-center gap-2">
                                                <a href="view-order.php?id=<?= $order['id'] ?>" 
                                                   class="btn btn-sm btn-light" 
                                                   data-bs-toggle="tooltip" 
                                                   title="View Details">
                                                    <i class="fas fa-eye text-primary"></i>
                                                </a>
                                                <?php if ($order['status'] === 'completed'): ?>
                                                <button type="button" 
                                                        class="btn btn-sm btn-light" 
                                                        data-bs-toggle="tooltip" 
                                                        title="Print Receipt">
                                                    <i class="fas fa-receipt text-success"></i>
                                                </button>
                                                <?php endif; ?>
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
    </script>
</body>
</html> 