<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header('Location: ../frontend/admin_login.php');
    exit;
}

header('Location: ../frontend/admin_dashboard.php');
exit;
