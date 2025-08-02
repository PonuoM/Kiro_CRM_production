<?php
/**
 * Call History Demo Page - With Line by Line Debug
 */

// Enable maximum error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

echo "<!-- DEBUG: Starting call_history_demo_debug.php -->\n";

try {
    echo "<!-- DEBUG: Step 1 - Loading permissions -->\n";
    require_once __DIR__ . '/includes/permissions.php';
    echo "<!-- DEBUG: Step 1 - ✅ Permissions loaded -->\n";

    echo "<!-- DEBUG: Step 2 - Loading main_layout -->\n";
    require_once __DIR__ . '/includes/main_layout.php';
    echo "<!-- DEBUG: Step 2 - ✅ Main layout loaded -->\n";

    echo "<!-- DEBUG: Step 3 - Checking login -->\n";
    Permissions::requireLogin();
    echo "<!-- DEBUG: Step 3 - ✅ Login checked -->\n";

    echo "<!-- DEBUG: Step 4 - Checking permission -->\n";
    if (!Permissions::hasPermission('call_history')) {
        echo "Debug: User " . Permissions::getCurrentUser() . " (Role: " . Permissions::getCurrentRole() . ") lacks call_history permission";
        exit;
    }
    echo "<!-- DEBUG: Step 4 - ✅ Permission checked -->\n";

    echo "<!-- DEBUG: Step 5 - Setting variables -->\n";
    $pageTitle = "ประวัติการโทร";
    $user_name = Permissions::getCurrentUser();
    $user_role = Permissions::getCurrentRole();
    $menuItems = Permissions::getMenuItems();
    $testCustomerCode = $_GET['customer'] ?? 'CUS20240115103012345';
    echo "<!-- DEBUG: Step 5 - ✅ Variables set -->\n";

    echo "<!-- DEBUG: Step 6 - Setting globals -->\n";
    $GLOBALS['currentUser'] = $user_name;
    $GLOBALS['currentRole'] = $user_role;
    $GLOBALS['menuItems'] = $menuItems;
    echo "<!-- DEBUG: Step 6 - ✅ Globals set -->\n";

    echo "<!-- DEBUG: Step 7 - Starting content buffer -->\n";
    ob_start();
    ?>

    <!-- Page Header -->
    <div class="page-header">
        <h1 class="page-title">
            <i class="fas fa-phone"></i>
            ประวัติการโทร
        </h1>
        <p class="page-description">
            ดูประวัติและจัดการบันทึกการโทรเข้าหาลูกค้า
        </p>
    </div>

    <!-- Demo Info -->
    <div class="alert alert-info border-start border-primary border-4 mb-4">
        <strong><i class="fas fa-info-circle"></i> หมายเหตุ:</strong> นี่เป็นหน้าทดสอบสำหรับ Call History Component ซึ่งจะถูกนำไปใช้ในหน้า Customer Detail ในภายหลัง
    </div>

    <!-- Customer Info -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0">
                <i class="fas fa-user"></i> ข้อมูลลูกค้า
            </h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3">
                    <strong>รหัสลูกค้า:</strong><br>
                    <span class="badge bg-primary"><?php echo htmlspecialchars($testCustomerCode); ?></span>
                </div>
                <div class="col-md-3">
                    <strong>ชื่อลูกค้า:</strong><br>
                    ลูกค้าทดสอบ
                </div>
                <div class="col-md-3">
                    <strong>เบอร์โทร:</strong><br>
                    <i class="fas fa-phone"></i> 081-234-5678
                </div>
                <div class="col-md-3">
                    <strong>สถานะ:</strong><br>
                    <span class="badge bg-success">ลูกค้าใหม่</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Call Statistics Dashboard -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0">
                <i class="fas fa-chart-bar"></i> สถิติการโทร
            </h5>
        </div>
        <div class="card-body">
            <div id="call-statistics-container">
                <div class="text-center py-3">
                    <i class="fas fa-spinner fa-spin"></i> กำลังโหลดสถิติการโทร...
                </div>
            </div>
        </div>
    </div>

    <!-- Call History Component Container -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0">
                <i class="fas fa-history"></i> ประวัติการโทร
            </h5>
        </div>
        <div class="card-body">
            <div id="call-history-container"></div>
        </div>
    </div>

    <!-- Back to Dashboard -->
    <div class="mt-4">
        <a href="dashboard.php" class="btn btn-outline-primary">
            <i class="fas fa-arrow-left"></i> กลับหน้าแดชบอร์ด
        </a>
    </div>

    <?php
    echo "<!-- DEBUG: Step 7 - ✅ Content buffer created -->\n";
    $content = ob_get_clean();
    echo "<!-- DEBUG: Step 7 - ✅ Content captured (" . strlen($content) . " chars) -->\n";

    echo "<!-- DEBUG: Step 8 - Creating CSS -->\n";
    $additionalCSS = '
        <style>
            .stat-card {
                background: white;
                border-radius: 8px;
                padding: 20px;
                border: 1px solid #e2e8f0;
                display: flex;
                align-items: center;
                gap: 16px;
                height: 100%;
            }
        </style>
    ';
    echo "<!-- DEBUG: Step 8 - ✅ CSS created (" . strlen($additionalCSS) . " chars) -->\n";

    echo "<!-- DEBUG: Step 9 - Creating JavaScript -->\n";
    $additionalJS = '
        <script>
            console.log("Call History Debug JS Loading...");
            
            class CallHistoryManager {
                constructor() {
                    this.customerCode = "' . $testCustomerCode . '";
                    this.currentCustomer = null;
                    this.callHistory = [];
                    console.log("CallHistoryManager initialized with customer:", this.customerCode);
                }
                
                init() {
                    console.log("CallHistoryManager init() called");
                }
            }
            
            document.addEventListener("DOMContentLoaded", function() {
                console.log("DOM loaded, creating CallHistoryManager...");
                window.callHistoryManager = new CallHistoryManager();
                window.callHistoryManager.init();
                console.log("CallHistoryManager created successfully");
            });
            
            console.log("Call History Debug JS loaded successfully");
        </script>
    ';
    echo "<!-- DEBUG: Step 9 - ✅ JavaScript created (" . strlen($additionalJS) . " chars) -->\n";

    echo "<!-- DEBUG: Step 10 - Calling renderMainLayout -->\n";
    
    // Try to render with error handling
    try {
        $result = renderMainLayout($pageTitle, $content, $additionalCSS, $additionalJS);
        echo "<!-- DEBUG: Step 10 - ✅ renderMainLayout succeeded -->\n";
        
        if ($result) {
            echo "<!-- DEBUG: Step 11 - Outputting result -->\n";
            echo $result;
            echo "<!-- DEBUG: Step 11 - ✅ Result output completed -->\n";
        } else {
            echo "<!-- DEBUG: Step 11 - ❌ renderMainLayout returned empty result -->\n";
            echo "<h1>Error: renderMainLayout returned empty result</h1>";
        }
        
    } catch (Exception $e) {
        echo "<!-- DEBUG: Step 10 - ❌ renderMainLayout failed -->\n";
        echo "<h1>Error in renderMainLayout: " . htmlspecialchars($e->getMessage()) . "</h1>";
        echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    }

} catch (Error $e) {
    echo "<!-- DEBUG: FATAL ERROR -->\n";
    echo "<h1>Fatal Error: " . htmlspecialchars($e->getMessage()) . "</h1>";
    echo "<p>File: " . htmlspecialchars($e->getFile()) . "</p>";
    echo "<p>Line: " . $e->getLine() . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
} catch (Exception $e) {
    echo "<!-- DEBUG: EXCEPTION -->\n";
    echo "<h1>Exception: " . htmlspecialchars($e->getMessage()) . "</h1>";
    echo "<p>File: " . htmlspecialchars($e->getFile()) . "</p>";
    echo "<p>Line: " . $e->getLine() . "</p>";
}

echo "<!-- DEBUG: Script completed at " . date('Y-m-d H:i:s') . " -->\n";
?>