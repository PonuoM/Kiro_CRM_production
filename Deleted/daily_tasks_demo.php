<?php
// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Simple login check
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require_once '../includes/main_layout.php';

$pageTitle = "งานประจำวัน";

// Get user information from session
$user_name = $_SESSION['username'] ?? 'Unknown';
$user_role = $_SESSION['user_role'] ?? 'Unknown';
$canViewAll = ($user_role === 'admin' || $user_role === 'manager');

// Initialize Permissions class first
try {
    require_once '../includes/permissions.php';
    
    // Set global variables for sidebar
    $GLOBALS['currentUser'] = $user_name;
    $GLOBALS['currentRole'] = $user_role;
    $GLOBALS['menuItems'] = Permissions::getMenuItems();
} catch (Exception $e) {
    error_log('Permissions error in daily_tasks_demo.php: ' . $e->getMessage());
    $GLOBALS['currentUser'] = $user_name;
    $GLOBALS['currentRole'] = $user_role;
    $GLOBALS['menuItems'] = [];
}

// Page content
ob_start();
?>

<!-- Page Header -->
<div class="page-header">
    <h1 class="page-title">
        <i class="fas fa-tasks"></i>
        งานประจำวัน
    </h1>
    <p class="page-description">
        จัดการและติดตามงานประจำวันของคุณและทีม
    </p>
</div>

<!-- Role Notice -->
<div class="alert alert-info border-start border-primary border-4 mb-4">
    <?php if ($canViewAll): ?>
        <strong><i class="fas fa-chart-bar"></i> งานทั้งหมด:</strong> คุณเห็นงานของทีมทั้งหมดในระบบ
    <?php else: ?>
        <strong><i class="fas fa-clipboard-list"></i> งานของฉัน:</strong> คุณเห็นเฉพาะงานที่ได้รับมอบหมายเท่านั้น
    <?php endif; ?>
</div>

<!-- Statistics -->
<div class="row mb-4" id="statsCards">
    <div class="col-md-3">
        <div class="card text-center shadow-sm border-0">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col">
                        <h3 class="card-title text-primary mb-1" id="todayTasks">-</h3>
                        <p class="card-text text-muted mb-0">งานวันนี้</p>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-calendar-day fa-2x text-primary opacity-75"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center shadow-sm border-0">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col">
                        <h3 class="card-title text-success mb-1" id="completedTasks">-</h3>
                        <p class="card-text text-muted mb-0">เสร็จแล้ว</p>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-check-circle fa-2x text-success opacity-75"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center shadow-sm border-0">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col">
                        <h3 class="card-title text-warning mb-1" id="pendingTasks">-</h3>
                        <p class="card-text text-muted mb-0">ค้างอยู่</p>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-clock fa-2x text-warning opacity-75"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center shadow-sm border-0">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col">
                        <h3 class="card-title text-danger mb-1" id="overdueTasks">-</h3>
                        <p class="card-text text-muted mb-0">เกินกำหนด</p>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-exclamation-triangle fa-2x text-danger opacity-75"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Today's Tasks -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">
            <i class="fas fa-calendar-day"></i> งานวันนี้ (<?= date('d/m/Y') ?>)
        </h5>
        <button class="btn btn-primary btn-sm" onclick="refreshTasks()">
            <i class="fas fa-sync-alt"></i> รีเฟรช
        </button>
    </div>
    <div class="card-body">
        <!-- Loading indicator -->
        <div class="text-center py-4" id="tasksLoading" style="display: none;">
            <i class="fas fa-spinner fa-spin"></i> กำลังโหลดงาน...
        </div>
        
        <!-- Tasks table -->
        <div id="todayTasksList"></div>
    </div>
</div>

<?php if ($canViewAll): ?>
<!-- Team Tasks -->
<div class="card mt-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">
            <i class="fas fa-users"></i> งานของทีม
        </h5>
        <button class="btn btn-primary btn-sm" onclick="refreshTeamTasks()">
            <i class="fas fa-sync-alt"></i> รีเฟรช
        </button>
    </div>
    <div class="card-body">
        <!-- Loading indicator -->
        <div class="text-center py-4" id="teamTasksLoading" style="display: none;">
            <i class="fas fa-spinner fa-spin"></i> กำลังโหลดงานทีม...
        </div>
        
        <!-- Team tasks table -->
        <div id="teamTasksList"></div>
    </div>
</div>

<div class="alert alert-info mt-4">
    <strong><i class="fas fa-crown"></i> ฟีเจอร์ Admin/Manager:</strong> คุณสามารถเห็นงานของทีมทั้งหมด, กำหนดงาน, และติดตามประสิทธิภาพการทำงาน
</div>
<?php else: ?>
<div class="alert alert-warning mt-4">
    <strong><i class="fas fa-briefcase"></i> Sales View:</strong> คุณเห็นเฉพาะงานที่ได้รับมอบหมายเท่านั้น หากต้องการดูงานของทีมให้ติดต่อ Manager
</div>
<?php endif; ?>

<!-- Back to Dashboard -->
<div class="mt-4">
    <a href="dashboard.php" class="btn btn-outline-primary">
        <i class="fas fa-arrow-left"></i> กลับหน้าแดชบอร์ด
    </a>
</div>

<?php
$content = ob_get_clean();

// Layout will get user info from session directly

// Additional JavaScript with improved error handling
$additionalJS = "
    <script>
        class DailyTasksManager {
            constructor() {
                this.apiUrl = '../api/tasks/daily_simple_fast.php';
                this.init();
            }
            
            init() {
                this.loadTaskStats();
                this.loadTodayTasks();
                this.loadTeamTasks();
            }
            
            async makeRequest(url, options = {}) {
                try {
                    const response = await fetch(url, {
                        ...options,
                        headers: {
                            'Content-Type': 'application/json',
                            ...options.headers
                        }
                    });
                    
                    if (!response.ok) {
                        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                    }
                    
                    const data = await response.json();
                    
                    if (!data.success) {
                        throw new Error(data.message || 'API request failed');
                    }
                    
                    return data;
                } catch (error) {
                    console.error('API request failed:', error);
                    throw error;
                }
            }
            
            async loadTaskStats() {
                try {
                    const data = await this.makeRequest(this.apiUrl);
                    
                    if (data.data && data.data.summary) {
                        const stats = data.data.summary;
                        document.getElementById('todayTasks').textContent = stats.today_count || 0;
                        document.getElementById('completedTasks').textContent = stats.total_completed || 0;
                        document.getElementById('pendingTasks').textContent = (stats.today_count - stats.total_completed) || 0;
                        document.getElementById('overdueTasks').textContent = stats.overdue_count || 0;
                    } else {
                        throw new Error('Invalid API response structure');
                    }
                } catch (error) {
                    console.error('Error loading task stats:', error);
                    this.setErrorValues(['todayTasks', 'completedTasks', 'pendingTasks', 'overdueTasks']);
                }
            }
            
            async loadTodayTasks() {
                const loadingEl = document.getElementById('tasksLoading');
                const contentEl = document.getElementById('todayTasksList');
                
                this.showLoading(loadingEl);
                
                try {
                    const data = await this.makeRequest(this.apiUrl);
                    this.hideLoading(loadingEl);
                    
                    if (data.data && data.data.today && data.data.today.tasks && data.data.today.tasks.length > 0) {
                        contentEl.innerHTML = this.renderTasksTable(data.data.today.tasks);
                    } else {
                        contentEl.innerHTML = this.renderEmptyState('ไม่มีงานสำหรับวันนี้', 'คุณไม่มีลูกค้าใหม่หรืองานที่ต้องทำในวันนี้');
                    }
                } catch (error) {
                    console.error('Error loading today tasks:', error);
                    this.hideLoading(loadingEl);
                    contentEl.innerHTML = this.renderErrorState('เกิดข้อผิดพลาดในการโหลดข้อมูล: ' + error.message);
                }
            }
            
            async loadTeamTasks() {
                if (!document.getElementById('teamTasksLoading')) return; // Only for users with team view permission
                
                const loadingEl = document.getElementById('teamTasksLoading');
                const contentEl = document.getElementById('teamTasksList');
                
                loadingEl.style.display = 'flex';
                
                try {
                    const response = await fetch('../api/tasks/daily_simple_fast.php?team=1');
                    
                    if (!response.ok) {
                        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                    }
                    
                    const data = await response.json();
                    
                    loadingEl.style.display = 'none';
                    
                    if (data.success && data.data && data.data.today && data.data.today.tasks && data.data.today.tasks.length > 0) {
                        contentEl.innerHTML = this.renderTasksTable(data.data.today.tasks, true);
                    } else {
                        contentEl.innerHTML = this.renderEmptyState('ไม่มีงานของทีม', 'ทีมไม่มีงานที่ต้องทำในวันนี้');
                    }
                } catch (error) {
                    console.error('Error loading team tasks:', error);
                    loadingEl.style.display = 'none';
                    contentEl.innerHTML = this.renderErrorState('เกิดข้อผิดพลาดในการโหลดข้อมูลทีม');
                }
            }
            
            renderTasksTable(tasks, showAssignee = false) {
                if (!tasks || tasks.length === 0) {
                    return this.renderEmptyState('ไม่พบงาน', 'ไม่มีงานที่ต้องแสดง');
                }
                
                const assigneeColumn = showAssignee ? '<th>ผู้รับผิดชอบ</th>' : '';
                const assigneeData = showAssignee ? 
                    task => '<td><span class=\"badge bg-info\">' + this.escapeHtml(task.AssignedTo || task.Sales || 'ไม่ระบุ') + '</span></td>' : 
                    task => '';
                
                let tableHtml = '<div class=\"table-responsive\"><table class=\"table table-hover\"><thead class=\"table-light\"><tr>';
                tableHtml += '<th>ลูกค้า</th><th>เบอร์โทร</th><th>วันที่นัดหมาย</th><th>หมายเหตุ</th><th>สถานะ</th>' + assigneeColumn + '<th>การจัดการ</th>';
                tableHtml += '</tr></thead><tbody>';
                
                tasks.forEach(task => {
                    const isNewCustomer = task.CustomerStatus === 'ลูกค้าใหม่';
                    tableHtml += '<tr>';
                    tableHtml += '<td>';
                    tableHtml += '<strong>' + this.escapeHtml(task.CustomerName || task.CustomerCode) + '</strong>';
                    tableHtml += '<br><small class=\"text-muted\">' + task.CustomerCode + '</small>';
                    
                    if (isNewCustomer) {
                        tableHtml += '<br><span class=\"badge bg-info\">ลูกค้าใหม่</span>';
                        if (task.contact_count) {
                            tableHtml += '<br><small class=\"text-muted\">ติดต่อแล้ว: ' + task.contact_count + ' ครั้ง</small>';
                        }
                    }
                    tableHtml += '</td>';
                    
                    tableHtml += '<td><i class=\"fas fa-phone text-primary\"></i> ' + this.escapeHtml(task.CustomerTel || '') + '</td>';
                    tableHtml += '<td>' + this.formatDateTime(task.FollowupDate) + '</td>';
                    tableHtml += '<td>' + (task.Remarks ? this.escapeHtml(task.Remarks) : '-') + '</td>';
                    tableHtml += '<td><span class=\"badge ' + this.getTaskStatusBadgeClass(task.Status || 'รอดำเนินการ') + '\">' + (task.Status || 'รอดำเนินการ') + '</span></td>';
                    tableHtml += assigneeData(task);
                    
                    tableHtml += '<td><div class=\"btn-group btn-group-sm\">';
                    tableHtml += '<button class=\"btn btn-outline-primary\" onclick=\"viewCustomerDetail(\\'' + task.CustomerCode + '\\')\" title=\"ดูข้อมูลลูกค้า\">';
                    tableHtml += '<i class=\"fas fa-eye\"></i></button>';
                    
                    if (isNewCustomer) {
                        tableHtml += '<button class=\"btn btn-primary\" onclick=\"contactCustomer(\\'' + task.CustomerCode + '\\')\" title=\"ติดต่อลูกค้า\">';
                        tableHtml += '<i class=\"fas fa-phone\"></i> ติดต่อ</button>';
                    } else {
                        tableHtml += '<button class=\"btn btn-outline-success\" onclick=\"callCustomer(\\'' + (task.CustomerTel || '') + '\\')\" title=\"โทรหาลูกค้า\">';
                        tableHtml += '<i class=\"fas fa-phone\"></i></button>';
                    }
                    
                    if ((task.Status || 'รอดำเนินการ') === 'รอดำเนินการ' && !isNewCustomer) {
                        tableHtml += '<button class=\"btn btn-outline-warning\" onclick=\"completeTask(\\'' + task.id + '\\')\" title=\"ทำเสร็จ\">';
                        tableHtml += '<i class=\"fas fa-check\"></i></button>';
                    }
                    
                    tableHtml += '</div></td>';
                    tableHtml += '</tr>';
                });
                
                tableHtml += '</tbody></table></div>';
                return tableHtml;
            }
            
            renderEmptyState(title, message) {
                return '<div class=\"text-center py-5\">' +
                    '<i class=\"fas fa-calendar-times fa-3x text-muted mb-3\"></i>' +
                    '<h5 class=\"text-muted\">' + title + '</h5>' +
                    '<p class=\"text-muted\">' + message + '</p>' +
                    '</div>';
            }
            
            renderErrorState(message) {
                return '<div class=\"alert alert-danger\">' +
                    '<i class=\"fas fa-exclamation-triangle\"></i>' +
                    '<strong>เกิดข้อผิดพลาด:</strong> ' + message + 
                    '<br><button class=\"btn btn-sm btn-danger mt-2\" onclick=\"location.reload()\">ลองใหม่</button>' +
                    '</div>';
            }
            
            getTaskStatusBadgeClass(status) {
                switch(status) {
                    case 'รอดำเนินการ': return 'bg-warning';
                    case 'เสร็จสิ้น': return 'bg-success';
                    case 'เลื่อน': return 'bg-secondary';
                    case 'ยกเลิก': return 'bg-danger';
                    default: return 'bg-primary';
                }
            }
            
            formatDateTime(dateString) {
                if (!dateString) return '-';
                const date = new Date(dateString);
                return date.toLocaleDateString('th-TH', {
                    year: 'numeric',
                    month: 'short',
                    day: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                });
            }
            
            showLoading(element) {
                if (element) element.style.display = 'flex';
            }
            
            hideLoading(element) {
                if (element) element.style.display = 'none';
            }
            
            setErrorValues(elementIds) {
                elementIds.forEach(id => {
                    const element = document.getElementById(id);
                    if (element) element.textContent = 'Error';
                });
            }
            
            escapeHtml(text) {
                if (!text) return '';
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            }
        }
        
        // Global functions
        function refreshTasks() {
            if (window.taskManager) {
                window.taskManager.loadTodayTasks();
                window.taskManager.loadTaskStats();
            }
        }
        
        function refreshTeamTasks() {
            if (window.taskManager) {
                window.taskManager.loadTeamTasks();
            }
        }
        
        function completeTask(taskId) {
            if (confirm('คุณต้องการทำเครื่องหมายงานนี้เป็นเสร็จสิ้นหรือไม่?')) {
                // TODO: Implement API call to complete task
                console.log('Completing task:', taskId);
                alert('ฟังก์ชันทำเครื่องหมายเสร็จสิ้นจะพัฒนาในขั้นตอนถัดไป');
            }
        }
        
        function postponeTask(taskId) {
            if (confirm('คุณต้องการเลื่อนงานนี้หรือไม่?')) {
                // TODO: Implement API call to postpone task
                console.log('Postponing task:', taskId);
                alert('ฟังก์ชันเลื่อนงานจะพัฒนาในขั้นตอนถัดไป');
            }
        }
        
        function viewCustomerDetail(customerCode) {
            window.location.href = 'customer_detail.php?code=' + encodeURIComponent(customerCode);
        }
        
        function callCustomer(phoneNumber) {
            if (phoneNumber) {
                window.open('tel:' + phoneNumber, '_self');
            } else {
                alert('ไม่พบเบอร์โทรศัพท์');
            }
        }
        
        function contactCustomer(customerCode) {
            // Redirect to customer detail page for contact
            window.location.href = 'customer_detail.php?code=' + encodeURIComponent(customerCode);
        }
        
        // Initialize when page loads
        document.addEventListener('DOMContentLoaded', function() {
            try {
                window.taskManager = new DailyTasksManager();
            } catch (error) {
                console.error('Failed to initialize DailyTasksManager:', error);
                document.body.insertAdjacentHTML('afterbegin', 
                    '<div class="alert alert-danger">' +
                    '<strong>ระบบผิดพลาด:</strong> ไม่สามารถเริ่มต้นระบบได้ กรุณาลองรีเฟรชหน้า' +
                    '</div>'
                );
            }
        });
    </script>
";

// Render the page
echo renderMainLayout($pageTitle, $content, '', $additionalJS);
?>