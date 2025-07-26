// Sales Performance Page JavaScript

class SalesPerformance {
    constructor(currentUser, userRole) {
        this.currentUser = currentUser;
        this.userRole = userRole;
        this.currentFilters = {};
        this.performanceData = [];
        this.teamSummary = [];
        this.init();
    }

    init() {
        this.setupFormHandlers();
        this.loadSalesUsers();
        this.setDefaultDateRange();
        this.loadInitialData();
        this.hideLoadingOverlay();
    }

    hideLoadingOverlay() {
        const overlay = document.getElementById('loading-overlay');
        if (overlay) {
            overlay.style.display = 'none';
        }
    }

    setupFormHandlers() {
        const filterForm = document.getElementById('filter-form');
        if (filterForm) {
            filterForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.applyFilters();
            });
        }
    }

    setDefaultDateRange() {
        const now = new Date();
        const firstDayOfMonth = new Date(now.getFullYear(), now.getMonth(), 1);
        const lastDayOfMonth = new Date(now.getFullYear(), now.getMonth() + 1, 0);

        const dateFromInput = document.getElementById('date-from');
        const dateToInput = document.getElementById('date-to');

        if (dateFromInput) {
            dateFromInput.value = firstDayOfMonth.toISOString().split('T')[0];
        }
        if (dateToInput) {
            dateToInput.value = lastDayOfMonth.toISOString().split('T')[0];
        }
    }

    async loadSalesUsers() {
        try {
            const response = await fetch('../api/users/list.php?role=Sales');
            const data = await response.json();

            if (data.success && data.data) {
                const salesFilter = document.getElementById('sales-filter');
                if (salesFilter) {
                    // Clear existing options except "ทั้งหมด"
                    salesFilter.innerHTML = '<option value="">ทั้งหมด</option>';
                    
                    data.data.forEach(user => {
                        const option = document.createElement('option');
                        option.value = user.Username;
                        option.textContent = `${user.FirstName} ${user.LastName} (${user.Username})`;
                        salesFilter.appendChild(option);
                    });
                }
            }
        } catch (error) {
            console.error('Error loading sales users:', error);
        }
    }

    async loadInitialData() {
        await this.loadPerformanceData();
    }

    async applyFilters() {
        const formData = new FormData(document.getElementById('filter-form'));
        this.currentFilters = {
            date_from: formData.get('date_from'),
            date_to: formData.get('date_to'),
            sales_name: formData.get('sales_name')
        };

        await this.loadPerformanceData();
        await this.loadTeamSummary();
        this.updateSummaryCards();
    }

    async loadPerformanceData() {
        try {
            const params = new URLSearchParams(this.currentFilters);
            const response = await fetch(`../api/sales/performance.php?${params}`);
            const data = await response.json();

            if (data.status === 'success' && data.data) {
                this.performanceData = data.data || [];
                this.summaryData = data.summary || {};
                this.renderPerformanceTable();
                this.updateSummaryCards();
            } else {
                this.renderEmptyPerformanceTable();
            }
        } catch (error) {
            console.error('Error loading performance data:', error);
            this.renderErrorPerformanceTable();
        }
    }

    async loadTeamSummary() {
        try {
            const params = new URLSearchParams({
                action: 'team_summary',
                ...this.currentFilters
            });

            const response = await fetch(`../api/sales/history.php?${params}`);
            const data = await response.json();

            if (data.success && data.data) {
                this.teamSummary = data.data.team_summary || [];
                this.renderTeamSummary();
            }
        } catch (error) {
            console.error('Error loading team summary:', error);
        }
    }

    renderPerformanceTable() {
        const tbody = document.getElementById('performance-table-body');
        if (!tbody) return;

        if (this.performanceData.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="8" class="text-center">ไม่มีข้อมูลประสิทธิภาพการขาย</td>
                </tr>
            `;
            return;
        }

        tbody.innerHTML = this.performanceData.map(perf => `
            <tr>
                <td>
                    <strong>${this.escapeHtml(perf.SalesFullName || perf.SaleName)}</strong>
                    <br>
                    <small class="text-muted">${this.escapeHtml(perf.SaleName)}</small>
                </td>
                <td class="number">${this.formatNumber(perf.TotalCustomers)}</td>
                <td class="number">${this.formatNumber(perf.ConvertedCustomers)}</td>
                <td class="number">${this.formatNumber(perf.TotalOrders)}</td>
                <td class="number">${this.formatCurrency(perf.TotalSales)}</td>
                <td class="number">${this.formatCurrency(perf.AverageSales)}</td>
                <td class="percentage">
                    <span class="conversion-rate ${this.getConversionRateClass(perf.ConversionRate)}">
                        ${this.formatPercentage(perf.ConversionRate)}%
                    </span>
                </td>
                <td>
                    <button class="btn btn-sm btn-info" onclick="showSalesDetail('${perf.SaleName}')">
                        รายละเอียด
                    </button>
                </td>
            </tr>
        `).join('');
    }

    renderEmptyPerformanceTable() {
        const tbody = document.getElementById('performance-table-body');
        if (!tbody) return;

        tbody.innerHTML = `
            <tr>
                <td colspan="8" class="text-center">
                    <div class="empty-state">
                        <div class="empty-state-icon">📊</div>
                        <div class="empty-state-title">ไม่มีข้อมูลประสิทธิภาพการขาย</div>
                        <div class="empty-state-message">ลองเปลี่ยนช่วงวันที่หรือเงื่อนไขการค้นหา</div>
                    </div>
                </td>
            </tr>
        `;
    }

    renderErrorPerformanceTable() {
        const tbody = document.getElementById('performance-table-body');
        if (!tbody) return;

        tbody.innerHTML = `
            <tr>
                <td colspan="8" class="text-center">
                    <div class="error-state">
                        <div class="error-state-icon">⚠️</div>
                        <div class="error-state-title">เกิดข้อผิดพลาด</div>
                        <div class="error-state-message">ไม่สามารถโหลดข้อมูลประสิทธิภาพการขายได้</div>
                    </div>
                </td>
            </tr>
        `;
    }

    renderTeamSummary() {
        const teamStats = document.getElementById('team-stats');
        if (!teamStats) return;

        if (this.teamSummary.length === 0) {
            teamStats.innerHTML = `
                <div class="empty-state">
                    <div class="empty-state-title">ไม่มีข้อมูลทีม</div>
                </div>
            `;
            return;
        }

        // Calculate team totals
        const totals = this.teamSummary.reduce((acc, member) => {
            acc.totalCustomers += member.AssignedCustomers;
            acc.totalConverted += member.ConvertedCustomers;
            acc.totalOrders += member.TotalOrders;
            acc.totalSales += member.TotalSales;
            return acc;
        }, {
            totalCustomers: 0,
            totalConverted: 0,
            totalOrders: 0,
            totalSales: 0
        });

        const avgConversionRate = totals.totalCustomers > 0 ? 
            (totals.totalConverted / totals.totalCustomers * 100) : 0;

        teamStats.innerHTML = `
            <div class="team-stat">
                <div class="team-stat-value">${this.formatNumber(totals.totalCustomers)}</div>
                <div class="team-stat-label">ลูกค้าทั้งหมด</div>
            </div>
            <div class="team-stat">
                <div class="team-stat-value">${this.formatNumber(totals.totalConverted)}</div>
                <div class="team-stat-label">ลูกค้าที่แปลงสถานะ</div>
            </div>
            <div class="team-stat">
                <div class="team-stat-value">${this.formatNumber(totals.totalOrders)}</div>
                <div class="team-stat-label">คำสั่งซื้อทั้งหมด</div>
            </div>
            <div class="team-stat">
                <div class="team-stat-value">${this.formatCurrency(totals.totalSales)}</div>
                <div class="team-stat-label">ยอดขายรวม</div>
            </div>
            <div class="team-stat">
                <div class="team-stat-value">${this.formatPercentage(avgConversionRate)}%</div>
                <div class="team-stat-label">อัตราการแปลงเฉลี่ย</div>
            </div>
            <div class="team-stat">
                <div class="team-stat-value">${this.teamSummary.length}</div>
                <div class="team-stat-label">สมาชิกในทีม</div>
            </div>
        `;
    }

    updateSummaryCards() {
        if (this.summaryData && typeof this.summaryData === 'object') {
            // Update individual card elements with server data
            const totalSalesEl = document.getElementById('total-sales');
            const totalOrdersEl = document.getElementById('total-orders');
            const avgConversionEl = document.getElementById('avg-conversion');
            const activeSalesEl = document.getElementById('active-sales');

            if (totalSalesEl) totalSalesEl.textContent = this.formatCurrency(this.summaryData.total_sales || 0);
            if (totalOrdersEl) totalOrdersEl.textContent = this.formatNumber(this.summaryData.total_orders || 0);
            if (avgConversionEl) avgConversionEl.textContent = this.formatPercentage(this.summaryData.avg_conversion_rate || 0) + '%';
            if (activeSalesEl) activeSalesEl.textContent = this.formatNumber(this.summaryData.active_sales || 0);
        } else {
            // Fallback: calculate from performance data
            const totals = this.performanceData.reduce((acc, perf) => {
                acc.totalCustomers += perf.TotalCustomers || 0;
                acc.totalConverted += perf.ConvertedCustomers || 0;
                acc.totalOrders += perf.TotalOrders || 0;
                acc.totalSales += perf.TotalSales || 0;
                return acc;
            }, {
                totalCustomers: 0,
                totalConverted: 0,
                totalOrders: 0,
                totalSales: 0
            });

            const avgConversionRate = totals.totalCustomers > 0 ? 
                (totals.totalConverted / totals.totalCustomers * 100) : 0;

            const totalSalesEl = document.getElementById('total-sales');
            const totalOrdersEl = document.getElementById('total-orders');
            const avgConversionEl = document.getElementById('avg-conversion');
            const activeSalesEl = document.getElementById('active-sales');

            if (totalSalesEl) totalSalesEl.textContent = this.formatCurrency(totals.totalSales);
            if (totalOrdersEl) totalOrdersEl.textContent = this.formatNumber(totals.totalOrders);
            if (avgConversionEl) avgConversionEl.textContent = this.formatPercentage(avgConversionRate) + '%';
            if (activeSalesEl) activeSalesEl.textContent = this.formatNumber(this.performanceData.length);
        }
    }

    async showSalesDetail(salesName) {
        try {
            const params = new URLSearchParams({
                action: 'list',
                sales: salesName,
                ...this.currentFilters
            });

            const response = await fetch(`../api/sales/history.php?${params}`);
            const data = await response.json();

            if (data.success && data.data) {
                this.renderSalesDetailModal(salesName, data.data.assignments || []);
            }
        } catch (error) {
            console.error('Error loading sales detail:', error);
        }
    }

    renderSalesDetailModal(salesName, assignments) {
        const modal = document.getElementById('sales-detail-modal');
        const content = document.getElementById('sales-detail-content');
        
        if (!modal || !content) return;

        content.innerHTML = `
            <h4>รายละเอียดการมอบหมายลูกค้า: ${this.escapeHtml(salesName)}</h4>
            <div class="table-container">
                <table class="performance-table">
                    <thead>
                        <tr>
                            <th>รหัสลูกค้า</th>
                            <th>ชื่อลูกค้า</th>
                            <th>เบอร์โทร</th>
                            <th>สถานะลูกค้า</th>
                            <th>วันที่เริ่มดูแล</th>
                            <th>วันที่สิ้นสุด</th>
                            <th>สถานะ</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${assignments.map(assignment => `
                            <tr>
                                <td>${this.escapeHtml(assignment.CustomerCode)}</td>
                                <td>${this.escapeHtml(assignment.CustomerName)}</td>
                                <td>${this.escapeHtml(assignment.CustomerTel)}</td>
                                <td>${this.escapeHtml(assignment.CustomerStatus)}</td>
                                <td>${this.formatDate(assignment.StartDate)}</td>
                                <td>${assignment.EndDate ? this.formatDate(assignment.EndDate) : 'ปัจจุบัน'}</td>
                                <td>
                                    <span class="badge ${assignment.IsActive ? 'badge-success' : 'badge-secondary'}">
                                        ${assignment.IsActive ? 'กำลังดูแล' : 'สิ้นสุดแล้ว'}
                                    </span>
                                </td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            </div>
        `;

        modal.style.display = 'block';
    }

    // Utility methods
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    formatNumber(num) {
        return new Intl.NumberFormat('th-TH').format(num || 0);
    }

    formatCurrency(amount) {
        return new Intl.NumberFormat('th-TH', {
            style: 'currency',
            currency: 'THB'
        }).format(amount || 0);
    }

    formatPercentage(percent) {
        return (percent || 0).toFixed(1);
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

    getConversionRateClass(rate) {
        if (rate >= 70) return 'high';
        if (rate >= 40) return 'medium';
        return 'low';
    }
}

// Global functions
function resetFilters() {
    document.getElementById('filter-form').reset();
    const salesPerformance = window.salesPerformanceInstance;
    if (salesPerformance) {
        salesPerformance.setDefaultDateRange();
        salesPerformance.currentFilters = {};
        salesPerformance.loadInitialData();
    }
}

function refreshData() {
    const salesPerformance = window.salesPerformanceInstance;
    if (salesPerformance) {
        salesPerformance.loadInitialData();
    }
}

function showSalesDetail(salesName) {
    const salesPerformance = window.salesPerformanceInstance;
    if (salesPerformance) {
        salesPerformance.showSalesDetail(salesName);
    }
}

function closeSalesDetailModal() {
    const modal = document.getElementById('sales-detail-modal');
    if (modal) {
        modal.style.display = 'none';
    }
}

function exportToCSV() {
    const salesPerformance = window.salesPerformanceInstance;
    if (!salesPerformance || !salesPerformance.performanceData.length) {
        alert('ไม่มีข้อมูลสำหรับส่งออก');
        return;
    }

    const headers = [
        'พนักงานขาย',
        'ลูกค้าที่ได้รับมอบหมาย',
        'ลูกค้าที่แปลงสถานะ',
        'จำนวนคำสั่งซื้อ',
        'ยอดขายรวม',
        'ยอดขายเฉลี่ย',
        'อัตราการแปลง (%)'
    ];

    const csvContent = [
        headers.join(','),
        ...salesPerformance.performanceData.map(perf => [
            `"${perf.SalesFullName || perf.SaleName}"`,
            perf.TotalCustomers,
            perf.ConvertedCustomers,
            perf.TotalOrders,
            perf.TotalSales,
            perf.AverageSales,
            perf.ConversionRate
        ].join(','))
    ].join('\n');

    const blob = new Blob(['\ufeff' + csvContent], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);
    link.setAttribute('href', url);
    link.setAttribute('download', `sales_performance_${new Date().toISOString().split('T')[0]}.csv`);
    link.style.visibility = 'hidden';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Store instance globally for access by other functions
    window.salesPerformanceInstance = window.salesPerformance;
});

// Close modal when clicking outside
window.addEventListener('click', function(event) {
    const modal = document.getElementById('sales-detail-modal');
    if (event.target === modal) {
        closeSalesDetailModal();
    }
});