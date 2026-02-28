<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}
require_once __DIR__ . '/language.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agricultural Loan System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <nav class="navbar">
        <button class="menu-toggle" id="menuToggle">☰</button>
        <div class="logo">Imbere Heza</div>
        
        <div class="nav-center">
            <?php if ($_SESSION['role'] === 'member'): ?>
                <div style="display:inline-flex; gap:8px; align-items:center;">
                    <select class="lang-selector" onchange="window.location.href='../set_language.php?lang='+this.value" style="width: 120px;">
                        <option value="en" <?php echo LanguageManager::getInstance()->getLanguage() === 'en' ? 'selected' : ''; ?>><?php echo t('common.english'); ?></option>
                        <option value="kin" <?php echo LanguageManager::getInstance()->getLanguage() === 'kin' ? 'selected' : ''; ?>><?php echo t('common.kinyarwanda'); ?></option>
                    </select>
                </div>
            <?php endif; ?>
            
            <?php if ($_SESSION['role'] === 'admin'): ?>
                <form method="get" action="search.php" class="search-form">
                    <select name="type" style="width: 110px;">
                        <option value="members"><?php echo t('navbar.members'); ?></option>
                        <option value="loans"><?php echo t('navbar.loans'); ?></option>
                        <option value="requests"><?php echo t('navbar.requests'); ?></option>
                    </select>
                    <input type="search" name="q" placeholder="<?php echo t('navbar.quick_search'); ?>" style="width: 180px;">
                    <button type="submit"><?php echo t('navbar.search'); ?></button>
                </form>
            <?php endif; ?>
        </div>
        
        <div class="user-info">
            <span><?php echo htmlspecialchars(substr($_SESSION['username'], 0, 15)); ?></span>
        </div>
    </nav>
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    <div class="sidebar" id="sidebar">
        <ul>
            <?php if ($_SESSION['role'] == 'admin'): ?>
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="members.php">Manage Members</a></li>
                <li><a href="loans.php">Manage Loans</a></li>
                <li><a href="repayments.php">Repayments</a></li>
                <li><a href="reports.php">Reports</a></li>
                <li><a href="profile.php">My Profile</a></li>
            <?php else: ?>
                <li><a href="dashboard.php"><?php echo t('menu.dashboard'); ?></a></li>
                <li><a href="my_loans.php"><?php echo t('menu.my_loans'); ?></a></li>
                <li><a href="my_repayments.php"><?php echo t('menu.my_repayments'); ?></a></li>
                <li><a href="profile.php"><?php echo t('menu.my_profile'); ?></a></li>
            <?php endif; ?>
            <li class="sidebar-logout"><a href="../auth.php?logout=1" class="logout-link"><?php echo t('menu.logout'); ?></a></li>
        </ul>
    </div>
    <div class="main-content">
        <main>