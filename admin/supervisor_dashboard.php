<?php
/**
 * Supervisor Dashboard
 * Team performance monitoring and management
 * Phase 2: SuperAdmin Role and Admin Workflows
 */

require_once '../includes/admin_layout.php';

// Check login and supervisor dashboard permission
Permissions::requireLogin();
Permissions::requirePermission('supervisor_dashboard');

$pageTitle = "แดชบอร์ดผู้จัดการ";

// Additional CSS for this page
$additionalCSS = '
    <link href="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.css" rel="stylesheet">
    <style>
        .metric-card {
            background: var(--card);
            border-radius: 0.75rem;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            border: 1px solid var(--border);
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
            border-left: 4px solid;
            transition: transform 0.2s ease;
        }
        
        .metric-card:hover {
            transform: translateY(-2px);
        }
        
        .metric-card.primary { border-left-color: var(--primary); }
        .metric-card.success { border-left-color: #22c55e; }
        .metric-card.warning { border-left-color: #f59e0b; }
        .metric-card.danger { border-left-color: #ef4444; }
        .metric-card.info { border-left-color: #3b82f6; }
        .metric-card.purple { border-left-color: #8b5cf6; }
        
        .metric-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--foreground);
            margin: 0 0 0.5rem 0;
        }
        
        .metric-label {
            color: var(--muted-foreground);
            margin: 0;
            font-weight: 500;
        }
        
        .performance-table {
            background: var(--card);
            border-radius: 0.75rem;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            border: 1px solid var(--border);
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
        }
        
        .chart-container {
            background: var(--card);
            border-radius: 0.75rem;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            border: 1px solid var(--border);
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
        }
        
        .performance-badge {
            font-weight: 600;
            padding: 0.25rem 0.5rem;
            border-radius: 0.375rem;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.025em;
        }
        
        .performance-excellent { background-color: #22c55e; color: white; }
        .performance-good { background-color: #3b82f6; color: white; }
        .performance-average { background-color: #f59e0b; color: white; }
        .performance-poor { background-color: #ef4444; color: white; }
        
        .activity-feed {
            background: var(--card);
            border-radius: 0.75rem;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            border: 1px solid var(--border);
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
            max-height: 400px;
            overflow-y: auto;
        }
        
        .activity-item {
            padding: 1rem 0;
            border-bottom: 1px solid var(--border);
        }
        
        .activity-item:last-child {
            border-bottom: none;
        }
        
        .loading-spinner {
            display: none;
            text-align: center;
            padding: 2rem;
            color: var(--muted-foreground);
        }
    </style>
';

// Page content
ob_start();
?>

<!-- Page Header -->
<div class="page-header">
    <h1 class="page-title">
        <i class="fas fa-tachometer-alt"></i>
        แดชบอร์ดผู้จัดการ
    </h1>
    <p class="page-description">
        แดชบอร์ดการจัดการทีมและติดตามประสิทธิภาพ - การวิเคราะห์และรายงานผลงาน
    </p>
</div>

        <!-- Statistics Dashboard -->
        <div class="row mb-3">
            <div class="col-md-12 text-end">
                <button class="btn btn-primary" onclick="refreshAllData()">
                    <i class="fas fa-sync-alt"></i> รีเฟรชข้อมูล
                </button>
            </div>
        </div>

        <!-- Key Metrics Row -->
        <div class="row" id="metricsSection">
            <div class="col-md-2">
                <div class="metric-card primary">
                    <div class="metric-value" id="totalCustomers">-</div>
                    <div class="metric-label">ลูกค้าทั้งหมด</div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="metric-card success">
                    <div class="metric-value" id="activeSales">-</div>
                    <div class="metric-label">พนักงานขายที่ใช้งาน</div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="metric-card warning">
                    <div class="metric-value" id="unassignedCustomers">-</div>
                    <div class="metric-label">ลูกค้าที่ยังไม่ได้แจก</div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="metric-card danger">
                    <div class="metric-value" id="hotCustomers">-</div>
                    <div class="metric-label">ลูกค้า HOT</div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="metric-card info">
                    <div class="metric-value" id="gradeACustomers">-</div>
                    <div class="metric-label">ลูกค้าเกรด A</div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="metric-card purple">
                    <div class="metric-value" id="totalRevenue">-</div>
                    <div class="metric-label">ยอดขายรวม</div>
                </div>
            </div>
        </div>

        <!-- Charts and Performance Section -->
        <div class="row">
            <!-- Team Performance Chart -->
            <div class="col-md-8">
                <div class="chart-container">
                    <h5><i class="fas fa-chart-bar"></i> ประสิทธิภาพทีมขาย</h5>
                    <canvas id="teamPerformanceChart" width="400" height="200"></canvas>
                </div>
            </div>
            
            <!-- Customer Distribution Chart -->
            <div class="col-md-4">
                <div class="chart-container">
                    <h5><i class="fas fa-pie-chart"></i> การกระจายลูกค้า</h5>
                    <canvas id="customerDistributionChart" width="300" height="300"></canvas>
                </div>
            </div>
        </div>

        <!-- Team Performance Table and Activity Feed -->
        <div class="row">
            <!-- Team Performance Table -->
            <div class="col-md-8">
                <div class="performance-table">
                    <h5><i class="fas fa-users"></i> ประสิทธิภาพรายบุคคล</h5>
                    <div class="loading-spinner" id="teamLoading">
                        <i class="fas fa-spinner fa-spin"></i> กำลังโหลด...
                    </div>
                    <div id="teamPerformanceTable"></div>
                </div>
            </div>
            
            <!-- Recent Activity Feed -->
            <div class="col-md-4">
                <div class="activity-feed">
                    <h5><i class="fas fa-clock"></i> กิจกรรมล่าสุด</h5>
                    <div class="loading-spinner" id="activityLoading">
                        <i class="fas fa-spinner fa-spin"></i> กำลังโหลด...
                    </div>
                    <div id="activityFeed"></div>
                </div>
            </div>
        </div>

        <!-- Customer Intelligence Summary -->
        <div class="row">
            <div class="col-md-6">
                <div class="chart-container">
                    <h5><i class="fas fa-star"></i> การกระจายตามเกรด</h5>
                    <canvas id="gradeDistributionChart" width="400" height="200"></canvas>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="chart-container">
                    <h5><i class="fas fa-thermometer-half"></i> การกระจายตามอุณหภูมิ</h5>
                    <canvas id="temperatureDistributionChart" width="400" height="200"></canvas>
                </div>
            </div>
        </div>
    </div>

<?php
$content = ob_get_clean();

// Additional JavaScript
$additionalJS = '
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
    <script>
        let teamPerformanceChart, customerDistributionChart, gradeDistributionChart, temperatureDistributionChart;

        // Initialize dashboard
        document.addEventListener('DOMContentLoaded', function() {
            refreshAllData();
        });

        function refreshAllData() {
            loadKeyMetrics();
            loadTeamPerformance();
            loadRecentActivity();
            loadIntelligenceData();
        }

        function loadKeyMetrics() {
            // Load comprehensive metrics from various APIs
            Promise.all([
                fetch('../api/distribution/basket.php').then(r => r.json()),
                fetch('../api/customers/intelligence-safe.php?action=summary').then(r => r.json()),
                fetch('../api/distribution/basket.php?action=assignment_stats').then(r => r.json())
            ]).then(([distributionData, intelligenceData, assignmentData]) => {
                
                // Update metrics from distribution data
                if (distributionData.status === 'success') {
                    const stats = distributionData.data.stats;
                    document.getElementById('unassignedCustomers').textContent = stats.unassigned || 0;
                    document.getElementById('activeSales').textContent = stats.active_sales || 0;
                    document.getElementById('hotCustomers').textContent = stats.hot_unassigned || 0;
                    document.getElementById('gradeACustomers').textContent = stats.grade_a_unassigned || 0;
                }

                // Calculate total customers and revenue from assignment data
                if (assignmentData.status === 'success') {
                    const totalCustomers = assignmentData.data.reduce((sum, user) => sum + parseInt(user.total_customers), 0);
                    const totalRevenue = assignmentData.data.reduce((sum, user) => sum + parseFloat(user.total_revenue || 0), 0);
                    
                    document.getElementById('totalCustomers').textContent = totalCustomers.toLocaleString();
                    document.getElementById('totalRevenue').textContent = '฿' + totalRevenue.toLocaleString();
                }
                
            }).catch(error => {
                console.error('Error loading key metrics:', error);
            });
        }

        function loadTeamPerformance() {
            document.getElementById('teamLoading').style.display = 'block';
            
            fetch('../api/distribution/basket.php?action=assignment_stats')
                .then(response => response.json())
                .then(data => {
                    document.getElementById('teamLoading').style.display = 'none';
                    
                    if (data.status === 'success') {
                        displayTeamPerformance(data.data);
                        createTeamPerformanceChart(data.data);
                        createCustomerDistributionChart(data.data);
                    }
                })
                .catch(error => {
                    document.getElementById('teamLoading').style.display = 'none';
                    console.error('Error loading team performance:', error);
                });
        }

        function displayTeamPerformance(teamData) {
            const container = document.getElementById('teamPerformanceTable');
            
            if (teamData.length === 0) {
                container.innerHTML = '<div class="alert alert-info">ไม่มีข้อมูลทีม</div>';
                return;
            }

            let html = `
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>พนักงานขาย</th>
                                <th>ลูกค้าที่รับผิดชอบ</th>
                                <th>Grade A</th>
                                <th>HOT</th>
                                <th>ยอดขายเฉลี่ย</th>
                                <th>ยอดขายรวม</th>
                                <th>ประสิทธิภาพ</th>
                            </tr>
                        </thead>
                        <tbody>
            `;

            teamData.forEach(member => {
                const avgPurchase = parseFloat(member.avg_purchase || 0);
                const totalRevenue = parseFloat(member.total_revenue || 0);
                const customerCount = parseInt(member.total_customers);
                
                // Calculate performance score
                let performance = 'average';
                let performanceScore = 0;
                
                if (customerCount > 0) {
                    performanceScore = (parseInt(member.grade_a_count) * 3) + 
                                     (parseInt(member.hot_count) * 2) + 
                                     (totalRevenue / 1000);
                    
                    if (performanceScore >= 50) performance = 'excellent';
                    else if (performanceScore >= 30) performance = 'good';
                    else if (performanceScore >= 15) performance = 'average';
                    else performance = 'poor';
                }

                html += `
                    <tr>
                        <td>
                            <strong>${member.first_name} ${member.last_name}</strong><br>
                            <small class="text-muted">@${member.username}</small>
                        </td>
                        <td><span class="badge bg-primary">${customerCount}</span></td>
                        <td><span class="badge bg-success">${member.grade_a_count}</span></td>
                        <td><span class="badge bg-danger">${member.hot_count}</span></td>
                        <td>฿${avgPurchase.toLocaleString()}</td>
                        <td>฿${totalRevenue.toLocaleString()}</td>
                        <td>
                            <span class="performance-badge performance-${performance}">
                                ${performance === 'excellent' ? 'ดีเยี่ยม' : 
                                  performance === 'good' ? 'ดี' : 
                                  performance === 'average' ? 'ปานกลาง' : 'ต้องปรับปรุง'}
                            </span>
                        </td>
                    </tr>
                `;
            });

            html += `
                        </tbody>
                    </table>
                </div>
            `;

            container.innerHTML = html;
        }

        function createTeamPerformanceChart(teamData) {
            const ctx = document.getElementById('teamPerformanceChart').getContext('2d');
            
            if (teamPerformanceChart) {
                teamPerformanceChart.destroy();
            }

            const labels = teamData.map(member => member.first_name + ' ' + member.last_name);
            const customerCounts = teamData.map(member => parseInt(member.total_customers));
            const revenues = teamData.map(member => parseFloat(member.total_revenue || 0) / 1000); // Convert to thousands

            teamPerformanceChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'จำนวนลูกค้า',
                            data: customerCounts,
                            backgroundColor: 'rgba(54, 162, 235, 0.8)',
                            borderColor: 'rgba(54, 162, 235, 1)',
                            borderWidth: 1,
                            yAxisID: 'y'
                        },
                        {
                            label: 'ยอดขาย (พัน)',
                            data: revenues,
                            backgroundColor: 'rgba(255, 99, 132, 0.8)',
                            borderColor: 'rgba(255, 99, 132, 1)',
                            borderWidth: 1,
                            yAxisID: 'y1'
                        }
                    ]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'top',
                        }
                    },
                    scales: {
                        y: {
                            type: 'linear',
                            display: true,
                            position: 'left',
                            title: {
                                display: true,
                                text: 'จำนวนลูกค้า'
                            }
                        },
                        y1: {
                            type: 'linear',
                            display: true,
                            position: 'right',
                            title: {
                                display: true,
                                text: 'ยอดขาย (พันบาท)'
                            },
                            grid: {
                                drawOnChartArea: false,
                            },
                        }
                    }
                }
            });
        }

        function createCustomerDistributionChart(teamData) {
            const ctx = document.getElementById('customerDistributionChart').getContext('2d');
            
            if (customerDistributionChart) {
                customerDistributionChart.destroy();
            }

            const labels = teamData.map(member => member.first_name);
            const data = teamData.map(member => parseInt(member.total_customers));
            
            const colors = [
                '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', 
                '#9966FF', '#FF9F40', '#FF6384', '#C9CBCF'
            ];

            customerDistributionChart = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: labels,
                    datasets: [{
                        data: data,
                        backgroundColor: colors.slice(0, data.length),
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'bottom',
                        }
                    }
                }
            });
        }

        function loadRecentActivity() {
            document.getElementById('activityLoading').style.display = 'block';
            
            fetch('../api/distribution/basket.php?action=recent_assignments&limit=10')
                .then(response => response.json())
                .then(data => {
                    document.getElementById('activityLoading').style.display = 'none';
                    
                    if (data.status === 'success') {
                        displayRecentActivity(data.data);
                    }
                })
                .catch(error => {
                    document.getElementById('activityLoading').style.display = 'none';
                    console.error('Error loading recent activity:', error);
                });
        }

        function displayRecentActivity(activities) {
            const container = document.getElementById('activityFeed');
            
            if (activities.length === 0) {
                container.innerHTML = '<div class="text-muted text-center">ไม่มีกิจกรรมล่าสุด</div>';
                return;
            }

            let html = '';
            
            activities.forEach(activity => {
                const timeAgo = formatTimeAgo(activity.assigned_at);
                
                html += `
                    <div class="activity-item">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <small class="text-primary"><i class="fas fa-user-plus"></i> แจกลูกค้า</small>
                                <div><strong>${activity.CustomerName}</strong></div>
                                <div class="text-muted">แจกให้: ${activity.Sales}</div>
                                <div>
                                    <span class="badge bg-secondary">${activity.CustomerGrade}</span>
                                    <span class="badge bg-info ms-1">${activity.CustomerTemperature}</span>
                                </div>
                            </div>
                            <small class="text-muted">${timeAgo}</small>
                        </div>
                    </div>
                `;
            });

            container.innerHTML = html;
        }

        function loadIntelligenceData() {
            Promise.all([
                fetch('../api/customers/intelligence-safe.php?action=grades').then(r => r.json()),
                fetch('../api/customers/intelligence-safe.php?action=temperatures').then(r => r.json())
            ]).then(([gradeData, tempData]) => {
                if (gradeData.status === 'success') {
                    createGradeDistributionChart(gradeData.data);
                }
                
                if (tempData.status === 'success') {
                    createTemperatureDistributionChart(tempData.data);
                }
            }).catch(error => {
                console.error('Error loading intelligence data:', error);
            });
        }

        function createGradeDistributionChart(gradeData) {
            const ctx = document.getElementById('gradeDistributionChart').getContext('2d');
            
            if (gradeDistributionChart) {
                gradeDistributionChart.destroy();
            }

            const labels = gradeData.map(item => `Grade ${item.CustomerGrade}`);
            const data = gradeData.map(item => parseInt(item.count));
            const colors = {
                'A': '#28a745',
                'B': '#007bff', 
                'C': '#ffc107',
                'D': '#6c757d'
            };

            gradeDistributionChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'จำนวนลูกค้า',
                        data: data,
                        backgroundColor: gradeData.map(item => colors[item.CustomerGrade]),
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'จำนวนลูกค้า'
                            }
                        }
                    }
                }
            });
        }

        function createTemperatureDistributionChart(tempData) {
            const ctx = document.getElementById('temperatureDistributionChart').getContext('2d');
            
            if (temperatureDistributionChart) {
                temperatureDistributionChart.destroy();
            }

            const labels = tempData.map(item => item.CustomerTemperature);
            const data = tempData.map(item => parseInt(item.count));
            const colors = {
                'HOT': '#dc3545',
                'WARM': '#fd7e14',
                'COLD': '#6c757d'
            };

            temperatureDistributionChart = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: labels,
                    datasets: [{
                        data: data,
                        backgroundColor: tempData.map(item => colors[item.CustomerTemperature]),
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
        }

        function formatTimeAgo(dateString) {
            const now = new Date();
            const past = new Date(dateString);
            const diffMs = now - past;
            const diffMins = Math.floor(diffMs / 60000);
            const diffHours = Math.floor(diffMins / 60);
            const diffDays = Math.floor(diffHours / 24);

            if (diffMins < 1) return 'เมื่อสักครู่';
            if (diffMins < 60) return `${diffMins} นาทีที่แล้ว`;
            if (diffHours < 24) return `${diffHours} ชั่วโมงที่แล้ว`;
            if (diffDays < 7) return `${diffDays} วันที่แล้ว`;
            
            return past.toLocaleDateString('th-TH');
        }

        // Auto-refresh every 5 minutes
        setInterval(refreshAllData, 300000);
    </script>
';

// Render the page
echo renderAdminLayout($pageTitle, $content, $additionalCSS, $additionalJS);
?>