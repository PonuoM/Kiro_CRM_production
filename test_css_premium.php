<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🎨 CSS Premium Test</title>
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <!-- Premium Dashboard CSS -->
    <link href="assets/css/dashboard.css" rel="stylesheet">
    
    <style>
        body { padding: 20px; }
        .test-section { margin: 30px 0; padding: 20px; border: 2px solid #ddd; border-radius: 8px; }
        .test-success { border-color: #28a745; background: #f8fff8; }
        .test-fail { border-color: #dc3545; background: #fff8f8; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🎨 CSS Premium UI Test</h1>
        <p>ทดสอบว่า Premium CSS ทำงานหรือไม่</p>
        
        <!-- Test 1: CSS Variables -->
        <div class="test-section test-success">
            <h2>📋 Test 1: CSS Variables & Inter Font</h2>
            <p style="font-family: var(--font-family, 'Arial'); color: var(--primary-color, #000);">
                ข้อความนี้ควรใช้ฟอนต์ Inter และสี primary-color
            </p>
            <div style="background: var(--accent-color, #000); color: white; padding: 10px; border-radius: var(--radius-md, 4px);">
                Background ควรเป็นสี accent-color (น้ำเงิน)
            </div>
        </div>

        <!-- Test 2: Premium Table -->
        <div class="test-section">
            <h2>📊 Test 2: Premium Data Table</h2>
            <div class="premium-table">
                <table class="table">
                    <thead>
                        <tr>
                            <th>ลูกค้า</th>
                            <th>เวลาที่เหลือ</th>
                            <th>Temperature</th>
                            <th>สถานะ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="row-hot">
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="priority-indicator priority-hot"></span>
                                    <div>
                                        <div class="fw-bold customer-name-hot">ลูกค้า HOT (ควรมีสีแดงและไฟระยิบระยับ)</div>
                                        <small class="text-muted">TEST001</small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="time-progress-container">
                                    <div class="time-progress-bar">
                                        <div class="time-progress-fill time-progress-red" style="width: 30%"></div>
                                    </div>
                                    <div class="time-progress-text">3 วัน</div>
                                </div>
                            </td>
                            <td>
                                <span class="temp-badge temp-hot">🔥 HOT</span>
                            </td>
                            <td>
                                <span class="badge bg-success">ลูกค้าใหม่</span>
                            </td>
                        </tr>
                        <tr class="row-urgent">
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="priority-indicator priority-urgent"></span>
                                    <div>
                                        <div class="fw-bold customer-name-urgent">ลูกค้า Urgent (ควรมีสีเหลือง)</div>
                                        <small class="text-muted">TEST002</small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="time-progress-container">
                                    <div class="time-progress-bar">
                                        <div class="time-progress-fill time-progress-yellow" style="width: 60%"></div>
                                    </div>
                                    <div class="time-progress-text">7 วัน</div>
                                </div>
                            </td>
                            <td>
                                <span class="temp-badge temp-warm">⚡ WARM</span>
                            </td>
                            <td>
                                <span class="badge bg-warning">ลูกค้าติดตาม</span>
                            </td>
                        </tr>
                        <tr class="row-normal">
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div>
                                        <div class="fw-bold text-primary">ลูกค้าปกติ</div>
                                        <small class="text-muted">TEST003</small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="time-progress-container">
                                    <div class="time-progress-bar">
                                        <div class="time-progress-fill time-progress-green" style="width: 85%"></div>
                                    </div>
                                    <div class="time-progress-text">25 วัน</div>
                                </div>
                            </td>
                            <td>
                                <span class="temp-badge temp-cold">❄️ COLD</span>
                            </td>
                            <td>
                                <span class="badge bg-info">ลูกค้าเก่า</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Test 3: Premium Buttons -->
        <div class="test-section">
            <h2>🔘 Test 3: Premium Buttons</h2>
            <button class="btn btn-premium me-2">Premium Button</button>
            <button class="btn btn-primary me-2">Bootstrap Primary</button>
            <button class="btn btn-success">Bootstrap Success</button>
        </div>

        <!-- Test 4: Temperature Badges -->
        <div class="test-section">
            <h2>🌡️ Test 4: Temperature Badges</h2>
            <span class="temp-badge temp-hot me-2">🔥 HOT</span>
            <span class="temp-badge temp-warm me-2">⚡ WARM</span>
            <span class="temp-badge temp-cold me-2">❄️ COLD</span>
            <span class="temp-badge temp-frozen">🧊 FROZEN</span>
        </div>

        <!-- Test 5: Priority Indicators -->
        <div class="test-section">
            <h2>⚡ Test 5: Priority Indicators (ควรมีเอฟเฟคระยิบระยับ)</h2>
            <span class="priority-indicator priority-hot me-3">HOT Priority</span>
            <span class="priority-indicator priority-urgent me-3">Urgent Priority</span>
            <span class="priority-indicator priority-normal">Normal Priority</span>
        </div>

        <!-- Test 6: Mobile Responsive -->
        <div class="test-section">
            <h2>📱 Test 6: Mobile Responsive</h2>
            <p>ลองย่อหน้าต่างให้เล็กลง หรือเปิดจากมือถือ</p>
            <div class="row">
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="summary-card">
                        <div class="summary-card-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="summary-card-value">123</div>
                        <div class="summary-card-label">ลูกค้าทั้งหมด</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- JavaScript Test -->
        <div class="test-section">
            <h2>💻 Test 7: JavaScript Functions</h2>
            <button class="btn btn-outline-primary" onclick="testJavaScript()">ทดสอบ JavaScript</button>
            <div id="js-result" class="mt-2"></div>
        </div>

        <!-- CSS Loading Status -->
        <div class="test-section">
            <h2>📈 CSS Loading Status</h2>
            <div id="css-status"></div>
        </div>
    </div>

    <script>
        // Test JavaScript function
        function testJavaScript() {
            document.getElementById('js-result').innerHTML = 
                '<div class="alert alert-success">✅ JavaScript ทำงานได้!</div>';
        }

        // Check CSS loading
        document.addEventListener('DOMContentLoaded', function() {
            const statusDiv = document.getElementById('css-status');
            
            // Check if CSS variables are working
            const testElement = document.createElement('div');
            testElement.style.color = 'var(--primary-color)';
            document.body.appendChild(testElement);
            
            const computedColor = window.getComputedStyle(testElement).color;
            document.body.removeChild(testElement);
            
            if (computedColor && computedColor !== 'var(--primary-color)') {
                statusDiv.innerHTML = '<div class="alert alert-success">✅ CSS Variables ทำงานได้!</div>';
            } else {
                statusDiv.innerHTML = '<div class="alert alert-danger">❌ CSS Variables ไม่ทำงาน</div>';
            }
            
            // Check if dashboard.css is loaded
            const stylesheets = Array.from(document.styleSheets);
            const dashboardCSS = stylesheets.find(sheet => 
                sheet.href && sheet.href.includes('dashboard.css')
            );
            
            if (dashboardCSS) {
                statusDiv.innerHTML += '<div class="alert alert-success">✅ dashboard.css โหลดแล้ว</div>';
            } else {
                statusDiv.innerHTML += '<div class="alert alert-danger">❌ dashboard.css ไม่ได้โหลด</div>';
            }
        });
    </script>
</body>
</html>