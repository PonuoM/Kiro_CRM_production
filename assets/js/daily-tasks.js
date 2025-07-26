/**
 * Daily Tasks JavaScript
 * Handles daily tasks functionality and interactions
 */

class DailyTasksManager {
    constructor() {
        this.tasksData = null;
        this.refreshInterval = null;
        this.autoRefreshEnabled = false;
        this.init();
    }

    // Initialize the manager
    init() {
        this.bindEvents();
        this.loadDailyTasks();
        this.startAutoRefresh();
    }

    // Bind event listeners
    bindEvents() {
        // Refresh button
        const refreshBtn = document.getElementById('refreshBtn');
        if (refreshBtn) {
            refreshBtn.addEventListener('click', () => this.refreshTasks());
        }

        // Auto-refresh toggle
        const autoRefreshToggle = document.getElementById('autoRefreshToggle');
        if (autoRefreshToggle) {
            autoRefreshToggle.addEventListener('change', (e) => {
                this.toggleAutoRefresh(e.target.checked);
            });
        }

        // Keyboard shortcuts
        document.addEventListener('keydown', (e) => {
            if (e.ctrlKey && e.key === 'r') {
                e.preventDefault();
                this.refreshTasks();
            }
        });
    }

    // Load daily tasks from API
    async loadDailyTasks() {
        try {
            this.showLoading();
            
            const response = await fetch('../api/tasks/daily_enhanced.php');
            const result = await response.json();

            if (result.success) {
                this.tasksData = result.data;
                this.renderStats(result.data.summary);
                this.renderTasks('today', result.data.today.tasks, 'todayTasks', 'todayCount');
                this.renderTasks('overdue', result.data.overdue.tasks, 'overdueTasks', 'overdueCount');
                this.renderTasks('upcoming', result.data.upcoming.tasks, 'upcomingTasks', 'upcomingCount');
                
                this.updateLastRefreshTime();
                this.hideLoading();
            } else {
                this.showError('เกิดข้อผิดพลาดในการโหลดข้อมูล: ' + result.message);
            }
        } catch (error) {
            console.error('Error loading daily tasks:', error);
            this.showError('เกิดข้อผิดพลาดในการเชื่อมต่อ');
        }
    }

    // Show loading state
    showLoading() {
        const loadingElements = document.querySelectorAll('.loading');
        loadingElements.forEach(el => {
            el.style.display = 'block';
        });

        const contentElements = document.querySelectorAll('.task-content');
        contentElements.forEach(el => {
            el.style.display = 'none';
        });
    }

    // Hide loading state
    hideLoading() {
        const loadingElements = document.querySelectorAll('.loading');
        loadingElements.forEach(el => {
            el.style.display = 'none';
        });

        const contentElements = document.querySelectorAll('.task-content');
        contentElements.forEach(el => {
            el.style.display = 'block';
        });
    }

    // Render statistics cards
    renderStats(stats) {
        const statsContainer = document.getElementById('statsCards');
        if (!statsContainer) return;

        const statsHtml = `
            <div class="col-md-3 mb-3">
                <div class="card stats-card">
                    <div class="card-body text-center">
                        <i class="fas fa-calendar-day fa-2x mb-2"></i>
                        <h3 class="mb-1">${stats.today_count}</h3>
                        <p class="mb-0">งานวันนี้</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card bg-danger text-white">
                    <div class="card-body text-center">
                        <i class="fas fa-exclamation-triangle fa-2x mb-2"></i>
                        <h3 class="mb-1">${stats.overdue_count}</h3>
                        <p class="mb-0">งานค้างคาว</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card bg-success text-white">
                    <div class="card-body text-center">
                        <i class="fas fa-check-circle fa-2x mb-2"></i>
                        <h3 class="mb-1">${stats.total_completed}</h3>
                        <p class="mb-0">งานเสร็จแล้ว</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card bg-info text-white">
                    <div class="card-body text-center">
                        <i class="fas fa-chart-line fa-2x mb-2"></i>
                        <h3 class="mb-1">${stats.completion_rate}%</h3>
                        <p class="mb-0">อัตราความสำเร็จ</p>
                    </div>
                </div>
            </div>
        `;
        
        statsContainer.innerHTML = statsHtml;
    }

    // Render tasks
    renderTasks(type, tasks, containerId, countId) {
        const container = document.getElementById(containerId);
        const countElement = document.getElementById(countId);
        
        if (!container) return;

        if (countElement) {
            countElement.textContent = tasks.length;
        }

        if (tasks.length === 0) {
            container.innerHTML = this.getEmptyStateHtml(type);
            return;
        }

        const tasksHtml = tasks.map(task => this.getTaskCardHtml(task, type)).join('');
        container.innerHTML = `<div class="task-list">${tasksHtml}</div>`;
    }

    // Get empty state HTML
    getEmptyStateHtml(type) {
        const messages = {
            today: 'ไม่มีงานวันนี้',
            overdue: 'ไม่มีงานค้างคาว',
            upcoming: 'ไม่มีงานสัปดาห์หน้า'
        };

        const icons = {
            today: 'fa-calendar-check',
            overdue: 'fa-check-double',
            upcoming: 'fa-calendar-week'
        };

        return `
            <div class="empty-state">
                <i class="fas ${icons[type]} fa-3x mb-3"></i>
                <p>${messages[type]}</p>
                <small class="text-muted">ยอดเยี่ยม! คุณทำงานได้ดีมาก</small>
            </div>
        `;
    }

    // Get task card HTML
    getTaskCardHtml(task, type) {
        const isCompleted = task.Status === 'เสร็จสิ้น';
        const isOverdue = type === 'overdue';
        const isNewCustomer = task.CustomerStatus === 'ลูกค้าใหม่';
        const cardClass = isCompleted ? 'completed' : (isOverdue ? 'overdue' : (isNewCustomer ? 'new-customer' : type));
        
        // Format contact count and last talk status
        const contactCount = parseInt(task.contact_count) || 0;
        const lastTalkStatus = task.last_talk_status || 'ยังไม่เคยติดต่อ';
        
        return `
            <div class="card task-card ${cardClass} mb-3" data-task-id="${task.id}">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="flex-grow-1">
                            <h6 class="card-title mb-2">
                                <i class="fas fa-user"></i> ${this.escapeHtml(task.CustomerName || 'ไม่ระบุชื่อ')}
                                ${isNewCustomer ? '<span class="badge bg-info ms-2">ลูกค้าใหม่</span>' : ''}
                                <span class="badge task-status-badge ${isCompleted ? 'bg-success' : 'bg-warning'} ms-2">
                                    ${task.Status}
                                </span>
                            </h6>
                            <p class="customer-info mb-2">
                                <i class="fas fa-phone"></i> ${this.escapeHtml(task.CustomerTel || 'ไม่ระบุเบอร์')}
                                <span class="ms-3">
                                    <i class="fas fa-code"></i> ${this.escapeHtml(task.CustomerCode)}
                                </span>
                            </p>
                            <p class="task-time mb-2">
                                <i class="fas fa-clock"></i> ${this.formatDateTime(task.FollowupDate)}
                                ${isOverdue ? '<span class="badge bg-danger ms-2">เกินกำหนด</span>' : ''}
                            </p>
                            ${isNewCustomer ? `
                                <div class="customer-stats mb-2">
                                    <small class="text-muted">
                                        <i class="fas fa-phone-alt"></i> ติดต่อแล้ว: ${contactCount} ครั้ง
                                        ${contactCount > 0 ? `| <i class="fas fa-comments"></i> สถานะล่าสุด: ${this.escapeHtml(lastTalkStatus)}` : ''}
                                    </small>
                                </div>
                            ` : ''}
                            ${task.Remarks ? `
                                <div class="task-remarks mb-2">
                                    <i class="fas fa-sticky-note"></i> ${this.escapeHtml(task.Remarks)}
                                </div>
                            ` : ''}
                        </div>
                        <div class="task-actions ms-3">
                            ${this.getTaskActionButtons(task, isNewCustomer)}
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    // Get task action buttons
    getTaskActionButtons(task, isNewCustomer = false) {
        const isCompleted = task.Status === 'เสร็จสิ้น';
        
        if (isCompleted) {
            return `
                <button class="btn btn-sm btn-secondary btn-reopen" onclick="dailyTasksManager.reopenTask('${task.id}')">
                    <i class="fas fa-undo"></i> เปิดใหม่
                </button>
            `;
        } else {
            // For new customers, show "ติดต่อแล้ว" button instead of "เสร็จ"
            if (isNewCustomer) {
                return `
                    <button class="btn btn-sm btn-primary" onclick="dailyTasksManager.contactCustomer('${task.CustomerCode}')">
                        <i class="fas fa-phone"></i> ติดต่อ
                    </button>
                `;
            } else {
                return `
                    <button class="btn btn-sm btn-complete text-white" onclick="dailyTasksManager.completeTask('${task.id}')">
                        <i class="fas fa-check"></i> เสร็จ
                    </button>
                `;
            }
        }
    }

    // Contact customer (for new customers)
    contactCustomer(customerCode) {
        // Redirect to customer detail page for contact
        window.location.href = `customer_detail.php?code=${customerCode}`;
    }

    // Complete task
    async completeTask(taskId) {
        if (!confirm('คุณต้องการทำเครื่องหมายงานนี้เป็นเสร็จสิ้นหรือไม่?')) {
            return;
        }

        await this.updateTaskStatus(taskId, 'เสร็จสิ้น', 'ทำเครื่องหมายงานเสร็จสิ้นแล้ว');
    }

    // Reopen task
    async reopenTask(taskId) {
        if (!confirm('คุณต้องการเปิดงานนี้ใหม่หรือไม่?')) {
            return;
        }

        await this.updateTaskStatus(taskId, 'รอดำเนินการ', 'เปิดงานใหม่แล้ว');
    }

    // Update task status
    async updateTaskStatus(taskId, status, successMessage) {
        try {
            // Show updating state
            const taskCard = document.querySelector(`[data-task-id="${taskId}"]`);
            if (taskCard) {
                taskCard.classList.add('task-updating');
            }

            const response = await fetch('../api/tasks/status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    id: taskId,
                    status: status
                })
            });

            const result = await response.json();

            if (result.success) {
                this.showSuccess(successMessage);
                await this.loadDailyTasks(); // Refresh tasks
            } else {
                this.showError('เกิดข้อผิดพลาด: ' + result.message);
            }
        } catch (error) {
            console.error('Error updating task status:', error);
            this.showError('เกิดข้อผิดพลาดในการเชื่อมต่อ');
        } finally {
            // Remove updating state
            const taskCard = document.querySelector(`[data-task-id="${taskId}"]`);
            if (taskCard) {
                taskCard.classList.remove('task-updating');
            }
        }
    }

    // Refresh tasks
    async refreshTasks() {
        const refreshBtn = document.getElementById('refreshBtn');
        if (refreshBtn) {
            const icon = refreshBtn.querySelector('i');
            if (icon) {
                icon.classList.add('fa-spin');
            }
        }

        await this.loadDailyTasks();

        if (refreshBtn) {
            const icon = refreshBtn.querySelector('i');
            if (icon) {
                setTimeout(() => {
                    icon.classList.remove('fa-spin');
                }, 500);
            }
        }
    }

    // Start auto refresh
    startAutoRefresh() {
        if (this.refreshInterval) {
            clearInterval(this.refreshInterval);
        }

        // Refresh every 5 minutes
        this.refreshInterval = setInterval(() => {
            if (this.autoRefreshEnabled) {
                this.loadDailyTasks();
            }
        }, 5 * 60 * 1000);

        this.autoRefreshEnabled = true;
    }

    // Toggle auto refresh
    toggleAutoRefresh(enabled) {
        this.autoRefreshEnabled = enabled;
        
        if (enabled) {
            this.startAutoRefresh();
        } else if (this.refreshInterval) {
            clearInterval(this.refreshInterval);
        }
    }

    // Update last refresh time
    updateLastRefreshTime() {
        const lastUpdateElement = document.getElementById('lastUpdate');
        if (lastUpdateElement) {
            const now = new Date();
            const day = String(now.getDate()).padStart(2, '0');
            const month = String(now.getMonth() + 1).padStart(2, '0');
            const year = now.getFullYear();
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            lastUpdateElement.textContent = `อัปเดตล่าสุด: ${day}/${month}/${year} ${hours}:${minutes}`;
        }
    }

    // Format datetime
    formatDateTime(datetime) {
        const date = new Date(datetime);
        const day = String(date.getDate()).padStart(2, '0');
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const year = date.getFullYear();
        const hours = String(date.getHours()).padStart(2, '0');
        const minutes = String(date.getMinutes()).padStart(2, '0');
        return `${day}/${month}/${year} ${hours}:${minutes}`;
    }

    // Escape HTML
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Show success message
    showSuccess(message) {
        this.showNotification(message, 'success');
    }

    // Show error message
    showError(message) {
        this.showNotification(message, 'error');
    }

    // Show notification
    showNotification(message, type) {
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `alert alert-${type === 'success' ? 'success' : 'danger'} alert-dismissible fade show position-fixed`;
        notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
        notification.innerHTML = `
            <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;

        document.body.appendChild(notification);

        // Auto remove after 5 seconds
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 5000);
    }

    // Destroy the manager
    destroy() {
        if (this.refreshInterval) {
            clearInterval(this.refreshInterval);
        }
    }
}

// Initialize daily tasks manager when DOM is loaded
let dailyTasksManager;

document.addEventListener('DOMContentLoaded', function() {
    dailyTasksManager = new DailyTasksManager();
});

// Clean up on page unload
window.addEventListener('beforeunload', function() {
    if (dailyTasksManager) {
        dailyTasksManager.destroy();
    }
});

// Global functions for backward compatibility
function refreshTasks() {
    if (dailyTasksManager) {
        dailyTasksManager.refreshTasks();
    }
}

function completeTask(taskId) {
    if (dailyTasksManager) {
        dailyTasksManager.completeTask(taskId);
    }
}

function reopenTask(taskId) {
    if (dailyTasksManager) {
        dailyTasksManager.reopenTask(taskId);
    }
}