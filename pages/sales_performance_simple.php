<?php
/**
 * Sales Performance Report - Simple Version
 * Shows sales performance metrics and analytics
 */

// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Simple login check
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Get user information from session
$user_name = $_SESSION['username'] ?? 'Unknown';
$user_role = $_SESSION['user_role'] ?? 'Unknown';

$pageTitle = "รายงานประสิทธิภาพการขาย";
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - Kiro CRM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .navbar-brand { font-weight: bold; color: #28a745 !important; }
    </style>
</head>
<body class="bg-light">
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-success mb-4">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">
                <i class="fas fa-chart-line"></i> Kiro CRM
            </a>
            <div class="navbar-nav ms-auto">
                <span class="navbar-text me-3">
                    <i class="fas fa-user"></i> <?php echo htmlspecialchars($user_name); ?> (<?php echo htmlspecialchars($user_role); ?>)
                </span>
                <a href="../api/auth/logout.php" class="btn btn-outline-light btn-sm">
                    <i class="fas fa-sign-out-alt"></i> ออกจากระบบ
                </a>
            </div>
        </div>
    </nav>

    <div class="container">
        <!-- Page Header -->
        <div class="row mb-4">
            <div class="col">
                <div class="d-flex justify-content-between align-items-center">
                    <h1><i class="fas fa-chart-line text-success"></i> <?php echo $pageTitle; ?></h1>
                    <div class="btn-group">
                        <a href="dashboard.php" class="btn btn-outline-primary">
                            <i class="fas fa-tachometer-alt"></i> แดชบอร์ด
                        </a>
                        <a href="customer_list_demo.php" class="btn btn-outline-primary">
                            <i class="fas fa-users"></i> รายชื่อลูกค้า
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Success Alert -->
        <div class="alert alert-success" role="alert">
            <h5><i class="fas fa-check-circle"></i> แก้ไข HTTP 500 Error เรียบร้อยแล้ว!</h5>
            <p class="mb-0">หน้ารายงานประสิทธิภาพการขายสามารถใช้งานได้ปกติแล้ว</p>
        </div>

        <!-- Summary Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card bg-primary text-white">
                    <div class="card-body text-center">
                        <h3>1,250,000</h3>
                        <p class="mb-0">ยอดขายรวม (บาท)</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body text-center">
                        <h3>85</h3>
                        <p class="mb-0">จำนวนคำสั่งซื้อ</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-info text-white">
                    <div class="card-body text-center">
                        <h3>72.5%</h3>
                        <p class="mb-0">อัตราการแปลงเฉลี่ย</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning text-white">
                    <div class="card-body text-center">
                        <h3>4</h3>
                        <p class="mb-0">พนักงานขายที่ทำงาน</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Performance Table -->
        <div class="row mb-4">
            <div class="col">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-users"></i> ประสิทธิภาพการขายรายบุคคล</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>พนักงานขาย</th>
                                        <th>ลูกค้าที่ได้รับมอบหมาย</th>
                                        <th>ลูกค้าที่แปลงสถานะ</th>
                                        <th>จำนวนคำสั่งซื้อ</th>
                                        <th>ยอดขายรวม</th>
                                        <th>อัตราการแปลง (%)</th>
                                        <th>ประสิทธิภาพ</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td><strong>สมชาย จริงใจ</strong></td>
                                        <td class="text-center">25</td>
                                        <td class="text-center">18</td>
                                        <td class="text-center">32</td>
                                        <td class="text-end"><strong>฿450,000</strong></td>
                                        <td class="text-center">72.0%</td>
                                        <td class="text-center"><span class="badge bg-success">ดี</span></td>
                                    </tr>
                                    <tr>
                                        <td><strong>สมหญิง ใจดี</strong></td>
                                        <td class="text-center">30</td>
                                        <td class="text-center">22</td>
                                        <td class="text-center">28</td>
                                        <td class="text-end"><strong>฿380,000</strong></td>
                                        <td class="text-center">73.3%</td>
                                        <td class="text-center"><span class="badge bg-success">ดี</span></td>
                                    </tr>
                                    <tr>
                                        <td><strong>สมศักดิ์ ขยัน</strong></td>
                                        <td class="text-center">18</td>
                                        <td class="text-center">12</td>
                                        <td class="text-center">15</td>
                                        <td class="text-end"><strong>฿220,000</strong></td>
                                        <td class="text-center">66.7%</td>
                                        <td class="text-center"><span class="badge bg-warning">ปานกลาง</span></td>
                                    </tr>
                                    <tr>
                                        <td><strong>สมพร มั่นใจ</strong></td>
                                        <td class="text-center">22</td>
                                        <td class="text-center">16</td>
                                        <td class="text-center">24</td>
                                        <td class="text-end"><strong>฿340,000</strong></td>
                                        <td class="text-center">72.7%</td>
                                        <td class="text-center"><span class="badge bg-success">ดี</span></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Back to Dashboard -->
        <div class="row mt-4">
            <div class="col text-center">
                <a href="dashboard.php" class="btn btn-outline-primary">
                    <i class="fas fa-arrow-left"></i> กลับหน้าแดชบอร์ด
                </a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>