<?php
require_once '../config/db.php';
include '../includes/header.php';

if ($_SESSION['role'] != 'admin') {
    header("Location: ../member/dashboard.php");
    exit();
}

$message = '';

// Handle Add Member
if (isset($_POST['add_member'])) {
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $full_name = $_POST['full_name'];
    $national_id = $_POST['national_id'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];
    $gender = $_POST['gender'];

    try {
        $pdo->beginTransaction();
        
        // Create user account
        $stmt = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, 'member')");
        $stmt->execute([$username, $password]);
        $user_id = $pdo->lastInsertId();

        // Create member profile
        $stmt = $pdo->prepare("INSERT INTO members (user_id, full_name, national_id, phone, address, gender) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, $full_name, $national_id, $phone, $address, $gender]);

        $pdo->commit();
        $message = "Member added successfully!";
    } catch (Exception $e) {
        $pdo->rollBack();
        $message = "Error: " . $e->getMessage();
    }
}

// Fetch all members
$search = isset($_GET['q']) ? trim($_GET['q']) : '';
if ($search !== '') {
    $like = '%' . $search . '%';
    $stmt = $pdo->prepare("SELECT m.*, u.username, u.id as user_id FROM members m LEFT JOIN users u ON m.user_id = u.id
        WHERE m.full_name LIKE ? OR m.national_id LIKE ? OR u.username LIKE ? OR m.phone LIKE ?
        ORDER BY m.created_at DESC");
    $stmt->execute([$like, $like, $like, $like]);
} else {
    $stmt = $pdo->query("SELECT m.*, u.username, u.id as user_id FROM members m LEFT JOIN users u ON m.user_id = u.id ORDER BY m.created_at DESC");
}
$members = $stmt->fetchAll();
?>

<h2>Manage Members</h2>

<?php if ($message): ?>
    <div class="alert alert-success">
        <?php echo htmlspecialchars($message); ?>
    </div>
<?php endif; ?>

<div style="margin-bottom: 30px; display: flex; gap: 10px; align-items: center;">
    <button type="button" onclick="openAddMemberModal()" style="background: #28a745; color: white; padding: 10px 15px; border-radius: 4px; border: none; cursor: pointer; font-weight: bold;">Add New Member</button>
    <a href="password_reset_requests.php" style="background: #ff9800; color: white; padding: 10px 15px; border-radius: 4px; text-decoration: none; font-weight: bold;">Password Reset Requests</a>
</div>

<!-- Modal for Add Member Form -->
<div id="addMemberModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
    <div style="background: white; padding: 30px; border-radius: 6px; box-shadow: 0 4px 20px rgba(0,0,0,0.2); max-width: 600px; width: 90%; max-height: 90vh; overflow-y: auto;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3 style="margin: 0;">Add New Member</h3>
            <button type="button" onclick="closeAddMemberModal()" style="background: none; border: none; font-size: 24px; cursor: pointer; color: #666;">×</button>
        </div>

        <form method="POST" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
            <div>
                <label style="font-weight: bold; display: block; margin-bottom: 5px;">Username *</label>
                <input type="text" name="username" required style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
            </div>
            <div>
                <label style="font-weight: bold; display: block; margin-bottom: 5px;">Password *</label>
                <input type="password" name="password" required style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
            </div>
            <div>
                <label style="font-weight: bold; display: block; margin-bottom: 5px;">Full Name *</label>
                <input type="text" name="full_name" required style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
            </div>
            <div>
                <label style="font-weight: bold; display: block; margin-bottom: 5px;">National ID *</label>
                <input type="text" name="national_id" required style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
            </div>
            <div>
                <label style="font-weight: bold; display: block; margin-bottom: 5px;">Phone</label>
                <input type="text" name="phone" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
            </div>
            <div>
                <label style="font-weight: bold; display: block; margin-bottom: 5px;">Gender</label>
                <select name="gender" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                    <option value="Male">Male</option>
                    <option value="Female">Female</option>
                    <option value="Other">Other</option>
                </select>
            </div>
            <div style="grid-column: 1 / -1;">
                <label style="font-weight: bold; display: block; margin-bottom: 5px;">Address</label>
                <textarea name="address" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; min-height: 100px;"></textarea>
            </div>
            <div style="grid-column: 1 / -1; display: flex; gap: 10px;">
                <button type="submit" name="add_member" style="background: #28a745; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; font-weight: bold;">Register Member</button>
                <button type="button" onclick="closeAddMemberModal()" style="background: #6c757d; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; font-weight: bold;">Cancel</button>
            </div>
        </form>
    </div>
</div>

<h3>Member List</h3>
<div style="margin-bottom:12px; display:flex; gap:8px; align-items:center;">
    <form method="get" action="" style="display:flex; gap:8px; align-items:center;">
        <input type="search" name="q" placeholder="Search members by name, username, national ID or phone" value="<?php echo htmlspecialchars($search ?? ''); ?>" style="padding:8px; border:1px solid #ddd; border-radius:4px; width:360px;">
        <button type="submit" style="background:#0066cc; color:white; padding:8px 12px; border-radius:4px; border:none; cursor:pointer;">Search</button>
        <?php if (!empty($search)): ?>
            <a href="members.php" style="display:inline-block; margin-left:6px; color:#0066cc; text-decoration:none;">Clear</a>
        <?php endif; ?>
    </form>
</div>
<div class="table-responsive">
<table style="width: 100%; border-collapse: collapse; background: white; border-radius: 5px; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-bottom: 30px;">
    <thead>
        <tr style="background: #398b6c; border-bottom: 2px solid #ddd;">
            <th style="padding: 12px; text-align: left;">ID</th>
            <th style="padding: 12px; text-align: left;">Full Name</th>
            <th style="padding: 12px; text-align: left;">National ID</th>
            <th style="padding: 12px; text-align: left;">Phone</th>
            <th style="padding: 12px; text-align: left;">Username</th>
            <th style="padding: 12px; text-align: left;">Created</th>
            <th style="padding: 12px; text-align: center;">Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($members as $member): ?>
        <tr style="border-bottom: 1px solid #eee;">
            <td style="padding: 12px;"><?php echo $member['id']; ?></td>
            <td style="padding: 12px;"><strong><?php echo htmlspecialchars($member['full_name']); ?></strong></td>
            <td style="padding: 12px;"><?php echo htmlspecialchars($member['national_id']); ?></td>
            <td style="padding: 12px;"><?php echo htmlspecialchars($member['phone'] ?? 'N/A'); ?></td>
            <td style="padding: 12px;"><?php echo htmlspecialchars($member['username'] ?? 'Not assigned'); ?></td>
            <td style="padding: 12px;"><?php echo date('Y-m-d', strtotime($member['created_at'])); ?></td>
            <td style="padding: 12px; text-align: center;">
                <a href="edit_member.php?id=<?php echo $member['id']; ?>" style="background: #0066cc; color: white; padding: 6px 12px; border-radius: 3px; text-decoration: none; font-size: 12px; font-weight: bold; margin-right: 5px;">Edit</a>
                <?php if ($member['user_id']): ?>
                    <a href="reset_member_password.php?id=<?php echo $member['id']; ?>" style="background: #ff9800; color: white; padding: 6px 12px; border-radius: 3px; text-decoration: none; font-size: 12px; font-weight: bold;">Reset</a>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
</div>

    <?php if (empty($members)): ?>
        <div style="background: #f0f0f0; padding: 20px; text-align: center; border-radius: 5px;">
            <p style="color: #666;">No members found.</p>
        </div>
    <?php endif; ?>
</div>

<script>
    function openAddMemberModal() {
        document.getElementById('addMemberModal').style.display = 'flex';
    }

    function closeAddMemberModal() {
        document.getElementById('addMemberModal').style.display = 'none';
    }

    // Close modal when clicking outside of it
    document.getElementById('addMemberModal').addEventListener('click', function(event) {
        if (event.target === this) {
            closeAddMemberModal();
        }
    });
</script>
