<?php
/**
 * Supervisor Dashboard - Simplified Version
 * Team performance monitoring and management
 */

require_once '../../includes/permissions.php';
require_once '../../includes/admin_layout.php';

// Check login and supervisor dashboard permission
Permissions::requireLogin();
Permissions::requirePermission('supervisor_dashboard');

// Get user information for layout
$user_name = Permissions::getCurrentUser();
$user_role = Permissions::getCurrentRole();
$menuItems = Permissions::getMenuItems();

// Set globals for admin_layout
$GLOBALS['currentUser'] = $user_name;
$GLOBALS['currentRole'] = $user_role;
$GLOBALS['menuItems'] = $menuItems;

$pageTitle = "แดชบอร์ดผู้ดูแล";

// Page content
ob_start();
?>

<!-- Page Header -->
<div class="page-header">
    <h1 class="page-title">
        <i class="fas fa-tachometer-alt"></i> Supervisor Dashboard
    </h1>
    <p class="page-description">
        แดชบอร์ดการจัดการทีมและติดตามประสิทธิภาพ | User: <?php echo htmlspecialchars($user_name); ?> (<?php echo htmlspecialchars($user_role); ?>)
    </p>
</div>

<!-- Removed success alert as requested -->

<!-- Key Metrics Row -->
<div class="row">
    <div class="col-md-3">
        <div class="card shadow-sm" style="background: white; border: 1px solid #e5e7eb;">
            <div class="card-body text-center">
                <div class="d-flex align-items-center justify-content-center mb-2">
                    <i class="fas fa-users fa-2x text-primary me-3"></i>
                    <h3 class="mb-0 text-dark" id="totalCustomers">0</h3>
                </div>
                <p class="mb-0 text-muted">ลูกค้าทั้งหมด</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm" style="background: white; border: 1px solid #e5e7eb;">
            <div class="card-body text-center">
                <div class="d-flex align-items-center justify-content-center mb-2">
                    <i class="fas fa-user-tie fa-2x text-success me-3"></i>
                    <h3 class="mb-0 text-dark" id="teamMembers">0</h3>
                </div>
                <p class="mb-0 text-muted">พนักงานขายในทีม</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm" style="background: white; border: 1px solid #e5e7eb;">
            <div class="card-body text-center">
                <div class="d-flex align-items-center justify-content-center mb-2">
                    <i class="fas fa-exclamation-triangle fa-2x text-warning me-3"></i>
                    <h3 class="mb-0 text-dark" id="unassignedCustomers">0</h3>
                </div>
                <p class="mb-0 text-muted">ลูกค้าที่ยังไม่ได้แจก</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm" style="background: white; border: 1px solid #e5e7eb;">
            <div class="card-body text-center">
                <div class="d-flex align-items-center justify-content-center mb-2">
                    <i class="fas fa-fire fa-2x text-danger me-3"></i>
                    <h3 class="mb-0 text-dark" id="hotCustomers">0</h3>
                </div>
                <p class="mb-0 text-muted">ลูกค้า HOT</p>
            </div>
        </div>
    </div>
</div>

<!-- Team Performance Table -->
<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-users"></i> ประสิทธิภาพทีมขาย</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>พนักงานขาย</th>
                                <th>ลูกค้าที่รับผิดชอบ</th>
                                <th>ลูกค้า Grade A</th>
                                <th>ลูกค้า HOT</th>
                                <th>ยอดขายรวม</th>
                                <th>ประสิทธิภาพ</th>
                            </tr>
                        </thead>
                        <tbody id="teamPerformanceTable">
                            <tr>
                                <td colspan="6" class="text-center">
                                    <i class="fas fa-spinner fa-spin"></i> กำลังโหลดข้อมูล...
                                </td>
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
        <a href="../dashboard.php" class="btn btn-outline-primary">
            <i class="fas fa-arrow-left"></i> กลับหน้าแดชบอร์ด
        </a>
    </div>
</div>

<?php
$content = ob_get_clean();

// Real data integration JavaScript
$additionalJS = '
<script>
document.addEventListener("DOMContentLoaded", function() {
    console.log("Supervisor Dashboard loading...");
    loadDashboardData();
    loadTeamPerformance();
});

async function loadDashboardData() {
    try {
        // Load metrics from multiple sources
        const [customersResponse, usersResponse, tempResponse] = await Promise.all([
            fetch("../../api/customers/list.php"),
            fetch("../../api/dashboard/stats.php?action=team"),
            fetch("../../api/customers/intelligence.php?action=temperatures")
        ]);
        
        const customersData = await customersResponse.json();
        const usersData = await usersResponse.json();
        const tempData = await tempResponse.json();
        
        // Update metrics
        if (customersData.status === "success") {
            document.getElementById("totalCustomers").textContent = customersData.total || 0;
            
            // Count unassigned customers (without Sales assignment)
            const unassigned = customersData.data ? customersData.data.filter(c => !c.Sales || c.Sales === "").length : 0;
            document.getElementById("unassignedCustomers").textContent = unassigned;
        }
        
        // Count team members (Sales role)
        let teamCount = 0;
        if (usersData.status === "success" && usersData.data) {
            teamCount = usersData.data.filter(u => u.Role === "Sales").length;
        } else {
            // Fallback: count distinct sales people from customers
            if (customersData.status === "success" && customersData.data) {
                const salesSet = new Set();
                customersData.data.forEach(c => {
                    if (c.Sales && c.Sales !== "") salesSet.add(c.Sales);
                });
                teamCount = salesSet.size;
            }
        }
        document.getElementById("teamMembers").textContent = teamCount;
        
        // Count HOT customers
        let hotCount = 0;
        if (tempData.status === "success" && tempData.data) {
            const hotData = tempData.data.find(t => t.CustomerTemperature === "HOT");
            hotCount = hotData ? hotData.count : 0;
        }
        document.getElementById("hotCustomers").textContent = hotCount;
        
    } catch (error) {
        console.error("Error loading dashboard data:", error);
        // Set fallback values
        document.getElementById("totalCustomers").textContent = "-";
        document.getElementById("teamMembers").textContent = "-";
        document.getElementById("unassignedCustomers").textContent = "-";
        document.getElementById("hotCustomers").textContent = "-";
    }
}

async function loadTeamPerformance() {
    try {
        const response = await fetch("../../api/customers/list.php");
        const data = await response.json();
        
        if (data.status === "success" && data.data) {
            const teamStats = calculateTeamStats(data.data);
            displayTeamPerformance(teamStats);
        } else {
            throw new Error("Failed to load customer data");
        }
        
    } catch (error) {
        console.error("Error loading team performance:", error);
        document.getElementById("teamPerformanceTable").innerHTML = `
            <tr>
                <td colspan="6" class="text-center text-danger">
                    <i class="fas fa-exclamation-triangle"></i> เกิดข้อผิดพลาดในการโหลดข้อมูลทีม
                </td>
            </tr>
        `;
    }
}

function calculateTeamStats(customers) {
    const teamStats = {};
    
    customers.forEach(customer => {
        const sales = customer.Sales || "ไม่ได้กำหนด";
        
        if (!teamStats[sales]) {
            teamStats[sales] = {
                name: sales,
                totalCustomers: 0,
                gradeA: 0,
                hotCustomers: 0,
                totalSales: 0
            };
        }
        
        teamStats[sales].totalCustomers++;
        
        if (customer.CustomerGrade === "A") {
            teamStats[sales].gradeA++;
        }
        
        if (customer.CustomerTemperature === "HOT") {
            teamStats[sales].hotCustomers++;
        }
        
        if (customer.TotalPurchase) {
            teamStats[sales].totalSales += parseFloat(customer.TotalPurchase || 0);
        }
    });
    
    return Object.values(teamStats).filter(stat => stat.name !== "ไม่ได้กำหนด");
}

function displayTeamPerformance(teamStats) {
    const tableBody = document.getElementById("teamPerformanceTable");
    
    if (teamStats.length === 0) {
        tableBody.innerHTML = `
            <tr>
                <td colspan="6" class="text-center text-muted">
                    <i class="fas fa-info-circle"></i> ไม่พบข้อมูลทีมขาย
                </td>
            </tr>
        `;
        return;
    }
    
    let tableHTML = "";
    teamStats.forEach(stat => {
        const performance = getPerformanceRating(stat.totalSales, stat.gradeA, stat.hotCustomers);
        const performanceBadge = getPerformanceBadge(performance);
        
        tableHTML += `
            <tr>
                <td><strong>${stat.name}</strong><br><small class="text-muted">@${stat.name.toLowerCase()}</small></td>
                <td><span class="badge bg-primary">${stat.totalCustomers}</span></td>
                <td><span class="badge bg-success">${stat.gradeA}</span></td>
                <td><span class="badge bg-danger">${stat.hotCustomers}</span></td>
                <td>฿${stat.totalSales.toLocaleString()}</td>
                <td>${performanceBadge}</td>
            </tr>
        `;
    });
    
    tableBody.innerHTML = tableHTML;
}

function getPerformanceRating(totalSales, gradeA, hotCustomers) {
    const salesScore = totalSales >= 200000 ? 3 : totalSales >= 100000 ? 2 : 1;
    const gradeScore = gradeA >= 5 ? 3 : gradeA >= 3 ? 2 : 1;
    const hotScore = hotCustomers >= 5 ? 3 : hotCustomers >= 3 ? 2 : 1;
    
    const averageScore = (salesScore + gradeScore + hotScore) / 3;
    
    if (averageScore >= 2.5) return "ดีเยี่ยม";
    if (averageScore >= 2) return "ดี";
    if (averageScore >= 1.5) return "ปานกลาง";
    return "ต้องปรับปรุง";
}

function getPerformanceBadge(performance) {
    const badgeMap = {
        "ดีเยี่ยม": "bg-success",
        "ดี": "bg-primary", 
        "ปานกลาง": "bg-warning",
        "ต้องปรับปรุง": "bg-danger"
    };
    
    const badgeClass = badgeMap[performance] || "bg-secondary";
    return `<span class="badge ${badgeClass}">${performance}</span>`;
}
</script>
';

// Render the page
echo renderAdminLayout($pageTitle, $content, '', $additionalJS);
?>