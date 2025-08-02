<?php
/**
 * Call History Version Selector
 * เลือกเวอร์ชันของหน้า Call History ที่ต้องการใช้
 */

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$current_user = $_SESSION['username'] ?? 'unknown';
$current_role = $_SESSION['user_role'] ?? 'unknown';
$customerCode = $_GET['customer'] ?? '';

// If no customer specified, redirect to customer selection
if (!$customerCode) {
    header('Location: simple_call_history_test.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Call History - เลือกเวอร์ชัน</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; }
        .main-container { margin-top: 50px; }
        .version-card { 
            background: white; 
            border-radius: 15px; 
            padding: 30px; 
            box-shadow: 0 10px 30px rgba(0,0,0,0.2); 
            margin-bottom: 30px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .version-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.3);
        }
        .version-header {
            border-bottom: 2px solid #f8f9fa;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        .version-icon {
            font-size: 3rem;
            margin-bottom: 15px;
        }
        .status-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            font-size: 0.8rem;
        }
        .header-info {
            background: rgba(255,255,255,0.95);
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            backdrop-filter: blur(10px);
        }
    </style>
</head>
<body>
    <div class="container main-container">
        <!-- Header Info -->
        <div class="header-info">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="mb-2"><i class="fas fa-phone text-primary"></i> Call History - เลือกเวอร์ชัน</h1>
                    <p class="mb-0 text-muted">เลือกเวอร์ชันที่เหมาะสมสำหรับลูกค้า: <strong><?php echo htmlspecialchars($customerCode); ?></strong></p>
                </div>
                <div class="col-md-4 text-end">
                    <div class="badge bg-primary fs-6 me-2">
                        <i class="fas fa-user"></i> <?php echo htmlspecialchars($current_user); ?>
                    </div>
                    <div class="badge bg-success fs-6">
                        <?php echo htmlspecialchars($current_role); ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="row justify-content-center">
            <!-- Simple Version -->
            <div class="col-lg-4">
                <div class="version-card position-relative">
                    <span class="status-badge badge bg-success">✅ แนะนำ</span>
                    <div class="version-header text-center">
                        <div class="version-icon text-success">
                            <i class="fas fa-rocket"></i>
                        </div>
                        <h3 class="text-success">Simple Version</h3>
                        <p class="text-muted">เวอร์ชันง่าย ทำงานได้แน่นอน</p>
                    </div>
                    
                    <div class="mb-4">
                        <h5><i class="fas fa-check-circle text-success"></i> คุณสมบัติ:</h5>
                        <ul class="list-unstyled">
                            <li><i class="fas fa-check text-success me-2"></i> ✅ ทำงานได้ 100%</li>
                            <li><i class="fas fa-check text-success me-2"></i> ✅ โหลดเร็ว</li>
                            <li><i class="fas fa-check text-success me-2"></i> ✅ แสดงข้อมูลลูกค้า</li>
                            <li><i class="fas fa-check text-success me-2"></i> ✅ แสดงสถิติการโทร</li>
                            <li><i class="fas fa-check text-success me-2"></i> ✅ แสดงประวัติการโทร</li>
                            <li><i class="fas fa-check text-success me-2"></i> ✅ เพิ่มบันทึกได้</li>
                            <li><i class="fas fa-times text-muted me-2"></i> ⚪ ไม่มี sidebar</li>
                            <li><i class="fas fa-times text-muted me-2"></i> ⚪ UI แบบง่าย</li>
                        </ul>
                    </div>
                    
                    <div class="text-center">
                        <a href="debug_call_history_simple.php?customer=<?php echo urlencode($customerCode); ?>" 
                           class="btn btn-success btn-lg px-5">
                            <i class="fas fa-play"></i> ใช้เวอร์ชันนี้
                        </a>
                    </div>
                </div>
            </div>

            <!-- Production Safe Version -->
            <div class="col-lg-4">
                <div class="version-card position-relative">
                    <span class="status-badge badge bg-info">⭐ ใหม่</span>
                    <div class="version-header text-center">
                        <div class="version-icon text-info">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <h3 class="text-info">Production Safe</h3>
                        <p class="text-muted">เวอร์ชัน Production ที่ปลอดภัย</p>
                    </div>
                    
                    <div class="mb-4">
                        <h5><i class="fas fa-shield-alt text-info"></i> คุณสมบัติ:</h5>
                        <ul class="list-unstyled">
                            <li><i class="fas fa-check text-success me-2"></i> ✅ Sidebar + Layout</li>
                            <li><i class="fas fa-check text-success me-2"></i> ✅ UI สวยงาม</li>
                            <li><i class="fas fa-check text-success me-2"></i> ✅ ทำงานได้มั่นคง</li>
                            <li><i class="fas fa-check text-success me-2"></i> ✅ ไม่มี complex JS</li>
                            <li><i class="fas fa-check text-success me-2"></i> ✅ แสดงข้อมูลครบ</li>
                            <li><i class="fas fa-check text-success me-2"></i> ✅ Error handling</li>
                            <li><i class="fas fa-times text-muted me-2"></i> ⚪ ไม่มี API interactive</li>
                        </ul>
                    </div>
                    
                    <div class="text-center">
                        <a href="pages/call_history_demo_production_safe.php?customer=<?php echo urlencode($customerCode); ?>" 
                           class="btn btn-info btn-lg px-5">
                            <i class="fas fa-shield-alt"></i> ใช้เวอร์ชันนี้
                        </a>
                    </div>
                </div>
            </div>

            <!-- Production Full Version -->
            <div class="col-lg-4">
                <div class="version-card position-relative">
                    <span class="status-badge badge bg-danger">❌ Error</span>
                    <div class="version-header text-center">
                        <div class="version-icon text-danger">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <h3 class="text-danger">Production Full</h3>
                        <p class="text-muted">เวอร์ชันเต็ม ติด HTTP 500</p>
                    </div>
                    
                    <div class="mb-4">
                        <h5><i class="fas fa-exclamation-triangle text-danger"></i> ปัญหา:</h5>
                        <ul class="list-unstyled">
                            <li><i class="fas fa-times text-danger me-2"></i> ❌ HTTP 500 Error</li>
                            <li><i class="fas fa-times text-danger me-2"></i> ❌ Complex JavaScript</li>
                            <li><i class="fas fa-times text-danger me-2"></i> ❌ API loading issues</li>
                            <li><i class="fas fa-exclamation text-warning me-2"></i> ⚠️ ยังแก้ไขไม่สำเร็จ</li>
                            <li><i class="fas fa-info text-info me-2"></i> ℹ️ ใช้สำหรับ debug</li>
                        </ul>
                    </div>
                    
                    <div class="text-center">
                        <a href="pages/call_history_demo_fixed.php?customer=<?php echo urlencode($customerCode); ?>" 
                           class="btn btn-outline-danger btn-lg px-5">
                            <i class="fas fa-bug"></i> Debug Version
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Debug Tools -->
        <div class="row justify-content-center mt-4">
            <div class="col-lg-8">
                <div class="version-card">
                    <div class="text-center">
                        <h4><i class="fas fa-tools text-info"></i> เครื่องมือ Debug</h4>
                        <p class="text-muted mb-4">สำหรับแก้ไขปัญหาและวิเคราะห์ระบบ</p>
                        
                        <div class="d-flex flex-wrap justify-content-center gap-2">
                            <a href="debug_call_history_500.php" class="btn btn-outline-info btn-sm">
                                <i class="fas fa-bug"></i> Debug HTTP 500
                            </a>
                            <a href="test_TEST050_specific.php" class="btn btn-outline-info btn-sm">
                                <i class="fas fa-search"></i> Analyze TEST050
                            </a>
                            <a href="simple_call_history_test.php" class="btn btn-outline-info btn-sm">
                                <i class="fas fa-list"></i> Customer List
                            </a>
                            <a href="debug_sales_assignment.php" class="btn btn-outline-info btn-sm">
                                <i class="fas fa-user-cog"></i> Sales Assignment
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Navigation -->
        <div class="row justify-content-center mt-4">
            <div class="col-lg-6">
                <div class="version-card text-center">
                    <h5><i class="fas fa-arrow-left text-muted"></i> การนำทาง</h5>
                    <div class="d-flex justify-content-center gap-2 flex-wrap">
                        <a href="index.php" class="btn btn-outline-secondary">
                            <i class="fas fa-home"></i> หน้าแรก
                        </a>
                        <a href="simple_call_history_test.php" class="btn btn-outline-secondary">
                            <i class="fas fa-users"></i> เลือกลูกค้าอื่น
                        </a>
                        <a href="logout.php" class="btn btn-outline-danger">
                            <i class="fas fa-sign-out-alt"></i> ออกจากระบบ
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>