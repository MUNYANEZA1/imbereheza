<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit();
}

$type = isset($_GET['type']) ? $_GET['type'] : 'members';
$q = isset($_GET['q']) ? trim($_GET['q']) : '';

// Simple routing to the relevant admin page with query parameter
if ($type === 'members') {
    $url = 'members.php';
} elseif ($type === 'loans') {
    $url = 'loans.php';
} elseif ($type === 'requests') {
    $url = 'loan_requests.php';
} else {
    $url = 'dashboard.php';
}

if ($q !== '') {
    header('Location: ' . $url . '?q=' . urlencode($q));
} else {
    header('Location: ' . $url);
}
exit();
?>