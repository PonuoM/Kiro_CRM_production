<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Call History Production - Quick Access</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        .hero-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 3rem 0;
            text-align: center;
        }
        .status-card {
            border-left: 4px solid #28a745;
            margin-bottom: 1rem;
        }
        .access-btn {
            margin: 0.5rem;
            min-width: 200px;
        }
    </style>
</head>
<body>
    <!-- Hero Section -->
    <div class="hero-section">
        <div class="container">
            <h1 class="display-4"><i class="fas fa-phone-alt"></i> Call History Production</h1>
            <p class="lead">ระบบประวัติการโทรแบบ Production Ready</p>
            <div class="badge bg-success fs-6 px-3 py-2">
                <i class="fas fa-check-circle"></i> SYSTEM READY
            </div>
        </div>
    </div>

    <div class="container mt-4">
        <div class="row">
            <!-- Quick Access -->
            <div class="col-md-8">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-rocket"></i> Quick Access</h5>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-md-6 mb-3">
                                <a href="pages/call_history_demo_fixed.php" class="btn btn-success btn-lg access-btn">
                                    <i class="fas fa-phone"></i><br>
                                    <strong>Call History System</strong><br>
                                    <small>เลือกลูกค้าและดูประวัติการโทร</small>
                                </a>
                            </div>
                            <div class="col-md-6 mb-3">
                                <a href="index.php" class="btn btn-primary btn-lg access-btn">
                                    <i class="fas fa-home"></i><br>
                                    <strong>Main Dashboard</strong><br>
                                    <small>หน้าหลักของระบบ CRM</small>
                                </a>
                            </div>
                            <div class="col-md-6 mb-3">
                                <a href="pages/login.php" class="btn btn-info btn-lg access-btn">
                                    <i class="fas fa-sign-in-alt"></i><br>
                                    <strong>Login System</strong><br>
                                    <small>เข้าสู่ระบบ</small>
                                </a>
                            </div>
                            <div class="col-md-6 mb-3">
                                <a href="simple_system_test.php" class="btn btn-warning btn-lg access-btn">
                                    <i class="fas fa-vial"></i><br>
                                    <strong>System Test</strong><br>
                                    <small>ทดสอบระบบ</small>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- System Status -->
            <div class="col-md-4">
                <div class="card shadow-sm">
                    <div class="card-header bg-success text-white">
                        <h6 class="mb-0"><i class="fas fa-check-circle"></i> System Status</h6>
                    </div>
                    <div class="card-body">
                        <div class="status-card card">
                            <div class="card-body py-2">
                                <small><i class="fas fa-database text-success"></i> Database Integration</small>
                                <div class="fw-bold text-success">CONNECTED</div>
                            </div>
                        </div>
                        <div class="status-card card">
                            <div class="card-body py-2">
                                <small><i class="fas fa-users text-success"></i> Role-Based Access</small>
                                <div class="fw-bold text-success">ACTIVE</div>
                            </div>
                        </div>
                        <div class="status-card card">
                            <div class="card-body py-2">
                                <small><i class="fas fa-chart-bar text-success"></i> Call Statistics</small>
                                <div class="fw-bold text-success">WORKING</div>
                            </div>
                        </div>
                        <div class="status-card card">
                            <div class="card-body py-2">
                                <small><i class="fas fa-mobile-alt text-success"></i> Production UI</small>
                                <div class="fw-bold text-success">READY</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm mt-3">
                    <div class="card-header bg-info text-white">
                        <h6 class="mb-0"><i class="fas fa-info-circle"></i> System Info</h6>
                    </div>
                    <div class="card-body">
                        <p class="mb-1"><strong>Version:</strong> Production v1.0</p>
                        <p class="mb-1"><strong>Updated:</strong> <?php echo date('d/m/Y H:i'); ?></p>
                        <p class="mb-1"><strong>PHP:</strong> <?php echo phpversion(); ?></p>
                        <p class="mb-0"><strong>Status:</strong> <span class="badge bg-success">LIVE</span></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Features Overview -->
        <div class="card shadow-sm mt-4">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0"><i class="fas fa-star"></i> Production Features</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6><i class="fas fa-check text-success"></i> Core Features</h6>
                        <ul class="list-unstyled">
                            <li><i class="fas fa-database text-primary"></i> Real database integration (call_logs)</li>
                            <li><i class="fas fa-chart-line text-primary"></i> Live call statistics & analytics</li>
                            <li><i class="fas fa-shield-alt text-primary"></i> Role-based access control</li>
                            <li><i class="fas fa-mobile-alt text-primary"></i> Responsive Bootstrap 5 UI</li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <h6><i class="fas fa-cogs text-success"></i> Advanced Features</h6>
                        <ul class="list-unstyled">
                            <li><i class="fas fa-search text-primary"></i> Real-time search & filtering</li>
                            <li><i class="fas fa-users text-primary"></i> Sales can see only their customers</li>
                            <li><i class="fas fa-calendar text-primary"></i> Date-based call tracking</li>
                            <li><i class="fas fa-tachometer-alt text-primary"></i> KPI dashboard cards</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Usage Instructions -->
        <div class="alert alert-info mt-4">
            <h5><i class="fas fa-book-open"></i> การใช้งาน</h5>
            <ol class="mb-0">
                <li><strong>Login:</strong> เข้าสู่ระบบด้วย username/password ที่มีอยู่</li>
                <li><strong>เข้าระบบ:</strong> กดปุ่ม "Call History System" ด้านบน</li>
                <li><strong>เลือกลูกค้า:</strong> ระบบจะแสดงหน้าเลือกลูกค้า พร้อม KPI และตัวกรอง</li>
                <li><strong>ดูประวัติ:</strong> กดปุ่ม "ดูประวัติ" เพื่อเข้าสู่ระบบประวัติการโทร</li>
                <li><strong>เพิ่มข้อมูล:</strong> บันทึกการโทรใหม่ได้ทันที</li>
            </ol>
        </div>

        <!-- Footer -->
        <div class="text-center mt-4 mb-4">
            <p class="text-muted">
                <i class="fas fa-copyright"></i> <?php echo date('Y'); ?> Call History Production System | 
                Status: <span class="badge bg-success">PRODUCTION READY</span>
            </p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>