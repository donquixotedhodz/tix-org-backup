<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id'])) {
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

    // Get all ongoing job orders (pending and in_progress)
    $sql = "
        SELECT 
            jo.*,
            COALESCE(am.model_name, 'Not Specified') as model_name,
            t.name as technician_name
        FROM job_orders jo 
        LEFT JOIN aircon_models am ON jo.aircon_model_id = am.id 
        LEFT JOIN technicians t ON jo.assigned_technician_id = t.id
        WHERE jo.status IN ('pending', 'in_progress')
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

    $sql .= "
        ORDER BY 
            CASE 
                WHEN jo.status = 'pending' THEN 1
                WHEN jo.status = 'in_progress' THEN 2
            END,
            jo.created_at DESC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get technicians for dropdown
    $stmt = $pdo->query("SELECT id, name FROM technicians");
    $technicians = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get aircon models for dropdown
    $stmt = $pdo->query("SELECT id, model_name, brand FROM aircon_models");
    $airconModels = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders - Job Order System</title>
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
                        <li class="active">
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
                    <h4 class="mb-0">Job Orders</h4>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addJobOrderModal">
                        <i class="fas fa-plus me-2"></i>Add Job Order
                    </button>
                </div>

                <!-- Search and Filter Form -->
                <form method="GET" action="" class="mb-4">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-4">
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
                                        <th>Due Date</th>
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
                                            <span class="badge bg-<?= $order['status'] === 'completed' ? 'success' : 
                                                ($order['status'] === 'in_progress' ? 'warning' : 
                                                ($order['status'] === 'pending' ? 'danger' : 'secondary')) ?> bg-opacity-10 text-<?= $order['status'] === 'completed' ? 'success' : 
                                                ($order['status'] === 'in_progress' ? 'warning' : 
                                                ($order['status'] === 'pending' ? 'danger' : 'secondary')) ?>">
                                                <?= ucfirst(str_replace('_', ' ', $order['status'])) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="fw-medium"><?= date('M d, Y', strtotime($order['due_date'])) ?></div>
                                            <?php
                                            $due_date = new DateTime($order['due_date']);
                                            $today = new DateTime();
                                            $interval = $today->diff($due_date);
                                            $days_left = $interval->days;
                                            if ($due_date < $today) {
                                                echo '<small class="text-danger">Overdue by ' . $days_left . ' days</small>';
                                            } elseif ($days_left <= 3) {
                                                echo '<small class="text-warning">Due in ' . $days_left . ' days</small>';
                                            }
                                            ?>
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
                                                <button type="button" 
                                                        class="btn btn-sm btn-light edit-order-btn" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#editOrderModal"
                                                        data-id="<?= $order['id'] ?>"
                                                        data-customer-name="<?= htmlspecialchars($order['customer_name']) ?>"
                                                        data-customer-phone="<?= htmlspecialchars($order['customer_phone']) ?>"
                                                        data-customer-address="<?= htmlspecialchars($order['customer_address']) ?>"
                                                        data-service-type="<?= htmlspecialchars($order['service_type']) ?>"
                                                        data-aircon-model="<?= $order['aircon_model_id'] ?>"
                                                        data-technician="<?= $order['assigned_technician_id'] ?>"
                                                        data-due-date="<?= date('Y-m-d', strtotime($order['due_date'])) ?>"
                                                        data-price="<?= $order['price'] ?>"
                                                        data-status="<?= htmlspecialchars($order['status']) ?>"
                                                        title="Edit Order">
                                                    <i class="fas fa-edit text-warning"></i>
                                                </button>
                                                <?php if ($order['status'] !== 'completed'): ?>
                                                <button type="button" 
                                                        class="btn btn-sm btn-light complete-order-btn" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#completeOrderModal"
                                                        data-id="<?= $order['id'] ?>"
                                                        data-order-number="<?= htmlspecialchars($order['job_order_number']) ?>"
                                                        data-customer-name="<?= htmlspecialchars($order['customer_name']) ?>"
                                                        title="Mark as Completed">
                                                    <i class="fas fa-check text-success"></i>
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

    <!-- Add Job Order Modal -->
    <div class="modal fade" id="addJobOrderModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Job Order</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="controller/process_order.php" method="POST">
                    <div class="modal-body">
                        <div class="row g-3">
                            <!-- Customer Information -->
                            <div class="col-md-6">
                                <label class="form-label">Customer Name</label>
                                <input type="text" class="form-control" name="customer_name" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Phone Number</label>
                                <input type="tel" class="form-control" name="customer_phone" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Address</label>
                                <textarea class="form-control" name="customer_address" rows="2" required></textarea>
                            </div>

                            <!-- Service Information -->
                            <div class="col-md-6">
                                <label class="form-label">Service Type</label>
                                <select class="form-select" name="service_type" required>
                                    <option value="">Select Service Type</option>
                                    <option value="installation">Installation</option>
                                    <option value="repair">Repair</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Aircon Model</label>
                                <select class="form-select" name="aircon_model_id">
                                    <option value="">Select Model</option>
                                    <?php foreach ($airconModels as $model): ?>
                                    <option value="<?= $model['id'] ?>"><?= htmlspecialchars($model['brand'] . ' - ' . $model['model_name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Assignment Information -->
                            <div class="col-md-6">
                                <label class="form-label">Assign Technician</label>
                                <select class="form-select" name="assigned_technician_id">
                                    <option value="">Select Technician</option>
                                    <?php foreach ($technicians as $tech): ?>
                                    <option value="<?= $tech['id'] ?>"><?= htmlspecialchars($tech['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Due Date</label>
                                <input type="date" class="form-control" name="due_date" required>
                            </div>

                            <!-- Price -->
                            <div class="col-md-6">
                                <label class="form-label">Price (₱)</label>
                                <input type="number" class="form-control" name="price" step="0.01" required>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Create Job Order</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Order Modal -->
    <div class="modal fade" id="editOrderModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Job Order</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="controller/process_edit.php" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="order_id" id="edit_order_id">
                        <div class="row g-3">
                            <!-- Customer Information -->
                            <div class="col-md-6">
                                <label class="form-label">Customer Name</label>
                                <input type="text" class="form-control" name="customer_name" id="edit_customer_name" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Phone Number</label>
                                <input type="tel" class="form-control" name="customer_phone" id="edit_customer_phone" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Address</label>
                                <textarea class="form-control" name="customer_address" id="edit_customer_address" rows="2" required></textarea>
                            </div>

                            <!-- Service Information -->
                            <div class="col-md-6">
                                <label class="form-label">Service Type</label>
                                <select class="form-select" name="service_type" id="edit_service_type" required>
                                    <option value="installation">Installation</option>
                                    <option value="repair">Repair</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Aircon Model</label>
                                <select class="form-select" name="aircon_model_id" id="edit_aircon_model">
                                    <option value="">Select Model</option>
                                    <?php foreach ($airconModels as $model): ?>
                                    <option value="<?= $model['id'] ?>"><?= htmlspecialchars($model['brand'] . ' - ' . $model['model_name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Assignment Information -->
                            <div class="col-md-6">
                                <label class="form-label">Assign Technician</label>
                                <select class="form-select" name="assigned_technician_id" id="edit_technician">
                                    <option value="">Select Technician</option>
                                    <?php foreach ($technicians as $tech): ?>
                                    <option value="<?= $tech['id'] ?>"><?= htmlspecialchars($tech['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Due Date</label>
                                <input type="date" class="form-control" name="due_date" id="edit_due_date" required>
                            </div>

                            <!-- Price -->
                            <div class="col-md-6">
                                <label class="form-label">Price (₱)</label>
                                <input type="number" class="form-control" name="price" id="edit_price" step="0.01" required>
                            </div>

                            <!-- Status -->
                            <div class="col-md-6">
                                <label class="form-label">Status</label>
                                <select class="form-select" name="status" id="edit_status" required>
                                    <option value="pending">Pending</option>
                                    <option value="in_progress">In Progress</option>
                                    <option value="completed">Completed</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Complete Order Modal -->
    <div class="modal fade" id="completeOrderModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Complete Job Order</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="controller/complete_order.php" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="order_id" id="complete_order_id">
                        <p>Are you sure you want to mark this job order as completed?</p>
                        <div class="alert alert-info">
                            <strong>Order #:</strong> <span id="complete_order_number"></span><br>
                            <strong>Customer:</strong> <span id="complete_customer_name"></span>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success">Mark as Completed</button>
                    </div>
                </form>
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

        // Handle edit order modal
        document.addEventListener('DOMContentLoaded', function() {
            const editModal = document.getElementById('editOrderModal');
            
            editModal.addEventListener('show.bs.modal', function (event) {
                const button = event.relatedTarget;
                
                // Get data from button
                const orderId = button.getAttribute('data-id');
                const customerName = button.getAttribute('data-customer-name');
                const customerPhone = button.getAttribute('data-customer-phone');
                const customerAddress = button.getAttribute('data-customer-address');
                const serviceType = button.getAttribute('data-service-type');
                const airconModel = button.getAttribute('data-aircon-model');
                const technician = button.getAttribute('data-technician');
                const dueDate = button.getAttribute('data-due-date');
                const price = button.getAttribute('data-price');
                const status = button.getAttribute('data-status');

                // Set form values
                editModal.querySelector('#edit_order_id').value = orderId;
                editModal.querySelector('#edit_customer_name').value = customerName;
                editModal.querySelector('#edit_customer_phone').value = customerPhone;
                editModal.querySelector('#edit_customer_address').value = customerAddress;
                editModal.querySelector('#edit_service_type').value = serviceType;
                editModal.querySelector('#edit_aircon_model').value = airconModel;
                editModal.querySelector('#edit_technician').value = technician;
                editModal.querySelector('#edit_due_date').value = dueDate;
                editModal.querySelector('#edit_price').value = price;
                editModal.querySelector('#edit_status').value = status;
            });

            // Handle complete order modal
            const completeModal = document.getElementById('completeOrderModal');
            
            completeModal.addEventListener('show.bs.modal', function (event) {
                const button = event.relatedTarget;
                
                // Get data from button
                const orderId = button.getAttribute('data-id');
                const orderNumber = button.getAttribute('data-order-number');
                const customerName = button.getAttribute('data-customer-name');

                // Set form values
                completeModal.querySelector('#complete_order_id').value = orderId;
                completeModal.querySelector('#complete_order_number').textContent = orderNumber;
                completeModal.querySelector('#complete_customer_name').textContent = customerName;
            });
        });
    </script>
</body>
</html> 