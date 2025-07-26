<?php
/**
 * Daily Tasks Page
 * Shows today's tasks and overdue tasks
 */

require_once '../includes/functions.php';

// Check authentication
session_start();
if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

$pageTitle = 'งานประจำวัน';
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - CRM System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/daily-tasks.css" rel="stylesheet">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1><i class="fas fa-tasks"></i> งานประจำวัน</h1>
                    <div>
                        <button class="btn btn-primary btn-refresh" id="refreshBtn" onclick="refreshTasks()">
                            <i class="fas fa-sync-alt"></i> รีเฟรช
                        </button>
                        <span class="text-muted ms-2" id="lastUpdate"></span>
                    </div>
                </div>

                <!-- Statistics Cards -->
                <div class="row mb-4" id="statsCards">
                    <!-- Stats will be loaded here -->
                </div>

                <!-- Task Sections -->
                <div class="row">
                    <!-- Today's Tasks -->
                    <div class="col-lg-6 mb-4">
                        <div class="card">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0">
                                    <i class="fas fa-calendar-day"></i> งานวันนี้
                                    <span class="badge bg-light text-dark ms-2" id="todayCount">0</span>
                                </h5>
                            </div>
                            <div class="card-body" id="todayTasks">
                                <div class="loading">
                                    <i class="fas fa-spinner fa-spin"></i> กำลังโหลด...
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Overdue Tasks -->
                    <div class="col-lg-6 mb-4">
                        <div class="card">
                            <div class="card-header bg-danger text-white">
                                <h5 class="mb-0">
                                    <i class="fas fa-exclamation-triangle"></i> งานค้างคาว
                                    <span class="badge bg-light text-dark ms-2" id="overdueCount">0</span>
                                </h5>
                            </div>
                            <div class="card-body" id="overdueTasks">
                                <div class="loading">
                                    <i class="fas fa-spinner fa-spin"></i> กำลังโหลด...
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Upcoming Tasks -->
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header bg-info text-white">
                                <h5 class="mb-0">
                                    <i class="fas fa-calendar-week"></i> งานสัปดาห์หน้า
                                    <span class="badge bg-light text-dark ms-2" id="upcomingCount">0</span>
                                </h5>
                            </div>
                            <div class="card-body" id="upcomingTasks">
                                <div class="loading">
                                    <i class="fas fa-spinner fa-spin"></i> กำลังโหลด...
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/daily-tasks.js"></script>
</body>
</html>