<?php
session_start();
require_once '../config/db.php';
include '../includes/header.php';

if ($_SESSION['role'] != 'admin') {
    header("Location: ../member/dashboard.php");
    exit();
}

$message = '';
$error_message = '';
$request_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Fetch loan request details
if ($request_id) {
    $stmt = $pdo->prepare("SELECT lr.*, m.full_name, m.national_id, m.phone FROM loan_requests lr 
                           JOIN members m ON lr.member_id = m.id 
                           WHERE lr.id = ?");
    $stmt->execute([$request_id]);
    $request = $stmt->fetch();

    if (!$request) {
        echo "<div style='background-color: #f8d7da; padding: 15px; border-radius: 4px;'>
              <h3>Error: Loan request not found.</h3>
              <a href='loan_requests.php' style='color: #0066cc;'>Back to Requests</a>
              <a href='loan_requests.php' class='btn btn-secondary'>Back</a>
              </div>";
        include '../includes/footer.php';
        exit();
    }

    // Get member loan history
    $stmt = $pdo->prepare("SELECT COUNT(*) as total_loans, 
                                  SUM(CASE WHEN status = 'Approved' THEN 1 ELSE 0 END) as approved_loans,
                                  SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) as completed_loans
                           FROM loans WHERE member_id = ?");
    $stmt->execute([$request['member_id']]);
    $loan_history = $stmt->fetch();

    // Handle approval/rejection
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = isset($_POST['action']) ? $_POST['action'] : '';
        
        if ($action === 'approve') {
            $approved_amount = isset($_POST['approved_amount']) ? floatval($_POST['approved_amount']) : 0;
            $interest_rate = isset($_POST['interest_rate']) ? floatval($_POST['interest_rate']) : 0;
            $loan_start_date = isset($_POST['loan_start_date']) ? $_POST['loan_start_date'] : '';
            $due_date = isset($_POST['due_date']) ? $_POST['due_date'] : '';
            $admin_comment = isset($_POST['admin_comment_approve']) ? trim($_POST['admin_comment_approve']) : '';

            // Validation
            if ($approved_amount <= 0) {
                $error_message = "Approved amount must be greater than 0.";
            } elseif (empty($loan_start_date)) {
                $error_message = "Please select a loan start date.";
            } elseif (empty($due_date)) {
                $error_message = "Please select a due date.";
            } elseif (strtotime($due_date) <= strtotime($loan_start_date)) {
                $error_message = "Due date must be after the start date.";
            } else {
                try {
                    // Start transaction
                    $pdo->beginTransaction();

                    // Create loan from request
                    $stmt = $pdo->prepare("INSERT INTO loans (request_id, member_id, amount, interest_rate, loan_date, due_date, admin_comment, status) 
                                           VALUES (?, ?, ?, ?, ?, ?, ?, 'Approved')");
                    $stmt->execute([$request_id, $request['member_id'], $approved_amount, $interest_rate, $loan_start_date, $due_date, $admin_comment]);

                    // Update request status
                    $stmt = $pdo->prepare("UPDATE loan_requests SET status = 'Approved' WHERE id = ?");
                    $stmt->execute([$request_id]);

                    $pdo->commit();
                    $message = "Loan approved successfully. Loan has been created.";
                } catch (PDOException $e) {
                    $pdo->rollBack();
                    $error_message = "Error approving loan. Please try again.";
                }
            }
        } elseif ($action === 'reject') {
            $admin_comment = isset($_POST['admin_comment_reject']) ? trim($_POST['admin_comment_reject']) : '';

            try {
                // Update request status to rejected and save admin comment
                $stmt = $pdo->prepare("UPDATE loan_requests SET status = 'Rejected', admin_comment = ? WHERE id = ?");
                $stmt->execute([$admin_comment, $request_id]);
                $message = "Loan request rejected successfully.";
            } catch (PDOException $e) {
                $error_message = "Error rejecting loan. Please try again.";
            }
        }

        // Refresh request data after update
        if (!$error_message && $message) {
            $stmt = $pdo->prepare("SELECT lr.*, m.full_name, m.national_id, m.phone FROM loan_requests lr 
                                   JOIN members m ON lr.member_id = m.id 
                                   WHERE lr.id = ?");
            $stmt->execute([$request_id]);
            $request = $stmt->fetch();
        }
    }
} else {
    echo "<div style='background-color: #f8d7da; padding: 15px; border-radius: 4px;'>
          <h3>Error: No loan request specified.</h3>
        <a href='loan_requests.php' style='color: #0066cc;'>Back to Requests</a>
          </div>";
    include '../includes/footer.php';
    exit();
}
?>

<div style="max-width: 900px; margin: 0 auto;">
    <a href="loan_requests.php" style="color: #0066cc; text-decoration: none; margin-bottom: 15px; display: inline-block;">Back to Requests</a>

    <h2>Loan Review & Approval Form</h2>

    <?php if (!empty($message)): ?>
        <div style="background-color: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; margin-bottom: 20px; border-radius: 4px;">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($error_message)): ?>
        <div style="background-color: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; margin-bottom: 20px; border-radius: 4px;">
            <?php echo $error_message; ?>
        </div>
    <?php endif; ?>

    <!-- Request Information (Read-Only) -->
    <div style="background: #f0f8ff; padding: 20px; border-radius: 5px; margin-bottom: 20px; border-left: 4px solid #0066cc;">
        <h3 style="margin-top: 0; color: #0066cc;">Request Information</h3>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
            <div>
                <label style="font-weight: bold; color: #333;">Request ID</label>
                <div style="background: white; padding: 10px; border-radius: 3px; border: 1px solid #ddd;">#<?php echo $request['id']; ?></div>
            </div>
            <div>
                <label style="font-weight: bold; color: #333;">Request Date</label>
                <div style="background: white; padding: 10px; border-radius: 3px; border: 1px solid #ddd;"><?php echo date('Y-m-d H:i', strtotime($request['request_date'])); ?></div>
            </div>
        </div>
    </div>

    <!-- Member Information (Read-Only) -->
    <div style="background: #f0fdf4; padding: 20px; border-radius: 5px; margin-bottom: 20px; border-left: 4px solid #22863a;">
        <h3 style="margin-top: 0; color: #22863a;">Member Information</h3>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
            <div>
                <label style="font-weight: bold; color: #333;">Member Name</label>
                <div style="background: white; padding: 10px; border-radius: 3px; border: 1px solid #ddd;"><?php echo htmlspecialchars($request['full_name']); ?></div>
            </div>
            <div>
                <label style="font-weight: bold; color: #333;">National ID</label>
                <div style="background: white; padding: 10px; border-radius: 3px; border: 1px solid #ddd;"><?php echo htmlspecialchars($request['national_id']); ?></div>
            </div>
            <div>
                <label style="font-weight: bold; color: #333;">Phone</label>
                <div style="background: white; padding: 10px; border-radius: 3px; border: 1px solid #ddd;"><?php echo htmlspecialchars($request['phone']); ?></div>
            </div>
            <div>
                <label style="font-weight: bold; color: #333;">Member Status</label>
                <div style="background: white; padding: 10px; border-radius: 3px; border: 1px solid #ddd;">
                    <?php echo "Total: {$loan_history['total_loans']} loans | Approved: {$loan_history['approved_loans']} | Completed: {$loan_history['completed_loans']}"; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Loan Request Details (Read-Only) -->
    <div style="background: #fff8f0; padding: 20px; border-radius: 5px; margin-bottom: 20px; border-left: 4px solid #ff6b6b;">
        <h3 style="margin-top: 0; color: #ff6b6b;">Loan Request Details</h3>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
            <div>
                <label style="font-weight: bold; color: #333;">Amount Requested (RWF)</label>
                <div style="background: white; padding: 10px; border-radius: 3px; border: 1px solid #ddd; font-size: 18px; font-weight: bold; color: #ff6b6b;">
                    RWF <?php echo number_format($request['amount_requested'], 2); ?>
                </div>
            </div>
            <div>
                <label style="font-weight: bold; color: #333;">Repayment Period</label>
                <div style="background: white; padding: 10px; border-radius: 3px; border: 1px solid #ddd;"><?php echo $request['repayment_period']; ?> month(s)</div>
            </div>
        </div>

        <div style="margin-bottom: 15px;">
            <label style="font-weight: bold; color: #333;">Loan Purpose</label>
            <div style="background: white; padding: 10px; border-radius: 3px; border: 1px solid #ddd;">
                <?php echo htmlspecialchars($request['purpose']); ?>
            </div>
        </div>

        <div style="margin-bottom: 15px;">
            <label style="font-weight: bold; color: #333;">Preferred Start Date</label>
            <div style="background: white; padding: 10px; border-radius: 3px; border: 1px solid #ddd;"><?php echo $request['preferred_start_date']; ?></div>
        </div>

        <?php if (!empty($request['additional_notes'])): ?>
            <div>
                <label style="font-weight: bold; color: #333;">Additional Notes</label>
                <div style="background: white; padding: 10px; border-radius: 3px; border: 1px solid #ddd;">
                    <?php echo htmlspecialchars($request['additional_notes']); ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Status Badge -->
    <div style="margin-bottom: 20px;">
        <label style="font-weight: bold; color: #333;">Current Status:</label>
        <span style="display: inline-block; padding: 8px 15px; border-radius: 4px; font-weight: bold; 
                     background: <?php 
                         echo $request['status'] == 'Approved' ? '#d4edda' : 
                              ($request['status'] == 'Rejected' ? '#f8d7da' : '#fff3cd');
                     ?>;
                     color: <?php 
                         echo $request['status'] == 'Approved' ? '#155724' : 
                              ($request['status'] == 'Rejected' ? '#721c24' : '#856404');
                     ?>;">
            <?php echo $request['status']; ?>
        </span>
    </div>

    <?php if ($request['status'] == 'Pending'): ?>
        <!-- Admin Approval Form -->
        <form method="POST" style="background: #fff9e6; padding: 25px; border-radius: 5px; border: 2px solid #ffc107;">
            <h3 style="margin-top: 0; color: #856404;">Admin Decision</h3>

            <div style="margin-bottom: 20px;">
                <input type="radio" id="action_approve" name="action" value="approve" required 
                       style="margin-right: 10px; cursor: pointer;"> 
                <label for="action_approve" style="font-weight: bold; cursor: pointer;">Approve Loan</label>
            </div>

            <div id="approve_section" style="display: none; background: white; padding: 20px; border-radius: 4px; margin-bottom: 20px; border: 1px solid #ddd;">
                <h4 style="margin-top: 0; color: #28a745;">Approval Details</h4>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                    <div>
                        <label for="approved_amount" style="display: block; margin-bottom: 5px; font-weight: bold;">
                            Approved Amount (RWF) *
                        </label>
                        <input type="number" id="approved_amount" name="approved_amount" step="1000" min="0" 
                               value="<?php echo $request['amount_requested']; ?>"
                               style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
                    </div>
                    <div>
                        <label for="interest_rate" style="display: block; margin-bottom: 5px; font-weight: bold;">
                            Interest Rate (%) *
                        </label>
                        <input type="number" id="interest_rate" name="interest_rate" step="0.01" min="0" value="5"
                               style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                    <div>
                        <label for="loan_start_date" style="display: block; margin-bottom: 5px; font-weight: bold;">
                            Loan Start Date *
                        </label>
                        <input type="date" id="loan_start_date" name="loan_start_date" required 
                               value="<?php echo $request['preferred_start_date']; ?>"
                               style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
                    </div>
                    <div>
                        <label for="due_date" style="display: block; margin-bottom: 5px; font-weight: bold;">
                            Due Date *
                        </label>
                        <input type="date" id="due_date" name="due_date" required 
                               style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
                    </div>
                </div>

                <div style="margin-bottom: 15px;">
                    <label for="admin_comment_approve" style="display: block; margin-bottom: 5px; font-weight: bold;">
                            Admin Comment
                        </label>
                        <textarea id="admin_comment_approve" name="admin_comment_approve" rows="3" placeholder="e.g., Approved based on repayment capacity..."
                                  style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-family: Arial, sans-serif;"></textarea>
                </div>
            </div>

            <div style="margin-bottom: 20px; border-top: 1px solid #ddd; padding-top: 20px;">
                <input type="radio" id="action_reject" name="action" value="reject" required 
                       style="margin-right: 10px; cursor: pointer;"> 
                <label for="action_reject" style="font-weight: bold; cursor: pointer;">Reject Loan</label>
            </div>

            <div id="reject_section" style="display: none; background: #fff5f5; padding: 20px; border-radius: 4px; margin-bottom: 20px; border: 1px solid #f5c6cb;">
                <h4 style="margin-top: 0; color: #dc3545;">Rejection Details</h4>

                <div>
                    <label for="admin_comment_reject" style="display: block; margin-bottom: 5px; font-weight: bold;">
                        Reason for Rejection
                    </label>
                    <textarea id="admin_comment_reject" name="admin_comment_reject" rows="3" placeholder="Please explain why this loan is being rejected..."
                              style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-family: Arial, sans-serif;"></textarea>
                </div>
            </div>

            <div style="margin-top: 25px;">
                <button type="submit" id="submit_btn" style="background-color: #28a745; color: white; padding: 12px 30px; border: none; border-radius: 4px; font-size: 16px; font-weight: bold; cursor: pointer; margin-right: 10px;">
                    Submit Decision
                </button>
                <a href="loan_requests.php" style="background-color: #6c757d; color: white; padding: 12px 20px; border-radius: 4px; font-size: 16px; text-decoration: none; display: inline-block;">
                    Cancel
                </a>
            </div>
        </form>

        <script>
            const approveRadio = document.getElementById('action_approve');
            const rejectRadio = document.getElementById('action_reject');
            const approveSection = document.getElementById('approve_section');
            const rejectSection = document.getElementById('reject_section');
            const submitBtn = document.getElementById('submit_btn');

            function updateForm() {
                if (approveRadio.checked) {
                    approveSection.style.display = 'block';
                    rejectSection.style.display = 'none';
                    submitBtn.textContent = 'Approve Loan';
                    submitBtn.style.backgroundColor = '#28a745';
                } else if (rejectRadio.checked) {
                    approveSection.style.display = 'none';
                    rejectSection.style.display = 'block';
                    submitBtn.textContent = 'Reject Loan';
                    submitBtn.style.backgroundColor = '#dc3545';
                } else {
                    approveSection.style.display = 'none';
                    rejectSection.style.display = 'none';
                    submitBtn.textContent = 'Submit Decision';
                    submitBtn.style.backgroundColor = '#28a745';
                }
            }

            approveRadio.addEventListener('change', updateForm);
            rejectRadio.addEventListener('change', updateForm);

            // Calculate due date based on repayment period
            const startDateInput = document.getElementById('loan_start_date');
            const dueDateInput = document.getElementById('due_date');

            startDateInput.addEventListener('change', function() {
                if (this.value) {
                    const startDate = new Date(this.value);
                    const repaymentPeriod = <?php echo $request['repayment_period']; ?>;
                    const dueDate = new Date(startDate);
                    dueDate.setMonth(dueDate.getMonth() + repaymentPeriod);
                    dueDateInput.value = dueDate.toISOString().split('T')[0];
                }
            });

            // Initialize form display
            updateForm();
        </script>
    <?php else: ?>
        <div style="background: #f0f0f0; padding: 20px; border-radius: 4px; text-align: center;">
            <p style="color: #666; font-size: 16px;">This loan request has already been <?php echo strtolower($request['status']); ?>.</p>
            <a href="loan_requests.php" style="color: #0066cc; text-decoration: none;">Back to Requests</a>
        </div>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>
