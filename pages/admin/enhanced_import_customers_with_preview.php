<?php
/**
 * Enhanced Customer Import Page with CSV Preview
 * Support both Lead Import and First-Time Order Import with file preview
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

$pageTitle = "นำเข้าลูกค้า - Enhanced with Preview";

// Page content
ob_start();
?>

<!-- Page Header -->
<div class="page-header">
    <h1 class="page-title">
        <i class="fas fa-file-import"></i>
        นำเข้าลูกค้า (Enhanced System + Preview)
    </h1>
    <p class="page-description">
        ระบบนำเข้าลูกค้าแบบใหม่ - รองรับทั้งการเพิ่ม Lead และนำเข้าพร้อม Order แรก พร้อมฟีเจอร์ตรวจสอบไฟล์ก่อน Import | User: <?php echo htmlspecialchars($user_name); ?> (<?php echo htmlspecialchars($user_role); ?>)
    </p>
</div>

<!-- Import Type Selection -->
<div class="row">
    <div class="col-12 mb-4">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-list"></i> เลือกประเภทการนำเข้า</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="importType" id="leadImport" value="leads" checked>
                            <label class="form-check-label" for="leadImport">
                                <strong>🎯 Lead Import</strong><br>
                                <small class="text-muted">นำเข้ารายชื่อลูกค้าเพียงอย่างเดียว (ไม่มียอดขาย)</small>
                            </label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="importType" id="orderImport" value="orders">
                            <label class="form-check-label" for="orderImport">
                                <strong>💰 Order Import</strong><br>
                                <small class="text-muted">นำเข้าลูกค้า + ยอดขายครั้งแรก</small>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Import Form -->
<div class="row">
    <div class="col-lg-8 col-12 mb-4">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-upload"></i> นำเข้าข้อมูล</h5>
            </div>
            <div class="card-body">
                <form id="enhancedImportForm" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="csvFile" class="form-label">เลือกไฟล์ CSV</label>
                        <div class="border-2 border-dashed p-4 text-center bg-light rounded">
                            <i class="fas fa-cloud-upload-alt fa-3x text-muted mb-3"></i>
                            <p class="mb-2">คลิกเพื่อเลือกไฟล์ หรือลากและวางไฟล์ที่นี่</p>
                            <input type="file" class="form-control" id="csvFile" name="csv_file" accept=".csv" required>
                            <small class="text-muted">ไฟล์ต้องเป็นรูปแบบ CSV และมีขนาดไม่เกิน 10MB</small>
                        </div>
                    </div>
                    
                    <!-- CSV Preview Section -->
                    <div id="csvPreviewSection" class="mb-3" style="display: none;">
                        <h6><i class="fas fa-eye"></i> ตรวจสอบข้อมูลก่อนนำเข้า</h6>
                        <div id="csvPreview" class="border rounded p-3 bg-light" style="max-height: 400px; overflow: auto;">
                            <!-- Preview content will be inserted here -->
                        </div>
                        <div class="d-flex justify-content-between mt-2">
                            <small class="text-muted" id="previewStats">กำลังโหลด...</small>
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="hidePreview()">
                                <i class="fas fa-eye-slash"></i> ซ่อนตัวอย่าง
                            </button>
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
                    
                    <div class="d-grid gap-2 d-md-flex">
                        <button type="button" class="btn btn-outline-info" onclick="previewCSV()" id="previewBtn">
                            <i class="fas fa-eye"></i> ตรวจสอบไฟล์ก่อน
                        </button>
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
    
    <div class="col-lg-4 col-12 mb-4">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-info-circle"></i> รูปแบบไฟล์ CSV</h5>
            </div>
            <div class="card-body">
                <div id="leadFormat" class="csv-format">
                    <h6>🎯 Lead Import Format:</h6>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr class="table-light">
                                    <th>Column</th>
                                    <th>Required</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr><td>CustomerCode</td><td><span class="badge bg-secondary">Auto</span></td></tr>
                                <tr><td>CustomerName</td><td><span class="badge bg-danger">ใช่</span></td></tr>
                                <tr><td>CustomerTel</td><td><span class="badge bg-danger">ใช่</span></td></tr>
                                <tr><td>CustomerAddress</td><td><span class="badge bg-secondary">ไม่</span></td></tr>
                                <tr><td>CustomerProvince</td><td><span class="badge bg-secondary">ไม่</span></td></tr>
                                <tr><td>CustomerPostalCode</td><td><span class="badge bg-secondary">ไม่</span></td></tr>
                                <tr><td>Agriculture</td><td><span class="badge bg-secondary">ไม่</span></td></tr>
                                <tr><td>CustomerStatus</td><td><span class="badge bg-secondary">ไม่</span></td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <div id="orderFormat" class="csv-format" style="display: none;">
                    <h6>💰 Order Import Format:</h6>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr class="table-light">
                                    <th>Column</th>
                                    <th>Required</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr class="table-info"><td colspan="2"><strong>Customer Fields:</strong></td></tr>
                                <tr><td>CustomerCode</td><td><span class="badge bg-secondary">Auto</span></td></tr>
                                <tr><td>CustomerName</td><td><span class="badge bg-danger">ใช่</span></td></tr>
                                <tr><td>CustomerTel</td><td><span class="badge bg-danger">ใช่</span></td></tr>
                                <tr><td>CustomerAddress</td><td><span class="badge bg-secondary">ไม่</span></td></tr>
                                <tr><td>CustomerProvince</td><td><span class="badge bg-secondary">ไม่</span></td></tr>
                                <tr><td>CustomerPostalCode</td><td><span class="badge bg-secondary">ไม่</span></td></tr>
                                <tr><td>Agriculture</td><td><span class="badge bg-secondary">ไม่</span></td></tr>
                                <tr><td>CustomerStatus</td><td><span class="badge bg-secondary">ไม่</span></td></tr>
                                <tr class="table-warning"><td colspan="2"><strong>Order Fields:</strong></td></tr>
                                <tr><td>DocumentDate</td><td><span class="badge bg-secondary">ไม่</span></td></tr>
                                <tr><td>Products</td><td><span class="badge bg-danger">ใช่</span></td></tr>
                                <tr><td>Quantity</td><td><span class="badge bg-danger">ใช่</span></td></tr>
                                <tr><td>Price</td><td><span class="badge bg-danger">ใช่</span></td></tr>
                                <tr><td>DiscountAmount</td><td><span class="badge bg-secondary">ไม่</span></td></tr>
                                <tr><td>DiscountPercent</td><td><span class="badge bg-secondary">ไม่</span></td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <div class="alert alert-info mt-3">
                    <i class="fas fa-lightbulb"></i>
                    <strong>เคล็ดลับ:</strong> ใช้ "ตรวจสอบไฟล์ก่อน" เพื่อดูข้อมูล 5 บรรทัดแรก
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
                <div class="d-flex gap-2 flex-wrap">
                    <button class="btn btn-outline-primary" onclick="downloadLeadSample()">
                        <i class="fas fa-download"></i> ตัวอย่าง Lead Import (5 รายการ)
                    </button>
                    <button class="btn btn-outline-success" onclick="downloadOrderSample()">
                        <i class="fas fa-download"></i> ตัวอย่าง Order Import (5 รายการ)
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

$additionalJS = '
<script>
    let csvData = null;
    let csvHeaders = null;
    
    // Toggle CSV format display
    document.querySelectorAll("input[name=importType]").forEach(radio => {
        radio.addEventListener("change", function() {
            document.getElementById("leadFormat").style.display = this.value === "leads" ? "block" : "none";
            document.getElementById("orderFormat").style.display = this.value === "orders" ? "block" : "none";
            
            // Clear preview when changing import type
            hidePreview();
        });
    });
    
    // File input change handler
    document.getElementById("csvFile").addEventListener("change", function() {
        if (this.files[0]) {
            document.getElementById("previewBtn").style.display = "inline-block";
            hidePreview(); // Hide previous preview
        }
    });
    
    // Preview CSV function
    function previewCSV() {
        const fileInput = document.getElementById("csvFile");
        const file = fileInput.files[0];
        
        if (!file) {
            alert("กรุณาเลือกไฟล์ CSV ก่อน");
            return;
        }
        
        const previewBtn = document.getElementById("previewBtn");
        previewBtn.disabled = true;
        previewBtn.innerHTML = "<i class=\\"fas fa-spinner fa-spin\\"></i> กำลังโหลด...";
        
        const reader = new FileReader();
        reader.onload = function(e) {
            try {
                const csvText = e.target.result;
                const lines = csvText.split("\\n").filter(line => line.trim());
                
                if (lines.length === 0) {
                    throw new Error("ไฟล์ CSV ว่างเปล่า");
                }
                
                // Parse headers
                csvHeaders = parseCSVLine(lines[0]);
                
                // Parse first 5 data rows (skip header)
                csvData = [];
                for (let i = 1; i < Math.min(6, lines.length); i++) {
                    if (lines[i].trim()) {
                        csvData.push(parseCSVLine(lines[i]));
                    }
                }
                
                displayPreview();
                
            } catch (error) {
                alert("เกิดข้อผิดพลาดในการอ่านไฟล์: " + error.message);
            } finally {
                previewBtn.disabled = false;
                previewBtn.innerHTML = "<i class=\\"fas fa-eye\\"></i> ตรวจสอบไฟล์ก่อน";
            }
        };
        
        reader.readAsText(file, "UTF-8");
    }
    
    // Simple CSV parser
    function parseCSVLine(line) {
        const result = [];
        let current = "";
        let inQuotes = false;
        
        for (let i = 0; i < line.length; i++) {
            const char = line[i];
            
            if (char === \'"\') {
                inQuotes = !inQuotes;
            } else if (char === "," && !inQuotes) {
                result.push(current.trim());
                current = "";
            } else {
                current += char;
            }
        }
        
        result.push(current.trim());
        return result;
    }
    
    // Display preview
    function displayPreview() {
        const importType = document.querySelector("input[name=importType]:checked").value;
        
        let html = `<h6><i class="fas fa-table"></i> ตัวอย่างข้อมูล (${csvData.length} รายการแรก)</h6>`;
        html += `<div class="table-responsive">`;
        html += `<table class="table table-sm table-striped">`;
        
        // Headers
        html += `<thead class="table-dark"><tr>`;
        csvHeaders.forEach(header => {
            html += `<th>${header}</th>`;
        });
        html += `</tr></thead>`;
        
        // Data rows
        html += `<tbody>`;
        csvData.forEach((row, index) => {
            html += `<tr>`;
            for (let i = 0; i < csvHeaders.length; i++) {
                const value = row[i] || "";
                html += `<td>${value}</td>`;
            }
            html += `</tr>`;
        });
        html += `</tbody></table></div>`;
        
        // Validation summary
        html += validateCSVData(importType);
        
        document.getElementById("csvPreview").innerHTML = html;
        document.getElementById("csvPreviewSection").style.display = "block";
        document.getElementById("previewStats").textContent = 
            `พบ ${csvHeaders.length} คอลัมน์, ${csvData.length} รายการตัวอย่าง`;
    }
    
    // Validate CSV data
    function validateCSVData(importType) {
        const requiredFields = importType === "leads" 
            ? ["CustomerName", "CustomerTel"]
            : ["CustomerName", "CustomerTel", "Products", "Quantity", "Price"];
        
        let validationHtml = `<div class="mt-3">`;
        validationHtml += `<h6><i class="fas fa-check-circle"></i> การตรวจสอบความถูกต้อง</h6>`;
        
        // Check required fields
        let missingFields = [];
        requiredFields.forEach(field => {
            if (!csvHeaders.includes(field)) {
                missingFields.push(field);
            }
        });
        
        if (missingFields.length > 0) {
            validationHtml += `<div class="alert alert-danger">`;
            validationHtml += `<strong>❌ ขาดคอลัมน์ที่จำเป็น:</strong> ${missingFields.join(", ")}`;
            validationHtml += `</div>`;
        } else {
            validationHtml += `<div class="alert alert-success">`;
            validationHtml += `<strong>✅ คอลัมน์ที่จำเป็นครบถ้วน</strong>`;
            validationHtml += `</div>`;
        }
        
        // Check data quality
        let emptyRows = 0;
        csvData.forEach(row => {
            if (row.every(cell => !cell || cell.trim() === "")) {
                emptyRows++;
            }
        });
        
        if (emptyRows > 0) {
            validationHtml += `<div class="alert alert-warning">`;
            validationHtml += `<strong>⚠️ พบแถวว่าง:</strong> ${emptyRows} แถว (จะถูกข้ามในการ import)`;
            validationHtml += `</div>`;
        }
        
        validationHtml += `</div>`;
        return validationHtml;
    }
    
    // Hide preview
    function hidePreview() {
        document.getElementById("csvPreviewSection").style.display = "none";
        csvData = null;
        csvHeaders = null;
    }
    
    // Enhanced import form (same as before)
    document.getElementById("enhancedImportForm").addEventListener("submit", async function(e) {
        e.preventDefault();
        
        const fileInput = document.getElementById("csvFile");
        const importType = document.querySelector("input[name=importType]:checked").value;
        const updateExisting = document.getElementById("updateExisting").checked;
        const submitButton = document.querySelector("button[type=submit]");
        
        if (!fileInput.files[0]) {
            alert("กรุณาเลือกไฟล์ CSV");
            return;
        }
        
        // Show loading state
        submitButton.disabled = true;
        submitButton.innerHTML = "<i class=\\"fas fa-spinner fa-spin\\"></i> กำลังประมวลผล...";
        
        const file = fileInput.files[0];
        const importTypeText = importType === "leads" ? "Lead Import" : "Order Import";
        
        document.getElementById("resultsContainer").innerHTML = `
            <div class="alert alert-info">
                <h6><i class="fas fa-spinner fa-spin"></i> กำลังนำเข้าข้อมูล...</h6>
                <p class="mb-0">ไฟล์: ${file.name} (${(file.size / 1024).toFixed(2)} KB)</p>
                <p class="mb-0">ประเภท: ${importTypeText}</p>
                <p class="mb-0">อัปเดตข้อมูลเดิม: ${updateExisting ? "ใช่" : "ไม่"}</p>
            </div>
        `;
        document.getElementById("resultsContainer").style.display = "block";
        
        try {
            const formData = new FormData();
            formData.append("csv_file", file);
            formData.append("import_type", importType);
            formData.append("update_existing", updateExisting);
            
            const response = await fetch("../../enhanced_import_system.php", {
                method: "POST",
                body: formData
            });
            
            const data = await response.json();
            
            if (data.status === "success") {
                let resultHTML = `
                    <div class="alert alert-success">
                        <h6><i class="fas fa-check-circle"></i> ${data.message}</h6>
                        <div class="row text-center mt-3">
                `;
                
                if (data.type === "leads") {
                    resultHTML += `
                        <div class="col-4">
                            <strong class="text-success">${data.imported}</strong><br>
                            <small>เพิ่มใหม่</small>
                        </div>
                        <div class="col-4">
                            <strong class="text-primary">${data.updated}</strong><br>
                            <small>อัปเดต</small>
                        </div>
                        <div class="col-4">
                            <strong class="text-warning">${data.skipped}</strong><br>
                            <small>ข้าม</small>
                        </div>
                    `;
                } else {
                    resultHTML += `
                        <div class="col-3">
                            <strong class="text-success">${data.customers_imported}</strong><br>
                            <small>ลูกค้าใหม่</small>
                        </div>
                        <div class="col-3">
                            <strong class="text-primary">${data.customers_updated}</strong><br>
                            <small>ลูกค้าอัปเดต</small>
                        </div>
                        <div class="col-3">
                            <strong class="text-info">${data.orders_imported}</strong><br>
                            <small>Order ใหม่</small>
                        </div>
                        <div class="col-3">
                            <strong class="text-warning">${data.skipped}</strong><br>
                            <small>ข้าม</small>
                        </div>
                    `;
                }
                
                resultHTML += `
                        </div>
                        <div class="mt-3">
                            <a href="../dashboard.php" class="btn btn-primary btn-sm">
                                <i class="fas fa-eye"></i> ดูข้อมูลลูกค้า
                            </a>
                        </div>
                    </div>
                `;
                
                document.getElementById("resultsContainer").innerHTML = resultHTML;
            } else {
                throw new Error(data.error || "เกิดข้อผิดพลาดในการนำเข้าข้อมูล");
            }
            
        } catch (error) {
            console.error("Import error:", error);
            document.getElementById("resultsContainer").innerHTML = `
                <div class="alert alert-danger">
                    <h6><i class="fas fa-exclamation-triangle"></i> เกิดข้อผิดพลาด</h6>
                    <p class="mb-0">${error.message}</p>
                </div>
            `;
        } finally {
            submitButton.disabled = false;
            submitButton.innerHTML = "<i class=\\"fas fa-upload\\"></i> นำเข้าข้อมูล";
        }
    });
    
    // Download samples with 5 rows
    function downloadLeadSample() {
        const csvContent = "CustomerCode,CustomerName,CustomerTel,CustomerAddress,CustomerProvince,CustomerPostalCode,Agriculture,CustomerStatus\\n" +
                          "C202501001,บริษัท ABC จำกัด,081-234-5678,123 ถนนสุขุมวิท แขวงคลองเตย,กรุงเทพมหานคร,10110,ปลูกข้าว,ลูกค้าใหม่\\n" +
                          ",สวนผลไม้สมบูรณ์,082-987-6543,456 ถนนรัชดาภิเษก แขวงดินแดง,กรุงเทพมหานคร,10400,ปลูกผลไม้,ลูกค้าติดตาม\\n" +
                          ",ฟาร์มไก่อินทรีย์,083-555-1234,789 ถนนพหลโยธิน ตำบลลำลูกกา,ปทุมธานี,12150,เลี้ยงสัตว์,ลูกค้าเก่า\\n" +
                          ",ร้านเกษตรภัณฑ์,084-777-8888,321 ถนนเพชรบุรี แขวงมักกะสัน,กรุงเทพมหานคร,10400,จำหน่ายปุ๋ย,สนใจ\\n" +
                          ",วิสาหกิจชุมชน,085-999-0000,654 ถนนลาดพร้าว แขวงจตุจักร,กรุงเทพมหานคร,10900,แปรรูปอาหาร,คุยจบ";
        
        downloadCSV(csvContent, "lead_import_sample_5rows.csv");
    }
    
    function downloadOrderSample() {
        const csvContent = "CustomerCode,CustomerName,CustomerTel,CustomerAddress,CustomerProvince,CustomerPostalCode,Agriculture,CustomerStatus,DocumentDate,Products,Quantity,Price,DiscountAmount,DiscountPercent\\n" +
                          "C202501001,บริษัท ABC จำกัด,081-234-5678,123 ถนนสุขุมวิท,กรุงเทพมหานคร,10110,ปลูกข้าว,ลูกค้าใหม่,2025-01-01,ปุ๋ยเคมี 16-16-16,10,150.00,0.00,0.00\\n" +
                          ",สวนผลไม้สมบูรณ์,082-987-6543,456 ถนนรัชดาภิเษก,กรุงเทพมหานคร,10400,ปลูกผลไม้,ลูกค้าใหม่,2025-01-02,ยาฆ่าแมลง,5,200.00,50.00,5.00\\n" +
                          ",ฟาร์มไก่อินทรีย์,083-555-1234,789 ถนนพหลโยธิน,ปทุมธานี,12150,เลี้ยงสัตว์,ลูกค้าใหม่,2025-01-03,อาหารสัตว์,20,75.00,0.00,10.00\\n" +
                          ",ร้านเกษตรภัณฑ์,084-777-8888,321 ถนนเพชรบุรี,กรุงเทพมหานคร,10400,จำหน่ายปุ๋ย,ลูกค้าใหม่,2025-01-04,สารป้องกันศัตรูพืช,8,300.00,100.00,0.00\\n" +
                          ",วิสาหกิจชุมชน,085-999-0000,654 ถนนลาดพร้าว,กรุงเทพมหานคร,10900,แปรรูปอาหาร,ลูกค้าใหม่,2025-01-05,เครื่องมือการเกษตร,3,500.00,0.00,15.00";
        
        downloadCSV(csvContent, "order_import_sample_5rows.csv");
    }
    
    function downloadCSV(content, filename) {
        const BOM = "\\ufeff";
        const blob = new Blob([BOM + content], { type: "text/csv;charset=utf-8;" });
        const link = document.createElement("a");
        const url = URL.createObjectURL(blob);
        link.setAttribute("href", url);
        link.setAttribute("download", filename);
        link.style.visibility = "hidden";
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }
</script>
';

// Render the page
echo renderAdminLayout($pageTitle, $content, '', $additionalJS);
?>