<?php
/**
 * Customer CSV Import Page - Simplified Version
 * Admin interface for importing customer data from CSV
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
        นำเข้าข้อมูลลูกค้าจากไฟล์ CSV - รองรับการอัปเดตข้อมูลลูกค้าที่มีอยู่แล้ว | User: <?php echo htmlspecialchars($user_name); ?> (<?php echo htmlspecialchars($user_role); ?>)
    </p>
</div>

<!-- User info (removed alert as requested) -->

<!-- Import Form -->
<div class="row">
    <div class="col-lg-6 col-12 mb-4">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-upload"></i> นำเข้าข้อมูลลูกค้าจาก CSV</h5>
            </div>
            <div class="card-body">
                <form id="importForm" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="csvFile" class="form-label">เลือกไฟล์ CSV</label>
                        <div class="border-2 border-dashed p-4 text-center bg-light rounded">
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
                
                <!-- Results -->
                <div id="resultsContainer" class="mt-3" style="display: none;"></div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-6 col-12 mb-4">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-info-circle"></i> รูปแบบไฟล์ CSV</h5>
            </div>
            <div class="card-body">
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
                            <tr>
                                <td>F</td>
                                <td>customer_province</td>
                                <td>กรุงเทพมหานคร</td>
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
</div>

<!-- Download Sample -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-download"></i> ดาวน์โหลดไฟล์ตัวอย่าง</h5>
            </div>
            <div class="card-body">
                <p>หากคุณยังไม่เคยนำเข้าข้อมูล หรือต้องการไฟล์ตัวอย่าง คุณสามารถดาวน์โหลดไฟล์ CSV ตัวอย่างได้</p>
                <div class="d-flex gap-2">
                    <button class="btn btn-outline-primary" onclick="downloadSampleCSV()">
                        <i class="fas fa-download"></i> ดาวน์โหลดไฟล์ตัวอย่าง
                    </button>
                    <a href="../dashboard.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> กลับหน้าแดชบอร์ด
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();

// Enhanced JavaScript with real API integration
$additionalJS = '
<script>
    document.getElementById("importForm").addEventListener("submit", async function(e) {
        e.preventDefault();
        
        const fileInput = document.getElementById("csvFile");
        const updateExisting = document.getElementById("updateExisting").checked;
        const submitButton = document.querySelector("button[type=submit]");
        
        if (!fileInput.files[0]) {
            alert("กรุณาเลือกไฟล์ CSV");
            return;
        }
        
        // Validate file type
        const file = fileInput.files[0];
        if (!file.name.toLowerCase().endsWith(".csv")) {
            alert("กรุณาเลือกไฟล์ CSV เท่านั้น");
            return;
        }
        
        // Show loading state
        submitButton.disabled = true;
        submitButton.innerHTML = "<i class=\"fas fa-spinner fa-spin\"></i> กำลังประมวลผล...";
        
        document.getElementById("resultsContainer").innerHTML = `
            <div class="alert alert-info">
                <h6><i class="fas fa-spinner fa-spin"></i> กำลังนำเข้าข้อมูล...</h6>
                <p class="mb-0">ไฟล์: ${file.name} (${(file.size / 1024).toFixed(2)} KB)</p>
                <p class="mb-0">อัปเดตข้อมูลเดิม: ${updateExisting ? "ใช่" : "ไม่"}</p>
                <small class="text-muted">กรุณารอสักครู่ ระบบกำลังประมวลผลข้อมูล...</small>
            </div>
        `;
        document.getElementById("resultsContainer").style.display = "block";
        
        try {
            // Create FormData
            const formData = new FormData();
            formData.append("csv_file", file);
            formData.append("update_existing", updateExisting);
            
            // Send to API
            const response = await fetch("../../api/customers/import.php", {
                method: "POST",
                body: formData
            });
            
            const data = await response.json();
            
            if (data.status === "success") {
                // Show success results
                let errorDetails = "";
                if (data.results.errors > 0) {
                    errorDetails = `
                        <div class="mt-2">
                            <strong>รายการที่มีปัญหา:</strong>
                            <ul class="small mb-0">
                                ${data.results.error_details.slice(0, 5).map(error => `<li>${error}</li>`).join("")}
                                ${data.results.error_details.length > 5 ? `<li>และอีก ${data.results.error_details.length - 5} รายการ...</li>` : ""}
                            </ul>
                        </div>
                    `;
                }
                
                document.getElementById("resultsContainer").innerHTML = `
                    <div class="alert alert-success">
                        <h6><i class="fas fa-check-circle"></i> นำเข้าข้อมูลสำเร็จ!</h6>
                        <div class="row text-center mt-3">
                            <div class="col-3">
                                <strong class="text-success">${data.results.imported}</strong><br>
                                <small>เพิ่มใหม่</small>
                            </div>
                            <div class="col-3">
                                <strong class="text-primary">${data.results.updated}</strong><br>
                                <small>อัปเดต</small>
                            </div>
                            <div class="col-3">
                                <strong class="text-warning">${data.results.errors}</strong><br>
                                <small>ข้อผิดพลาด</small>
                            </div>
                            <div class="col-3">
                                <strong class="text-info">${data.results.total_processed}</strong><br>
                                <small>ทั้งหมด</small>
                            </div>
                        </div>
                        ${errorDetails}
                        <div class="mt-3">
                            <a href="../dashboard.php" class="btn btn-primary btn-sm">
                                <i class="fas fa-eye"></i> ดูข้อมูลลูกค้า
                            </a>
                        </div>
                    </div>
                `;
            } else {
                throw new Error(data.message || "เกิดข้อผิดพลาดในการนำเข้าข้อมูล");
            }
            
        } catch (error) {
            console.error("Import error:", error);
            document.getElementById("resultsContainer").innerHTML = `
                <div class="alert alert-danger">
                    <h6><i class="fas fa-exclamation-triangle"></i> เกิดข้อผิดพลาด</h6>
                    <p class="mb-0">${error.message}</p>
                    <small class="text-muted">กรุณาตรวจสอบไฟล์ CSV และลองใหม่อีกครั้ง</small>
                </div>
            `;
        } finally {
            // Reset button state
            submitButton.disabled = false;
            submitButton.innerHTML = "<i class=\"fas fa-upload\"></i> นำเข้าข้อมูล";
        }
    });
    
    // Download sample CSV file with Thai language support
    function downloadSampleCSV() {
        const csvContent = "customer_name,customer_tel,customer_email,customer_address,customer_status,customer_province\\n" +
                          "สมชาย ใจดี,081-234-5678,somchai@email.com,123 ถนนสุขุมวิท แขวงคลองเตย,ลูกค้าใหม่,กรุงเทพมหานคร\\n" +
                          "สมหญิง รักดี,081-987-6543,somying@email.com,456 ถนนรัชดาภิเษก แขวงดินแดง,ลูกค้าติดตาม,กรุงเทพมหานคร\\n" +
                          "สมศักดิ์ ดีใจ,081-555-1234,somsak@email.com,789 ถนนพหลโยธิน ตำบลลำลูกกา,ลูกค้าเก่า,ปทุมธานี\\n" +
                          "วิไล สุขใจ,082-111-2222,wilai@email.com,321 ถนนเพชรบุรี แขวงมักกะสัน,สนใจ,กรุงเทพมหานคร\\n" +
                          "ประชา ดีดี,083-333-4444,pracha@email.com,654 ถนนลาดพร้าว แขวงจตุจักร,คุยจบ,กรุงเทพมหานคร";
        
        // Use UTF-8 BOM for better Thai language support in Excel
        const BOM = "\\ufeff";
        const blob = new Blob([BOM + csvContent], { type: "text/csv;charset=utf-8;" });
        const link = document.createElement("a");
        const url = URL.createObjectURL(blob);
        link.setAttribute("href", url);
        link.setAttribute("download", "customer_import_sample_thai.csv");
        link.style.visibility = "hidden";
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        
        // Show helpful tip
        setTimeout(() => {
            alert("📝 เคล็ดลับ: หากเปิดไฟล์ CSV ใน Excel แล้วตัวอักษรไทยแสดงผิด ให้เปิดแบบ Data > From Text และเลือก UTF-8 encoding");
        }, 500);
    }
    
    console.log("Import Customers loaded successfully!");
</script>
';

// Render the page
echo renderAdminLayout($pageTitle, $content, '', $additionalJS);
?>