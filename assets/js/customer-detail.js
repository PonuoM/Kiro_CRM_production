// Customer Detail Page JavaScript

class CustomerDetail {
    constructor(customerCode, currentUser) {
        this.customerCode = customerCode;
        this.currentUser = currentUser;
        this.currentTab = 'call-history';
        
        // Initialize submission states for double-submit prevention
        this.isSubmittingCallLog = false;
        this.isSubmittingTask = false;
        this.isSubmittingOrder = false;
        
        this.init();
    }

    init() {
        this.setupTabNavigation();
        this.setupFormHandlers();
        this.loadCustomerInfo();
        this.loadInitialHistory();
        this.hideLoadingOverlay();
        
        // Load products data immediately for the order form
        loadProductsData();
    }

    hideLoadingOverlay() {
        const overlay = document.getElementById('loading-overlay');
        if (overlay) {
            overlay.style.display = 'none';
        }
    }

    setupTabNavigation() {
        const tabButtons = document.querySelectorAll('.tab-btn');
        const tabContents = document.querySelectorAll('.tab-content');

        tabButtons.forEach(button => {
            button.addEventListener('click', () => {
                const tabId = button.getAttribute('data-tab');
                this.switchTab(tabId);
            });
        });
    }

    switchTab(tabId) {
        // Update active tab button
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        document.querySelector(`[data-tab="${tabId}"]`).classList.add('active');

        // Update active tab content
        document.querySelectorAll('.tab-content').forEach(content => {
            content.classList.remove('active');
        });
        document.getElementById(`${tabId}-tab`).classList.add('active');

        this.currentTab = tabId;
        this.loadTabHistory(tabId);
    }

    setupFormHandlers() {
        // Call log form
        const callLogForm = document.getElementById('call-log-form');
        if (callLogForm) {
            callLogForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.submitCallLog();
            });
        }

        // Task form
        const taskForm = document.getElementById('task-form');
        if (taskForm) {
            taskForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.submitTask();
            });
        }

        // Order form
        const orderForm = document.getElementById('order-form');
        if (orderForm) {
            orderForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.submitOrder();
            });
        }

        // Set default datetime values
        this.setDefaultDateTimes();
    }

    setDefaultDateTimes() {
        const now = new Date();
        const localDateTime = new Date(now.getTime() - now.getTimezoneOffset() * 60000).toISOString().slice(0, 16);
        const localDate = new Date(now.getTime() - now.getTimezoneOffset() * 60000).toISOString().slice(0, 10);
        
        const callDateInput = document.getElementById('call-date');
        const followupDateInput = document.getElementById('followup-date');
        const documentDateInput = document.getElementById('document-date');
        
        if (callDateInput) callDateInput.value = localDateTime;
        if (followupDateInput) followupDateInput.value = localDateTime;
        if (documentDateInput) documentDateInput.value = localDate;
    }

    async loadCustomerInfo() {
        try {
            const response = await fetch(`../api/customers/detail.php?code=${encodeURIComponent(this.customerCode)}`);
            const data = await response.json();

            if (data.status === 'success' && data.data && data.data.customer) {
                this.renderCustomerInfo(data.data.customer);
            } else {
                this.showError('ไม่พบข้อมูลลูกค้า');
            }
        } catch (error) {
            console.error('Error loading customer info:', error);
            this.showError('เกิดข้อผิดพลาดในการโหลดข้อมูลลูกค้า');
        }
    }

    renderCustomerInfo(customer) {
        const content = document.getElementById('customer-info-content');
        if (!content) return;

        content.innerHTML = `
            <div class="customer-info-grid">
                <div class="info-section">
                    <h4>ข้อมูลพื้นฐาน</h4>
                    <div class="info-item">
                        <span class="info-label">รหัสลูกค้า:</span>
                        <span class="info-value">${this.escapeHtml(customer.CustomerCode)}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">ชื่อลูกค้า:</span>
                        <span class="info-value">${this.escapeHtml(customer.CustomerName)}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">เบอร์โทร:</span>
                        <span class="info-value">${this.escapeHtml(customer.CustomerTel)}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">ที่อยู่:</span>
                        <span class="info-value">${this.escapeHtml(customer.CustomerAddress || '-')}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">จังหวัด:</span>
                        <span class="info-value">${this.escapeHtml(customer.CustomerProvince || '-')}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">รหัสไปรษณีย์:</span>
                        <span class="info-value">${this.escapeHtml(customer.CustomerPostalCode || '-')}</span>
                    </div>
                </div>
                
                <div class="info-section">
                    <h4>สถานะและการจัดการ</h4>
                    <div class="info-item">
                        <span class="info-label">สถานะลูกค้า:</span>
                        <span class="status-badge ${this.getStatusClass(customer.CustomerStatus)}">${this.escapeHtml(customer.CustomerStatus)}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">สถานะตะกร้า:</span>
                        <span class="info-value">${this.escapeHtml(customer.CartStatus || '-')}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Sales ดูแล:</span>
                        <span class="info-value">${this.escapeHtml(customer.Sales || 'ไม่ได้กำหนด')}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">วันที่มอบหมาย:</span>
                        <span class="info-value">${this.formatDate(customer.AssignDate)}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">วันที่สั่งซื้อล่าสุด:</span>
                        <span class="info-value">${this.formatDate(customer.OrderDate)}</span>
                    </div>
                </div>
                
                <div class="info-section">
                    <h4>ข้อมูลเพิ่มเติม</h4>
                    <div class="info-item">
                        <span class="info-label">ประเภทการเกษตร:</span>
                        <span class="info-value">${this.escapeHtml(customer.Agriculture || '-')}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">แท็ก:</span>
                        <span class="info-value">${this.escapeHtml(customer.Tags || '-')}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">วันที่สร้าง:</span>
                        <span class="info-value">${this.formatDateTime(customer.CreatedDate)}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">อัปเดตล่าสุด:</span>
                        <span class="info-value">${this.formatDateTime(customer.ModifiedDate)}</span>
                    </div>
                </div>
            </div>
        `;
        
        // Load customer intelligence after rendering basic info
        this.loadCustomerIntelligence();
    }

    async loadCustomerIntelligence() {
        try {
            const response = await fetch(`../api/customers/intelligence-safe.php?action=customer&customer_code=${encodeURIComponent(this.customerCode)}`);
            const data = await response.json();

            if (data.status === 'success' && data.data && data.data.customer) {
                this.renderCustomerIntelligence(data.data.customer, data.data.intelligence);
            } else if (data.status === 'setup_required') {
                this.renderIntelligenceSetup(data.message);
            } else {
                this.renderIntelligenceError('ไม่พบข้อมูล Intelligence');
            }
        } catch (error) {
            console.error('Error loading customer intelligence:', error);
            this.renderIntelligenceError('เกิดข้อผิดพลาดในการโหลดข้อมูล Intelligence');
        }
    }

    renderCustomerIntelligence(customer, intelligence) {
        const content = document.getElementById('customer-intelligence-content');
        if (!content) return;

        const gradeColors = {
            'A': '#28a745',
            'B': '#007bff', 
            'C': '#ffc107',
            'D': '#6c757d'
        };

        const tempColors = {
            'HOT': '#dc3545',
            'WARM': '#fd7e14',
            'COLD': '#6f42c1'
        };

        const tempIcons = {
            'HOT': '🔥',
            'WARM': '☀️',
            'COLD': '❄️'
        };

        content.innerHTML = `
            <div class="intelligence-grid">
                <div class="intelligence-section">
                    <h4>📊 Customer Grade</h4>
                    <div class="grade-display">
                        <div class="grade-badge-large" style="background: ${gradeColors[customer.CustomerGrade]}">
                            Grade ${customer.CustomerGrade}
                        </div>
                        <div class="grade-details">
                            <div class="detail-item">
                                <span class="label">Total Purchase:</span>
                                <span class="value">${this.formatMoney(customer.TotalPurchase)}</span>
                            </div>
                            <div class="detail-item">
                                <span class="label">Last Calculated:</span>
                                <span class="value">${this.formatDateTime(customer.GradeCalculatedDate)}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="intelligence-section">
                    <h4>🌡️ Customer Temperature</h4>
                    <div class="temperature-display">
                        <div class="temperature-badge-large" style="background: ${tempColors[customer.CustomerTemperature]}">
                            ${tempIcons[customer.CustomerTemperature]} ${customer.CustomerTemperature}
                        </div>
                        <div class="temperature-details">
                            <div class="detail-item">
                                <span class="label">Last Contact:</span>
                                <span class="value">${this.formatDate(customer.LastContactDate)}</span>
                            </div>
                            <div class="detail-item">
                                <span class="label">Contact Attempts:</span>
                                <span class="value">${customer.ContactAttempts || 0}</span>
                            </div>
                            <div class="detail-item">
                                <span class="label">Last Updated:</span>
                                <span class="value">${this.formatDateTime(customer.TemperatureUpdatedDate)}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="intelligence-section">
                    <h4>💡 Recommendations</h4>
                    <div class="recommendations">
                        ${intelligence.recommendations.map(rec => `
                            <div class="recommendation-item">
                                ${rec}
                            </div>
                        `).join('')}
                    </div>
                </div>

                <div class="intelligence-section">
                    <h4>📈 Grade Criteria</h4>
                    <div class="criteria-info">
                        <div class="current-amount">
                            Current: ${this.formatMoney(intelligence.grade_criteria.current_amount)}
                        </div>
                        <div class="criteria-list">
                            ${Object.entries(intelligence.grade_criteria.criteria).map(([grade, criteria]) => `
                                <div class="criteria-item ${customer.CustomerGrade === grade ? 'current' : ''}">
                                    <span class="grade">Grade ${grade}:</span>
                                    <span class="amount">${this.formatMoney(criteria.min)}+</span>
                                    <span class="description">${criteria.description}</span>
                                </div>
                            `).join('')}
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    renderIntelligenceError(message) {
        const content = document.getElementById('customer-intelligence-content');
        if (!content) return;

        content.innerHTML = `
            <div class="intelligence-error">
                <p>${message}</p>
                <button class="btn btn-sm btn-primary" onclick="window.customerDetail.loadCustomerIntelligence()">
                    ลองใหม่อีกครั้ง
                </button>
            </div>
        `;
    }

    renderIntelligenceSetup(message) {
        const content = document.getElementById('customer-intelligence-content');
        if (!content) return;

        content.innerHTML = `
            <div class="intelligence-setup">
                <div style="text-align: center; padding: 20px; background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 8px;">
                    <h4 style="color: #856404; margin-bottom: 15px;">🔧 Intelligence System Setup Required</h4>
                    <p style="color: #856404; margin-bottom: 15px;">${message}</p>
                    <div style="display: flex; gap: 10px; justify-content: center;">
                        <button class="btn btn-warning" onclick="window.customerDetail.setupIntelligenceSystem()">
                            🚀 Setup Intelligence System
                        </button>
                        <button class="btn btn-secondary" onclick="window.customerDetail.loadCustomerIntelligence()">
                            🔄 Check Again
                        </button>
                    </div>
                    <div style="margin-top: 15px; font-size: 0.9em; color: #856404;">
                        <strong>Note:</strong> This will add Grade and Temperature columns to your database.
                    </div>
                </div>
            </div>
        `;
    }

    async setupIntelligenceSystem() {
        if (!confirm('ต้องการติดตั้ง Intelligence System ใช่หรือไม่?\n\nการติดตั้งจะเพิ่ม columns และ functions ใหม่ในฐานข้อมูล')) {
            return;
        }

        try {
            const response = await fetch(`../api/customers/intelligence-safe.php?action=setup`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                }
            });

            const data = await response.json();

            if (data.status === 'success') {
                this.showSuccess('ติดตั้ง Intelligence System เรียบร้อยแล้ว! กำลังโหลดข้อมูล...');
                // Wait a bit then reload
                setTimeout(() => {
                    this.loadCustomerIntelligence();
                }, 1000);
            } else {
                this.showError('ไม่สามารถติดตั้ง Intelligence System ได้: ' + data.message);
            }
        } catch (error) {
            console.error('Error setting up intelligence system:', error);
            this.showError('เกิดข้อผิดพลาดในการติดตั้ง Intelligence System');
        }
    }

    async updateIntelligence() {
        try {
            // Update both grade and temperature using safe API
            const gradeResponse = await fetch(`../api/customers/intelligence-safe.php?action=update_grade`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ customer_code: this.customerCode })
            });

            const temperatureResponse = await fetch(`../api/customers/intelligence-safe.php?action=update_temperature`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ customer_code: this.customerCode })
            });

            const gradeData = await gradeResponse.json();
            const temperatureData = await temperatureResponse.json();

            if (gradeData.status === 'success' && temperatureData.status === 'success') {
                this.showSuccess('อัปเดต Intelligence เรียบร้อยแล้ว');
                // Reload intelligence data
                this.loadCustomerIntelligence();
            } else {
                this.showError('ไม่สามารถอัปเดต Intelligence ได้');
            }
        } catch (error) {
            console.error('Error updating intelligence:', error);
            this.showError('เกิดข้อผิดพลาดในการอัปเดต Intelligence');
        }
    }

    refreshIntelligence() {
        this.loadCustomerIntelligence();
    }

    formatMoney(amount) {
        if (!amount || amount === '0' || amount === 0) return '0 ฿';
        return new Intl.NumberFormat('th-TH', {
            style: 'currency',
            currency: 'THB',
            minimumFractionDigits: 0
        }).format(amount);
    }

    loadInitialHistory() {
        this.loadTabHistory('call-history');
    }

    loadTabHistory(tabId) {
        switch(tabId) {
            case 'call-history':
                this.loadCallHistory();
                break;
            case 'order-history':
                this.loadOrderHistory();
                break;
            case 'sales-history':
                this.loadSalesHistory();
                break;
            case 'task-history':
                this.loadTaskHistory();
                break;
        }
    }

    async loadCallHistory() {
        const content = document.getElementById('call-history-content');
        if (!content) return;

        content.innerHTML = '<div class="loading">กำลังโหลด...</div>';

        try {
            const response = await fetch(`../api/calls/history.php?customer=${encodeURIComponent(this.customerCode)}`);
            const data = await response.json();

            if (data.status === 'success' && data.data && data.data.length > 0) {
                content.innerHTML = this.renderCallHistory(data.data);
            } else {
                content.innerHTML = this.renderEmptyState('ไม่มีประวัติการโทร', 'ยังไม่มีการบันทึกการโทรสำหรับลูกค้านี้');
            }
        } catch (error) {
            console.error('Error loading call history:', error);
            content.innerHTML = this.renderErrorState('เกิดข้อผิดพลาดในการโหลดประวัติการโทร');
        }
    }

    async loadOrderHistory() {
        const content = document.getElementById('order-history-content');
        if (!content) return;

        content.innerHTML = '<div class="loading">กำลังโหลด...</div>';

        try {
            const response = await fetch(`../api/orders/history.php?customer=${encodeURIComponent(this.customerCode)}`);
            const data = await response.json();

            if (data.status === 'success' && data.data && data.data.length > 0) {
                content.innerHTML = this.renderOrderHistory(data.data);
            } else {
                content.innerHTML = this.renderEmptyState('ไม่มีประวัติคำสั่งซื้อ', 'ยังไม่มีคำสั่งซื้อสำหรับลูกค้านี้');
            }
        } catch (error) {
            console.error('Error loading order history:', error);
            content.innerHTML = this.renderErrorState('เกิดข้อผิดพลาดในการโหลดประวัติคำสั่งซื้อ');
        }
    }

    async loadSalesHistory() {
        const content = document.getElementById('sales-history-content');
        if (!content) return;

        content.innerHTML = '<div class="loading">กำลังโหลด...</div>';

        try {
            const response = await fetch(`../api/sales/history.php?action=customer&customer=${encodeURIComponent(this.customerCode)}`);
            const data = await response.json();

            if (data.status === 'success' && data.data) {
                const salesHistory = data.data || [];
                const currentAssignment = null;
                
                if (salesHistory.length > 0 || currentAssignment) {
                    content.innerHTML = this.renderSalesHistory(salesHistory, currentAssignment);
                } else {
                    content.innerHTML = this.renderEmptyState('ไม่มีประวัติ Sales', 'ยังไม่มีการมอบหมาย Sales สำหรับลูกค้านี้');
                }
            } else {
                content.innerHTML = this.renderEmptyState('ไม่มีประวัติ Sales', 'ยังไม่มีการมอบหมาย Sales สำหรับลูกค้านี้');
            }
        } catch (error) {
            console.error('Error loading sales history:', error);
            content.innerHTML = this.renderErrorState('เกิดข้อผิดพลาดในการโหลดประวัติ Sales');
        }
    }

    async loadTaskHistory() {
        const content = document.getElementById('task-history-content');
        if (!content) return;

        content.innerHTML = '<div class="loading">กำลังโหลด...</div>';

        try {
            const response = await fetch(`../api/tasks/list.php?CustomerCode=${encodeURIComponent(this.customerCode)}`);
            const data = await response.json();

            if (data.status === 'success' && data.data && data.data.length > 0) {
                content.innerHTML = this.renderTaskHistory(data.data);
            } else {
                content.innerHTML = this.renderEmptyState('ไม่มีประวัติงาน', 'ยังไม่มีงานหรือนัดหมายสำหรับลูกค้านี้');
            }
        } catch (error) {
            console.error('Error loading task history:', error);
            content.innerHTML = this.renderErrorState('เกิดข้อผิดพลาดในการโหลดประวัติงาน');
        }
    }

    renderCallHistory(calls) {
        return calls.map(call => `
            <div class="history-item">
                <div class="history-header">
                    <div class="history-title">การโทร - ${this.escapeHtml(call.CallStatus)}</div>
                    <div class="history-date">${this.formatDateTime(call.CallDate)}</div>
                </div>
                <div class="history-details">
                    ${call.CallMinutes ? `<div>ระยะเวลา: ${call.CallMinutes} นาที</div>` : ''}
                    ${call.TalkStatus ? `<div>สถานะการคุย: ${this.escapeHtml(call.TalkStatus)}</div>` : ''}
                    ${call.CallReason ? `<div>เหตุผล: ${this.escapeHtml(call.CallReason)}</div>` : ''}
                    ${call.TalkReason ? `<div>เหตุผลที่คุยไม่จบ: ${this.escapeHtml(call.TalkReason)}</div>` : ''}
                    ${call.Remarks ? `<div>หมายเหตุ: ${this.escapeHtml(call.Remarks)}</div>` : ''}
                </div>
                <div class="history-meta">
                    <span>บันทึกโดย: ${this.escapeHtml(call.CreatedBy || '-')}</span>
                    <span>วันที่บันทึก: ${this.formatDateTime(call.CreatedDate)}</span>
                </div>
            </div>
        `).join('');
    }

    renderOrderHistory(orders) {
        return orders.map(order => `
            <div class="history-item">
                <div class="history-header">
                    <div class="history-title">คำสั่งซื้อ #${this.escapeHtml(order.DocumentNo)}</div>
                    <div class="history-date">${this.formatDate(order.DocumentDate)}</div>
                </div>
                <div class="history-details">
                    <div>สินค้า: ${this.escapeHtml(order.Products || '-')}</div>
                    ${order.Quantity ? `<div>จำนวน: ${order.Quantity}</div>` : ''}
                    ${order.Price ? `<div>ราคา: ${this.formatCurrency(order.Price)}</div>` : ''}
                    ${order.PaymentMethod ? `<div>วิธีชำระเงิน: ${this.escapeHtml(order.PaymentMethod)}</div>` : ''}
                </div>
                <div class="history-meta">
                    <span>สั่งโดย: ${this.escapeHtml(order.OrderBy || '-')}</span>
                    <span>วันที่บันทึก: ${this.formatDateTime(order.CreatedDate)}</span>
                </div>
            </div>
        `).join('');
    }

    renderSalesHistory(salesHistory, currentAssignment) {
        let html = '';
        
        // Show current assignment first if exists
        if (currentAssignment) {
            html += `
                <div class="history-item current-assignment">
                    <div class="history-header">
                        <div class="history-title">
                            <span class="current-badge">ปัจจุบัน</span>
                            Sales: ${this.escapeHtml(currentAssignment.SalesFullName || currentAssignment.SaleName)}
                        </div>
                        <div class="history-date">${this.formatDate(currentAssignment.StartDate)} - ปัจจุบัน</div>
                    </div>
                    <div class="history-details">
                        <div>ระยะเวลาดูแล: ${this.calculateDuration(currentAssignment.StartDate, null)}</div>
                        ${currentAssignment.AssignBy ? `<div>มอบหมายโดย: ${this.escapeHtml(currentAssignment.AssignBy)}</div>` : ''}
                    </div>
                    <div class="history-meta">
                        <span>วันที่มอบหมาย: ${this.formatDateTime(currentAssignment.StartDate)}</span>
                    </div>
                </div>
            `;
        }
        
        // Show historical assignments
        if (salesHistory && salesHistory.length > 0) {
            html += salesHistory.map(history => `
                <div class="history-item ${history.IsActive ? 'active-assignment' : 'past-assignment'}">
                    <div class="history-header">
                        <div class="history-title">
                            Sales: ${this.escapeHtml(history.SalesFullName || history.SaleName)}
                        </div>
                        <div class="history-date">${this.formatDate(history.StartDate)} - ${history.EndDate ? this.formatDate(history.EndDate) : 'ปัจจุบัน'}</div>
                    </div>
                    <div class="history-details">
                        <div>ระยะเวลาดูแล: ${this.calculateDuration(history.StartDate, history.EndDate)}</div>
                        ${history.AssignBy ? `<div>มอบหมายโดย: ${this.escapeHtml(history.AssignBy)}</div>` : ''}
                    </div>
                    <div class="history-meta">
                        <span>วันที่มอบหมาย: ${this.formatDateTime(history.StartDate)}</span>
                    </div>
                </div>
            `).join('');
        }
        
        return html;
    }

    renderTaskHistory(tasks) {
        return tasks.map(task => `
            <div class="history-item">
                <div class="history-header">
                    <div class="history-title">งาน/นัดหมาย</div>
                    <div class="history-date">${this.formatDateTime(task.FollowupDate)}</div>
                </div>
                <div class="history-details">
                    <div>สถานะ: ${this.escapeHtml(task.Status || 'รอดำเนินการ')}</div>
                    ${task.Remarks ? `<div>หมายเหตุ: ${this.escapeHtml(task.Remarks)}</div>` : ''}
                </div>
                <div class="history-meta">
                    <span>สร้างโดย: ${this.escapeHtml(task.CreatedBy || '-')}</span>
                    <span>วันที่สร้าง: ${this.formatDateTime(task.CreatedDate)}</span>
                </div>
            </div>
        `).join('');
    }

    async submitCallLog() {
        const form = document.getElementById('call-log-form');
        const submitButton = form.querySelector('button[type="submit"], input[type="submit"]');
        
        // Prevent double submission
        if (this.isSubmittingCallLog) {
            return;
        }
        
        this.isSubmittingCallLog = true;
        
        // Disable submit button and show loading state
        if (submitButton) {
            submitButton.disabled = true;
            submitButton.style.backgroundColor = '#6c757d';
            submitButton.style.cursor = 'not-allowed';
            const originalText = submitButton.textContent;
            submitButton.textContent = 'กำลังบันทึก...';
            submitButton.setAttribute('data-original-text', originalText);
        }
        
        const formData = new FormData(form);
        
        // Convert FormData to JSON
        const data = {};
        formData.forEach((value, key) => {
            data[key] = value;
        });
        data.customer_code = this.customerCode;

        try {
            const response = await fetch('../api/calls/log.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data)
            });

            const result = await response.json();

            if (result.success) {
                CRMUtils.showNotification('บันทึกการโทรสำเร็จ', 'success');
                this.closeModal('call-log-modal');
                form.reset();
                this.setDefaultDateTimes();
                this.loadCallHistory();
            } else {
                CRMUtils.showNotification(result.message || 'เกิดข้อผิดพลาดในการบันทึก', 'error');
            }
        } catch (error) {
            console.error('Error submitting call log:', error);
            CRMUtils.showNotification('เกิดข้อผิดพลาดในการบันทึก', 'error');
        } finally {
            // Reset submission state and button
            this.isSubmittingCallLog = false;
            
            if (submitButton) {
                submitButton.disabled = false;
                submitButton.style.backgroundColor = '';
                submitButton.style.cursor = '';
                const originalText = submitButton.getAttribute('data-original-text');
                if (originalText) {
                    submitButton.textContent = originalText;
                    submitButton.removeAttribute('data-original-text');
                }
            }
        }
    }

    async submitTask() {
        const form = document.getElementById('task-form');
        const submitButton = form.querySelector('button[type="submit"], input[type="submit"]');
        
        // Prevent double submission
        if (this.isSubmittingTask) {
            return;
        }
        
        this.isSubmittingTask = true;
        
        // Disable submit button and show loading state
        if (submitButton) {
            submitButton.disabled = true;
            submitButton.style.backgroundColor = '#6c757d';
            submitButton.style.cursor = 'not-allowed';
            const originalText = submitButton.textContent;
            submitButton.textContent = 'กำลังสร้าง...';
            submitButton.setAttribute('data-original-text', originalText);
        }
        
        const formData = new FormData(form);
        
        const data = {};
        formData.forEach((value, key) => {
            data[key] = value;
        });
        
        // Map form fields to API expected fields
        data.CustomerCode = this.customerCode;
        data.FollowupDate = data.followup_date || data.FollowupDate;
        data.Remarks = data.remarks || data.Remarks;
        
        // Remove underscore versions
        delete data.followup_date;
        delete data.remarks;

        try {
            const response = await fetch('../api/tasks/create.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data)
            });

            const result = await response.json();

            if (result.status === 'success') {
                CRMUtils.showNotification('สร้างนัดหมายสำเร็จ', 'success');
                this.closeModal('task-modal');
                form.reset();
                this.setDefaultDateTimes();
                this.loadTaskHistory();
            } else {
                CRMUtils.showNotification(result.message || 'เกิดข้อผิดพลาดในการสร้างนัดหมาย', 'error');
            }
        } catch (error) {
            console.error('Error submitting task:', error);
            CRMUtils.showNotification('เกิดข้อผิดพลาดในการสร้างนัดหมาย', 'error');
        } finally {
            // Reset submission state and button
            this.isSubmittingTask = false;
            
            if (submitButton) {
                submitButton.disabled = false;
                submitButton.style.backgroundColor = '';
                submitButton.style.cursor = '';
                const originalText = submitButton.getAttribute('data-original-text');
                if (originalText) {
                    submitButton.textContent = originalText;
                    submitButton.removeAttribute('data-original-text');
                }
            }
        }
    }

    async submitOrder() {
        const form = document.getElementById('order-form');
        const submitButton = form.querySelector('button[type="submit"], input[type="submit"]');
        
        // Prevent double submission
        if (this.isSubmittingOrder) {
            return;
        }
        
        this.isSubmittingOrder = true;
        
        // Disable submit button and show loading state
        if (submitButton) {
            submitButton.disabled = true;
            submitButton.style.backgroundColor = '#6c757d';
            submitButton.style.cursor = 'not-allowed';
            const originalText = submitButton.textContent;
            submitButton.textContent = 'กำลังสร้าง...';
            submitButton.setAttribute('data-original-text', originalText);
        }
        
        const formData = new FormData(form);
        
        // Collect product data from dynamic form
        const productCodes = formData.getAll('product_code[]');
        const productNames = formData.getAll('product_name[]');
        const productQuantities = formData.getAll('product_quantity[]');
        const productPrices = formData.getAll('product_price[]');
        
        // Build products array
        const products = [];
        for (let i = 0; i < productCodes.length; i++) {
            if (productCodes[i] && productNames[i] && productQuantities[i] && productPrices[i]) {
                const quantity = parseFloat(productQuantities[i]);
                const price = parseFloat(productPrices[i]);
                
                if (quantity > 0 && price >= 0) {
                    products.push({
                        code: productCodes[i].trim(),
                        name: productNames[i].trim(),
                        quantity: quantity,
                        price: price
                    });
                }
            }
        }
        
        // Validate products
        if (products.length === 0) {
            CRMUtils.showNotification('กรุณากรอกข้อมูลสินค้าอย่างน้อย 1 รายการ', 'error');
            // Reset submission state
            this.isSubmittingOrder = false;
            if (submitButton) {
                submitButton.disabled = false;
                submitButton.style.backgroundColor = '';
                submitButton.style.cursor = '';
                const originalText = submitButton.getAttribute('data-original-text');
                if (originalText) {
                    submitButton.textContent = originalText;
                    submitButton.removeAttribute('data-original-text');
                }
            }
            return;
        }
        
        // Validate document date
        const documentDate = formData.get('document_date');
        if (!documentDate) {
            CRMUtils.showNotification('กรุณาระบุวันที่เอกสาร', 'error');
            // Reset submission state
            this.isSubmittingOrder = false;
            if (submitButton) {
                submitButton.disabled = false;
                submitButton.style.backgroundColor = '';
                submitButton.style.cursor = '';
                const originalText = submitButton.getAttribute('data-original-text');
                if (originalText) {
                    submitButton.textContent = originalText;
                    submitButton.removeAttribute('data-original-text');
                }
            }
            return;
        }
        
        // Get discount information
        const discountAmount = parseFloat(formData.get('discount_amount') || 0);
        const discountPercent = parseFloat(formData.get('discount_percent') || 0);
        const discountRemarks = formData.get('discount_remarks') || '';
        
        // Prepare data for API
        const data = {
            CustomerCode: this.customerCode,
            DocumentDate: documentDate,
            PaymentMethod: formData.get('payment_method') || '',
            products: products,
            discount_amount: discountAmount,
            discount_percent: discountPercent,
            discount_remarks: discountRemarks
        };
        
        // Debug log
        console.log('Order data being sent:', data);

        try {
            console.log('Sending order data:', data);
            const response = await fetch('../api/orders/create.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data)
            });

            console.log('Response status:', response.status);
            const result = await response.json();
            console.log('API response:', result);

            // API returns both 'success' and 'status' fields - check both
            if (result.success === true || result.status === 'success') {
                CRMUtils.showNotification('สร้างคำสั่งซื้อสำเร็จ', 'success');
                this.closeModal('order-modal');
                form.reset();
                this.setDefaultDateTimes();
                this.loadOrderHistory();
                this.loadCustomerInfo(); // Reload to update customer status
            } else {
                console.error('Order creation failed:', result);
                
                // Show detailed errors if available, otherwise show general message
                if (result.errors && result.errors.length > 0) {
                    console.error('Validation errors:', result.errors);
                    const errorDetails = result.errors.join('\n• ');
                    CRMUtils.showNotification(`ข้อมูลไม่ถูกต้อง:\n• ${errorDetails}`, 'error');
                } else {
                    // Show the API message if no detailed errors
                    CRMUtils.showNotification(result.message || 'เกิดข้อผิดพลาดในการสร้างคำสั่งซื้อ', 'error');
                }
            }
        } catch (error) {
            console.error('Error submitting order:', error);
            CRMUtils.showNotification('เกิดข้อผิดพลาดในการสร้างคำสั่งซื้อ', 'error');
        } finally {
            // Reset submission state and button
            this.isSubmittingOrder = false;
            
            if (submitButton) {
                submitButton.disabled = false;
                submitButton.style.backgroundColor = '';
                submitButton.style.cursor = '';
                const originalText = submitButton.getAttribute('data-original-text');
                if (originalText) {
                    submitButton.textContent = originalText;
                    submitButton.removeAttribute('data-original-text');
                }
            }
        }
    }

    async showModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.add('show');
            
            // Reset order form to default state when opening
            if (modalId === 'order-modal') {
                this.resetOrderForm();
                
                // Load products data if not already loaded
                if (productsData.length === 0) {
                    await loadProductsData();
                }
                
                // Initialize event listeners for existing product search input
                this.initializeProductSearchListeners();
            }
        }
    }

    closeModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.remove('show');
        }
    }
    
    resetOrderForm() {
        // Reset to single product row
        const container = document.getElementById('products-container');
        const productRows = container.querySelectorAll('.product-row');
        
        // Remove all rows except the first one
        for (let i = 1; i < productRows.length; i++) {
            productRows[i].remove();
        }
        
        // Clear the first row
        const firstRow = container.querySelector('.product-row');
        if (firstRow) {
            firstRow.querySelectorAll('input').forEach(input => {
                if (!input.readOnly) {
                    input.value = '';
                }
            });
            
            // Reset quantity to 1
            const quantityField = firstRow.querySelector('input[name="product_quantity[]"]');
            if (quantityField) {
                quantityField.value = '1';
            }
            
            // Hide suggestions
            const suggestions = firstRow.querySelector('.product-suggestions');
            if (suggestions) {
                suggestions.style.display = 'none';
            }
        }
        
        // Reset totals and discount fields
        document.getElementById('total-quantity').value = '';
        document.getElementById('subtotal-amount').value = '';
        document.getElementById('total-amount').value = '';
        document.getElementById('discount-amount').value = '';
        document.getElementById('discount-percent').value = '';
        document.getElementById('discount-remarks').value = '';
        
        // Reset product index
        productIndex = 1;
    }
    
    initializeProductSearchListeners() {
        // Initialize event listeners for all product search inputs
        const searchInputs = document.querySelectorAll('.product-search');
        
        searchInputs.forEach(input => {
            // Remove existing listeners first to avoid duplicates
            if (input._searchHandler) {
                input.removeEventListener('input', input._searchHandler);
                input.removeEventListener('focus', input._focusHandler);
                input.removeEventListener('blur', input._blurHandler);
            }
            
            // Create handlers and store references
            input._searchHandler = function() { searchProducts(this); };
            input._focusHandler = function() { showProductSuggestions(this); };
            input._blurHandler = function() { hideProductSuggestions(this); };
            
            // Add new listeners
            input.addEventListener('input', input._searchHandler);
            input.addEventListener('focus', input._focusHandler);
            input.addEventListener('blur', input._blurHandler);
        });

        // Initialize event listeners for quantity and price inputs
        const quantityInputs = document.querySelectorAll('input[name="product_quantity[]"]');
        const priceInputs = document.querySelectorAll('input[name="product_price[]"]');
        
        quantityInputs.forEach(input => {
            input.removeEventListener('change', input._changeHandler);
            input._changeHandler = function() { calculateProductTotal(this); };
            input.addEventListener('change', input._changeHandler);
        });
        
        priceInputs.forEach(input => {
            input.removeEventListener('change', input._changeHandler);
            input._changeHandler = function() { calculateProductTotal(this); };
            input.addEventListener('change', input._changeHandler);
        });

        // Initialize discount field listeners
        const discountAmount = document.getElementById('discount-amount');
        const discountPercent = document.getElementById('discount-percent');
        
        if (discountAmount) {
            discountAmount.removeEventListener('change', discountAmount._changeHandler);
            discountAmount._changeHandler = function() { calculateFinalTotal(); };
            discountAmount.addEventListener('change', discountAmount._changeHandler);
        }
        
        if (discountPercent) {
            discountPercent.removeEventListener('change', discountPercent._changeHandler);
            discountPercent._changeHandler = function() { calculateDiscountFromPercent(); };
            discountPercent.addEventListener('change', discountPercent._changeHandler);
        }
    }

    renderEmptyState(title, message) {
        return `
            <div class="empty-state">
                <h4>${title}</h4>
                <p>${message}</p>
            </div>
        `;
    }

    renderErrorState(message) {
        return `
            <div class="empty-state">
                <h4>เกิดข้อผิดพลาด</h4>
                <p>${message}</p>
                <button class="btn btn-primary" onclick="customerDetail.loadTabHistory(customerDetail.currentTab)">ลองใหม่</button>
            </div>
        `;
    }

    showError(message) {
        CRMUtils.showNotification(message, 'error');
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

    formatDate(dateString) {
        if (!dateString) return '-';
        const date = new Date(dateString);
        const day = String(date.getDate()).padStart(2, '0');
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const year = date.getFullYear();
        return `${day}/${month}/${year}`;
    }

    formatDateTime(dateString) {
        if (!dateString) return '-';
        const date = new Date(dateString);
        const day = String(date.getDate()).padStart(2, '0');
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const year = date.getFullYear();
        const hours = String(date.getHours()).padStart(2, '0');
        const minutes = String(date.getMinutes()).padStart(2, '0');
        return `${day}/${month}/${year} ${hours}:${minutes}`;
    }

    formatCurrency(amount) {
        if (!amount) return '-';
        return new Intl.NumberFormat('th-TH', {
            style: 'currency',
            currency: 'THB'
        }).format(amount);
    }

    calculateDuration(startDate, endDate) {
        const start = new Date(startDate);
        const end = endDate ? new Date(endDate) : new Date();
        const diffTime = Math.abs(end - start);
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
        
        if (diffDays < 30) {
            return `${diffDays} วัน`;
        } else if (diffDays < 365) {
            const months = Math.floor(diffDays / 30);
            return `${months} เดือน`;
        } else {
            const years = Math.floor(diffDays / 365);
            const months = Math.floor((diffDays % 365) / 30);
            return `${years} ปี ${months} เดือน`;
        }
    }

    escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

// Global functions for button clicks and form interactions
function goBack() {
    window.history.back();
}

function editCustomer() {
    // This will be implemented in later tasks
    CRMUtils.showNotification('ฟังก์ชันแก้ไขข้อมูลจะพัฒนาในงานถัดไป', 'info');
}

function showCallLogForm() {
    if (window.customerDetail) {
        window.customerDetail.showModal('call-log-modal');
    }
}

function showTaskForm() {
    if (window.customerDetail) {
        window.customerDetail.showModal('task-modal');
    }
}

async function showOrderForm() {
    if (window.customerDetail) {
        await window.customerDetail.showModal('order-modal');
    }
}

function closeModal(modalId) {
    if (window.customerDetail) {
        window.customerDetail.closeModal(modalId);
    }
}

function refreshCallHistory() {
    if (window.customerDetail) {
        window.customerDetail.loadCallHistory();
    }
}

function refreshOrderHistory() {
    if (window.customerDetail) {
        window.customerDetail.loadOrderHistory();
    }
}

function refreshSalesHistory() {
    if (window.customerDetail) {
        window.customerDetail.loadSalesHistory();
    }
}

function refreshTaskHistory() {
    if (window.customerDetail) {
        window.customerDetail.loadTaskHistory();
    }
}

function toggleCallFields() {
    const callStatus = document.getElementById('call-status').value;
    const callReasonGroup = document.getElementById('call-reason-group');
    const talkStatusGroup = document.getElementById('talk-status-group');
    
    if (callStatus === 'ติดต่อไม่ได้') {
        callReasonGroup.style.display = 'block';
        talkStatusGroup.style.display = 'none';
        document.getElementById('talk-reason-group').style.display = 'none';
    } else if (callStatus === 'ติดต่อได้') {
        callReasonGroup.style.display = 'none';
        talkStatusGroup.style.display = 'block';
    } else {
        callReasonGroup.style.display = 'none';
        talkStatusGroup.style.display = 'none';
        document.getElementById('talk-reason-group').style.display = 'none';
    }
}

function toggleTalkReason() {
    const talkStatus = document.getElementById('talk-status').value;
    const talkReasonGroup = document.getElementById('talk-reason-group');
    
    // Show talk reason for certain statuses
    const needsReasonStatuses = ['ยังไม่สนใจ', 'ขอคิดดูก่อน', 'ไม่สนใจแล้ว', 'ใช้สินค้าอื่น', 'อย่าโทรมาอีก'];
    
    if (needsReasonStatuses.includes(talkStatus)) {
        talkReasonGroup.style.display = 'block';
        // Update label based on status
        const label = talkReasonGroup.querySelector('label');
        if (label) {
            label.innerHTML = `เหตุผล/รายละเอียดเพิ่มเติม <small>(หรือกรอกในหมายเหตุด้านล่าง)</small>`;
        }
    } else {
        talkReasonGroup.style.display = 'none';
    }
}

// Product management functions for order form
let productIndex = 1;
let productsData = [];

// Load products data from API
async function loadProductsData() {
    try {
        const response = await fetch('../api/products/list.php');
        const result = await response.json();
        
        // API returns both 'success' and 'status' fields - check both
        console.log('Products API response:', result);
        if (result.success === true || result.status === 'success') {
            productsData = result.data || [];
            console.log('Products loaded successfully:', productsData.length, 'items');
        } else {
            console.error('Failed to load products:', result.message);
            // Use mock data as fallback
            productsData = [
                {product_code: 'F001', product_name: 'ปุ๋ยเคมี 16-16-16', category: 'ปุ๋ยเคมี', unit: 'กก', standard_price: '18.50'},
                {product_code: 'F002', product_name: 'ปุ๋ยเคมี 15-15-15', category: 'ปุ๋ยเคมี', unit: 'กก', standard_price: '17.50'},
                {product_code: 'O001', product_name: 'ปุ๋ยหมักมีกากมด', category: 'ปุ๋ยอินทรีย์', unit: 'กก', standard_price: '45.00'}
            ];
            console.log('Using fallback product data');
        }
    } catch (error) {
        console.error('Error loading products:', error);
        // Use mock data as fallback
        productsData = [
            {product_code: 'F001', product_name: 'ปุ๋ยเคมี 16-16-16', category: 'ปุ๋ยเคมี', unit: 'กก', standard_price: '18.50'},
            {product_code: 'F002', product_name: 'ปุ๋ยเคมี 15-15-15', category: 'ปุ๋ยเคมี', unit: 'กก', standard_price: '17.50'},
            {product_code: 'O001', product_name: 'ปุ๋ยหมักมีกากมด', category: 'ปุ๋ยอินทรีย์', unit: 'กก', standard_price: '45.00'}
        ];
        console.log('Using fallback product data due to error');
    }
}


// Search products based on input
function searchProducts(inputElement) {
    let query = inputElement.value.toLowerCase().trim();
    const suggestions = inputElement.parentElement.querySelector('.product-suggestions');
    
    // Check if user is modifying a previously selected product
    const selectedProduct = inputElement.getAttribute('data-selected-product');
    const originalFormat = selectedProduct ? `${selectedProduct.toLowerCase()} - ` : null;
    
    // If user is typing and it doesn't match the original selected format, clear selection
    if (selectedProduct && !query.startsWith(originalFormat)) {
        inputElement.removeAttribute('data-selected-product');
        
        // Clear hidden fields
        const productRow = inputElement.closest('.product-row');
        productRow.querySelector('input[name="product_code[]"]').value = '';
        productRow.querySelector('input[name="product_name[]"]').value = '';
    }
    
    // If the input contains " - " (selected product format), extract just the code for searching
    if (query.includes(' - ')) {
        const parts = query.split(' - ');
        query = parts[0]; // Use only the product code part
    }
    
    if (query.length < 1) {
        suggestions.style.display = 'none';
        return;
    }
    
    const filteredProducts = productsData.filter(product => 
        product.product_code.toLowerCase().includes(query) ||
        product.product_name.toLowerCase().includes(query)
    );
    
    showSuggestions(inputElement, filteredProducts);
}

// Show product suggestions
function showProductSuggestions(inputElement) {
    const suggestions = inputElement.parentElement.querySelector('.product-suggestions');
    
    if (inputElement.value.trim().length > 0) {
        searchProducts(inputElement);
    } else {
        // Show all products when focused with empty input
        showSuggestions(inputElement, productsData);
    }
}

// Hide product suggestions (with delay to allow clicking)
function hideProductSuggestions(inputElement) {
    setTimeout(() => {
        const suggestions = inputElement.parentElement.querySelector('.product-suggestions');
        suggestions.style.display = 'none';
    }, 200);
}

// Show suggestions dropdown
function showSuggestions(inputElement, products) {
    const suggestions = inputElement.parentElement.querySelector('.product-suggestions');
    
    if (products.length === 0) {
        suggestions.innerHTML = '<div class="product-suggestion-item">ไม่พบสินค้า</div>';
        suggestions.style.display = 'block';
        return;
    }
    
    suggestions.innerHTML = '';
    
    // Group by category
    const categories = {};
    products.forEach(product => {
        if (!categories[product.category]) {
            categories[product.category] = [];
        }
        categories[product.category].push(product);
    });
    
    // Display grouped suggestions
    Object.keys(categories).sort().forEach(category => {
        categories[category].forEach(product => {
            const item = document.createElement('div');
            item.className = 'product-suggestion-item';
            item.innerHTML = `
                <div class="suggestion-code">${product.product_code}</div>
                <div class="suggestion-name">${product.product_name}</div>
                <div class="suggestion-price">${parseFloat(product.standard_price || 0).toFixed(2)} บาท</div>
            `;
            
            item.addEventListener('click', () => selectProduct(inputElement, product));
            suggestions.appendChild(item);
        });
    });
    
    suggestions.style.display = 'block';
}

// Select a product from suggestions
function selectProduct(inputElement, product) {
    const productRow = inputElement.closest('.product-row');
    
    // Update search input
    inputElement.value = `${product.product_code} - ${product.product_name}`;
    
    // Mark input as having a selected product
    inputElement.setAttribute('data-selected-product', product.product_code);
    
    // Update hidden fields
    productRow.querySelector('input[name="product_code[]"]').value = product.product_code;
    productRow.querySelector('input[name="product_name[]"]').value = product.product_name;
    
    // Update price field
    const priceField = productRow.querySelector('input[name="product_price[]"]');
    priceField.value = parseFloat(product.standard_price || 0).toFixed(2);
    
    // Set quantity to 1 if empty
    const quantityField = productRow.querySelector('input[name="product_quantity[]"]');
    if (!quantityField.value || quantityField.value === "0") {
        quantityField.value = "1";
    }
    
    // Hide suggestions
    const suggestions = inputElement.parentElement.querySelector('.product-suggestions');
    suggestions.style.display = 'none';
    
    // Recalculate totals
    calculateProductTotal(priceField);
}

function addProduct() {
    const container = document.getElementById('products-container');
    const newRow = document.createElement('div');
    newRow.className = 'product-row';
    newRow.setAttribute('data-product-index', productIndex);
    
    newRow.innerHTML = `
        <div class="form-row">
            <div class="form-group">
                <label>เลือกสินค้า</label>
                <input type="text" name="product_search[]" class="product-search" 
                       placeholder="พิมพ์เพื่อค้นหาสินค้า..." 
                       autocomplete="off">
                <div class="product-suggestions" style="display: none;"></div>
                <input type="hidden" name="product_code[]" value="" required>
                <input type="hidden" name="product_name[]" value="">
            </div>
            <div class="form-group">
                <label>จำนวน</label>
                <input type="number" name="product_quantity[]" min="1" step="1" required value="1">
            </div>
            <div class="form-group">
                <label>ราคาต่อหน่วย</label>
                <input type="number" name="product_price[]" min="0" step="0.01" required placeholder="0.00">
            </div>
            <div class="form-group">
                <label>จำนวนเงิน</label>
                <input type="number" class="product-total" readonly placeholder="0.00">
            </div>
            <div class="form-group">
                <button type="button" class="btn btn-danger btn-sm remove-product" onclick="removeProduct(this)" style="margin-top: 25px;">ลบ</button>
            </div>
        </div>
    `;
    
    container.appendChild(newRow);
    
    // Add event listeners to the new inputs
    const newSearchInput = newRow.querySelector('.product-search');
    const newQuantityInput = newRow.querySelector('input[name="product_quantity[]"]');
    const newPriceInput = newRow.querySelector('input[name="product_price[]"]');
    
    if (newSearchInput) {
        newSearchInput._searchHandler = function() { searchProducts(this); };
        newSearchInput._focusHandler = function() { showProductSuggestions(this); };
        newSearchInput._blurHandler = function() { hideProductSuggestions(this); };
        
        newSearchInput.addEventListener('input', newSearchInput._searchHandler);
        newSearchInput.addEventListener('focus', newSearchInput._focusHandler);
        newSearchInput.addEventListener('blur', newSearchInput._blurHandler);
    }
    
    if (newQuantityInput) {
        newQuantityInput._changeHandler = function() { calculateProductTotal(this); };
        newQuantityInput.addEventListener('change', newQuantityInput._changeHandler);
    }
    
    if (newPriceInput) {
        newPriceInput._changeHandler = function() { calculateProductTotal(this); };
        newPriceInput.addEventListener('change', newPriceInput._changeHandler);
    }
    
    productIndex++;
    calculateOrderTotals();
}

function removeProduct(button) {
    const container = document.getElementById('products-container');
    const productRows = container.querySelectorAll('.product-row');
    
    // Don't allow removing the last row
    if (productRows.length > 1) {
        button.closest('.product-row').remove();
        calculateOrderTotals();
    } else {
        alert('ต้องมีสินค้าอย่างน้อย 1 รายการ');
    }
}

function calculateProductTotal(input) {
    const productRow = input.closest('.product-row');
    const quantity = parseFloat(productRow.querySelector('input[name="product_quantity[]"]').value) || 0;
    const price = parseFloat(productRow.querySelector('input[name="product_price[]"]').value) || 0;
    const total = quantity * price;
    
    productRow.querySelector('.product-total').value = total.toFixed(2);
    calculateOrderTotals();
}

function calculateOrderTotals() {
    const productRows = document.querySelectorAll('.product-row');
    let totalQuantity = 0;
    let subtotalAmount = 0;
    
    productRows.forEach(row => {
        const quantity = parseFloat(row.querySelector('input[name="product_quantity[]"]').value) || 0;
        const price = parseFloat(row.querySelector('input[name="product_price[]"]').value) || 0;
        
        totalQuantity += quantity;
        subtotalAmount += (quantity * price);
    });
    
    document.getElementById('total-quantity').value = totalQuantity.toFixed(0);
    document.getElementById('subtotal-amount').value = subtotalAmount.toFixed(2);
    
    // Calculate final total with discount
    calculateFinalTotal();
}

// Calculate discount from percentage
function calculateDiscountFromPercent() {
    const subtotal = parseFloat(document.getElementById('subtotal-amount').value) || 0;
    const discountPercent = parseFloat(document.getElementById('discount-percent').value) || 0;
    
    if (discountPercent > 0 && subtotal > 0) {
        const discountAmount = (subtotal * discountPercent) / 100;
        document.getElementById('discount-amount').value = discountAmount.toFixed(2);
    } else if (discountPercent === 0) {
        // Clear discount amount if percentage is 0
        document.getElementById('discount-amount').value = '';
    }
    
    calculateFinalTotal();
}

// Calculate final total amount
function calculateFinalTotal() {
    const subtotal = parseFloat(document.getElementById('subtotal-amount').value) || 0;
    const discountAmount = parseFloat(document.getElementById('discount-amount').value) || 0;
    
    // Update discount percentage if discount amount is manually entered
    const currentDiscountPercent = parseFloat(document.getElementById('discount-percent').value || 0);
    const calculatedDiscountPercent = subtotal > 0 ? (discountAmount / subtotal) * 100 : 0;
    
    // Only update if there's a significant difference (avoid infinite loops)
    if (Math.abs(calculatedDiscountPercent - currentDiscountPercent) > 0.01) {
        document.getElementById('discount-percent').value = calculatedDiscountPercent.toFixed(2);
    }
    
    const finalTotal = Math.max(0, subtotal - discountAmount);
    document.getElementById('total-amount').value = finalTotal.toFixed(2);
}

// Global customerDetail variable will be initialized from the HTML page
// Remove local initialization to avoid conflicts