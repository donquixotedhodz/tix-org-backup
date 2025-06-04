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
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Basic validation
    if (empty($username) || empty($password) || empty($confirm_password)) {
        $_SESSION['error_message'] = 'All fields are required.';
    } else if ($password !== $confirm_password) {
        $_SESSION['error_message'] = 'Password and confirm password do not match.';
    } else {
        try {
            $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Check if username already exists
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM admins WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetchColumn() > 0) {
                $_SESSION['error_message'] = 'Username already exists.';
            } else {
                // Hash the password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                // Insert new admin into the database
                $insert_stmt = $pdo->prepare("INSERT INTO admins (username, password) VALUES (?, ?)");
                $insert_stmt->execute([$username, $hashed_password]);

                $_SESSION['success_message'] = 'New admin created successfully.';
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