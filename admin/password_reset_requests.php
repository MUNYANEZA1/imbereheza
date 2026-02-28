<?php
session_start();
require_once '../config/db.php';
include '../includes/header.php';

if ($_SESSION['role'] != 'admin') {
    header("Location: ../member/dashboard.php");
    exit();
}

$message = '';
$error = '';

// Handle approve/reject
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $request_id = isset($_POST['request_id']) ? intval($_POST['request_id']) : 0;
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    $admin_comment = isset($_POST['admin_comment']) ? trim($_POST['admin_comment']) : '';
    $new_password = isset($_POST['new_password']) ? trim($_POST['new_password']) : '';

    if ($action === 'approve') {
        if (empty($new_password) || strlen($new_password) < 6) {
            $error = 'Password must be at least 6 characters.';
        } else {
            try {
                $pdo->beginTransaction();

                // Get member and user info
                $stmt = $pdo->prepare("SELECT m.id, u.id as user_id FROM password_reset_requests prr 
                                       JOIN members m ON prr.member_id = m.id 
                                       LEFT JOIN users u ON m.user_id = u.id 
                                       WHERE prr.id = ?");
                $stmt->execute([$request_id]);
                $info = $stmt->fetch();

                if ($info && $info['user_id']) {
                    // Update user password
                    $hashed = password_hash($new_password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                    $stmt->execute([$hashed, $info['user_id']]);

                    // Update request status
                    $stmt = $pdo->prepare("UPDATE password_reset_requests SET status = 'Approved', admin_comment = ?, approved_date = NOW(), new_password = ? WHERE id = ?");
                    $stmt->execute([$admin_comment, $new_password, $request_id]);

                    $pdo->commit();
                    $message = 'Password reset approved successfully.';
                } else {
                    $error = 'Member or user not found.';
                }
            } catch (PDOException $e) {
                $pdo->rollBack();
                $error = 'Error: ' . htmlspecialchars($e->getMessage());
            }
        }
    } elseif ($action === 'reject') {
        if (empty($admin_comment)) {
            $error = 'Please provide a reason for rejection.';
        } else {
            try {
                $stmt = $pdo->prepare("UPDATE password_reset_requests SET status = 'Rejected', admin_comment = ? WHERE id = ?");
                $stmt->execute([$admin_comment, $request_id]);
                $message = 'Password reset request rejected.';
            } catch (PDOException $e) {
                $error = 'Error: ' . htmlspecialchars($e->getMessage());
            }
        }
    }
}

// Fetch all pending requests
$stmt = $pdo->query("SELECT prr.*, m.full_name, m.national_id FROM password_reset_requests prr 
                     JOIN members m ON prr.member_id = m.id 
                     WHERE prr.status = 'Pending' 
                     ORDER BY prr.request_date DESC");
$pending_requests = $stmt->fetchAll();

// Get stats
$stmt = $pdo->query("SELECT 
                        COUNT(*) as total,
                        SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending,
                        SUM(CASE WHEN status = 'Approved' THEN 1 ELSE 0 END) as approved,
                        SUM(CASE WHEN status = 'Rejected' THEN 1 ELSE 0 END) as rejected
                     FROM password_reset_requests");
$stats = $stmt->fetch();
?>

<div style="max-width: 1200px; margin: 0 auto;">
    <h2>Password Reset Requests</h2>

    <?php if ($message): ?>
        <div style="background: #d4edda; padding: 12px; border-radius: 4px; margin-bottom: 12px;">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div style="background: #f8d7da; padding: 12px; border-radius: 4px; margin-bottom: 12px;">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <!-- Stats Cards -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; margin-bottom: 25px;">
        <div style="background: #fff3cd; padding: 15px; border-radius: 5px; border-left: 4px solid #ff9800; text-align: center;">
            <div style="font-size: 24px; font-weight: bold; color: #ff9800;"><?php echo $stats['pending']; ?></div>
            <div style="font-size: 12px; color: #666;">Pending</div>
        </div>
        <div style="background: #d4edda; padding: 15px; border-radius: 5px; border-left: 4px solid #4caf50; text-align: center;">
            <div style="font-size: 24px; font-weight: bold; color: #4caf50;"><?php echo $stats['approved']; ?></div>
            <div style="font-size: 12px; color: #666;">Approved</div>
        </div>
        <div style="background: #f8d7da; padding: 15px; border-radius: 5px; border-left: 4px solid #f44336; text-align: center;">
            <div style="font-size: 24px; font-weight: bold; color: #f44336;"><?php echo $stats['rejected']; ?></div>
            <div style="font-size: 12px; color: #666;">Rejected</div>
        </div>
    </div>

    <h3>Pending Requests</h3>
    <?php if (!empty($pending_requests)): ?>
        <?php foreach ($pending_requests as $req): ?>
            <div style="background: white; padding: 20px; border-radius: 5px; border: 1px solid #ddd; margin-bottom: 15px;">
                <h4 style="margin-top: 0; color: #0066cc;"><?php echo htmlspecialchars($req['full_name']); ?> - ID: <?php echo $req['national_id']; ?></h4>
                <p><strong>Request Date:</strong> <?php echo date('Y-m-d H:i', strtotime($req['request_date'])); ?></p>
                <p><strong>Reason:</strong> <?php echo nl2br(htmlspecialchars($req['reason'])); ?></p>

                <form method="POST" style="background: #f9f9f9; padding: 15px; border-radius: 4px; margin-top: 15px;">
                    <input type="hidden" name="request_id" value="<?php echo $req['id']; ?>">

                    <div style="display: flex; gap: 20px; margin-bottom: 15px;">
                        <label style="flex: 1;">
                            <input type="radio" name="action" value="approve" required style="margin-right: 8px;"> Approve
                        </label>
                        <label style="flex: 1;">
                            <input type="radio" name="action" value="reject" required style="margin-right: 8px;"> Reject
                        </label>
                    </div>

                    <div id="approve_section_<?php echo $req['id']; ?>" style="display: none; margin-bottom: 15px; padding: 15px; background: white; border-radius: 4px; border: 1px solid #ddd;">
                        <label style="font-weight: bold; display: block; margin-bottom: 5px;">New Temporary Password *</label>
                        <input type="password" id="new_pwd_<?php echo $req['id']; ?>" name="new_password" minlength="6" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; margin-bottom: 8px;">
                        <small style="color: #666;">Password will be changed when approved. Member can change it again on first login.</small>

                        <label style="font-weight: bold; display: block; margin-top: 8px; margin-bottom: 5px;">Admin Comment (Optional)</label>
                        <textarea id="approve_comment_<?php echo $req['id']; ?>" name="admin_comment" rows="2" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;" placeholder="E.g., Password reset approved."></textarea>
                    </div>

                    <div id="reject_section_<?php echo $req['id']; ?>" style="display: none; margin-bottom: 15px; padding: 15px; background: #fff5f5; border-radius: 4px; border: 1px solid #f5c6cb;">
                        <label style="font-weight: bold; display: block; margin-bottom: 5px;">Reason for Rejection *</label>
                        <textarea id="reject_comment_<?php echo $req['id']; ?>" name="admin_comment" rows="2" required style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;" placeholder="Please explain why request is rejected..."></textarea>
                    </div>

                    <button type="submit" id="submit_btn_<?php echo $req['id']; ?>" style="background: #28a745; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; font-weight: bold;">Submit Decision</button>
                </form>

                <script>
                    const req_id = <?php echo $req['id']; ?>;
                    const radios_<?php echo $req['id']; ?> = document.querySelectorAll('input[name="action"]');
                    const approve_section_<?php echo $req['id']; ?> = document.getElementById('approve_section_' + req_id);
                    const reject_section_<?php echo $req['id']; ?> = document.getElementById('reject_section_' + req_id);
                    const submit_btn_<?php echo $req['id']; ?> = document.getElementById('submit_btn_' + req_id);

                    function updateForm_<?php echo $req['id']; ?>() {
                        const action = document.querySelector('input[name="action"]:checked')?.value;
                        if (action === 'approve') {
                            approve_section_<?php echo $req['id']; ?>.style.display = 'block';
                            reject_section_<?php echo $req['id']; ?>.style.display = 'none';
                            document.getElementById('new_pwd_' + req_id).required = true;
                            document.getElementById('reject_comment_' + req_id).required = false;
                            submit_btn_<?php echo $req['id']; ?>.textContent = 'Approve';
                            submit_btn_<?php echo $req['id']; ?>.style.backgroundColor = '#28a745';
                        } else if (action === 'reject') {
                            approve_section_<?php echo $req['id']; ?>.style.display = 'none';
                            reject_section_<?php echo $req['id']; ?>.style.display = 'block';
                            document.getElementById('new_pwd_' + req_id).required = false;
                            document.getElementById('reject_comment_' + req_id).required = true;
                            submit_btn_<?php echo $req['id']; ?>.textContent = 'Reject';
                            submit_btn_<?php echo $req['id']; ?>.style.backgroundColor = '#dc3545';
                        }
                    }

                    radios_<?php echo $req['id']; ?>.forEach(radio => radio.addEventListener('change', updateForm_<?php echo $req['id']; ?>));
                </script>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div style="background: #f0f0f0; padding: 30px; text-align: center; border-radius: 4px;">
            <p style="color: #666;">No pending password reset requests.</p>
        </div>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>
