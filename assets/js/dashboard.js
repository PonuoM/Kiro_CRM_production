// Dashboard JavaScript functionality

class Dashboard {
    constructor() {
        this.currentTab = 'do';
        this.searchTimeouts = {};
        this.renderCache = new Map(); // Performance: Cache rendered content
        this.lastDataHash = new Map(); // Performance: Track data changes
        this.intersectionObserver = null; // Performance: Lazy loading
        this.init();
    }

    init() {
        this.setupTabNavigation();
        this.loadInitialData();
        this.setupEventListeners();
        this.initPerformanceOptimizations();
    }

    initPerformanceOptimizations() {
        // Setup intersection observer for lazy loading
        if ('IntersectionObserver' in window) {
            this.intersectionObserver = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const element = entry.target;
                        if (element.dataset.lazyLoad) {
                            this.loadLazyContent(element);
                        }
                    }
                });
            }, { threshold: 0.1 });
        }

        // Setup performance monitoring
        this.startPerformanceMonitoring();
    }

    // Performance: Generate hash for data comparison
    generateDataHash(data) {
        if (!data) return '';
        return JSON.stringify(data).split('').reduce((a, b) => {
            a = ((a << 5) - a) + b.charCodeAt(0);
            return a & a;
        }, 0).toString();
    }

    // Performance: Check if re-render is needed
    shouldRerender(tabType, data) {
        const currentHash = this.generateDataHash(data);
        const lastHash = this.lastDataHash.get(tabType);
        
        if (currentHash !== lastHash) {
            this.lastDataHash.set(tabType, currentHash);
            return true;
        }
        return false;
    }

    // Performance: Get cached content if available
    getCachedContent(cacheKey) {
        return this.renderCache.get(cacheKey);
    }

    // Performance: Set cached content
    setCachedContent(cacheKey, content) {
        // Limit cache size to prevent memory issues
        if (this.renderCache.size > 10) {
            const firstKey = this.renderCache.keys().next().value;
            this.renderCache.delete(firstKey);
        }
        this.renderCache.set(cacheKey, content);
    }

    // Performance monitoring
    startPerformanceMonitoring() {
        this.performanceMetrics = {
            renderTimes: [],
            apiCalls: [],
            memoryUsage: []
        };

        // Monitor memory usage periodically
        if (performance.memory) {
            setInterval(() => {
                this.performanceMetrics.memoryUsage.push({
                    timestamp: Date.now(),
                    used: performance.memory.usedJSHeapSize,
                    total: performance.memory.totalJSHeapSize
                });
                
                // Keep only last 50 measurements
                if (this.performanceMetrics.memoryUsage.length > 50) {
                    this.performanceMetrics.memoryUsage.shift();
                }
            }, 30000); // Every 30 seconds
        }
    }

    // Performance: Measure render time
    measureRenderTime(operation, fn) {
        const start = performance.now();
        const result = fn();
        const end = performance.now();
        
        this.performanceMetrics.renderTimes.push({
            operation,
            duration: end - start,
            timestamp: Date.now()
        });
        
        // Keep only last 100 measurements
        if (this.performanceMetrics.renderTimes.length > 100) {
            this.performanceMetrics.renderTimes.shift();
        }
        
        // Log slow operations
        if (end - start > 100) {
            console.warn(`Slow render operation: ${operation} took ${(end - start).toFixed(2)}ms`);
        }
        
        return result;
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
            // Use Enhanced Dashboard API from Story 3.1
            const url = `../api/dashboard/summary.php?include_customers=true&limit=50`;
            console.log('Loading customers with enhanced API:', url);
            
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
            console.log('Enhanced customer data loaded:', data);

            loadingEl.style.display = 'none';

            if (data.status === 'success' && data.data && data.data.customers && data.data.customers.length > 0) {
                // Filter customers by status if needed
                let customers = data.data.customers;
                if (status !== 'all') {
                    customers = customers.filter(customer => customer.CustomerStatus === status);
                }
                
                if (customers.length > 0) {
                    // Always render on tab switch - fix for data disappearing issue
                    const renderedContent = this.measureRenderTime(`renderEnhancedCustomers-${tabType}`, () => {
                        return this.renderEnhancedCustomers(customers);
                    });
                    contentEl.innerHTML = renderedContent;
                    
                    // Setup lazy loading for images if any
                    this.setupLazyLoading(contentEl);
                    
                    // Update data hash for future comparison
                    this.lastDataHash.set(tabType, this.generateDataHash(customers));
                } else {
                    contentEl.innerHTML = this.renderEmptyState(`ไม่มี${status}`, `ไม่พบข้อมูล${status}ในระบบ`);
                }
            } else {
                contentEl.innerHTML = this.renderEmptyState(`ไม่มี${status}`, `ไม่พบข้อมูล${status}ในระบบ`);
            }
        } catch (error) {
            console.error(`Error loading ${status}:`, error);
            loadingEl.style.display = 'none';
            
            // Fallback to original API
            console.log('Falling back to original API...');
            await this.loadCustomersFallback(status, tabType);
        }
    }

    async loadCustomersFallback(status, tabType) {
        const loadingEl = document.getElementById(`${tabType}-loading`);
        const contentEl = document.getElementById(`${tabType}-content`);
        
        try {
            const url = `../api/customers/list-simple.php?customer_status=${encodeURIComponent(status)}`;
            const response = await fetch(url);
            const data = await response.json();

            if (data.status === 'success' && data.data && data.data.length > 0) {
                contentEl.innerHTML = this.renderCustomers(data.data);
            } else {
                contentEl.innerHTML = this.renderEmptyState(`ไม่มี${status}`, `ไม่พบข้อมูล${status}ในระบบ`);
            }
        } catch (error) {
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
            <div class="premium-table">
                <table class="table">
                    <thead>
                        <tr>
                            <th>ลูกค้า</th>
                            <th>วันที่นัดหมาย</th>
                            <th>เบอร์โทร</th>
                            <th>รายละเอียดงาน</th>
                            <th>สถานะ</th>
                            <th>การจัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${tasks.map(task => {
                            const isUrgent = this.isTaskUrgent(task.FollowupDate);
                            const isOverdue = this.isTaskOverdue(task.FollowupDate);
                            const rowClass = isOverdue ? 'row-hot' : (isUrgent ? 'row-urgent' : 'row-normal');
                            
                            return `
                            <tr class="${rowClass}">
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        ${isOverdue ? '<span class="priority-indicator priority-hot"></span>' : ''}
                                        ${isUrgent && !isOverdue ? '<span class="priority-indicator priority-urgent"></span>' : ''}
                                        <div>
                                            <div class="fw-bold ${isOverdue ? 'customer-name-hot' : (isUrgent ? 'customer-name-urgent' : 'text-primary')}">${this.escapeHtml(task.CustomerName || task.CustomerCode)}</div>
                                            <small class="text-muted">${task.CustomerCode}</small>
                                        </div>
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
                            `;
                        }).join('')}
                    </tbody>
                </table>
            </div>
        `;
    }

    renderEnhancedCustomers(customers) {
        if (!customers || customers.length === 0) {
            return '<div class="text-center py-4 text-muted">ไม่พบข้อมูลลูกค้า</div>';
        }

        return `
            <div class="premium-table">
                <table class="table">
                    <thead>
                        <tr>
                            <th>ลูกค้า</th>
                            <th>เบอร์โทร</th>
                            <th>สถานะ</th>
                            <th>เวลาที่เหลือ</th>
                            <th>Temperature</th>
                            <th>Grade</th>
                            <th>วันที่ได้รับ</th>
                            <th>Sales</th>
                            <th>การจัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${customers.map(customer => {
                            const isHot = customer.CustomerTemperature === 'HOT';
                            const isUrgent = customer.time_remaining_days <= 5;
                            const isOverdue = customer.time_remaining_days < 0;
                            
                            // Enhanced row classification logic
                            let rowClass = '';
                            let customerNameClass = '';
                            
                            if (isHot) {
                                rowClass = 'row-hot';
                                customerNameClass = 'customer-name-hot';
                            } else if (isUrgent || isOverdue) {
                                rowClass = 'row-urgent';
                                customerNameClass = 'customer-name-urgent';
                            } else {
                                rowClass = 'row-normal';
                            }
                            
                            return `
                                <tr class="${rowClass}">
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            ${this.renderPriorityIndicator(customer)}
                                            <div>
                                                <div class="fw-bold text-primary ${customerNameClass}">${this.escapeHtml(customer.CustomerName)}</div>
                                                <small class="text-muted">${customer.CustomerCode}</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center gap-1">
                                            <i class="fas fa-phone text-success"></i>
                                            <span>${this.escapeHtml(customer.CustomerTel)}</span>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge ${this.getStatusBadgeClass(customer.CustomerStatus)}">${this.escapeHtml(customer.CustomerStatus)}</span>
                                    </td>
                                    <td>
                                        ${this.renderTimeProgress(customer.time_remaining_days, customer.time_status)}
                                    </td>
                                    <td>
                                        ${this.renderTemperatureBadge(customer.CustomerTemperature)}
                                    </td>
                                    <td>
                                        <span class="badge ${this.getGradeBadgeClass(customer.CustomerGrade || 'D')}">${customer.CustomerGrade || 'D'}</span>
                                    </td>
                                    <td>
                                        <small class="text-muted">${this.formatReceivedDate(customer)}</small>
                                    </td>
                                    <td>
                                        ${customer.Sales ? this.escapeHtml(customer.Sales) : '<span class="text-muted">ยังไม่มอบหมาย</span>'}
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <button class="btn btn-sm btn-outline-primary" onclick="viewCustomerDetail('${customer.CustomerCode}')" title="ดูข้อมูลลูกค้า">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-success" onclick="callCustomer('${customer.CustomerTel}')" title="โทรหาลูกค้า">
                                                <i class="fas fa-phone"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            `;
                        }).join('')}
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
        if (!dateString || dateString === null || dateString === 'null' || dateString === '') {
            return '';
        }
        
        try {
            const date = new Date(dateString);
            // Check if date is valid
            if (isNaN(date.getTime())) {
                console.log('Invalid date:', dateString);
                return '';
            }
            
            const day = String(date.getDate()).padStart(2, '0');
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const year = date.getFullYear();
            return `${day}/${month}/${year}`;
        } catch (error) {
            console.log('Date formatting error:', error, 'for date:', dateString);
            return '';
        }
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

    renderTimeProgress(timeRemaining, timeStatus) {
        if (timeRemaining === null || timeRemaining === undefined) {
            return '<span class="text-muted">ไม่มีข้อมูล</span>';
        }

        // Determine color class and progress percent based on time remaining
        let colorClass = 'time-progress-green';
        let progressPercent = 100;
        let badgeClass = 'badge bg-success';
        
        if (timeRemaining < 0) {
            colorClass = 'time-progress-red';
            badgeClass = 'badge bg-danger';
            progressPercent = 0;
        } else if (timeRemaining <= 3) {
            colorClass = 'time-progress-red';
            badgeClass = 'badge bg-danger';
            progressPercent = Math.max(10, (timeRemaining / 30) * 100);
        } else if (timeRemaining <= 7) {
            colorClass = 'time-progress-yellow';
            badgeClass = 'badge bg-warning';
            progressPercent = Math.max(20, (timeRemaining / 30) * 100);
        } else if (timeRemaining <= 14) {
            colorClass = 'time-progress-yellow';
            badgeClass = 'badge bg-warning';
            progressPercent = Math.max(30, (timeRemaining / 30) * 100);
        } else {
            colorClass = 'time-progress-green';
            badgeClass = 'badge bg-success';
            progressPercent = Math.min(100, (timeRemaining / 30) * 100);
        }

        // Format display text - Fixed the logic to prevent "เลย 999 วัน"
        let displayText = '';
        if (timeRemaining < 0) {
            // Show overdue days with proper limit
            const overdueDays = Math.abs(timeRemaining);
            if (overdueDays > 365) {
                displayText = 'เลยมากกว่า 1 ปี';
            } else if (overdueDays > 90) {
                displayText = `เลย ${overdueDays} วัน`;
            } else {
                displayText = `เลย ${overdueDays} วัน`;
            }
        } else if (timeRemaining === 0) {
            displayText = 'วันสุดท้าย';
        } else if (timeRemaining === 1) {
            displayText = 'เหลือ 1 วัน';
        } else {
            displayText = `เหลือ ${timeRemaining} วัน`;
        }

        return `
            <div class="d-flex flex-column align-items-center">
                <span class="${badgeClass} mb-1">${displayText}</span>
                <div class="progress" style="width: 60px; height: 6px;">
                    <div class="progress-bar ${colorClass === 'time-progress-red' ? 'bg-danger' : colorClass === 'time-progress-yellow' ? 'bg-warning' : 'bg-success'}" 
                         style="width: ${progressPercent}%"></div>
                </div>
            </div>
        `;
    }

    renderTemperatureBadge(temperature) {
        if (!temperature) {
            return '<span class="temp-badge temp-cold">COLD</span>';
        }

        const tempClass = `temp-${temperature.toLowerCase()}`;
        const tempText = temperature.toUpperCase();
        
        // Add icon based on temperature
        let icon = '';
        switch (temperature.toUpperCase()) {
            case 'HOT':
                icon = '🔥';
                break;
            case 'WARM':
                icon = '⚡';
                break;
            case 'COLD':
                icon = '❄️';
                break;
            case 'FROZEN':
                icon = '🧊';
                break;
            default:
                icon = '🌡️';
        }

        return `<span class="temp-badge ${tempClass}">${icon} ${tempText}</span>`;
    }

    renderPriorityIndicator(customer) {
        const isHot = customer.CustomerTemperature === 'HOT';
        const isUrgent = customer.time_remaining_days <= 5;
        const isOverdue = customer.time_remaining_days < 0;
        
        // Determine priority level and visual indicator
        if (isHot) {
            return '<span class="priority-indicator priority-hot" title="ลูกค้า HOT - ความสำคัญสูงสุด"></span>';
        } else if (isOverdue) {
            return '<span class="priority-indicator priority-urgent" title="เลยกำหนดเวลาแล้ว - ติดตามด่วน"></span>';
        } else if (isUrgent) {
            return '<span class="priority-indicator priority-urgent" title="เหลือเวลาน้อย - ต้องติดตาม"></span>';
        } else {
            return '';
        }
    }

    // Performance: Setup lazy loading for elements
    setupLazyLoading(container) {
        if (!this.intersectionObserver) return;
        
        const lazyElements = container.querySelectorAll('[data-lazy-load]');
        lazyElements.forEach(element => {
            this.intersectionObserver.observe(element);
        });
    }

    // Performance: Load lazy content
    loadLazyContent(element) {
        // Implement lazy loading logic if needed
        this.intersectionObserver.unobserve(element);
    }

    // Performance: Optimized batch DOM updates
    batchDOMUpdates(updates) {
        // Use requestAnimationFrame for smooth updates
        return new Promise(resolve => {
            requestAnimationFrame(() => {
                updates.forEach(update => update());
                resolve();
            });
        });
    }

    // Enhanced error handling with user feedback
    handleEnhancedError(error, context, userMessage = 'เกิดข้อผิดพลาดในการโหลดข้อมูล') {
        console.error(`Error in ${context}:`, error);
        
        // Log error for monitoring
        this.performanceMetrics.apiCalls.push({
            context,
            error: error.message,
            timestamp: Date.now(),
            status: 'error'
        });

        // Show user-friendly error message
        return this.renderErrorState(userMessage, error.name || 'UnknownError');
    }

    // Enhanced error state with retry option
    renderErrorState(message, errorType = '') {
        return `
            <div class="empty-state">
                <div class="alert alert-danger border-start border-danger border-4" role="alert">
                    <h4 class="alert-heading">
                        <i class="fas fa-exclamation-triangle"></i> เกิดข้อผิดพลาด
                    </h4>
                    <p class="mb-3">${message}</p>
                    ${errorType ? `<small class="text-muted">Error Type: ${errorType}</small>` : ''}
                    <hr>
                    <div class="d-flex gap-2">
                        <button class="btn btn-outline-primary btn-sm" onclick="dashboard.loadTabData(dashboard.currentTab)">
                            <i class="fas fa-sync-alt"></i> ลองใหม่
                        </button>
                        <button class="btn btn-outline-secondary btn-sm" onclick="dashboard.showPerformanceInfo()">
                            <i class="fas fa-info-circle"></i> ข้อมูลประสิทธิภาพ
                        </button>
                    </div>
                </div>
            </div>
        `;
    }

    // Performance info for debugging
    showPerformanceInfo() {
        const metrics = this.performanceMetrics;
        const avgRenderTime = metrics.renderTimes.length > 0 
            ? metrics.renderTimes.reduce((sum, m) => sum + m.duration, 0) / metrics.renderTimes.length 
            : 0;
            
        const recentErrors = metrics.apiCalls.filter(call => call.status === 'error' && Date.now() - call.timestamp < 300000);
        
        console.group('Dashboard Performance Metrics');
        console.log('Average render time:', avgRenderTime.toFixed(2) + 'ms');
        console.log('Recent errors:', recentErrors.length);
        console.log('Cache size:', this.renderCache.size);
        console.log('Memory usage:', performance.memory ? 
            `${(performance.memory.usedJSHeapSize / 1024 / 1024).toFixed(2)}MB` : 'Not available');
        console.groupEnd();
        
        alert(`ข้อมูลประสิทธิภาพ:\n- เวลาเฉลี่ยในการแสดงผล: ${avgRenderTime.toFixed(2)}ms\n- ข้อผิดพลาดล่าสุด: ${recentErrors.length} รายการ\n- ข้อมูลแคช: ${this.renderCache.size} รายการ`);
    }

    escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Helper methods for task urgency checks
    isTaskUrgent(followupDate) {
        if (!followupDate) return false;
        const now = new Date();
        const taskDate = new Date(followupDate);
        const diffHours = (taskDate - now) / (1000 * 60 * 60);
        return diffHours <= 24 && diffHours > 0; // Urgent if within 24 hours
    }

    isTaskOverdue(followupDate) {
        if (!followupDate) return false;
        const now = new Date();
        const taskDate = new Date(followupDate);
        return taskDate < now; // Overdue if past due
    }

    // Format received date with proper fallback and debugging
    formatReceivedDate(customer) {
        // Try multiple date sources with proper priority
        let dateToFormat = null;
        let source = '';
        
        // Priority: assign_date > AssignDate > created_date > CreatedDate (API can return both formats)
        if (customer.assign_date && customer.assign_date !== null && customer.assign_date !== 'null') {
            dateToFormat = customer.assign_date;
            source = 'มอบหมาย';
        } else if (customer.AssignDate && customer.AssignDate !== null && customer.AssignDate !== 'null') {
            dateToFormat = customer.AssignDate;
            source = 'มอบหมาย';
        } else if (customer.created_date && customer.created_date !== null && customer.created_date !== 'null') {
            dateToFormat = customer.created_date;
            source = 'สร้าง';
        } else if (customer.CreatedDate && customer.CreatedDate !== null && customer.CreatedDate !== 'null') {
            dateToFormat = customer.CreatedDate;
            source = 'สร้าง';
        }
        
        if (!dateToFormat) {
            // Debug log for missing dates only if in development mode
            if (window.location.hostname === 'localhost') {
                console.log('No valid date found for customer:', customer.CustomerCode, {
                    assign_date: customer.assign_date,
                    AssignDate: customer.AssignDate,
                    created_date: customer.created_date,
                    CreatedDate: customer.CreatedDate
                });
            }
            return '<div class="text-center"><span class="text-muted">ไม่มีข้อมูล</span></div>';
        }
        
        const formatted = this.formatDate(dateToFormat);
        if (!formatted) {
            console.log('Date formatting failed for:', dateToFormat, 'from', source);
            return '<span class="text-warning">รูปแบบวันที่ไม่ถูกต้อง</span>';
        }
        
        // Return formatted date with source indicator
        return `<div class="text-center">
                    <div class="fw-bold text-primary">${formatted}</div>
                    <small class="text-muted">(${source})</small>
                </div>`;
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