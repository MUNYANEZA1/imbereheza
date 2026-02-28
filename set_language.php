<?php
require_once 'includes/language.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'member') {
    header('Location: index.php');
    exit();
}

if (isset($_GET['lang'])) {
    $lang = $_GET['lang'];
    LanguageManager::getInstance()->setLanguage($lang);
    
    // Redirect back to the referring page or dashboard
    $referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'member/dashboard.php';
    header('Location: ' . $referer);
    exit();
}

header('Location: dashboard.php');
exit();
?>
