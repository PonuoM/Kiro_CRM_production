<?php
/**
 * Enhanced Customer Import Page
 * Support both Lead Import and First-Time Order Import
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

$pageTitle = "นำเข้าลูกค้า - Enhanced";

// Page content
ob_start();
?>

<!-- Page Header -->
<div class="page-header">
    <h1 class="page-title">
        <i class="fas fa-file-import"></i>
        นำเข้าลูกค้า (Enhanced System)
    </h1>
    <p class="page-description">
        ระบบนำเข้าลูกค้าแบบใหม่ - รองรับทั้งการเพิ่ม Lead และนำเข้าพร้อม Order แรก | User: <?php echo htmlspecialchars($user_name); ?> (<?php echo htmlspecialchars($user_role); ?>)
    </p>
</div>

<!-- Fix SubtotalAmount First -->
<div class="row mb-4">
    <div class="col-12">
        <div class="alert alert-warning">
            <h6><i class="fas fa-tools"></i> ก่อนใช้งาน: แก้ไข SubtotalAmount Calculation</h6>
            <p class="mb-2">ระบบตรวจพบว่า SubtotalAmount ในตาราง orders ไม่ได้คำนวณถูกต้อง ต้องแก้ไขก่อนใช้งาน Import System</p>
            <button class="btn btn-warning" onclick="fixSubtotalCalculation()">
                <i class="fas fa-wrench"></i> แก้ไข SubtotalAmount และสร้าง Triggers
            </button>
            <div id="fixResults" class="mt-2" style="display: none;"></div>
        </div>
    </div>
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
                                <small class="text-muted">นำเข้ารายชื่อลูกค้าเพียวๆ (ไม่มียอดขาย)</small>
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
                    <strong>เคล็ดลับ:</strong> ระบบจะ Auto-generate CustomerCode และคำนวณ SubtotalAmount อัตโนมัติ
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
                        <i class="fas fa-download"></i> ตัวอย่าง Lead Import
                    </button>
                    <button class="btn btn-outline-success" onclick="downloadOrderSample()">
                        <i class="fas fa-download"></i> ตัวอย่าง Order Import
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
    // Toggle CSV format display
    document.querySelectorAll("input[name=importType]").forEach(radio => {
        radio.addEventListener("change", function() {
            document.getElementById("leadFormat").style.display = this.value === "leads" ? "block" : "none";
            document.getElementById("orderFormat").style.display = this.value === "orders" ? "block" : "none";
        });
    });
    
    // Fix SubtotalAmount calculation
    function fixSubtotalCalculation() {
        const fixBtn = document.querySelector("button[onclick=\\"fixSubtotalCalculation()\\"]");
        fixBtn.disabled = true;
        fixBtn.innerHTML = "<i class=\\"fas fa-spinner fa-spin\\"></i> กำลังแก้ไข...";
        
        document.getElementById("fixResults").innerHTML = `
            <div class="alert alert-info">
                <i class="fas fa-spinner fa-spin"></i> กำลังแก้ไข SubtotalAmount calculation และสร้าง triggers...
            </div>
        `;
        document.getElementById("fixResults").style.display = "block";
        
        fetch("../../fix_subtotal_calculation.php", {
            method: "POST"
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === "success") {
                document.getElementById("fixResults").innerHTML = `
                    <div class="alert alert-success">
                        <h6><i class="fas fa-check-circle"></i> แก้ไขสำเร็จ!</h6>
                        <ul class="mb-2">
                            <li>อัพเดต ${data.database_changes.updated_orders} orders</li>
                            <li>อัพเดต ${data.database_changes.updated_customers} customers</li>
                            <li>คำนวณ Grade ใหม่ ${data.database_changes.regraded_customers} คน</li>
                            <li>สร้าง ${data.database_changes.triggers_created} triggers</li>
                            <li>สร้าง ${data.database_changes.procedures_created} stored procedures</li>
                        </ul>
                        <small><strong>Formula:</strong> ${data.calculation_formula}</small>
                    </div>
                `;
                fixBtn.innerHTML = "<i class=\\"fas fa-check\\"></i> แก้ไขแล้ว";
                fixBtn.classList.remove("btn-warning");
                fixBtn.classList.add("btn-success");
            } else {
                document.getElementById("fixResults").innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i> เกิดข้อผิดพลาด: ${data.error}
                    </div>
                `;
                fixBtn.disabled = false;
                fixBtn.innerHTML = "<i class=\\"fas fa-wrench\\"></i> แก้ไข SubtotalAmount";
            }
        })
        .catch(error => {
            document.getElementById("fixResults").innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i> Network Error: ${error.message}
                </div>
            `;
            fixBtn.disabled = false;
            fixBtn.innerHTML = "<i class=\\"fas fa-wrench\\"></i> แก้ไข SubtotalAmount";
        });
    }
    
    // Enhanced import form
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
    
    // Download samples
    function downloadLeadSample() {
        const csvContent = "CustomerCode,CustomerName,CustomerTel,CustomerAddress,CustomerProvince,CustomerPostalCode,Agriculture,CustomerStatus\\n" +
                          "C202501001,บริษัท ABC จำกัด,081-234-5678,123 ถนนสุขุมวิท แขวงคลองเตย,กรุงเทพมหานคร,10110,ปลูกข้าว,ลูกค้าใหม่\\n" +
                          ",สวนผลไม้สมบูรณ์,082-987-6543,456 ถนนรัชดาภิเษก แขวงดินแดง,กรุงเทพมหานคร,10400,ปลูกผลไม้,ลูกค้าติดตาม\\n" +
                          ",ฟาร์มไก่อินทรีย์,083-555-1234,789 ถนนพหลโยธิน ตำบลลำลูกกา,ปทุมธานี,12150,เลี้ยงสัตว์,ลูกค้าเก่า\\n" +
                          ",ร้านเกษตรภัณฑ์,084-777-8888,321 ถนนเพชรบุรี แขวงมักกะสัน,กรุงเทพมหานคร,10400,จำหน่ายปุ๋ย,สนใจ\\n" +
                          ",วิสาหกิจชุมชน,085-999-0000,654 ถนนลาดพร้าว แขวงจตุจักร,กรุงเทพมหานคร,10900,แปรรูปอาหาร,คุยจบ";
        
        downloadCSV(csvContent, "lead_import_sample.csv");
    }
    
    function downloadOrderSample() {
        const csvContent = "CustomerCode,CustomerName,CustomerTel,CustomerAddress,CustomerProvince,CustomerPostalCode,Agriculture,CustomerStatus,DocumentDate,Products,Quantity,Price,DiscountAmount,DiscountPercent\\n" +
                          "C202501001,บริษัท ABC จำกัด,081-234-5678,123 ถนนสุขุมวิท,กรุงเทพมหานคร,10110,ปลูกข้าว,ลูกค้าใหม่,2025-01-01,ปุ๋ยเคมี 16-16-16,10,150.00,0.00,0.00\\n" +
                          ",สวนผลไม้สมบูรณ์,082-987-6543,456 ถนนรัชดาภิเษก,กรุงเทพมหานคร,10400,ปลูกผลไม้,ลูกค้าใหม่,2025-01-02,ยาฆ่าแมลง,5,200.00,50.00,5.00\\n" +
                          ",ฟาร์มไก่อินทรีย์,083-555-1234,789 ถนนพหลโยธิน,ปทุมธานี,12150,เลี้ยงสัตว์,ลูกค้าใหม่,2025-01-03,อาหารสัตว์,20,75.00,0.00,10.00\\n" +
                          ",ร้านเกษตรภัณฑ์,084-777-8888,321 ถนนเพชรบุรี,กรุงเทพมหานคร,10400,จำหน่ายปุ๋ย,ลูกค้าใหม่,2025-01-04,สารป้องกันศัตรูพืช,8,300.00,100.00,0.00\\n" +
                          ",วิสาหกิจชุมชน,085-999-0000,654 ถนนลาดพร้าว,กรุงเทพมหานคร,10900,แปรรูปอาหาร,ลูกค้าใหม่,2025-01-05,เครื่องมือการเกษตร,3,500.00,0.00,15.00";
        
        downloadCSV(csvContent, "order_import_sample.csv");
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