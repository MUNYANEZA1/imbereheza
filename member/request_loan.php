<?php
session_start();
require_once '../config/db.php';
include '../includes/header.php';

if ($_SESSION['role'] != 'member') {
    header("Location: ../admin/dashboard.php");
    exit();
}

// Get member details
$stmt = $pdo->prepare("SELECT * FROM members WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$member = $stmt->fetch();

if (!$member) {
    echo "<h2>Error: Member profile not found.</h2>";
    include '../includes/footer.php';
    exit();
}

$member_id = $member['id'];
$success_message = '';
$error_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount_requested = isset($_POST['amount_requested']) ? floatval($_POST['amount_requested']) : 0;
    $purpose = isset($_POST['purpose']) ? trim($_POST['purpose']) : '';
    $repayment_period = isset($_POST['repayment_period']) ? intval($_POST['repayment_period']) : 0;
    $preferred_start_date = isset($_POST['preferred_start_date']) ? $_POST['preferred_start_date'] : '';
    $additional_notes = isset($_POST['additional_notes']) ? trim($_POST['additional_notes']) : '';

    // Validation
    if ($amount_requested <= 0) {
        $error_message = "Loan amount must be greater than 0.";
    } elseif (empty($purpose) || strlen($purpose) < 10) {
        $error_message = "Please provide a loan purpose (at least 10 characters).";
    } elseif ($repayment_period < 1 || $repayment_period > 12) {
        $error_message = "Repayment period must be between 1 and 12 months.";
    } elseif (empty($preferred_start_date)) {
        $error_message = "Please select a preferred start date.";
    } elseif (strtotime($preferred_start_date) < strtotime(date('Y-m-d'))) {
        $error_message = "Preferred start date must be today or later.";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO loan_requests (member_id, amount_requested, purpose, repayment_period, preferred_start_date, additional_notes, status) 
                                   VALUES (?, ?, ?, ?, ?, ?, 'Pending')");
            $stmt->execute([$member_id, $amount_requested, $purpose, $repayment_period, $preferred_start_date, $additional_notes]);
            $success_message = "Loan request submitted successfully. Your request is under review by the admin.";
        } catch (PDOException $e) {
            $error_message = "Error submitting loan request. Please try again.";
        }
    }
}
?>

<div style="max-width: 700px; margin: 0 auto;">
    <h2><?php echo t('requests.request_loan_form'); ?></h2>
    <p style="color: #666;"><?php echo t('requests.submit'); ?></p>

    <?php if (!empty($success_message)): ?>
        <div style="background-color: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; margin-bottom: 20px; border-radius: 4px;">
            <?php echo $success_message; ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($error_message)): ?>
        <div style="background-color: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; margin-bottom: 20px; border-radius: 4px;">
            <?php echo $error_message; ?>
        </div>
    <?php endif; ?>

    <form method="POST" style="background: #f9f9f9; padding: 25px; border: 1px solid #ddd; border-radius: 5px;">
        
        <!-- Auto-filled fields -->
        <fieldset style="border: none; padding: 0; margin-bottom: 20px;">
            <legend style="font-weight: bold; margin-bottom: 10px;"><?php echo t('profile.full_name'); ?> (<?php echo t('common.n_a'); ?>)</legend>
            
            <div style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 5px; font-weight: bold;"><?php echo t('loans.loan_id'); ?></label>
                <input type="text" value="<?php echo htmlspecialchars($member_id); ?>" disabled 
                       style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; background-color: #e9ecef;">
            </div>

            <div style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 5px; font-weight: bold;"><?php echo t('profile.full_name'); ?></label>
                <input type="text" value="<?php echo htmlspecialchars($member['full_name']); ?>" disabled 
                       style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; background-color: #e9ecef;">
            </div>
        </fieldset>

        <!-- Loan Request fields -->
        <fieldset style="border: none; padding: 0;">
            <legend style="font-weight: bold; margin-bottom: 10px;"><?php echo t('requests.request_loan_form'); ?></legend>

            <div style="margin-bottom: 15px;">
                <label for="amount_requested" style="display: block; margin-bottom: 5px; font-weight: bold;"><?php echo t('requests.amount_requested'); ?> *</label>
                <input type="number" id="amount_requested" name="amount_requested" step="1000" min="0" required 
                       placeholder="e.g., 50000"
                       style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;">
            </div>

            <div style="margin-bottom: 15px;">
                <label for="purpose" style="display: block; margin-bottom: 5px; font-weight: bold;"><?php echo t('requests.purpose'); ?> *</label>
                <textarea id="purpose" name="purpose" rows="4" required placeholder="e.g., Buying fertilizers, seeds, or farming equipment..." 
                          style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px; font-family: Arial, sans-serif;"></textarea>
                <small style="color: #666;">Please explain what the loan will be used for (minimum 10 characters)</small>
            </div>

            <div style="margin-bottom: 15px;">
                <label for="repayment_period" style="display: block; margin-bottom: 5px; font-weight: bold;"><?php echo t('requests.repayment_period'); ?> *</label>
                <select id="repayment_period" name="repayment_period" required 
                        style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;">
                    <option value="">-- Select Repayment Period --</option>
                    <?php for ($i = 1; $i <= 12; $i++): ?>
                        <option value="<?php echo $i; ?>"><?php echo $i; ?> month<?php echo $i > 1 ? 's' : ''; ?></option>
                    <?php endfor; ?>
                </select>
            </div>

            <div style="margin-bottom: 15px;">
                <label for="preferred_start_date" style="display: block; margin-bottom: 5px; font-weight: bold;"><?php echo t('requests.preferred_start_date'); ?> *</label>
                <input type="date" id="preferred_start_date" name="preferred_start_date" required 
                       value="<?php echo date('Y-m-d'); ?>"
                       style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;">
            </div>

            <div style="margin-bottom: 20px;">
                <label for="additional_notes" style="display: block; margin-bottom: 5px; font-weight: bold;"><?php echo t('requests.additional_notes'); ?> (<?php echo t('common.n_a'); ?>)</label>
                <textarea id="additional_notes" name="additional_notes" rows="3" placeholder="Any additional information you'd like to share..." 
                          style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px; font-family: Arial, sans-serif;"></textarea>
            </div>
        </fieldset>

        <div style="margin-top: 25px;">
                <button type="submit" style="background-color: #28a745; color: white; padding: 12px 30px; border: none; border-radius: 4px; font-size: 16px; font-weight: bold; cursor: pointer; margin-right: 10px;">
                    <?php echo t('requests.submit'); ?>
                </button>
                    <a href="dashboard.php" style="background-color: #6c757d; color: white; padding: 12px 20px; border: none; border-radius: 4px; font-size: 16px; text-decoration: none; display: inline-block;">
                        <?php echo t('common.cancel'); ?>
                </a>
        </div>
    </form>
</div>

<?php include '../includes/footer.php'; ?>

