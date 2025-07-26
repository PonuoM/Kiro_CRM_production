// Sales Records JavaScript Functions

updateStats(stats) {
    document.getElementById("totalOrders").textContent = stats.total_orders || 0;
    document.getElementById("todayOrders").textContent = stats.today_orders || 0;
    document.getElementById("monthOrders").textContent = stats.month_orders || 0;
    
    const totalSales = stats.total_sales || 0;
    document.getElementById("totalSales").textContent = this.formatCurrency(totalSales);
}

filterSales(searchTerm) {
    if (!searchTerm.trim()) {
        this.filteredSales = [...this.salesRecords];
    } else {
        const term = searchTerm.toLowerCase();
        this.filteredSales = this.salesRecords.filter(sale => 
            (sale.CustomerName && sale.CustomerName.toLowerCase().includes(term)) ||
            (sale.OrderNumber && sale.OrderNumber.toLowerCase().includes(term)) ||
            (sale.CustomerCode && sale.CustomerCode.toLowerCase().includes(term)) ||
            (sale.Products && sale.Products.some(p => p.ProductName.toLowerCase().includes(term)))
        );
    }
    
    const contentEl = document.getElementById("salesRecordsList");
    contentEl.innerHTML = this.renderSalesTable(this.filteredSales);
}

renderSalesTable(salesRecords) {
    if (!salesRecords || salesRecords.length === 0) {
        return this.renderEmptyState("ไม่พบรายการขาย", "ไม่มีรายการขายในระบบ หรือคุณไม่มีสิทธิ์เข้าถึงข้อมูล");
    }
    
    return `
        <div class="table-responsive">
            <table class="table table-hover">
                <thead class="table-light">
                    <tr>
                        <th>หมายเลขคำสั่งซื้อ</th>
                        <th>วันที่สั่งซื้อ</th>
                        <th>ลูกค้า</th>
                        <th>สินค้า</th>
                        <th>มูลค่า</th>
                        <th>สถานะ</th>
                        <th>ผู้ขาย</th>
                        <th>การจัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    ${salesRecords.map(sale => `
                        <tr>
                            <td>
                                <strong>${this.escapeHtml(sale.OrderNumber || sale.OrderID)}</strong>
                            </td>
                            <td>${this.formatDateTime(sale.OrderDate)}</td>
                            <td>
                                <strong>${this.escapeHtml(sale.CustomerName || 'ไม่ระบุ')}</strong>
                                <br><small class="text-muted">${sale.CustomerCode}</small>
                                ${sale.CustomerTel ? `<br><small><i class="fas fa-phone"></i> ${sale.CustomerTel}</small>` : ''}
                            </td>
                            <td>
                                ${sale.Products && sale.Products.length > 0 ? 
                                    sale.Products.map(product => `
                                        <div class="mb-1">
                                            <strong>${this.escapeHtml(product.ProductName)}</strong>
                                            <br><small class="text-muted">จำนวน: ${product.Quantity} | ราคา: ${this.formatCurrency(product.UnitPrice)}</small>
                                        </div>
                                    `).join('') : '-'
                                }
                            </td>
                            <td>
                                <strong class="text-success">${this.formatCurrency(sale.TotalAmount)}</strong>
                            </td>
                            <td>
                                <span class="badge ${this.getOrderStatusBadgeClass(sale.OrderStatus)}">${sale.OrderStatus}</span>
                            </td>
                            <td>
                                <span class="badge bg-info">${this.escapeHtml(sale.SalesBy || sale.AssignedSales || 'ไม่ระบุ')}</span>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <button class="btn btn-outline-primary" onclick="viewOrderDetail('${sale.OrderID}')" title="ดูรายละเอียด">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="btn btn-outline-success" onclick="viewCustomerDetail('${sale.CustomerCode}')" title="ดูข้อมูลลูกค้า">
                                        <i class="fas fa-user"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    `).join("")}
                </tbody>
            </table>
        </div>
    `;
}

renderEmptyState(title, message) {
    return `
        <div class="text-center py-5">
            <i class="fas fa-chart-line fa-3x text-muted mb-3"></i>
            <h5 class="text-muted">${title}</h5>
            <p class="text-muted">${message}</p>
        </div>
    `;
}

renderErrorState(message) {
    return `
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-triangle"></i>
            <strong>เกิดข้อผิดพลาด:</strong> ${message}
            <br><button class="btn btn-sm btn-danger mt-2" onclick="location.reload()">ลองใหม่</button>
        </div>
    `;
}

getOrderStatusBadgeClass(status) {
    switch(status) {
        case "รอดำเนินการ": return "bg-warning";
        case "กำลังดำเนินการ": return "bg-info";
        case "เสร็จสิ้น": return "bg-success";
        case "ยกเลิก": return "bg-danger";
        default: return "bg-secondary";
    }
}

formatDateTime(dateString) {
    if (!dateString) return "-";
    const date = new Date(dateString);
    return date.toLocaleDateString("th-TH", {
        year: "numeric",
        month: "short",
        day: "numeric",
        hour: "2-digit",
        minute: "2-digit"
    });
}

formatCurrency(amount) {
    if (!amount) return "0.00";
    return parseFloat(amount).toLocaleString('th-TH', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

escapeHtml(text) {
    if (!text) return "";
    const div = document.createElement("div");
    div.textContent = text;
    return div.innerHTML;
}

// Global functions
function refreshSalesRecords() {
    if (window.salesManager) {
        window.salesManager.loadSalesRecords();
    }
}

function loadSalesRecords() {
    if (window.salesManager) {
        window.salesManager.loadSalesRecords();
    }
}

function searchSales() {
    const searchTerm = document.getElementById("search").value;
    if (window.salesManager) {
        window.salesManager.filterSales(searchTerm);
    }
}

function viewOrderDetail(orderId) {
    window.location.href = `order_detail.php?id=${encodeURIComponent(orderId)}`;
}

function viewCustomerDetail(customerCode) {
    window.location.href = `customer_detail.php?code=${encodeURIComponent(customerCode)}`;
}