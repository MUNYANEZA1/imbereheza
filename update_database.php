<?php
/**
 * Database Migration Script
 * This script adds the loan_requests table and updates the loans table structure
 */

require_once 'config/db.php';

try {
    echo "<h2>Database Migration</h2>";
    echo "<p>Updating database schema...</p>";

    // Create loan_requests table
    $sql1 = "CREATE TABLE IF NOT EXISTS loan_requests (
        id INT AUTO_INCREMENT PRIMARY KEY,
        member_id INT NOT NULL,
        amount_requested DECIMAL(15, 2) NOT NULL,
        purpose TEXT NOT NULL,
        repayment_period INT NOT NULL,
        preferred_start_date DATE,
        additional_notes TEXT,
        request_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        status ENUM('Pending', 'Approved', 'Rejected') DEFAULT 'Pending',
        FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
    )";

    $pdo->exec($sql1);
    echo "<strong>loan_requests</strong> table created successfully!<br>";

    // Check if loans table has request_id column
    $result = $pdo->query("SHOW COLUMNS FROM loans LIKE 'request_id'");
    if ($result->rowCount() == 0) {
        // Add request_id column if it doesn't exist
        $sql2 = "ALTER TABLE loans ADD COLUMN request_id INT AFTER id";
        $pdo->exec($sql2);
        echo "Added <strong>request_id</strong> column to loans table<br>";

        // Add foreign key constraint
        $sql3 = "ALTER TABLE loans ADD CONSTRAINT fk_loan_request FOREIGN KEY (request_id) REFERENCES loan_requests(id) ON DELETE SET NULL";
        $pdo->exec($sql3);
        echo "Added foreign key constraint<br>";
    } else {
        echo "<strong>request_id</strong> column already exists in loans table<br>";
    }

    // Check if loans table has admin_comment column
    $result = $pdo->query("SHOW COLUMNS FROM loans LIKE 'admin_comment'");
    if ($result->rowCount() == 0) {
        // Add admin_comment column if it doesn't exist
        $sql4 = "ALTER TABLE loans ADD COLUMN admin_comment TEXT AFTER due_date";
        $pdo->exec($sql4);
        echo "Added <strong>admin_comment</strong> column to loans table<br>";
    } else {
        echo "<strong>admin_comment</strong> column already exists in loans table<br>";
    }

    // Ensure loan_requests has admin_comment column
    $chk = $pdo->query("SHOW COLUMNS FROM loan_requests LIKE 'admin_comment'");
    if ($chk && $chk->rowCount() == 0) {
        $pdo->exec("ALTER TABLE loan_requests ADD COLUMN admin_comment TEXT AFTER additional_notes");
        echo "Added <strong>admin_comment</strong> column to loan_requests table<br>";
    } else {
        echo "<strong>admin_comment</strong> column already exists in loan_requests table<br>";
    }

    echo "<hr>";
    echo "<p style='color: green; font-weight: bold;'>Database migration completed successfully!</p>";
    echo "<p><a href='admin/dashboard.php'>Go to Admin Dashboard</a></p>";

} catch (PDOException $e) {
    echo "<p style='color: red; font-weight: bold;'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><a href='javascript:history.back()'>Go Back</a></p>";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Database Migration</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background: #f5f5f5;
        }
        h2 {
            color: #333;
        }
        hr {
            margin-top: 20px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
