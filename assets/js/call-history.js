/**
 * Call History Component
 * Handles displaying and filtering call history for customers
 */

class CallHistory {
    constructor(containerId, customerCode, options = {}) {
        this.container = document.getElementById(containerId);
        this.customerCode = customerCode;
        this.options = {
            showFilters: true,
            showStats: true,
            showPagination: true,
            limit: 20,
            ...options
        };
        
        this.currentFilters = {};
        this.currentPage = 0;
        this.totalCount = 0;
        this.loading = false;
        
        this.init();
    }
    
    init() {
        if (!this.container) {
            console.error('Call history container not found');
            return;
        }
        
        this.render();
        this.loadCallHistory();
    }
    
    render() {
        this.container.innerHTML = `
            <div class="call-history-container">
                <div class="call-history-header">
                    <h3 class="call-history-title">ประวัติการโทร</h3>
                    <button class="btn btn-primary" onclick="this.refreshHistory()">รีเฟรช</button>
                </div>
                
                ${this.options.showFilters ? this.renderFilters() : ''}
                ${this.options.showStats ? this.renderStatsPlaceholder() : ''}
                
                <div class="call-history-content">
                    <div class="loading">กำลังโหลดข้อมูล...</div>
                </div>
                
                ${this.options.showPagination ? this.renderPaginationPlaceholder() : ''}
            </div>
        `;
        
        this.bindEvents();
    }
    
    renderFilters() {
        return `
            <div class="call-history-filters">
                <div class="filter-row">
                    <div class="filter-group">
                        <label>วันที่เริ่มต้น</label>
                        <input type="date" id="filter-date-from" class="filter-input">
                    </div>
                    <div class="filter-group">
                        <label>วันที่สิ้นสุด</label>
                        <input type="date" id="filter-date-to" class="filter-input">
                    </div>
                    <div class="filter-group">
                        <label>สถานะการโทร</label>
                        <select id="filter-call-status" class="filter-input">
                            <option value="">ทั้งหมด</option>
                            <option value="ติดต่อได้">ติดต่อได้</option>
                            <option value="ติดต่อไม่ได้">ติดต่อไม่ได้</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label>สถานะการคุย</label>
                        <select id="filter-talk-status" class="filter-input">
                            <option value="">ทั้งหมด</option>
                            <option value="คุยจบ">คุยจบ</option>
                            <option value="คุยไม่จบ">คุยไม่จบ</option>
                        </select>
                    </div>
                    <div class="filter-actions">
                        <button class="btn btn-primary" onclick="callHistoryInstance.applyFilters()">กรอง</button>
                        <button class="btn btn-secondary" onclick="callHistoryInstance.clearFilters()">ล้าง</button>
                    </div>
                </div>
            </div>
        `;
    }
    
    renderStatsPlaceholder() {
        return `
            <div class="call-history-stats" id="call-stats">
                <!-- Stats will be loaded here -->
            </div>
        `;
    }
    
    renderPaginationPlaceholder() {
        return `
            <div class="pagination" id="call-pagination">
                <!-- Pagination will be loaded here -->
            </div>
        `;
    }
    
    bindEvents() {
        // Store instance reference for global access
        window.callHistoryInstance = this;
        
        // Bind filter inputs
        const filterInputs = this.container.querySelectorAll('.filter-input');
        filterInputs.forEach(input => {
            input.addEventListener('change', () => {
                if (this.options.autoFilter) {
                    this.applyFilters();
                }
            });
        });
    }
    
    async loadCallHistory() {
        if (this.loading) return;
        
        this.loading = true;
        this.showLoading();
        
        try {
            const params = new URLSearchParams({
                customer_code: this.customerCode,
                limit: this.options.limit,
                offset: this.currentPage * this.options.limit,
                get_stats: this.options.showStats ? 'true' : 'false',
                ...this.currentFilters
            });
            
            const response = await fetch(`/api/calls/history.php?${params}`);
            const data = await response.json();
            
            if (data.success) {
                this.renderCallHistory(data.data);
                this.totalCount = data.pagination.total_count;
                
                if (this.options.showStats && data.statistics) {
                    this.renderStats(data.statistics);
                }
                
                if (this.options.showPagination) {
                    this.renderPagination(data.pagination);
                }
            } else {
                this.showError(data.message || 'เกิดข้อผิดพลาดในการโหลดข้อมูล');
            }
        } catch (error) {
            console.error('Error loading call history:', error);
            this.showError('เกิดข้อผิดพลาดในการเชื่อมต่อ');
        } finally {
            this.loading = false;
        }
    }
    
    renderCallHistory(callHistory) {
        const content = this.container.querySelector('.call-history-content');
        
        if (!callHistory || callHistory.length === 0) {
            content.innerHTML = `
                <div class="empty-state">
                    <div class="empty-state-icon">📞</div>
                    <p>ยังไม่มีประวัติการโทร</p>
                </div>
            `;
            return;
        }
        
        const listHtml = callHistory.map(call => this.renderCallItem(call)).join('');
        
        content.innerHTML = `
            <ul class="call-history-list">
                ${listHtml}
            </ul>
        `;
    }
    
    renderCallItem(call) {
        const statusClass = this.getStatusClass(call.call_status, call.talk_status);
        const statusText = this.getStatusText(call.call_status, call.talk_status);
        
        return `
            <li class="call-history-item">
                <div class="call-date-time">
                    <div class="call-date">${call.formatted_date}</div>
                    <div class="call-time">${call.formatted_time}</div>
                </div>
                
                <div class="call-status">
                    <span class="status-badge ${statusClass}">${statusText}</span>
                </div>
                
                <div class="call-details">
                    ${call.talk_status ? `<div class="call-talk-status">การคุย: ${call.talk_status}</div>` : ''}
                    ${call.call_reason ? `<div class="call-reason">เหตุผล: ${call.call_reason}</div>` : ''}
                    ${call.talk_reason ? `<div class="call-reason">เหตุผลการคุย: ${call.talk_reason}</div>` : ''}
                    ${call.remarks ? `<div class="call-remarks">"${call.remarks}"</div>` : ''}
                </div>
                
                <div class="call-duration">
                    ${call.call_minutes ? `${call.call_minutes} นาที` : '-'}
                </div>
                
                <div class="call-created-by">
                    ${call.created_by || '-'}
                </div>
            </li>
        `;
    }
    
    renderStats(statistics) {
        const statsContainer = this.container.querySelector('#call-stats');
        if (!statsContainer) return;
        
        statsContainer.innerHTML = `
            <div class="stats-row">
                <div class="stat-item">
                    <div class="stat-value">${statistics.total_calls}</div>
                    <div class="stat-label">ทั้งหมด</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value">${statistics.successful_calls}</div>
                    <div class="stat-label">ติดต่อได้</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value">${statistics.failed_calls}</div>
                    <div class="stat-label">ติดต่อไม่ได้</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value">${statistics.success_rate}%</div>
                    <div class="stat-label">อัตราสำเร็จ</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value">${statistics.avg_call_duration}</div>
                    <div class="stat-label">เฉลี่ย (นาที)</div>
                </div>
            </div>
        `;
    }
    
    renderPagination(pagination) {
        const paginationContainer = this.container.querySelector('#call-pagination');
        if (!paginationContainer) return;
        
        const totalPages = Math.ceil(pagination.total_count / this.options.limit);
        const currentPage = Math.floor(pagination.offset / this.options.limit);
        
        let paginationHtml = `
            <div class="pagination-info">
                แสดง ${pagination.offset + 1}-${Math.min(pagination.offset + this.options.limit, pagination.total_count)} 
                จาก ${pagination.total_count} รายการ
            </div>
            <div class="pagination-controls">
        `;
        
        // Previous button
        paginationHtml += `
            <button class="pagination-btn" ${currentPage === 0 ? 'disabled' : ''} 
                    onclick="callHistoryInstance.goToPage(${currentPage - 1})">
                ก่อนหน้า
            </button>
        `;
        
        // Page numbers
        const startPage = Math.max(0, currentPage - 2);
        const endPage = Math.min(totalPages - 1, currentPage + 2);
        
        for (let i = startPage; i <= endPage; i++) {
            paginationHtml += `
                <button class="pagination-btn ${i === currentPage ? 'active' : ''}" 
                        onclick="callHistoryInstance.goToPage(${i})">
                    ${i + 1}
                </button>
            `;
        }
        
        // Next button
        paginationHtml += `
            <button class="pagination-btn" ${currentPage >= totalPages - 1 ? 'disabled' : ''} 
                    onclick="callHistoryInstance.goToPage(${currentPage + 1})">
                ถัดไป
            </button>
        `;
        
        paginationHtml += '</div>';
        
        paginationContainer.innerHTML = paginationHtml;
    }
    
    getStatusClass(callStatus, talkStatus) {
        if (callStatus === 'ติดต่อได้') {
            return talkStatus === 'คุยจบ' ? 'status-success' : 'status-incomplete';
        }
        return 'status-failed';
    }
    
    getStatusText(callStatus, talkStatus) {
        if (callStatus === 'ติดต่อได้') {
            return talkStatus === 'คุยจบ' ? 'สำเร็จ' : 'คุยไม่จบ';
        }
        return 'ติดต่อไม่ได้';
    }
    
    showLoading() {
        const content = this.container.querySelector('.call-history-content');
        content.innerHTML = '<div class="loading">กำลังโหลดข้อมูล...</div>';
    }
    
    showError(message) {
        const content = this.container.querySelector('.call-history-content');
        content.innerHTML = `<div class="error">${message}</div>`;
    }
    
    applyFilters() {
        this.currentFilters = {};
        
        const dateFrom = this.container.querySelector('#filter-date-from')?.value;
        const dateTo = this.container.querySelector('#filter-date-to')?.value;
        const callStatus = this.container.querySelector('#filter-call-status')?.value;
        const talkStatus = this.container.querySelector('#filter-talk-status')?.value;
        
        if (dateFrom) this.currentFilters.date_from = dateFrom;
        if (dateTo) this.currentFilters.date_to = dateTo;
        if (callStatus) this.currentFilters.call_status = callStatus;
        if (talkStatus) this.currentFilters.talk_status = talkStatus;
        
        this.currentPage = 0;
        this.loadCallHistory();
    }
    
    clearFilters() {
        this.currentFilters = {};
        this.currentPage = 0;
        
        // Clear filter inputs
        const filterInputs = this.container.querySelectorAll('.filter-input');
        filterInputs.forEach(input => {
            input.value = '';
        });
        
        this.loadCallHistory();
    }
    
    goToPage(page) {
        if (page < 0) return;
        
        this.currentPage = page;
        this.loadCallHistory();
    }
    
    refreshHistory() {
        this.loadCallHistory();
    }
    
    // Public methods for external use
    setCustomer(customerCode) {
        this.customerCode = customerCode;
        this.currentPage = 0;
        this.loadCallHistory();
    }
    
    addCallLog(callData) {
        // Refresh the history after adding a new call log
        this.refreshHistory();
    }
}

// Export for use in other scripts
window.CallHistory = CallHistory;