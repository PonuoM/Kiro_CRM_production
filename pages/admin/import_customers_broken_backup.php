<?php
/**
 * Customer CSV Import Page
 * Admin/Supervisor interface for importing customer data from CSV
 */

require_once '../../includes/permissions.php';
require_once '../../includes/admin_layout.php';

// Check login and permission
Permissions::requireLogin();
Permissions::requirePermission('import_customers');

// Get user information for layout
$user_name = Permissions::getCurrentUser();
$user_role = Permissions::getCurrentRole();
$menuItems = Permissions::getMenuItems();

// Set globals for admin_layout
$GLOBALS['currentUser'] = $user_name;
$GLOBALS['currentRole'] = $user_role;
$GLOBALS['menuItems'] = $menuItems;

$pageTitle = "นำเข้าลูกค้า";

// Additional CSS for this page
$additionalCSS = '
    <style>
        .import-card {
            background: #ffffff;
            border-radius: 0.75rem;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            border: 1px solid #e5e7eb;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
        }
        
        .import-card h5 {
            color: #374151;
            font-weight: 600;
            margin-bottom: 1rem;
        }
        
        .file-upload-area {
            border: 2px dashed #e5e7eb;
            border-radius: 0.5rem;
            padding: 2rem;
            text-align: center;
            background: #f9fafb;
            transition: all 0.2s ease;
        }
        
        .file-upload-area:hover {
            border-color: #3b82f6;
            background: rgba(59, 130, 246, 0.05);
        }
        
        .progress-bar {
            background-color: #3b82f6;
        }
        
        .alert {
            border-radius: 0.5rem;
        }
    </style>
';

// Page content
ob_start();
?>

<!-- Page Header -->
<div class="page-header">
    <h1 class="page-title">
        <i class="fas fa-file-import"></i>
        นำเข้าลูกค้า
    </h1>
    <p class="page-description">
        นำเข้าข้อมูลลูกค้าจากไฟล์ CSV - รองรับการอัปเดตข้อมูลลูกค้าที่มีอยู่แล้ว
    </p>
</div>

<!-- Import Form -->
<div class="row">
    <div class="col-lg-6 col-12 mb-4">
        <div class="import-card">
            <h5><i class="fas fa-upload"></i> นำเข้าข้อมูลลูกค้าจาก CSV</h5>
            
            <form id="importForm" enctype="multipart/form-data">
                <div class="mb-3">
                    <label for="csvFile" class="form-label">เลือกไฟล์ CSV</label>
                    <div class="file-upload-area">
                        <i class="fas fa-cloud-upload-alt fa-3x text-muted mb-3"></i>
                        <p class="mb-2">คลิกเพื่อเลือกไฟล์ หรือลากและวางไฟล์ที่นี่</p>
                        <input type="file" class="form-control" id="csvFile" name="csv_file" accept=".csv" required>
                        <small class="text-muted">ไฟล์ต้องเป็นรูปแบบ CSV และมีขนาดไม่เกิน 10MB</small>
                    </div>
                </div>
                
                <div class="mb-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="updateExisting" name="update_existing" checked>
                        <label class="form-check-label" for="updateExisting">
                            อัปเดตข้อมูลลูกค้าที่มีอยู่แล้ว
                        </label>
                    </div>
                </div>
                
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-upload"></i> นำเข้าข้อมูล
                    </button>
                </div>
            </form>
            
            <!-- Progress Bar -->
            <div id="progressContainer" class="mt-3" style="display: none;">
                <div class="progress">
                    <div id="progressBar" class="progress-bar" role="progressbar" style="width: 0%"></div>
                </div>
                <div class="text-center mt-2">
                    <span id="progressText">กำลังประมวลผล...</span>
                </div>
            </div>
            
            <!-- Results -->
            <div id="resultsContainer" class="mt-3" style="display: none;"></div>
        </div>
    </div>
    
    <div class="col-lg-6 col-12 mb-4">
        <div class="import-card">
            <h5><i class="fas fa-info-circle"></i> รูปแบบไฟล์ CSV</h5>
            
            <p>ไฟล์ CSV ควรมีคอลัมน์ดังต่อไปนี้:</p>
            
            <div class="table-responsive">
                <table class="table table-sm">
                    <thead>
                        <tr class="table-light">
                            <th>คอลัมน์</th>
                            <th>ชื่อฟิลด์</th>
                            <th>ตัวอย่าง</th>
                            <th>จำเป็น</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>A</td>
                            <td>customer_name</td>
                            <td>สมชาย ใจดี</td>
                            <td><span class="badge bg-danger">ใช่</span></td>
                        </tr>
                        <tr>
                            <td>B</td>
                            <td>customer_tel</td>
                            <td>081-234-5678</td>
                            <td><span class="badge bg-danger">ใช่</span></td>
                        </tr>
                        <tr>
                            <td>C</td>
                            <td>customer_email</td>
                            <td>somchai@email.com</td>
                            <td><span class="badge bg-secondary">ไม่</span></td>
                        </tr>
                        <tr>
                            <td>D</td>
                            <td>customer_address</td>
                            <td>123 ถนนสุขุมวิท กรุงเทพฯ</td>
                            <td><span class="badge bg-secondary">ไม่</span></td>
                        </tr>
                        <tr>
                            <td>E</td>
                            <td>customer_status</td>
                            <td>ลูกค้าใหม่</td>
                            <td><span class="badge bg-secondary">ไม่</span></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <div class="alert alert-info">
                <i class="fas fa-lightbulb"></i>
                <strong>เคล็ดลับ:</strong> หากไม่ระบุข้อมูลในคอลัมน์ที่ไม่จำเป็น ระบบจะใส่ค่าเริ่มต้นให้อัตโนมัติ
            </div>
        </div>
    </div>
</div>

<!-- Import History or Additional Information -->
<div class="row">
    <div class="col-12">
        <div class="import-card">
            <h5><i class="fas fa-download"></i> ดาวน์โหลดไฟล์ตัวอย่าง</h5>
            <p>หากคุณยังไม่เคยนำเข้าข้อมูล หรือต้องการไฟล์ตัวอย่าง คุณสามารถดาวน์โหลดไฟล์ CSV ตัวอย่างได้</p>
            <div class="d-flex gap-2">
                <button class="btn btn-outline-primary" onclick="downloadSampleCSV()">
                    <i class="fas fa-download"></i> ดาวน์โหลดไฟล์ตัวอย่าง
                </button>
                <button class="btn btn-outline-secondary" onclick="viewImportHistory()" id="viewHistoryBtn">
                    <i class="fas fa-history"></i> ดูประวัติการนำเข้า
                </button>
            </div>
            
            <!-- Import History Table -->
            <div id="importHistoryContainer" class="mt-4" style="display: none;">
                <h6><i class="fas fa-history"></i> ประวัติการนำเข้าล่าสุด</h6>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead class="table-light">
                            <tr>
                                <th>วันที่</th>
                                <th>ผู้นำเข้า</th>
                                <th>นำเข้าใหม่</th>
                                <th>อัปเดต</th>
                                <th>ข้าม</th>
                                <th>สถานะ</th>
                            </tr>
                        </thead>
                        <tbody id="historyTableBody">
                            <tr>
                                <td colspan="6" class="text-center text-muted py-3">
                                    <i class="fas fa-spinner fa-spin"></i> กำลังโหลดประวัติ...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();

// Additional JavaScript
$additionalJS = '
    <script>
        // Use relative API path
        const apiPath = "../../api/";
        
        document.getElementById("importForm").addEventListener("submit", function(e) {
            e.preventDefault();
            
            const fileInput = document.getElementById("csvFile");
            const updateExisting = document.getElementById("updateExisting").checked;
            
            if (!fileInput.files[0]) {
                alert("กรุณาเลือกไฟล์ CSV");
                return;
            }
            
            const formData = new FormData();
            formData.append("csv_file", fileInput.files[0]);
            formData.append("update_existing", updateExisting ? "1" : "0");
            
            // Show progress
            document.getElementById("progressContainer").style.display = "block";
            document.getElementById("resultsContainer").style.display = "none";
            
            fetch(apiPath + "customers/import.php", {
                method: "POST",
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                document.getElementById("progressContainer").style.display = "none";
                displayResults(data);
            })
            .catch(error => {
                document.getElementById("progressContainer").style.display = "none";
                console.error("Error:", error);
                alert("เกิดข้อผิดพลาดในการนำเข้าข้อมูล");
            });
        });
        
        function displayResults(data) {
            const container = document.getElementById("resultsContainer");
            
            if (data.status === "success") {
                container.innerHTML = `
                    <div class="alert alert-success">
                        <h6><i class="fas fa-check-circle"></i> นำเข้าข้อมูลสำเร็จ</h6>
                        <p class="mb-1">นำเข้าลูกค้าใหม่: ${data.data.imported} รายการ</p>
                        <p class="mb-1">อัปเดตลูกค้าเดิม: ${data.data.updated} รายการ</p>
                        <p class="mb-0">ข้ามรายการที่มีปัญหา: ${data.data.skipped} รายการ</p>
                    </div>
                `;
                
                if (data.data.errors && data.data.errors.length > 0) {
                    container.innerHTML += `
                        <div class="alert alert-warning">
                            <h6><i class="fas fa-exclamation-triangle"></i> รายการที่มีปัญหา</h6>
                            <ul class="mb-0">
                                ${data.data.errors.map(error => `<li>${error}</li>`).join("")}
                            </ul>
                        </div>
                    `;
                }
            } else {
                container.innerHTML = `
                    <div class="alert alert-danger">
                        <h6><i class="fas fa-times-circle"></i> เกิดข้อผิดพลาด</h6>
                        <p class="mb-0">${data.error}</p>
                    </div>
                `;
            }
            
            container.style.display = "block";
        }
        
        // Download sample CSV file
        function downloadSampleCSV() {
            const csvContent = "customer_name,customer_tel,customer_email,customer_address,customer_status\n" +
                              "สมชาย ใจดี,081-234-5678,somchai@email.com,123 ถนนสุขุมวิท กรุงเทพฯ,ลูกค้าใหม่\n" +
                              "สมหญิง รักดี,081-987-6543,somying@email.com,456 ถนนรัชดาภิเษก กรุงเทพฯ,ลูกค้าติดตาม\n" +
                              "สมศักดิ์ ดีใจ,081-555-1234,somsak@email.com,789 ถนนพหลโยธิน กรุงเทพฯ,ลูกค้าเก่า";
            
            const blob = new Blob([csvContent], { type: "text/csv;charset=utf-8;" });
            const link = document.createElement("a");
            const url = URL.createObjectURL(blob);
            link.setAttribute("href", url);
            link.setAttribute("download", "customer_import_sample.csv");
            link.style.visibility = "hidden";
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
        
        // Toggle import history view
        function viewImportHistory() {
            const container = document.getElementById("importHistoryContainer");
            const btn = document.getElementById("viewHistoryBtn");
            
            if (container.style.display === "none") {
                container.style.display = "block";
                btn.innerHTML = '<i class="fas fa-eye-slash"></i> ซ่อนประวัติการนำเข้า';
                loadImportHistory();
            } else {
                container.style.display = "none";
                btn.innerHTML = '<i class="fas fa-history"></i> ดูประวัติการนำเข้า';
            }
        }
        
        // Load import history (simulated data)
        function loadImportHistory() {
            const tbody = document.getElementById("historyTableBody");
            
            // Simulate loading delay
            setTimeout(() => {
                tbody.innerHTML = `
                    <tr>
                        <td>25/01/2024 14:30</td>
                        <td>admin</td>
                        <td><span class="badge bg-success">25</span></td>
                        <td><span class="badge bg-warning">5</span></td>
                        <td><span class="badge bg-secondary">2</span></td>
                        <td><span class="badge bg-success">สำเร็จ</span></td>
                    </tr>
                    <tr>
                        <td>24/01/2024 09:15</td>
                        <td>manager</td>
                        <td><span class="badge bg-success">18</span></td>
                        <td><span class="badge bg-warning">3</span></td>
                        <td><span class="badge bg-secondary">0</span></td>
                        <td><span class="badge bg-success">สำเร็จ</span></td>
                    </tr>
                    <tr>
                        <td>23/01/2024 16:45</td>
                        <td>admin</td>
                        <td><span class="badge bg-success">12</span></td>
                        <td><span class="badge bg-warning">8</span></td>
                        <td><span class="badge bg-secondary">1</span></td>
                        <td><span class="badge bg-success">สำเร็จ</span></td>
                    </tr>
                `;
            }, 500);
        }
    </script>
';

// Render the page
echo renderAdminLayout($pageTitle, $content, $additionalCSS, $additionalJS);
?>