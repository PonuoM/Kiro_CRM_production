/**
 * Customer Intelligence System JavaScript
 * Handles Grade and Temperature functionality
 * Phase 1: Customer Intelligence Implementation
 */

class CustomerIntelligence {
    constructor() {
        this.baseUrl = '/crm_system/Kiro_CRM_production/api/customers/intelligence-safe.php';
        this.currentFilters = {
            grade: '',
            temperature: ''
        };
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.loadIntelligenceDashboard();
    }

    setupEventListeners() {
        // Grade filter buttons
        document.querySelectorAll('.grade-filter').forEach(btn => {
            btn.addEventListener('click', (e) => {
                this.setGradeFilter(e.target.dataset.grade);
            });
        });

        // Temperature filter buttons
        document.querySelectorAll('.temperature-filter').forEach(btn => {
            btn.addEventListener('click', (e) => {
                this.setTemperatureFilter(e.target.dataset.temperature);
            });
        });

        // Clear filters button
        const clearFiltersBtn = document.getElementById('clear-filters');
        if (clearFiltersBtn) {
            clearFiltersBtn.addEventListener('click', () => {
                this.clearAllFilters();
            });
        }

        // Update buttons for admin
        const updateGradesBtn = document.getElementById('update-all-grades');
        if (updateGradesBtn) {
            updateGradesBtn.addEventListener('click', () => {
                this.updateAllGrades();
            });
        }

        const updateTemperaturesBtn = document.getElementById('update-all-temperatures');
        if (updateTemperaturesBtn) {
            updateTemperaturesBtn.addEventListener('click', () => {
                this.updateAllTemperatures();
            });
        }
    }

    async loadIntelligenceDashboard() {
        try {
            const response = await fetch(this.baseUrl);
            const data = await response.json();
            
            if (data.status === 'success') {
                this.renderDashboard(data.data);
            } else {
                console.error('Failed to load dashboard:', data.message);
                this.showError('Failed to load intelligence dashboard');
            }
        } catch (error) {
            console.error('Error loading dashboard:', error);
            this.showError('Error loading dashboard');
        }
    }

    renderDashboard(data) {
        this.renderGradeDistribution(data.grades);
        this.renderTemperatureDistribution(data.temperatures);
        this.renderTopCustomers(data.top_customers);
    }

    renderGradeDistribution(grades) {
        const container = document.getElementById('grade-distribution');
        if (!container) return;

        const gradeColors = {
            'A': '#28a745', // Green
            'B': '#007bff', // Blue  
            'C': '#ffc107', // Yellow
            'D': '#6c757d'  // Gray
        };

        const gradeLabels = {
            'A': 'VIP (≥10,000฿)',
            'B': 'Premium (5,000-9,999฿)',
            'C': 'Regular (2,000-4,999฿)',
            'D': 'New (<2,000฿)'
        };

        let html = `
            <div class="intelligence-section">
                <h3>📊 Grade Distribution</h3>
                <div class="grade-stats">
        `;

        grades.forEach(grade => {
            const percentage = grade.count > 0 ? ((grade.count / grades.reduce((sum, g) => sum + parseInt(g.count), 0)) * 100).toFixed(1) : 0;
            
            html += `
                <div class="grade-card" style="border-left: 4px solid ${gradeColors[grade.CustomerGrade]}">
                    <div class="grade-header">
                        <span class="grade-badge" style="background: ${gradeColors[grade.CustomerGrade]}">
                            Grade ${grade.CustomerGrade}
                        </span>
                        <span class="grade-count">${grade.count} customers</span>
                    </div>
                    <div class="grade-details">
                        <div class="grade-label">${gradeLabels[grade.CustomerGrade]}</div>
                        <div class="grade-percentage">${percentage}% of total</div>
                        <div class="grade-revenue">Revenue: ${this.formatMoney(grade.total_revenue || 0)}</div>
                    </div>
                    <button class="btn btn-sm grade-filter" data-grade="${grade.CustomerGrade}">
                        View Customers
                    </button>
                </div>
            `;
        });

        html += `
                </div>
            </div>
        `;

        container.innerHTML = html;
        this.setupEventListeners(); // Re-setup listeners for new buttons
    }

    renderTemperatureDistribution(temperatures) {
        const container = document.getElementById('temperature-distribution');
        if (!container) return;

        const tempColors = {
            'HOT': '#dc3545',   // Red
            'WARM': '#fd7e14',  // Orange
            'COLD': '#6f42c1'   // Purple
        };

        const tempIcons = {
            'HOT': '🔥',
            'WARM': '☀️',
            'COLD': '❄️'
        };

        const tempLabels = {
            'HOT': 'Ready to Buy',
            'WARM': 'In Progress',
            'COLD': 'Need Attention'
        };

        let html = `
            <div class="intelligence-section">
                <h3>🌡️ Temperature Distribution</h3>
                <div class="temperature-stats">
        `;

        temperatures.forEach(temp => {
            const percentage = temp.count > 0 ? ((temp.count / temperatures.reduce((sum, t) => sum + parseInt(t.count), 0)) * 100).toFixed(1) : 0;
            
            html += `
                <div class="temperature-card" style="border-left: 4px solid ${tempColors[temp.CustomerTemperature]}">
                    <div class="temperature-header">
                        <span class="temperature-badge" style="background: ${tempColors[temp.CustomerTemperature]}">
                            ${tempIcons[temp.CustomerTemperature]} ${temp.CustomerTemperature}
                        </span>
                        <span class="temperature-count">${temp.count} customers</span>
                    </div>
                    <div class="temperature-details">
                        <div class="temperature-label">${tempLabels[temp.CustomerTemperature]}</div>
                        <div class="temperature-percentage">${percentage}% of total</div>
                        <div class="temperature-avg">Avg days since contact: ${Math.round(temp.avg_days_since_contact || 0)}</div>
                    </div>
                    <button class="btn btn-sm temperature-filter" data-temperature="${temp.CustomerTemperature}">
                        View Customers
                    </button>
                </div>
            `;
        });

        html += `
                </div>
            </div>
        `;

        container.innerHTML = html;
        this.setupEventListeners(); // Re-setup listeners for new buttons
    }

    renderTopCustomers(customers) {
        const container = document.getElementById('top-customers');
        if (!container) return;

        let html = `
            <div class="intelligence-section">
                <h3>⭐ Top Performing Customers</h3>
                <div class="top-customers-list">
        `;

        if (customers.length === 0) {
            html += '<p>No top customers found. Start by upgrading customer grades!</p>';
        } else {
            customers.forEach((customer, index) => {
                const gradeColors = {
                    'A': '#28a745',
                    'B': '#007bff',
                    'C': '#ffc107',
                    'D': '#6c757d'
                };

                const tempIcons = {
                    'HOT': '🔥',
                    'WARM': '☀️',
                    'COLD': '❄️'
                };

                html += `
                    <div class="top-customer-card">
                        <div class="customer-rank">#${index + 1}</div>
                        <div class="customer-info">
                            <div class="customer-name">${customer.CustomerName}</div>
                            <div class="customer-code">${customer.CustomerCode}</div>
                        </div>
                        <div class="customer-badges">
                            <span class="grade-badge" style="background: ${gradeColors[customer.CustomerGrade]}">
                                Grade ${customer.CustomerGrade}
                            </span>
                            <span class="temp-badge">
                                ${tempIcons[customer.CustomerTemperature]} ${customer.CustomerTemperature}
                            </span>
                        </div>
                        <div class="customer-value">
                            ${this.formatMoney(customer.TotalPurchase)}
                        </div>
                        <button class="btn btn-sm btn-primary" onclick="viewCustomerDetail('${customer.CustomerCode}')">
                            View Details
                        </button>
                    </div>
                `;
            });
        }

        html += `
                </div>
            </div>
        `;

        container.innerHTML = html;
    }

    async setGradeFilter(grade) {
        this.currentFilters.grade = this.currentFilters.grade === grade ? '' : grade;
        this.updateFilterButtons();
        await this.loadFilteredCustomers();
    }

    async setTemperatureFilter(temperature) {
        this.currentFilters.temperature = this.currentFilters.temperature === temperature ? '' : temperature;
        this.updateFilterButtons();
        await this.loadFilteredCustomers();
    }

    clearAllFilters() {
        this.currentFilters = { grade: '', temperature: '' };
        this.updateFilterButtons();
        this.loadFilteredCustomers();
    }

    updateFilterButtons() {
        // Update grade filter buttons
        document.querySelectorAll('.grade-filter').forEach(btn => {
            const grade = btn.dataset.grade;
            if (grade === this.currentFilters.grade) {
                btn.classList.add('active');
            } else {
                btn.classList.remove('active');
            }
        });

        // Update temperature filter buttons
        document.querySelectorAll('.temperature-filter').forEach(btn => {
            const temperature = btn.dataset.temperature;
            if (temperature === this.currentFilters.temperature) {
                btn.classList.add('active');
            } else {
                btn.classList.remove('active');
            }
        });

        // Update filter status display
        const filterStatus = document.getElementById('filter-status');
        if (filterStatus) {
            const activeFilters = [];
            if (this.currentFilters.grade) activeFilters.push(`Grade ${this.currentFilters.grade}`);
            if (this.currentFilters.temperature) activeFilters.push(`${this.currentFilters.temperature} Temperature`);
            
            filterStatus.textContent = activeFilters.length > 0 
                ? `Active Filters: ${activeFilters.join(', ')}`
                : 'No filters active';
        }
    }

    async loadFilteredCustomers() {
        const container = document.getElementById('filtered-customers');
        if (!container) return;

        try {
            const params = new URLSearchParams();
            if (this.currentFilters.grade) params.append('grade', this.currentFilters.grade);
            if (this.currentFilters.temperature) params.append('temperature', this.currentFilters.temperature);
            
            const response = await fetch(`${this.baseUrl}?action=filters&${params.toString()}`);
            const data = await response.json();
            
            if (data.status === 'success') {
                this.renderFilteredCustomers(data.data);
            } else {
                console.error('Failed to load filtered customers:', data.message);
                this.showError('Failed to load customers');
            }
        } catch (error) {
            console.error('Error loading filtered customers:', error);
            this.showError('Error loading customers');
        }
    }

    renderFilteredCustomers(customers) {
        const container = document.getElementById('filtered-customers');
        if (!container) return;

        let html = `
            <div class="intelligence-section">
                <h3>🎯 Filtered Customers (${customers.length})</h3>
                <div class="customers-grid">
        `;

        if (customers.length === 0) {
            html += '<p>No customers found with the selected filters.</p>';
        } else {
            customers.forEach(customer => {
                const gradeColors = {
                    'A': '#28a745',
                    'B': '#007bff', 
                    'C': '#ffc107',
                    'D': '#6c757d'
                };

                const tempIcons = {
                    'HOT': '🔥',
                    'WARM': '☀️',
                    'COLD': '❄️'
                };

                html += `
                    <div class="customer-intelligence-card">
                        <div class="customer-header">
                            <div class="customer-name">${customer.CustomerName}</div>
                            <div class="customer-code">${customer.CustomerCode}</div>
                        </div>
                        <div class="customer-intelligence">
                            <div class="intelligence-badges">
                                <span class="grade-badge" style="background: ${gradeColors[customer.CustomerGrade]}">
                                    Grade ${customer.CustomerGrade}
                                </span>
                                <span class="temp-badge">
                                    ${tempIcons[customer.CustomerTemperature]} ${customer.CustomerTemperature}
                                </span>
                            </div>
                            <div class="customer-metrics">
                                <div class="metric">
                                    <label>Total Purchase:</label>
                                    <span>${this.formatMoney(customer.TotalPurchase)}</span>
                                </div>
                                <div class="metric">
                                    <label>Status:</label>
                                    <span>${customer.CustomerStatus}</span>
                                </div>
                                <div class="metric">
                                    <label>Last Contact:</label>
                                    <span>${customer.LastContactDate ? this.formatDate(customer.LastContactDate) : 'Never'}</span>
                                </div>
                            </div>
                        </div>
                        <div class="customer-actions">
                            <button class="btn btn-sm btn-primary" onclick="viewCustomerDetail('${customer.CustomerCode}')">
                                View Details
                            </button>
                            <button class="btn btn-sm btn-outline-secondary" onclick="updateCustomerIntelligence('${customer.CustomerCode}')">
                                Update Intelligence
                            </button>
                        </div>
                    </div>
                `;
            });
        }

        html += `
                </div>
            </div>
        `;

        container.innerHTML = html;
    }

    async updateAllGrades() {
        if (!confirm('อัปเดต Grade ทุกลูกค้าใช่หรือไม่? กระบวนการนี้อาจใช้เวลาสักครู่')) {
            return;
        }

        try {
            const response = await fetch(`${this.baseUrl}?action=update_all_grades`, {
                method: 'POST'
            });
            
            const data = await response.json();
            
            if (data.status === 'success') {
                this.showSuccess('อัปเดต Grade ทุกลูกค้าเรียบร้อยแล้ว');
                this.loadIntelligenceDashboard(); // Reload dashboard
            } else {
                this.showError('ไม่สามารถอัปเดต Grade ได้: ' + data.message);
            }
        } catch (error) {
            console.error('Error updating all grades:', error);
            this.showError('เกิดข้อผิดพลาดในการอัปเดต Grade');
        }
    }

    async updateAllTemperatures() {
        if (!confirm('อัปเดต Temperature ทุกลูกค้าใช่หรือไม่? กระบวนการนี้อาจใช้เวลาสักครู่')) {
            return;
        }

        try {
            const response = await fetch(`${this.baseUrl}?action=update_all_temperatures`, {
                method: 'POST'
            });
            
            const data = await response.json();
            
            if (data.status === 'success') {
                this.showSuccess('อัปเดต Temperature ทุกลูกค้าเรียบร้อยแล้ว');
                this.loadIntelligenceDashboard(); // Reload dashboard
            } else {
                this.showError('ไม่สามารถอัปเดต Temperature ได้: ' + data.message);
            }
        } catch (error) {
            console.error('Error updating all temperatures:', error);
            this.showError('เกิดข้อผิดพลาดในการอัปเดต Temperature');
        }
    }

    async updateCustomerGrade(customerCode) {
        try {
            const response = await fetch(`${this.baseUrl}?action=update_grade`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ customer_code: customerCode })
            });
            
            const data = await response.json();
            
            if (data.status === 'success') {
                this.showSuccess('อัปเดต Grade เรียบร้อยแล้ว');
                return data.data;
            } else {
                this.showError('ไม่สามารถอัปเดต Grade ได้: ' + data.message);
                return null;
            }
        } catch (error) {
            console.error('Error updating customer grade:', error);
            this.showError('เกิดข้อผิดพลาดในการอัปเดต Grade');
            return null;
        }
    }

    async updateCustomerTemperature(customerCode) {
        try {
            const response = await fetch(`${this.baseUrl}?action=update_temperature`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ customer_code: customerCode })
            });
            
            const data = await response.json();
            
            if (data.status === 'success') {
                this.showSuccess('อัปเดต Temperature เรียบร้อยแล้ว');
                return data.data;
            } else {
                this.showError('ไม่สามารถอัปเดต Temperature ได้: ' + data.message);
                return null;
            }
        } catch (error) {
            console.error('Error updating customer temperature:', error);
            this.showError('เกิดข้อผิดพลาดในการอัปเดต Temperature');
            return null;
        }
    }

    formatMoney(amount) {
        if (!amount || amount === '0' || amount === 0) return '0 ฿';
        return new Intl.NumberFormat('th-TH', {
            style: 'currency',
            currency: 'THB',
            minimumFractionDigits: 0
        }).format(amount);
    }

    formatDate(dateString) {
        if (!dateString) return 'N/A';
        const date = new Date(dateString);
        const day = String(date.getDate()).padStart(2, '0');
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const year = date.getFullYear();
        return `${day}/${month}/${year}`;
    }

    showSuccess(message) {
        // Create or update success message
        this.showMessage(message, 'success');
    }

    showError(message) {
        // Create or update error message
        this.showMessage(message, 'error');
    }

    showMessage(message, type) {
        // Remove existing messages
        const existingMessages = document.querySelectorAll('.intelligence-message');
        existingMessages.forEach(msg => msg.remove());

        // Create new message
        const messageDiv = document.createElement('div');
        messageDiv.className = `intelligence-message alert ${type === 'success' ? 'alert-success' : 'alert-danger'}`;
        messageDiv.innerHTML = `
            <span>${message}</span>
            <button type="button" class="btn-close" onclick="this.parentElement.remove()"></button>
        `;

        // Insert at top of main container
        const mainContainer = document.querySelector('.intelligence-container') || document.body;
        mainContainer.insertBefore(messageDiv, mainContainer.firstChild);

        // Auto-remove after 5 seconds
        setTimeout(() => {
            if (messageDiv.parentElement) {
                messageDiv.remove();
            }
        }, 5000);
    }
}

// Global functions for external use
async function updateCustomerIntelligence(customerCode) {
    if (!window.customerIntelligence) {
        window.customerIntelligence = new CustomerIntelligence();
    }
    
    const grade = await window.customerIntelligence.updateCustomerGrade(customerCode);
    const temperature = await window.customerIntelligence.updateCustomerTemperature(customerCode);
    
    if (grade && temperature) {
        // Reload filtered customers if filters are active
        if (window.customerIntelligence.currentFilters.grade || window.customerIntelligence.currentFilters.temperature) {
            window.customerIntelligence.loadFilteredCustomers();
        }
    }
}

function viewCustomerDetail(customerCode) {
    // Navigate to customer detail page
    window.location.href = `customer_detail.php?code=${encodeURIComponent(customerCode)}`;
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    // Only initialize if we're on a page with intelligence containers
    if (document.querySelector('.intelligence-container')) {
        window.customerIntelligence = new CustomerIntelligence();
    }
});

// Export for module usage
if (typeof module !== 'undefined' && module.exports) {
    module.exports = CustomerIntelligence;
}