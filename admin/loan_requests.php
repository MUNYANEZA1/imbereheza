<?php
session_start();
require_once '../config/db.php';
include '../includes/header.php';

if ($_SESSION['role'] != 'admin') {
    header("Location: ../member/dashboard.php");
    exit();
}

$filter = isset($_GET['filter']) ? $_GET['filter'] : 'pending';
$search = isset($_GET['q']) ? trim($_GET['q']) : '';

// Build base query and parameters depending on filter and search
$params = [];
$whereStatus = '';
if ($filter === 'all') {
    $whereStatus = '';
} elseif ($filter === 'approved') {
    $whereStatus = "lr.status = 'Approved'";
} elseif ($filter === 'rejected') {
    $whereStatus = "lr.status = 'Rejected'";
} else { // pending
    $whereStatus = "lr.status = 'Pending'";
}

if ($search !== '') {
    $like = '%' . $search . '%';
    $params = [$like, $like, $like, $like]; // for name, national_id, purpose, id
    $searchClause = "(m.full_name LIKE ? OR m.national_id LIKE ? OR lr.purpose LIKE ? OR CAST(lr.id AS CHAR) LIKE ?)";
    if ($whereStatus !== '') {
        $sql = "SELECT lr.*, m.full_name, m.national_id FROM loan_requests lr JOIN members m ON lr.member_id = m.id WHERE " . $whereStatus . " AND " . $searchClause . " ORDER BY lr.request_date DESC";
    } else {
        $sql = "SELECT lr.*, m.full_name, m.national_id FROM loan_requests lr JOIN members m ON lr.member_id = m.id WHERE " . $searchClause . " ORDER BY lr.request_date DESC";
    }
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
} else {
    if ($whereStatus !== '') {
        $stmt = $pdo->query("SELECT lr.*, m.full_name, m.national_id 
                         FROM loan_requests lr 
                         JOIN members m ON lr.member_id = m.id 
                         WHERE $whereStatus
                         ORDER BY lr.request_date DESC");
    } else {
        $stmt = $pdo->query("SELECT lr.*, m.full_name, m.national_id 
                         FROM loan_requests lr 
                         JOIN members m ON lr.member_id = m.id 
                         ORDER BY lr.request_date DESC");
    }
}

$requests = $stmt->fetchAll();

// Get stats
$stmt = $pdo->query("SELECT 
                        COUNT(*) as total,
                        SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending,
                        SUM(CASE WHEN status = 'Approved' THEN 1 ELSE 0 END) as approved,
                        SUM(CASE WHEN status = 'Rejected' THEN 1 ELSE 0 END) as rejected
                     FROM loan_requests");
$stats = $stmt->fetch();
?>

<div style="max-width: 1200px; margin: 0 auto;">
    <h2>Loan Requests Management</h2>

    <!-- Stats Cards -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 30px;">
        <div style="background: #e3f2fd; padding: 20px; border-radius: 5px; border-left: 4px solid #2196F3;">
            <div style="font-size: 12px; color: #666; margin-bottom: 5px;">TOTAL REQUESTS</div>
            <div style="font-size: 28px; font-weight: bold; color: #2196F3;"><?php echo $stats['total']; ?></div>
        </div>

        <div style="background: #fff3e0; padding: 20px; border-radius: 5px; border-left: 4px solid #ff9800;">
            <div style="font-size: 12px; color: #666; margin-bottom: 5px;">PENDING REVIEW</div>
            <div style="font-size: 28px; font-weight: bold; color: #ff9800;"><?php echo $stats['pending']; ?></div>
        </div>

        <div style="background: #e8f5e9; padding: 20px; border-radius: 5px; border-left: 4px solid #4caf50;">
            <div style="font-size: 12px; color: #666; margin-bottom: 5px;">APPROVED</div>
            <div style="font-size: 28px; font-weight: bold; color: #4caf50;"><?php echo $stats['approved']; ?></div>
        </div>

        <div style="background: #ffebee; padding: 20px; border-radius: 5px; border-left: 4px solid #f44336;">
            <div style="font-size: 12px; color: #666; margin-bottom: 5px;">REJECTED</div>
            <div style="font-size: 28px; font-weight: bold; color: #f44336;"><?php echo $stats['rejected']; ?></div>
        </div>
    </div>

    <!-- Filter Buttons -->
    <div style="margin-bottom: 20px; display: flex; gap: 10px; flex-wrap: wrap;">
            <a href="?filter=pending" style="padding: 10px 15px; border-radius: 4px; text-decoration: none; font-weight: bold;
                                          background: <?php echo $filter === 'pending' ? '#ff9800' : '#e9ecef'; ?>;
                                          color: <?php echo $filter === 'pending' ? 'white' : '#333'; ?>;">
            Pending (<?php echo $stats['pending']; ?>)
        </a>
        <a href="?filter=approved" style="padding: 10px 15px; border-radius: 4px; text-decoration: none; font-weight: bold;
                                           background: <?php echo $filter === 'approved' ? '#4caf50' : '#e9ecef'; ?>;
                                           color: <?php echo $filter === 'approved' ? 'white' : '#333'; ?>;">
            Approved (<?php echo $stats['approved']; ?>)
        </a>
        <a href="?filter=rejected" style="padding: 10px 15px; border-radius: 4px; text-decoration: none; font-weight: bold;
                                           background: <?php echo $filter === 'rejected' ? '#f44336' : '#e9ecef'; ?>;
                                           color: <?php echo $filter === 'rejected' ? 'white' : '#333'; ?>;">
            Rejected (<?php echo $stats['rejected']; ?>)
        </a>
        <a href="?filter=all" style="padding: 10px 15px; border-radius: 4px; text-decoration: none; font-weight: bold;
                                      background: <?php echo $filter === 'all' ? '#2196F3' : '#e9ecef'; ?>;
                                      color: <?php echo $filter === 'all' ? 'white' : '#333'; ?>;">
            All (<?php echo $stats['total']; ?>)
        </a>
    </div>

    <div style="margin-bottom:12px; display:flex; gap:8px; align-items:center;">
        <form method="get" action="" style="display:flex; gap:8px; align-items:center;">
            <input type="hidden" name="filter" value="<?php echo htmlspecialchars($filter); ?>">
            <input type="search" name="q" placeholder="Search requests by member, ID, national ID or purpose" value="<?php echo htmlspecialchars($search ?? ''); ?>" style="padding:8px; border:1px solid #ddd; border-radius:4px; width:420px;">
            <button type="submit" style="background:#0066cc; color:white; padding:8px 12px; border-radius:4px; border:none; cursor:pointer;">Search</button>
            <?php if (!empty($search)): ?>
                <a href="loan_requests.php?filter=<?php echo urlencode($filter); ?>" style="display:inline-block; margin-left:6px; color:#0066cc; text-decoration:none;">Clear</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Loan Requests Table -->
    <?php if (!empty($requests)): ?>
        <div style="overflow-x: auto; background: white; border-radius: 5px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="background: #f5f5f5; border-bottom: 2px solid #ddd;">
                        <th style="padding: 12px; text-align: left; font-weight: bold;">Request ID</th>
                        <th style="padding: 12px; text-align: left; font-weight: bold;">Member Name</th>
                        <th style="padding: 12px; text-align: left; font-weight: bold;">Amount (RWF)</th>
                        <th style="padding: 12px; text-align: left; font-weight: bold;">Purpose</th>
                        <th style="padding: 12px; text-align: left; font-weight: bold;">Period</th>
                        <th style="padding: 12px; text-align: left; font-weight: bold;">Request Date</th>
                        <th style="padding: 12px; text-align: left; font-weight: bold;">Status</th>
                        <th style="padding: 12px; text-align: left; font-weight: bold;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($requests as $req): ?>
                        <tr style="border-bottom: 1px solid #eee; hover: background #f9f9f9;">
                            <td style="padding: 12px;">#<?php echo $req['id']; ?></td>
                            <td style="padding: 12px;">
                                <strong><?php echo htmlspecialchars($req['full_name']); ?></strong>
                                <br><small style="color: #666;"><?php echo htmlspecialchars($req['national_id']); ?></small>
                            </td>
                            <td style="padding: 12px; font-weight: bold; color: #ff6b6b;">
                                RWF <?php echo number_format($req['amount_requested'], 2); ?>
                            </td>
                            <td style="padding: 12px;">
                                <small><?php echo htmlspecialchars(substr($req['purpose'], 0, 40)) . (strlen($req['purpose']) > 40 ? '...' : ''); ?></small>
                            </td>
                            <td style="padding: 12px; text-align: center;">
                                <?php echo $req['repayment_period']; ?> mo.
                            </td>
                            <td style="padding: 12px;">
                                <?php echo date('Y-m-d', strtotime($req['request_date'])); ?>
                            </td>
                            <td style="padding: 12px;">
                                <span style="display: inline-block; padding: 6px 12px; border-radius: 20px; font-size: 12px; font-weight: bold;
                                           background: <?php 
                                               echo $req['status'] == 'Approved' ? '#d4edda' : 
                                                    ($req['status'] == 'Rejected' ? '#f8d7da' : '#fff3cd');
                                           ?>;
                                           color: <?php 
                                               echo $req['status'] == 'Approved' ? '#155724' : 
                                                    ($req['status'] == 'Rejected' ? '#721c24' : '#856404');
                                           ?>;">
                                    <?php 
                                        echo $req['status'] == 'Pending' ? 'Pending' : 
                                             ($req['status'] == 'Approved' ? 'Approved' : 'Rejected');
                                    ?>
                                </span>
                            </td>
                            <td style="padding: 12px;">
                                <a href="loan_request_review.php?id=<?php echo $req['id']; ?>" 
                                   style="background: #0066cc; color: white; padding: 6px 12px; border-radius: 3px; text-decoration: none; font-size: 12px; font-weight: bold;">
                                    Review
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div style="background: #f0f0f0; padding: 40px; text-align: center; border-radius: 5px;">
            <p style="font-size: 18px; color: #666; margin-bottom: 10px;">
                <?php 
                    if ($filter === 'pending') {
                        echo "No pending loan requests.";
                    } elseif ($filter === 'approved') {
                        echo "No approved loan requests.";
                    } elseif ($filter === 'rejected') {
                        echo "No rejected loan requests.";
                    } else {
                        echo "No loan requests found.";
                    }
                ?>
            </p>
            <a href="?filter=pending" style="color: #0066cc; text-decoration: none;">Back to Pending</a>
        </div>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>
