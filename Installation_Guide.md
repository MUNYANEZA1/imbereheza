# Installation Guide: Digital Financial Loan Management System

This guide provides instructions for setting up the Digital Financial Loan Management System for Agricultural Cooperatives in Rwanda.

## 1. System Requirements

Before proceeding with the installation, ensure your system meets the following requirements:

*   **Web Server:** Apache (typically included with XAMPP/WAMP/LAMP)
*   **Database Server:** MySQL 8.0+
*   **PHP Version:** 7.4+
*   **Web Browser:** Modern web browser (Chrome, Firefox, Edge)

## 2. Installation Steps

Follow these steps to install and configure the system:

### 2.1. Download and Extract

1.  Download the project source code to your local machine.
2.  Extract the contents of the `.zip` file to your web server's document root directory (e.g., `htdocs` for XAMPP).
    *   Rename the extracted folder to `agricultural-loan-system` (or your preferred project name).

### 2.2. Database Setup

1.  **Start MySQL Server:** Ensure your MySQL server is running.
2.  **Create Database:**
    *   Open your MySQL client (e.g., phpMyAdmin, MySQL Workbench, or command line).
    *   Create a new database named `agricultural_loan_db`.
    *   Alternatively, you can run the following SQL command:

    ```sql
    CREATE DATABASE IF NOT EXISTS agricultural_loan_db;
    ```

3.  **Import Schema:**
    *   Import the `schema.sql` file located in the `agricultural-loan-system/sql/` directory into the newly created `agricultural_loan_db` database.
    *   Using the command line, navigate to the project root and run:

    ```bash
    mysql -u root -p agricultural_loan_db < sql/schema.sql
    ```
    (Enter your MySQL root password when prompted. If no password, omit `-p`)

### 2.3. Configure Database Connection

1.  Navigate to the `agricultural-loan-system/config/` directory.
2.  Open the `db.php` file in a text editor.
3.  Update the database connection details if they differ from the defaults:

    ```php
    <?php
    $host = 'localhost';
    $dbname = 'agricultural_loan_db';
    $username = 'root'; // Your MySQL username
    $password = '';     // Your MySQL password

    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        die("Connection failed: " . $e->getMessage());
    }
    ?>
    ```

### 2.4. Access the Application

1.  Start your Apache web server.
2.  Open your web browser and navigate to the URL where you extracted the project.
    *   If you extracted to `htdocs/agricultural-loan-system` in XAMPP, the URL would typically be `http://localhost/agricultural-loan-system/`.

## 3. Default Admin Credentials

Upon successful database import, a default admin user is created:

*   **Username:** `admin`
*   **Password:** `admin123`

**Note:** It is highly recommended to change the default admin password immediately after the first login for security reasons.

## 4. Troubleshooting

*   **Database Connection Errors:** Double-check `db.php` settings and ensure MySQL is running.
*   **Page Not Found Errors:** Verify the project is extracted to the correct web server directory and Apache is configured correctly.
*   **Permissions Issues:** Ensure your web server has read/write permissions to the project directory if any file operations are intended (though not strictly required for this application).

---

**Author:** Manus AI
**Date:** Feb 05, 2026
