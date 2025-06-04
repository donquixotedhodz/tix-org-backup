<?php
session_start();
require_once('../../config/database.php');

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    $_SESSION['error_message'] = 'Access denied.';
    header('Location: ../../index.php');
    exit();
}

// Check if the form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_new_password = $_POST['confirm_new_password'] ?? '';

    // Basic validation
    if (empty($current_password) || empty($new_password) || empty($confirm_new_password)) {
        $_SESSION['error_message'] = 'All fields are required.';
    } else if ($new_password !== $confirm_new_password) {
        $_SESSION['error_message'] = 'New password and confirm password do not match.';
    } else {
        try {
            $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Fetch admin details to verify current password
            $stmt = $pdo->prepare("SELECT id, password FROM admins WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($admin && password_verify($current_password, $admin['password'])) {
                // Current password is correct, update with new password
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $update_stmt = $pdo->prepare("UPDATE admins SET password = ? WHERE id = ?");
                $update_stmt->execute([$hashed_password, $_SESSION['user_id']]);

                $_SESSION['success_message'] = 'Password changed successfully.';
            } else {
                $_SESSION['error_message'] = 'Incorrect current password.';
            }

        } catch (PDOException $e) {
            $_SESSION['error_message'] = 'Database error: ' . $e->getMessage();
        }
    }
}

// Redirect back to the settings selection page (or dashboard)
header('Location: ../settings/index.php');
exit();
?> 