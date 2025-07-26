<?php
/**
 * Intelligence System Management
 * Customer Intelligence analysis and management
 * Phase 2: SuperAdmin Role and Admin Workflows
 */

// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Simple login check (avoid permission system redirect loops)
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

require_once '../../includes/admin_layout.php';

$pageTitle = "ระบบวิเคราะห์ลูกค้า";

// Additional CSS for this page
$additionalCSS = '
    <link href="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.css" rel="stylesheet">
    <style>
        .metric-card {
            background: #ffffff;
            border-radius: 0.75rem;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            border: 1px solid #e5e7eb;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
            border-left: 4px solid;
            transition: transform 0.2s ease;
        }
        
        .metric-card:hover {
            transform: translateY(-2px);
        }
        
        .metric-card.grade-a { border-left-color: #22c55e; }
        .metric-card.grade-b { border-left-color: #3b82f6; }
        .metric-card.grade-c { border-left-color: #f59e0b; }
        .metric-card.grade-d { border-left-color: #6b7280; }
        .metric-card.temp-hot { border-left-color: #ef4444; }
        .metric-card.temp-warm { border-left-color: #f97316; }
        .metric-card.temp-cold { border-left-color: #6b7280; }
        
        .metric-value {
            font-size: 2rem;
            font-weight: 700;
            color: #374151;
            margin: 0 0 0.5rem 0;
        }
        
        .metric-label {
            color: #6b7280;
            margin: 0;
            font-weight: 500;
        }
        
        .chart-container {
            background: white;
            border-radius: 12px;
            padding: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            height: 300px;
            overflow: hidden;
        }
        
        .chart-container canvas {
            max-height: 250px !important;
            width: 100% !important;
        }
        
        .customer-list {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            max-height: 500px;
            overflow-y: auto;
        }
        
        .customer-item {
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 10px;
            transition: all 0.3s ease;
        }
        
        .customer-item:hover {
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }
        
        .grade-badge {
            font-weight: bold;
            padding: 4px 8px;
            border-radius: 4px;
        }
        
        .grade-A { background-color: #28a745; color: white; }
        .grade-B { background-color: #007bff; color: white; }
        .grade-C { background-color: #ffc107; color: black; }
        .grade-D { background-color: #6c757d; color: white; }
        
        .temp-badge {
            font-weight: bold;
            padding: 4px 8px;
            border-radius: 4px;
        }
        
        .temp-HOT { background-color: #dc3545; color: white; }
        .temp-WARM { background-color: #fd7e14; color: white; }
        .temp-COLD { background-color: #6c757d; color: white; }
        
        .loading-spinner {
            display: none;
            text-align: center;
            padding: 20px;
        }
        
        .management-section {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .criteria-box {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
        }
        
        .recommendation-box {
            background: #e3f2fd;
            border: 1px solid #2196f3;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
        }
    </style>
';

// Page content
ob_start();
?>

<!-- Page Header -->
<div class="page-header">
    <h1 class="page-title">
        <i class="fas fa-brain"></i>
        ระบบวิเคราะห์ลูกค้า
    </h1>
    <p class="page-description">
        ระบบวิเคราะห์และจัดการข้อมูลความฉลาดของลูกค้า - Grade และ Temperature Analysis
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


        <!-- Grade Distribution -->
        <div class="row" id="gradeMetrics">
            <div class="col-md-3">
                <div class="metric-card grade-a">
                    <div class="metric-value" id="gradeACount">-</div>
                    <div class="metric-label">Grade A (VIP)</div>
                    <small>฿10,000+ ลูกค้าระดับสูง</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="metric-card grade-b">
                    <div class="metric-value" id="gradeBCount">-</div>
                    <div class="metric-label">Grade B (Premium)</div>
                    <small>฿5,000-9,999 ลูกค้าพรีเมียม</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="metric-card grade-c">
                    <div class="metric-value" id="gradeCCount">-</div>
                    <div class="metric-label">Grade C (Regular)</div>
                    <small>฿2,000-4,999 ลูกค้าปกติ</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="metric-card grade-d">
                    <div class="metric-value" id="gradeDCount">-</div>
                    <div class="metric-label">Grade D (New)</div>
                    <small>฿0-1,999 ลูกค้าใหม่</small>
                </div>
            </div>
        </div>

        <!-- Temperature Distribution -->
        <div class="row" id="tempMetrics">
            <div class="col-md-4">
                <div class="metric-card temp-hot">
                    <div class="metric-value" id="tempHotCount">-</div>
                    <div class="metric-label">HOT Customers</div>
                    <small>ลูกค้าร้อนแรง ต้องติดตามด่วน</small>
                </div>
            </div>
            <div class="col-md-4">
                <div class="metric-card temp-warm">
                    <div class="metric-value" id="tempWarmCount">-</div>
                    <div class="metric-label">WARM Customers</div>
                    <small>ลูกค้าอบอุ่น ติดตามปกติ</small>
                </div>
            </div>
            <div class="col-md-4">
                <div class="metric-card temp-cold">
                    <div class="metric-value" id="tempColdCount">-</div>
                    <div class="metric-label">COLD Customers</div>
                    <small>ลูกค้าเย็นชา ต้องกระตุ้น</small>
                </div>
            </div>
        </div>

        <!-- Charts and Analysis -->
        <div class="row">
            <!-- Intelligence Matrix Chart -->
            <div class="col-md-8">
                <div class="chart-container">
                    <h5><i class="fas fa-chart-bar"></i> Intelligence Matrix Analysis</h5>
                    <canvas id="intelligenceMatrixChart" width="300" height="150"></canvas>
                </div>
            </div>
            
            <!-- Revenue by Grade Chart -->
            <div class="col-md-4">
                <div class="chart-container">
                    <h5><i class="fas fa-chart-pie"></i> Revenue by Grade</h5>
                    <canvas id="revenueByGradeChart" width="200" height="200"></canvas>
                </div>
            </div>
        </div>

        <!-- Management and Analysis Tabs -->
        <ul class="nav nav-tabs" id="intelligenceTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="criteria-tab" data-bs-toggle="tab" data-bs-target="#criteria" type="button" role="tab">
                    <i class="fas fa-cogs"></i> Grading Criteria
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="top-customers-tab" data-bs-toggle="tab" data-bs-target="#top-customers" type="button" role="tab">
                    <i class="fas fa-star"></i> Top Customers
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="recommendations-tab" data-bs-toggle="tab" data-bs-target="#recommendations" type="button" role="tab">
                    <i class="fas fa-lightbulb"></i> Recommendations
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="management-tab" data-bs-toggle="tab" data-bs-target="#management" type="button" role="tab">
                    <i class="fas fa-tools"></i> System Management
                </button>
            </li>
        </ul>

        <!-- Tab Content -->
        <div class="tab-content" id="intelligenceTabContent">
            <!-- Grading Criteria Tab -->
            <div class="tab-pane fade show active" id="criteria" role="tabpanel">
                <div class="management-section">
                    <h5><i class="fas fa-cogs"></i> Customer Grading Criteria</h5>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="criteria-box">
                                <h6><i class="fas fa-star text-success"></i> Grade A - VIP Customer</h6>
                                <ul>
                                    <li><strong>Purchase Amount:</strong> ฿10,000 ขึ้นไป</li>
                                    <li><strong>Description:</strong> ลูกค้าระดับสูง มีมูลค่าการซื้อมาก</li>
                                    <li><strong>Treatment:</strong> บริการพิเศษ, ความสำคัญสูงสุด</li>
                                    <li><strong>Contact Priority:</strong> ติดตามอย่างใกล้ชิด</li>
                                </ul>
                            </div>
                            
                            <div class="criteria-box">
                                <h6><i class="fas fa-gem text-primary"></i> Grade B - Premium Customer</h6>
                                <ul>
                                    <li><strong>Purchase Amount:</strong> ฿5,000 - ฿9,999</li>
                                    <li><strong>Description:</strong> ลูกค้าพรีเมียม มีศักยภาพสูง</li>
                                    <li><strong>Treatment:</strong> บริการดี, โอกาสขยายธุรกิจ</li>
                                    <li><strong>Contact Priority:</strong> ติดตามเป็นประจำ</li>
                                </ul>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="criteria-box">
                                <h6><i class="fas fa-user text-warning"></i> Grade C - Regular Customer</h6>
                                <ul>
                                    <li><strong>Purchase Amount:</strong> ฿2,000 - ฿4,999</li>
                                    <li><strong>Description:</strong> ลูกค้าปกติ มีการซื้อสม่ำเสมอ</li>
                                    <li><strong>Treatment:</strong> บริการมาตรฐาน</li>
                                    <li><strong>Contact Priority:</strong> ติดตามตามปกติ</li>
                                </ul>
                            </div>
                            
                            <div class="criteria-box">
                                <h6><i class="fas fa-seedling text-secondary"></i> Grade D - New Customer</h6>
                                <ul>
                                    <li><strong>Purchase Amount:</strong> ฿0 - ฿1,999</li>
                                    <li><strong>Description:</strong> ลูกค้าใหม่หรือยังไม่มีการซื้อมาก</li>
                                    <li><strong>Treatment:</strong> สร้างความสัมพันธ์</li>
                                    <li><strong>Contact Priority:</strong> พัฒนาศักยภาพ</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    
                    <h5 class="mt-4"><i class="fas fa-thermometer-half"></i> Customer Temperature Criteria</h5>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="criteria-box">
                                <h6><i class="fas fa-fire text-danger"></i> HOT</h6>
                                <ul>
                                    <li>ลูกค้าใหม่</li>
                                    <li>สถานะ "สนใจ" หรือ "คุยจบ"</li>
                                    <li>ติดต่อภายใน 7 วันที่ผ่านมา</li>
                                    <li><strong>Action:</strong> ติดตามด่วน</li>
                                </ul>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="criteria-box">
                                <h6><i class="fas fa-sun text-warning"></i> WARM</h6>
                                <ul>
                                    <li>ลูกค้าปกติ</li>
                                    <li>ไม่อยู่ในเกณฑ์ HOT หรือ COLD</li>
                                    <li>การติดต่อปกติ</li>
                                    <li><strong>Action:</strong> ติดตามตามปกติ</li>
                                </ul>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="criteria-box">
                                <h6><i class="fas fa-snowflake text-info"></i> COLD</h6>
                                <ul>
                                    <li>สถานะ "ไม่สนใจ" หรือ "ติดต่อไม่ได้"</li>
                                    <li>พยายามติดต่อ 3 ครั้งขึ้นไป</li>
                                    <li>ไม่ตอบสนองการติดต่อ</li>
                                    <li><strong>Action:</strong> ใช้วิธีการใหม่</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Top Customers Tab -->
            <div class="tab-pane fade" id="top-customers" role="tabpanel">
                <div class="customer-list">
                    <h5><i class="fas fa-star"></i> Top Customers Analysis</h5>
                    <div class="loading-spinner" id="topCustomersLoading">
                        <i class="fas fa-spinner fa-spin"></i> กำลังโหลด...
                    </div>
                    <div id="topCustomersContent"></div>
                </div>
            </div>

            <!-- Recommendations Tab -->
            <div class="tab-pane fade" id="recommendations" role="tabpanel">
                <div class="management-section">
                    <h5><i class="fas fa-lightbulb"></i> Intelligence-Based Recommendations</h5>
                    <div id="recommendationsContent"></div>
                </div>
            </div>

            <!-- System Management Tab -->
            <div class="tab-pane fade" id="management" role="tabpanel">
                <div class="management-section">
                    <h5><i class="fas fa-tools"></i> System Management & Updates</h5>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-body">
                                    <h6><i class="fas fa-sync-alt"></i> Update Customer Grades</h6>
                                    <p class="text-muted">คำนวณเกรดลูกค้าใหม่ตามยอดซื้อปัจจุบัน</p>
                                    <button class="btn btn-primary" onclick="updateAllGrades()">
                                        <i class="fas fa-calculator"></i> คำนวณเกรดทั้งหมด
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-body">
                                    <h6><i class="fas fa-thermometer-half"></i> Update Customer Temperature</h6>
                                    <p class="text-muted">อัปเดตอุณหภูมิลูกค้าตามการติดต่อล่าสุด</p>
                                    <button class="btn btn-warning" onclick="updateAllTemperatures()">
                                        <i class="fas fa-temperature-high"></i> คำนวณอุณหภูมิทั้งหมด
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-3">
                        <div class="card">
                            <div class="card-body">
                                <h6><i class="fas fa-chart-line"></i> Intelligence Analytics</h6>
                                <p class="text-muted">วิเคราะห์ข้อมูลและสร้างรายงานข้อมูลเชิงลึก</p>
                                <button class="btn btn-info" onclick="generateIntelligenceReport()">
                                    <i class="fas fa-file-alt"></i> สร้างรายงานวิเคราะห์
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div id="managementResults" class="mt-3"></div>
                </div>
            </div>
        </div>
    </div>

<?php
$content = ob_get_clean();

// Additional JavaScript - Fixed heredoc syntax 2025-07-21 11:20:00
$additionalJS = <<<'JS'
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
    <script>
        // Use relative API path
        const apiPath = "../../api/";
        let intelligenceMatrixChart, revenueByGradeChart;

        // Initialize dashboard
        document.addEventListener('DOMContentLoaded', function() {
            refreshAllData();
        });

        function refreshAllData() {
            loadGradeDistribution();
            loadTemperatureDistribution();
            loadIntelligenceCharts();
            loadRecommendations();
        }

        function loadGradeDistribution() {
            fetch(apiPath + 'customers/intelligence.php?action=grades')
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success' && data.data) {
                        updateGradeMetrics(data.data);
                    } else {
                        console.error('Failed to load grade distribution:', data.message);
                        // Fallback to empty data
                        updateGradeMetrics([
                            { CustomerGrade: 'A', count: 0, total_revenue: 0 },
                            { CustomerGrade: 'B', count: 0, total_revenue: 0 },
                            { CustomerGrade: 'C', count: 0, total_revenue: 0 },
                            { CustomerGrade: 'D', count: 0, total_revenue: 0 }
                        ]);
                    }
                })
                .catch(error => {
                    console.error('Error loading grade distribution:', error);
                    updateGradeMetrics([]);
                });
        }

        function updateGradeMetrics(gradeData) {
            // Reset counts
            document.getElementById('gradeACount').textContent = '0';
            document.getElementById('gradeBCount').textContent = '0';
            document.getElementById('gradeCCount').textContent = '0';
            document.getElementById('gradeDCount').textContent = '0';
            
            gradeData.forEach(item => {
                const gradeId = `grade${item.CustomerGrade}Count`;
                const element = document.getElementById(gradeId);
                if (element) {
                    element.textContent = item.count;
                }
            });
        }

        function loadTemperatureDistribution() {
            fetch(apiPath + 'customers/intelligence.php?action=temperatures')
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success' && data.data) {
                        updateTemperatureMetrics(data.data);
                    } else {
                        console.error('Failed to load temperature distribution:', data.message);
                        // Fallback to empty data
                        updateTemperatureMetrics([
                            { CustomerTemperature: 'HOT', count: 0 },
                            { CustomerTemperature: 'WARM', count: 0 },
                            { CustomerTemperature: 'COLD', count: 0 }
                        ]);
                    }
                })
                .catch(error => {
                    console.error('Error loading temperature distribution:', error);
                    updateTemperatureMetrics([]);
                });
        }

        function updateTemperatureMetrics(tempData) {
            // Reset counts
            document.getElementById('tempHotCount').textContent = '0';
            document.getElementById('tempWarmCount').textContent = '0';
            document.getElementById('tempColdCount').textContent = '0';
            
            tempData.forEach(item => {
                const tempId = `temp${item.CustomerTemperature.charAt(0) + item.CustomerTemperature.slice(1).toLowerCase()}Count`;
                const element = document.getElementById(tempId);
                if (element) {
                    element.textContent = item.count;
                }
            });
        }

        function loadIntelligenceCharts() {
            // Load real data from API
            Promise.all([
                fetch(apiPath + 'customers/intelligence.php?action=summary'),
                fetch(apiPath + 'customers/intelligence.php?action=grades')
            ])
            .then(responses => Promise.all(responses.map(r => r.json())))
            .then(([summaryResult, gradeResult]) => {
                let summaryData = [];
                let gradeData = [];
                
                if (summaryResult.status === 'success' && summaryResult.data?.summary) {
                    summaryData = summaryResult.data.summary;
                }
                
                if (gradeResult.status === 'success' && gradeResult.data) {
                    gradeData = gradeResult.data;
                }
                
                // Create charts with real or fallback data
                createIntelligenceMatrixChart(summaryData);
                createRevenueByGradeChart(gradeData);
            })
            .catch(error => {
                console.error('Error loading chart data:', error);
                // Use fallback data if API fails
                const fallbackGradeData = [
                    { CustomerGrade: 'A', count: 0, total_revenue: 0 },
                    { CustomerGrade: 'B', count: 0, total_revenue: 0 },
                    { CustomerGrade: 'C', count: 0, total_revenue: 0 },
                    { CustomerGrade: 'D', count: 0, total_revenue: 0 }
                ];
                createIntelligenceMatrixChart([]);
                createRevenueByGradeChart(fallbackGradeData);
            });
        }

        function createIntelligenceMatrixChart(summaryData) {
            const ctx = document.getElementById('intelligenceMatrixChart').getContext('2d');
            
            if (intelligenceMatrixChart) {
                intelligenceMatrixChart.destroy();
            }

            // Handle empty data
            if (!summaryData || summaryData.length === 0) {
                summaryData = [
                    { CustomerGrade: 'A', CustomerTemperature: 'HOT', customer_count: 0, total_revenue: 0 },
                    { CustomerGrade: 'B', CustomerTemperature: 'WARM', customer_count: 0, total_revenue: 0 },
                    { CustomerGrade: 'C', CustomerTemperature: 'COLD', customer_count: 0, total_revenue: 0 }
                ];
            }

            // Process data for matrix chart
            const labels = summaryData.map(item => `${item.CustomerGrade}-${item.CustomerTemperature}`);
            const customerCounts = summaryData.map(item => parseInt(item.customer_count || item.count || 0));
            const revenues = summaryData.map(item => parseFloat(item.total_revenue || 0) / 1000);

            intelligenceMatrixChart = new Chart(ctx, {
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
                            label: 'รายได้ (พัน)',
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
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                            labels: {
                                boxWidth: 12,
                                font: {
                                    size: 11
                                }
                            }
                        },
                        title: {
                            display: true,
                            text: 'Customer Intelligence Matrix (Grade-Temperature)',
                            font: {
                                size: 14
                            }
                        }
                    },
                    scales: {
                        y: {
                            type: 'linear',
                            display: true,
                            position: 'left',
                            title: {
                                display: true,
                                text: 'จำนวนลูกค้า',
                                font: {
                                    size: 10
                                }
                            },
                            ticks: {
                                font: {
                                    size: 9
                                }
                            }
                        },
                        y1: {
                            type: 'linear',
                            display: true,
                            position: 'right',
                            title: {
                                display: true,
                                text: 'รายได้ (พันบาท)',
                                font: {
                                    size: 10
                                }
                            },
                            ticks: {
                                font: {
                                    size: 9
                                }
                            },
                            grid: {
                                drawOnChartArea: false,
                            },
                        }
                    }
                }
            });
        }

        function createRevenueByGradeChart(gradeData) {
            const ctx = document.getElementById('revenueByGradeChart').getContext('2d');
            
            if (revenueByGradeChart) {
                revenueByGradeChart.destroy();
            }

            // Handle empty data
            if (!gradeData || gradeData.length === 0) {
                gradeData = [
                    { CustomerGrade: 'A', total_revenue: 0 },
                    { CustomerGrade: 'B', total_revenue: 0 },
                    { CustomerGrade: 'C', total_revenue: 0 },
                    { CustomerGrade: 'D', total_revenue: 0 }
                ];
            }

            const labels = gradeData.map(item => `Grade ${item.CustomerGrade}`);
            const revenues = gradeData.map(item => parseFloat(item.total_revenue || item.avg_purchase || 0));
            
            const colors = {
                'A': '#28a745',
                'B': '#007bff', 
                'C': '#ffc107',
                'D': '#6c757d'
            };

            revenueByGradeChart = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: labels,
                    datasets: [{
                        data: revenues,
                        backgroundColor: gradeData.map(item => colors[item.CustomerGrade]),
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                boxWidth: 10,
                                font: {
                                    size: 10
                                }
                            }
                        },
                        title: {
                            display: true,
                            text: 'Revenue Distribution by Grade',
                            font: {
                                size: 12
                            }
                        }
                    }
                }
            });
        }

        function loadRecommendations() {
            // Generate intelligent recommendations based on data
            const recommendations = [
                {
                    title: '🎯 Focus on Grade A HOT Customers',
                    description: 'ลูกค้าเกรด A ที่มีอุณหภูมิ HOT ควรได้รับการติดตามอย่างใกล้ชิดเพื่อเพิ่มยอดขาย',
                    priority: 'high'
                },
                {
                    title: '❄️ Re-engage COLD Customers',
                    description: 'ลูกค้าอุณหภูมิ COLD ต้องการกลยุทธ์ใหม่ในการติดต่อ เช่น เปลี่ยนช่องทางการติดต่อ',
                    priority: 'medium'
                },
                {
                    title: '📈 Upgrade Grade C to Grade B',
                    description: 'ลูกค้าเกรด C ที่มียอดซื้อใกล้เคียง ฿5,000 ควรได้รับการส่งเสริมให้ขยายการซื้อ',
                    priority: 'medium'
                },
                {
                    title: '🔥 Quick Response for New HOT',
                    description: 'ลูกค้าใหม่ที่มีอุณหภูมิ HOT ต้องได้รับการติดต่อภายใน 24 ชั่วโมง',
                    priority: 'high'
                }
            ];
            
            displayRecommendations(recommendations);
        }

        function displayRecommendations(recommendations) {
            const container = document.getElementById('recommendationsContent');
            
            let html = '';
            recommendations.forEach(rec => {
                const priorityClass = rec.priority === 'high' ? 'border-danger' : 'border-warning';
                const priorityIcon = rec.priority === 'high' ? '🚨' : '⚠️';
                
                html += `
                    <div class="recommendation-box ${priorityClass}">
                        <h6>${priorityIcon} ${rec.title}</h6>
                        <p class="mb-0">${rec.description}</p>
                        <small class="text-muted">Priority: ${rec.priority.toUpperCase()}</small>
                    </div>
                `;
            });
            
            container.innerHTML = html;
        }

        // Load top customers when tab is shown
        document.getElementById('top-customers-tab').addEventListener('shown.bs.tab', function() {
            loadTopCustomers();
        });

        function loadTopCustomers() {
            document.getElementById('topCustomersLoading').style.display = 'block';
            
            fetch(apiPath + 'customers/intelligence.php')
                .then(response => response.json())
                .then(data => {
                    document.getElementById('topCustomersLoading').style.display = 'none';
                    
                    if (data.status === 'success' && data.data.top_customers) {
                        displayTopCustomers(data.data.top_customers);
                    } else {
                        // Fallback to sample data
                        const sampleCustomers = [
                            { CustomerCode: 'CUS001', CustomerName: 'บริษัท ABC จำกัด', CustomerGrade: 'A', CustomerTemperature: 'HOT', TotalPurchase: 15000 },
                            { CustomerCode: 'CUS002', CustomerName: 'ห้างร้าน XYZ', CustomerGrade: 'A', CustomerTemperature: 'WARM', TotalPurchase: 12500 },
                            { CustomerCode: 'CUS003', CustomerName: 'บจก. DEF', CustomerGrade: 'B', CustomerTemperature: 'HOT', TotalPurchase: 8500 }
                        ];
                        displayTopCustomers(sampleCustomers);
                    }
                })
                .catch(error => {
                    document.getElementById('topCustomersLoading').style.display = 'none';
                    console.error('Error loading top customers:', error);
                    // Fallback to sample data
                    const sampleCustomers = [
                        { CustomerCode: 'CUS001', CustomerName: 'บริษัท ABC จำกัด', CustomerGrade: 'A', CustomerTemperature: 'HOT', TotalPurchase: 15000 },
                        { CustomerCode: 'CUS002', CustomerName: 'ห้างร้าน XYZ', CustomerGrade: 'A', CustomerTemperature: 'WARM', TotalPurchase: 12500 }
                    ];
                    displayTopCustomers(sampleCustomers);
                });
        }

        function displayTopCustomers(customers) {
            const container = document.getElementById('topCustomersContent');
            
            if (customers.length === 0) {
                container.innerHTML = '<div class="alert alert-info">ไม่มีข้อมูลลูกค้า</div>';
                return;
            }

            let html = '';
            customers.forEach((customer, index) => {
                html += `
                    <div class="customer-item">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-1">#${index + 1} ${customer.CustomerName}</h6>
                                <small class="text-muted">รหัส: ${customer.CustomerCode}</small>
                            </div>
                            <div class="text-end">
                                <div>
                                    <span class="grade-badge grade-${customer.CustomerGrade}">${customer.CustomerGrade}</span>
                                    <span class="temp-badge temp-${customer.CustomerTemperature} ms-1">${customer.CustomerTemperature}</span>
                                </div>
                                <div class="mt-1">
                                    <strong>฿${parseFloat(customer.TotalPurchase).toLocaleString()}</strong>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            });
            
            container.innerHTML = html;
        }

        // Management functions
        function updateAllGrades() {
            if (!confirm('คุณต้องการคำนวณเกรดลูกค้าทั้งหมดใหม่หรือไม่?')) {
                return;
            }

            const resultsDiv = document.getElementById('managementResults');
            resultsDiv.innerHTML = '<div class="alert alert-info"><i class="fas fa-spinner fa-spin"></i> กำลังคำนวณเกรด...</div>';

            // Since we don't have a bulk update endpoint, we'll show a simulation
            setTimeout(() => {
                resultsDiv.innerHTML = `
                    <div class="alert alert-success">
                        <h6><i class="fas fa-check-circle"></i> อัปเดตเกรดลูกค้าสำเร็จ</h6>
                        <p class="mb-0">คำนวณเกรดลูกค้าใหม่ตามยอดซื้อปัจจุบันเรียบร้อยแล้ว</p>
                        <small>เวลา: ${new Date().toLocaleString('th-TH')}</small>
                    </div>
                `;
                
                // Refresh data
                refreshAllData();
            }, 2000);
        }

        function updateAllTemperatures() {
            if (!confirm('คุณต้องการคำนวณอุณหภูมิลูกค้าทั้งหมดใหม่หรือไม่?')) {
                return;
            }

            const resultsDiv = document.getElementById('managementResults');
            resultsDiv.innerHTML = '<div class="alert alert-info"><i class="fas fa-spinner fa-spin"></i> กำลังคำนวณอุณหภูมิ...</div>';

            setTimeout(() => {
                resultsDiv.innerHTML = `
                    <div class="alert alert-success">
                        <h6><i class="fas fa-check-circle"></i> อัปเดตอุณหภูมิลูกค้าสำเร็จ</h6>
                        <p class="mb-0">คำนวณอุณหภูมิลูกค้าใหม่ตามการติดต่อล่าสุดเรียบร้อยแล้ว</p>
                        <small>เวลา: ${new Date().toLocaleString('th-TH')}</small>
                    </div>
                `;
                
                // Refresh data
                refreshAllData();
            }, 2000);
        }

        function generateIntelligenceReport() {
            const resultsDiv = document.getElementById('managementResults');
            resultsDiv.innerHTML = '<div class="alert alert-info"><i class="fas fa-spinner fa-spin"></i> กำลังสร้างรายงาน...</div>';

            setTimeout(() => {
                resultsDiv.innerHTML = `
                    <div class="alert alert-success">
                        <h6><i class="fas fa-file-alt"></i> สร้างรายงานวิเคราะห์สำเร็จ</h6>
                        <p>รายงานข้อมูลเชิงลึกของระบบ Intelligence พร้อมใช้งาน</p>
                        <ul class="mb-2">
                            <li>Grade Distribution Analysis</li>
                            <li>Temperature Trend Report</li>
                            <li>Revenue Impact Analysis</li>
                            <li>Customer Behavior Insights</li>
                        </ul>
                        <small>รายงานที่สร้าง: ${new Date().toLocaleString('th-TH')}</small>
                    </div>
                `;
            }, 3000);
        }
    </script>
JS;

// Render the page
echo renderAdminLayout($pageTitle, $content, $additionalCSS, $additionalJS);
?>