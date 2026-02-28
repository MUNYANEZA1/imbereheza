# User Manual: Digital Financial Loan Management System

This manual provides a comprehensive guide for users of the Digital Financial Loan Management System for Agricultural Cooperatives in Rwanda.

## 1. Introduction

This system is designed to streamline the management of members, loans, repayments, and financial transactions for Cooperative Imbere Heza Mwaro. It replaces manual processes with a user-friendly web-based interface, accessible to both cooperative leaders (Admins) and members.

## 2. Getting Started

### 2.1. Accessing the System

Open your web browser and navigate to the system's URL (e.g., `http://localhost/agricultural-loan-system/`). You will be presented with the login page.

### 2.2. Login

To log in, enter your **Username** and **Password** in the respective fields and click the **Login** button.

*   **Admin Default Credentials:**
    *   Username: `admin`
    *   Password: `admin123`
*   **Member Credentials:** Provided by the Admin upon registration.

If login is successful, you will be redirected to your respective dashboard (Admin Dashboard or Member Dashboard).

### 2.3. Logout

To log out of the system, click the **Logout** link located in the top right corner of the navigation bar.

## 3. Admin Functions

Admins (Cooperative Leaders) have full control over the system. Upon logging in, Admins will see a navigation menu on the left with the following options:

### 3.1. Dashboard

The Admin Dashboard provides an overview of key statistics:

*   Total Members
*   Total Loans
*   Total Repaid Amount
*   Active Loans
*   Recent Loan Applications (with options to view details)

### 3.2. Manage Members

This section allows Admins to manage cooperative members.

#### 3.2.1. Register New Member

1.  Fill in the **Add New Member** form with the following details:
    *   **Username:** Unique username for the member's login.
    *   **Password:** Password for the member's login.
    *   **Full Name:** Member's full name.
    *   **National ID:** Member's national identification number.
    *   **Phone:** Member's phone number.
    *   **Gender:** Member's gender.
    *   **Address:** Member's physical address.
2.  Click **Register Member** to create the member's account and profile.

#### 3.2.2. View Member List

The **Member List** table displays all registered members with their details. You can click **Edit** to modify a member's information (functionality to be implemented).

### 3.3. Manage Loans

This section is for managing loan applications and approvals.

#### 3.3.1. Create New Loan

1.  Fill in the **Create New Loan** form:
    *   **Select Member:** Choose the member applying for the loan from the dropdown.
    *   **Loan Amount (RWF):** The amount of money to be loaned.
    *   **Interest Rate (%):** The annual interest rate (e.g., 0.00 for no interest).
    *   **Loan Date:** The date the loan is issued.
    *   **Due Date:** The date the loan is expected to be fully repaid.
2.  Click **Create Loan** to submit the loan application.

#### 3.3.2. View Loan List

The **Loan List** table shows all loan applications. Admins can:

*   **Approve/Reject:** For loans with 'Pending' status, click **Approve** or **Reject** to change the loan status.
*   **Repayments:** Click **Repayments** to view the repayment history for a specific loan and record new payments.

### 3.4. Repayments

This page allows Admins to record loan repayments and view repayment history.

#### 3.4.1. Record New Repayment

1.  Fill in the **Record New Repayment** form:
    *   **Select Loan:** Choose the loan for which the repayment is being made.
    *   **Amount Paid (RWF):** The amount of money paid by the member.
    *   **Payment Date:** The date the payment was made.
2.  Click **Record Repayment**.

    *Note: The system automatically updates the loan status to 'Completed' if the total amount paid equals or exceeds the loan amount.*

#### 3.4.2. Repayment History

The **Repayment History** table displays all recorded payments, including the member's name, loan amount, amount paid, and payment date.

### 3.5. Reports

The Reports section provides financial summaries and insights.

*   **Financial Overview:**
    *   Total Members
    *   Total Loans Issued
    *   Total Money Repaid
    *   Outstanding Balance
*   **Members with Active Loans:** List of members with approved loans and their current payment status.
*   **Overdue Loans:** List of loans that are past their due date and are still active.

## 4. Member Functions

Members of the cooperative can access their personal loan information. Upon logging in, Members will see a navigation menu on the left with the following options:

### 4.1. Dashboard

The Member Dashboard provides a summary of the member's financial standing:

*   My Total Loans
*   Total Borrowed Amount
*   Total Repaid Amount
*   Current Outstanding Balance
*   My Recent Loans

### 4.2. My Loans

This page lists all loans associated with the logged-in member, including loan ID, amount, interest rate, loan date, due date, and current status.

### 4.3. My Repayments

This page displays a detailed history of all repayments made by the member, showing repayment ID, loan amount, amount paid, and payment date.

### 4.4. My Profile

This page shows the member's personal details as recorded in the system, including full name, username, national ID, phone, gender, address, and registration date.

---

**Author:** Manus AI
**Date:** Feb 05, 2026
