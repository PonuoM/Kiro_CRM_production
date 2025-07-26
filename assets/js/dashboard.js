// Dashboard JavaScript functionality

class Dashboard {
    constructor() {
        this.currentTab = 'do';
        this.searchTimeouts = {};
        this.init();
    }

    init() {
        this.setupTabNavigation();
        this.loadInitialData();
        this.setupEventListeners();
    }

    setupTabNavigation() {
        // Use Bootstrap 5 tab elements
        const tabButtons = document.querySelectorAll('.nav-link[data-bs-toggle="tab"]');
        
        tabButtons.forEach(button => {
            button.addEventListener('click', (e) => {
                // Extract tab ID from data-bs-target (remove # prefix)
                const tabTarget = button.getAttribute('data-bs-target');
                const tabId = this.getTabIdFromTarget(tabTarget);
                
                if (tabId) {
                    // Set current tab for data loading
                    this.currentTab = tabId;
                    
                    // Load data when tab becomes active
                    // Use setTimeout to ensure Bootstrap has switched the tab first
                    setTimeout(() => {
                        this.loadTabData(tabId);
                    }, 50);
                }
            });
        });
    }
    
    getTabIdFromTarget(target) {
        if (!target) return null;
        
        const targetMap = {
            '#do': 'do',
            '#new-customers': 'new-customers', 
            '#follow-customers': 'follow-customers',
            '#old-customers': 'old-customers',
            '#follow-all': 'follow-all',
            '#unassigned': 'unassigned'
        };
        
        return targetMap[target] || null;
    }

    // Bootstrap handles tab switching, we just need to load data
    switchTab(tabId) {
        this.currentTab = tabId;
        this.loadTabData(tabId);
    }

    loadInitialData() {
        // Load data for the default active tab (DO)
        this.loadTabData('do');
    }

    loadTabData(tabId) {
        switch(tabId) {
            case 'do':
                this.loadTodayTasks();
                break;
            case 'new-customers':
                this.loadCustomers('ลูกค้าใหม่', 'new');
                break;
            case 'follow-customers':
                this.loadCustomers('ลูกค้าติดตาม', 'follow');
                break;
            case 'old-customers':
                this.loadCustomers('ลูกค้าเก่า', 'old');
                break;
            case 'follow-all':
                this.loadAllTasks();
                break;
            case 'unassigned':
                this.loadUnassignedCustomers();
                break;
        }
    }

    async loadTodayTasks() {
        const loadingEl = document.getElementById('do-loading');
        const contentEl = document.getElementById('do-content');
        
        loadingEl.style.display = 'flex';
        contentEl.innerHTML = '';

        try {
            const response = await fetch('../api/tasks/daily.php');
            
            // Check if response is ok
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            // Check if response is JSON
            const contentType = response.headers.get("content-type");
            if (!contentType || !contentType.includes("application/json")) {
                const textResponse = await response.text();
                console.error('Non-JSON response:', textResponse);
                throw new Error('Response is not JSON');
            }
            
            const data = await response.json();

            loadingEl.style.display = 'none';

            if (data.status === 'success' && data.data) {
                const allTasks = data.data || [];
                
                if (allTasks.length > 0) {
                    // Update card header with count
                    const cardHeader = document.querySelector('#do .card-header h5');
                    if (cardHeader) {
                        cardHeader.innerHTML = `<i class="fas fa-tasks"></i> งานวันนี้ (${data.total || allTasks.length})`;
                    }
                    
                    contentEl.innerHTML = this.renderTasks(allTasks);
                    
                    // Add pagination if needed
                    if (data.total_pages > 1) {
                        contentEl.innerHTML += this.renderPagination(data.page, data.total_pages, 'loadTodayTasks');
                    }
                } else {
                    contentEl.innerHTML = this.renderEmptyState('ไม่มีงานสำหรับวันนี้', 'คุณไม่มีนัดหมายหรืองานที่ต้องทำในวันนี้');
                }
            } else {
                contentEl.innerHTML = this.renderEmptyState('ไม่มีงานสำหรับวันนี้', 'คุณไม่มีนัดหมายหรืองานที่ต้องทำในวันนี้');
            }
        } catch (error) {
            console.error('Error loading today tasks:', error);
            loadingEl.style.display = 'none';
            contentEl.innerHTML = this.renderErrorState('เกิดข้อผิดพลาดในการโหลดข้อมูล');
        }
    }

    async loadCustomers(status, tabType) {
        const loadingEl = document.getElementById(`${tabType}-loading`);
        const contentEl = document.getElementById(`${tabType}-content`);
        
        loadingEl.style.display = 'flex';
        contentEl.innerHTML = '';

        try {
            const url = `../api/customers/list-simple.php?customer_status=${encodeURIComponent(status)}`;
            console.log('Loading customers:', status, 'from URL:', url);
            
            const response = await fetch(url);
            
            // Check if response is ok
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            // Check if response is JSON
            const contentType = response.headers.get("content-type");
            if (!contentType || !contentType.includes("application/json")) {
                const textResponse = await response.text();
                console.error('Non-JSON response:', textResponse);
                throw new Error('Response is not JSON');
            }
            
            const data = await response.json();
            console.log('Customer data loaded:', data);

            loadingEl.style.display = 'none';

            if (data.status === 'success' && data.data && data.data.length > 0) {
                contentEl.innerHTML = this.renderCustomers(data.data);
            } else {
                contentEl.innerHTML = this.renderEmptyState(`ไม่มี${status}`, `ไม่พบข้อมูล${status}ในระบบ`);
            }
        } catch (error) {
            console.error(`Error loading ${status}:`, error);
            loadingEl.style.display = 'none';
            contentEl.innerHTML = this.renderErrorState('เกิดข้อผิดพลาดในการโหลดข้อมูล');
        }
    }

    async loadAllTasks(dateFilter = null) {
        const loadingEl = document.getElementById('follow-all-loading');
        const contentEl = document.getElementById('follow-all-content');
        
        loadingEl.style.display = 'flex';
        contentEl.innerHTML = '';

        try {
            let url = '../api/tasks/list.php';
            if (dateFilter) {
                url += `?Date=${dateFilter}`;
            }

            const response = await fetch(url);
            const data = await response.json();

            loadingEl.style.display = 'none';

            if (data.status === 'success' && data.data && data.data.length > 0) {
                contentEl.innerHTML = this.renderTasks(data.data, true);
            } else {
                contentEl.innerHTML = this.renderEmptyState('ไม่มีนัดหมาย', 'ไม่พบนัดหมายในช่วงเวลาที่เลือก');
            }
        } catch (error) {
            console.error('Error loading all tasks:', error);
            loadingEl.style.display = 'none';
            contentEl.innerHTML = this.renderErrorState('เกิดข้อผิดพลาดในการโหลดข้อมูล');
        }
    }

    async loadUnassignedCustomers() {
        const loadingEl = document.getElementById('unassigned-loading');
        const contentEl = document.getElementById('unassigned-content');
        
        loadingEl.style.display = 'flex';
        contentEl.innerHTML = '';

        try {
            const response = await fetch('../api/customers/list-simple.php?unassigned=true');
            const data = await response.json();

            loadingEl.style.display = 'none';

            if (data.status === 'success' && data.data && data.data.length > 0) {
                contentEl.innerHTML = this.renderCustomers(data.data);
            } else {
                contentEl.innerHTML = this.renderEmptyState('ไม่มีลูกค้ารอมอบหมาย', 'ลูกค้าทั้งหมดได้รับการมอบหมายแล้ว');
            }
        } catch (error) {
            console.error('Error loading unassigned customers:', error);
            loadingEl.style.display = 'none';
            contentEl.innerHTML = this.renderErrorState('เกิดข้อผิดพลาดในการโหลดข้อมูล');
        }
    }

    renderTasks(tasks, showAllDates = false) {
        if (!tasks || tasks.length === 0) {
            return '<div class="text-center py-4 text-muted">ไม่พบงานในวันนี้</div>';
        }

        return `
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 25%;">ชื่อลูกค้า</th>
                            <th style="width: 15%;">วันที่นัดหมาย</th>
                            <th style="width: 12%;">เบอร์โทร</th>
                            <th style="width: 35%;">รายละเอียดงาน</th>
                            <th style="width: 8%;">สถานะ</th>
                            <th style="width: 5%;">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${tasks.map(task => `
                            <tr>
                                <td>
                                    <div class="d-flex flex-column">
                                        <strong class="text-primary">${this.escapeHtml(task.CustomerName || task.CustomerCode)}</strong>
                                        <small class="text-muted">${task.CustomerCode}</small>
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex flex-column">
                                        <span class="fw-bold">${this.formatDate(task.FollowupDate)}</span>
                                        <small class="text-muted">${this.formatTime(task.FollowupDate)}</small>
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-phone text-success me-1"></i>
                                        <span class="small">${this.escapeHtml(task.CustomerTel || '-')}</span>
                                    </div>
                                </td>
                                <td>
                                    <div class="task-details">
                                        <strong class="text-dark">${task.Remarks ? this.escapeHtml(task.Remarks) : 'ติดตามลูกค้าทั่วไป'}</strong>
                                        ${task.TaskType ? `<br><small class="text-muted"><i class="fas fa-tag"></i> ${this.escapeHtml(task.TaskType)}</small>` : ''}
                                    </div>
                                </td>
                                <td>
                                    <span class="badge ${this.getTaskStatusBadgeClass(task.Status || 'รอดำเนินการ')}">${this.getTaskStatusText(task.Status || 'รอดำเนินการ')}</span>
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <button class="btn btn-sm btn-outline-primary" onclick="viewCustomerDetail('${task.CustomerCode}')" title="ดูข้อมูลลูกค้า">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-success" onclick="callCustomer('${task.CustomerTel}')" title="โทรหาลูกค้า">
                                            <i class="fas fa-phone"></i>
                                        </button>
                                        ${(task.Status || 'รอดำเนินการ') === 'รอดำเนินการ' ? `<button class="btn btn-sm btn-outline-warning" onclick="completeTask('${task.id}')" title="ทำเสร็จ"><i class="fas fa-check"></i></button>` : ''}
                                    </div>
                                </td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            </div>
        `;
    }

    renderCustomers(customers) {
        if (!customers || customers.length === 0) {
            return '<div class="text-center py-4 text-muted">ไม่พบข้อมูลลูกค้า</div>';
        }

        return `
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>รหัสลูกค้า</th>
                            <th>ชื่อลูกค้า</th>
                            <th>เบอร์โทร</th>
                            <th>จังหวัด</th>
                            <th>สถานะ</th> 
                            <th>Grade</th>
                            <th>Temperature</th>
                            <th>ยอดซื้อ</th>
                            <th>Sales</th>
                            <th>วันที่อัปเดต</th>
                            <th>การจัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${customers.map(customer => `
                            <tr>
                                <td><span class="badge bg-primary">${customer.CustomerCode}</span></td>
                                <td><strong>${this.escapeHtml(customer.CustomerName)}</strong></td>
                                <td><i class="fas fa-phone text-primary"></i> ${this.escapeHtml(customer.CustomerTel)}</td>
                                <td>${this.escapeHtml(customer.CustomerProvince || '-')}</td>
                                <td><span class="badge ${this.getStatusBadgeClass(customer.CustomerStatus)}">${this.escapeHtml(customer.CustomerStatus)}</span></td>
                                <td><span class="badge ${this.getGradeBadgeClass(customer.CustomerGrade || 'D')}">${customer.CustomerGrade || 'D'}</span></td>
                                <td><span class="badge ${this.getTemperatureBadgeClass(customer.CustomerTemperature || 'WARM')}">${customer.CustomerTemperature || 'WARM'}</span></td>
                                <td><strong>${customer.TotalPurchase ? '฿' + parseFloat(customer.TotalPurchase).toLocaleString() : '฿0'}</strong></td>
                                <td>${customer.Sales ? this.escapeHtml(customer.Sales) : '<span class="text-muted">ยังไม่มอบหมาย</span>'}</td>
                                <td>${this.formatDate(customer.ModifiedDate || customer.CreatedDate)}</td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <button class="btn btn-sm btn-outline-primary" onclick="viewCustomerDetail('${customer.CustomerCode}')" title="ดูข้อมูลลูกค้า">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-success" onclick="callCustomer('${customer.CustomerTel}')" title="โทรหาลูกค้า">
                                            <i class="fas fa-phone"></i>
                                        </button>
                                        ${!customer.Sales ? `<button class="btn btn-sm btn-outline-warning" onclick="assignCustomer('${customer.CustomerCode}')" title="มอบหมาย"><i class="fas fa-user-plus"></i></button>` : ''}
                                    </div>
                                </td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            </div>
        `;
    }

    renderEmptyState(title, message) {
        return `
            <div class="empty-state">
                <h3>${title}</h3>
                <p>${message}</p>
            </div>
        `;
    }

    renderErrorState(message) {
        return `
            <div class="empty-state">
                <h3>เกิดข้อผิดพลาด</h3>
                <p>${message}</p>
                <button class="btn btn-primary" onclick="dashboard.loadTabData(dashboard.currentTab)">ลองใหม่</button>
            </div>
        `;
    }

    renderPagination(currentPage, totalPages, functionName) {
        if (totalPages <= 1) return '';
        
        let pagination = '<nav aria-label="Page navigation" class="mt-3"><ul class="pagination pagination-sm justify-content-center">';
        
        // Previous button
        if (currentPage > 1) {
            pagination += `<li class="page-item"><a class="page-link" href="#" onclick="${functionName}(${currentPage - 1}); return false;">ก่อนหน้า</a></li>`;
        }
        
        // Page numbers (show max 5 pages)
        const startPage = Math.max(1, currentPage - 2);
        const endPage = Math.min(totalPages, currentPage + 2);
        
        for (let i = startPage; i <= endPage; i++) {
            const activeClass = i === currentPage ? 'active' : '';
            pagination += `<li class="page-item ${activeClass}"><a class="page-link" href="#" onclick="${functionName}(${i}); return false;">${i}</a></li>`;
        }
        
        // Next button
        if (currentPage < totalPages) {
            pagination += `<li class="page-item"><a class="page-link" href="#" onclick="${functionName}(${currentPage + 1}); return false;">ถัดไป</a></li>`;
        }
        
        pagination += '</ul></nav>';
        return pagination;
    }

    getStatusClass(status) {
        switch(status) {
            case 'ลูกค้าใหม่':
                return 'status-new';
            case 'ลูกค้าติดตาม':
                return 'status-follow';
            case 'ลูกค้าเก่า':
                return 'status-old';
            default:
                return '';
        }
    }

    getStatusBadgeClass(status) {
        switch(status) {
            case 'ลูกค้าใหม่':
                return 'bg-success';
            case 'ลูกค้าติดตาม':
                return 'bg-warning';
            case 'ลูกค้าเก่า':
                return 'bg-info';
            default:
                return 'bg-secondary';
        }
    }

    getTaskStatusBadgeClass(status) {
        switch(status) {
            case 'รอดำเนินการ':
                return 'bg-warning';
            case 'เสร็จสิ้น':
                return 'bg-success';
            default:
                return 'bg-secondary';
        }
    }

    getGradeBadgeClass(grade) {
        switch(grade) {
            case 'A':
                return 'bg-success';
            case 'B':
                return 'bg-primary';
            case 'C':
                return 'bg-warning';
            case 'D':
                return 'bg-danger';
            default:
                return 'bg-secondary';
        }
    }

    getTemperatureBadgeClass(temperature) {
        switch(temperature) {
            case 'HOT':
                return 'bg-danger';
            case 'WARM':
                return 'bg-warning';
            case 'COLD':
                return 'bg-secondary';
            default:
                return 'bg-info';
        }
    }

    setupEventListeners() {
        // Search functionality
        ['new', 'follow', 'old'].forEach(type => {
            const searchInput = document.getElementById(`${type}-search`);
            if (searchInput) {
                searchInput.addEventListener('input', (e) => {
                    this.handleSearch(type, e.target.value);
                });
            }
        });

        // Date filter for all tasks
        const dateFilter = document.getElementById('task-date-filter');
        if (dateFilter) {
            dateFilter.addEventListener('change', (e) => {
                this.loadAllTasks(e.target.value);
            });
        }
    }

    handleSearch(type, query) {
        // Clear previous timeout
        if (this.searchTimeouts[type]) {
            clearTimeout(this.searchTimeouts[type]);
        }

        // Set new timeout for search
        this.searchTimeouts[type] = setTimeout(() => {
            this.performSearch(type, query);
        }, 300);
    }

    async performSearch(type, query) {
        const statusMap = {
            'new': 'ลูกค้าใหม่',
            'follow': 'ลูกค้าติดตาม',
            'old': 'ลูกค้าเก่า'
        };

        const loadingEl = document.getElementById(`${type}-loading`);
        const contentEl = document.getElementById(`${type}-content`);
        
        loadingEl.style.display = 'flex';
        contentEl.innerHTML = '';

        try {
            let url = `./api/customers/list-simple.php?customer_status=${encodeURIComponent(statusMap[type])}`;
            if (query.trim()) {
                url += `&search=${encodeURIComponent(query)}`;
            }

            const response = await fetch(url);
            const data = await response.json();

            loadingEl.style.display = 'none';

            if (data.status === 'success' && data.data && data.data.length > 0) {
                contentEl.innerHTML = this.renderCustomers(data.data);
            } else {
                const message = query.trim() ? 
                    `ไม่พบผลการค้นหา "${query}"` : 
                    `ไม่มี${statusMap[type]}`;
                contentEl.innerHTML = this.renderEmptyState(message, 'ลองใช้คำค้นหาอื่น');
            }
        } catch (error) {
            console.error(`Error searching ${type}:`, error);
            loadingEl.style.display = 'none';
            contentEl.innerHTML = this.renderErrorState('เกิดข้อผิดพลาดในการค้นหา');
        }
    }

    formatDate(dateString) {
        if (!dateString) return '';
        const date = new Date(dateString);
        const day = String(date.getDate()).padStart(2, '0');
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const year = date.getFullYear();
        return `${day}/${month}/${year}`;
    }

    formatDateTime(dateString) {
        if (!dateString) return '';
        const date = new Date(dateString);
        const day = String(date.getDate()).padStart(2, '0');
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const year = date.getFullYear();
        const hours = String(date.getHours()).padStart(2, '0');
        const minutes = String(date.getMinutes()).padStart(2, '0');
        return `${day}/${month}/${year} ${hours}:${minutes}`;
    }
    
    formatTime(dateString) {
        if (!dateString) return '';
        const date = new Date(dateString);
        const hours = String(date.getHours()).padStart(2, '0');
        const minutes = String(date.getMinutes()).padStart(2, '0');
        return `${hours}:${minutes}`;
    }
    
    getTaskStatusText(status) {
        const statusMap = {
            'รอดำเนินการ': 'รอ',
            'เสร็จสิ้น': 'เสร็จ',
            'กำลังดำเนินการ': 'กำลังทำ',
            'ยกเลิก': 'ยกเลิก'
        };
        return statusMap[status] || status;
    }

    escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

// Global functions for button clicks
function refreshTasks() {
    dashboard.loadTodayTasks();
}

function refreshCustomers(type) {
    const statusMap = {
        'new': 'ลูกค้าใหม่',
        'follow': 'ลูกค้าติดตาม',
        'old': 'ลูกค้าเก่า'
    };
    dashboard.loadCustomers(statusMap[type], type);
}

function refreshAllTasks() {
    const dateFilter = document.getElementById('task-date-filter').value;
    dashboard.loadAllTasks(dateFilter);
}

function filterTasksByDate() {
    const dateFilter = document.getElementById('task-date-filter').value;
    dashboard.loadAllTasks(dateFilter);
}

function searchCustomers(type) {
    const searchInput = document.getElementById(`${type}-search`);
    dashboard.handleSearch(type, searchInput.value);
}

function addNewCustomer() {
    // This will be implemented in later tasks
    alert('ฟังก์ชันเพิ่มลูกค้าใหม่จะพัฒนาในงานถัดไป');
}

function refreshUnassigned() {
    dashboard.loadUnassignedCustomers();
}

function bulkAssign() {
    // This will be implemented for bulk assignment functionality
    alert('ฟังก์ชันมอบหมายแบบกลุ่มจะพัฒนาในงานถัดไป');
}

function assignCustomer(customerCode) {
    // This will be implemented for individual customer assignment
    alert(`มอบหมายลูกค้า ${customerCode} จะพัฒนาในงานถัดไป`);
}

function callCustomer(phone) {
    if (phone && phone !== '') {
        // Try to open phone dialer on mobile devices
        window.open(`tel:${phone}`, '_self');
    } else {
        alert('ไม่พบหมายเลขโทรศัพท์');
    }
}

function completeTask(taskId) {
    if (confirm('ต้องการทำเครื่องหมายงานนี้เป็น "เสร็จสิ้น" หรือไม่?')) {
        // This will be implemented to update task status
        alert(`งาน ID: ${taskId} จะถูกอัปเดตเป็น "เสร็จสิ้น" (ระบบจะพัฒนาในงานถัดไป)`);
        // Refresh tasks after update
        dashboard.loadTodayTasks();
    }
}

function viewCustomerDetail(customerCode) {
    // Navigate to customer detail page
    window.location.href = `customer_detail.php?code=${encodeURIComponent(customerCode)}`;
}

// Initialize dashboard when DOM is loaded
let dashboard;
document.addEventListener('DOMContentLoaded', function() {
    dashboard = new Dashboard();
});