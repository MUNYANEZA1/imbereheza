-- Schema for Agricultural Loan System
-- Creates database and all required tables (users, members, loan_requests, loans, repayments,
-- password_reset_requests, password_otps). Use on a fresh MySQL server or migrate carefully.

CREATE DATABASE IF NOT EXISTS agricultural_loan_db CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE agricultural_loan_db;

-- Users table for authentication
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(150) DEFAULT NULL,
    role ENUM('admin','member') NOT NULL DEFAULT 'member',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Members table for personal details
CREATE TABLE IF NOT EXISTS members (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    full_name VARCHAR(150) NOT NULL,
    national_id VARCHAR(50) NOT NULL UNIQUE,
    phone VARCHAR(30),
    address TEXT,
    gender ENUM('Male','Female','Other') DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_members_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Loan requests submitted by members
CREATE TABLE IF NOT EXISTS loan_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    member_id INT NOT NULL,
    amount_requested DECIMAL(15,2) NOT NULL,
    purpose TEXT NOT NULL,
    repayment_period INT NOT NULL,
    preferred_start_date DATE DEFAULT NULL,
    additional_notes TEXT,
    admin_comment TEXT,
    request_date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    status ENUM('Pending','Approved','Rejected') NOT NULL DEFAULT 'Pending',
    CONSTRAINT fk_request_member FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Loans table (created when a request is approved or manually created)
CREATE TABLE IF NOT EXISTS loans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    request_id INT NULL,
    member_id INT NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    interest_rate DECIMAL(7,4) NOT NULL DEFAULT 0.0000,
    loan_date DATE NOT NULL,
    due_date DATE NOT NULL,
    admin_comment TEXT,
    status ENUM('Pending','Approved','Rejected','Completed') NOT NULL DEFAULT 'Pending',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_loan_member FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE,
    CONSTRAINT fk_loan_request FOREIGN KEY (request_id) REFERENCES loan_requests(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Repayments table
CREATE TABLE IF NOT EXISTS repayments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    loan_id INT NOT NULL,
    amount_paid DECIMAL(15,2) NOT NULL,
    payment_date DATE NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_repayment_loan FOREIGN KEY (loan_id) REFERENCES loans(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Password reset requests (members can request password reset from admin)
CREATE TABLE IF NOT EXISTS password_reset_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    member_id INT NOT NULL,
    reason TEXT,
    status ENUM('Pending','Approved','Rejected') NOT NULL DEFAULT 'Pending',
    admin_comment TEXT,
    new_password VARCHAR(255),
    request_date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    approved_date TIMESTAMP NULL,
    CONSTRAINT fk_reset_member FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table to store short-lived OTPs for passwordless or recovery logins
CREATE TABLE IF NOT EXISTS password_otps (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    otp VARCHAR(10) NOT NULL,
    expires_at DATETIME NOT NULL,
    used TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_otp_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Transactions table for company finances
CREATE TABLE IF NOT EXISTS transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type ENUM('income','expense') NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    description TEXT,
    transaction_date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    related_loan_id INT NULL,
    related_repayment_id INT NULL,
    CONSTRAINT fk_transaction_loan FOREIGN KEY (related_loan_id) REFERENCES loans(id) ON DELETE SET NULL,
    CONSTRAINT fk_transaction_repayment FOREIGN KEY (related_repayment_id) REFERENCES repayments(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Optional: sample admin user
-- The password hash below is bcrypt for a sample password (replace it on production).
INSERT INTO users (username, password, email, role) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'nezaemma6@gmail.com', 'admin')
ON DUPLICATE KEY UPDATE email = VALUES(email), role = VALUES(role);

-- Indexes to speed up common queries
CREATE INDEX IF NOT EXISTS idx_loans_member_id ON loans(member_id);
CREATE INDEX IF NOT EXISTS idx_requests_member_id ON loan_requests(member_id);
CREATE INDEX IF NOT EXISTS idx_repayments_loan_id ON repayments(loan_id);

-- End of schema
