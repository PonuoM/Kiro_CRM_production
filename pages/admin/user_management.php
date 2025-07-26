<?php
/**
 * User Management Page
 * Admin interface for managing user accounts
 */

// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Simple login check (avoid permission system redirect loops)
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

require_once '../../includes/admin_layout.php';

$pageTitle = "จัดการผู้ใช้งาน";

// Additional CSS for this page
$additionalCSS = '
    <style>
        .user-card {
            background: #ffffff;
            border-radius: 0.75rem;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            border: 1px solid #e5e7eb;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
        }
        
        .toolbar {
            background: #ffffff;
            padding: 1.5rem;
            border-radius: 0.75rem;
            margin-bottom: 1.5rem;
            border: 1px solid #e5e7eb;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
        }
        
        .users-table {
            background: #ffffff;
            border-radius: 0.75rem;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
            overflow: hidden;
            border: 1px solid #e5e7eb;
        }
        
        .table th {
            background-color: #f9fafb;
            font-weight: 600;
            color: #374151;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .table tbody tr:hover {
            background-color: #f9fafb;
        }
        
        .status-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 0.375rem;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .status-active {
            background-color: rgba(34, 197, 94, 0.1);
            color: #16a34a;
        }
        
        .status-inactive {
            background-color: rgba(239, 68, 68, 0.1);
            color: #dc2626;
        }
        
        .role-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 0.375rem;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .role-admin {
            background-color: rgba(59, 130, 246, 0.1);
            color: #2563eb;
        }
        
        .role-supervisor {
            background-color: rgba(245, 158, 11, 0.1);
            color: #d97706;
        }
        
        .role-sales {
            background-color: rgba(168, 85, 247, 0.1);
            color: #9333ea;
        }
        
        .pagination {
            background: #ffffff;
            border-radius: 0.75rem;
            margin-top: 1.5rem;
            border: 1px solid #e5e7eb;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
        }
        
        .pagination button {
            border: 1px solid #e5e7eb;
            background: #ffffff;
            color: #374151;
            border-radius: 0.375rem;
            padding: 0.5rem 0.75rem;
            margin: 0 0.25rem;
        }
        
        .pagination button:hover:not(:disabled) {
            background-color: #f9fafb;
        }
        
        .pagination .current {
            background-color: #3b82f6;
            color: #ffffff;
            border-color: #3b82f6;
        }
        
        .modal-content {
            background-color: #ffffff;
            color: #374151;
            border: 1px solid #e5e7eb;
        }
        
        .form-control:focus,
        .form-select:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 0.2rem rgba(59, 130, 246, 0.25);
        }
        
        .alert.success {
            background-color: rgba(34, 197, 94, 0.1);
            color: #16a34a;
            border: 1px solid rgba(34, 197, 94, 0.2);
            border-radius: 0.5rem;
            padding: 0.75rem 1rem;
        }
        
        .alert.error {
            background-color: rgba(239, 68, 68, 0.1);
            color: #dc2626;
            border: 1px solid rgba(239, 68, 68, 0.2);
            border-radius: 0.5rem;
            padding: 0.75rem 1rem;
        }
        
        .actions {
            display: flex;
            gap: 0.5rem;
            justify-content: center;
        }
        
        .text-center {
            text-align: center !important;
        }
    </style>
';

// Page content
ob_start();
?>

<!-- Page Header -->
<div class="page-header">
    <h1 class="page-title">
        <i class="fas fa-users-cog"></i>
        จัดการผู้ใช้งาน
    </h1>
    <p class="page-description">
        จัดการบัญชีผู้ใช้ บทบาท และสิทธิ์การเข้าถึงระบบ
    </p>
</div>

<!-- Alert Messages -->
<div id="alert" class="alert" style="display: none;"></div>

<!-- Search and Filter Toolbar -->
<div class="user-card">
    <div class="row">
        <div class="col-md-8">
            <div class="row g-3">
                <div class="col-md-4">
                    <input type="text" class="form-control" id="searchInput" placeholder="ค้นหาผู้ใช้...">
                </div>
                <div class="col-md-3">
                    <select class="form-select" id="roleFilter">
                        <option value="">ทุกบทบาท</option>
                        <option value="Admin">Admin</option>
                        <option value="Supervisor">Supervisor</option>
                        <option value="Sales">Sales</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select class="form-select" id="statusFilter">
                        <option value="">ทุกสถานะ</option>
                        <option value="1">ใช้งาน</option>
                        <option value="0">ปิดใช้งาน</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="button" class="btn btn-primary w-100" onclick="loadUsers()">
                        <i class="fas fa-search"></i> ค้นหา
                    </button>
                </div>
            </div>
        </div>
        <div class="col-md-4 text-end">
            <button type="button" class="btn btn-success" onclick="showCreateModal()">
                <i class="fas fa-plus"></i> เพิ่มผู้ใช้ใหม่
            </button>
        </div>
    </div>
</div>

<!-- Users Table -->
<div class="users-table">
    <div id="loading" class="text-center py-4" style="display: none;">
        <i class="fas fa-spinner fa-spin"></i> กำลังโหลดข้อมูล...
    </div>
    
    <div id="usersContent">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>ชื่อผู้ใช้</th>
                        <th>ชื่อ-นามสกุล</th>
                        <th>อีเมล</th>
                        <th>บทบาท</th>
                        <th>สถานะ</th>
                        <th>เข้าสู่ระบบล่าสุด</th>
                        <th class="text-center">จัดการ</th>
                    </tr>
                </thead>
                <tbody id="usersTableBody">
                    <!-- Users will be loaded here -->
                </tbody>
            </table>
        </div>
    </div>
    
    <div id="emptyState" class="text-center py-5" style="display: none;">
        <i class="fas fa-users fa-3x text-muted mb-3"></i>
        <p class="text-muted">ไม่พบข้อมูลผู้ใช้</p>
    </div>
</div>

<!-- Pagination -->
<div id="pagination" class="pagination d-flex justify-content-center align-items-center gap-2 py-3" style="display: none;">
    <!-- Pagination will be loaded here -->
</div>

<!-- User Modal -->
<div class="modal fade" id="userModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">เพิ่มผู้ใช้ใหม่</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="userForm">
                    <input type="hidden" id="userId" name="userId">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="username" class="form-label">Username *</label>
                                <input type="text" class="form-control" id="username" name="username" required>
                                <div class="text-danger" id="username-error" style="display: none;"></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="password" class="form-label">Password *</label>
                                <input type="password" class="form-control" id="password" name="password">
                                <div class="text-danger" id="password-error" style="display: none;"></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="firstName" class="form-label">ชื่อ *</label>
                                <input type="text" class="form-control" id="firstName" name="firstName" required>
                                <div class="text-danger" id="firstName-error" style="display: none;"></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="lastName" class="form-label">นามสกุล *</label>
                                <input type="text" class="form-control" id="lastName" name="lastName" required>
                                <div class="text-danger" id="lastName-error" style="display: none;"></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="email" class="form-label">อีเมล</label>
                                <input type="email" class="form-control" id="email" name="email">
                                <div class="text-danger" id="email-error" style="display: none;"></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="phone" class="form-label">เบอร์โทรศัพท์</label>
                                <input type="text" class="form-control" id="phone" name="phone">
                                <div class="text-danger" id="phone-error" style="display: none;"></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="companyCode" class="form-label">รหัสบริษัท</label>
                                <input type="text" class="form-control" id="companyCode" name="companyCode">
                                <div class="text-danger" id="companyCode-error" style="display: none;"></div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="position" class="form-label">ตำแหน่ง</label>
                                <input type="text" class="form-control" id="position" name="position">
                                <div class="text-danger" id="position-error" style="display: none;"></div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="role" class="form-label">บทบาท *</label>
                                <select class="form-select" id="role" name="role" required>
                                    <option value="">เลือกบทบาท</option>
                                    <option value="Admin">Admin</option>
                                    <option value="Supervisor">Supervisor</option>
                                    <option value="Sales">Sales</option>
                                </select>
                                <div class="text-danger" id="role-error" style="display: none;"></div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                <button type="submit" class="btn btn-primary" id="submitBtn" form="userForm">บันทึก</button>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();

// Additional JavaScript - Fixed heredoc syntax 2025-07-21 11:20:00
$additionalJS = <<<'JS'
    <script>
        // Use relative API path
        const apiPath = "../../api/";
        let currentPage = 1;
        let isEditing = false;
        
        // Load users on page load
        document.addEventListener(\'DOMContentLoaded\', function() {
            loadUsers();
        });
        
        // Load users function
        async function loadUsers(page = 1) {
            currentPage = page;
            
            const search = document.getElementById(\'searchInput\').value;
            const role = document.getElementById(\'roleFilter\').value;
            const status = document.getElementById(\'statusFilter\').value;
            
            const params = new URLSearchParams({
                page: page,
                limit: 20
            });
            
            if (search) params.append(\'search\', search);
            if (role) params.append(\'role\', role);
            if (status !== \'\') params.append(\'status\', status);
            
            showLoading(true);
            
            try {
                const response = await fetch(`${apiPath}users/list.php?${params}`);
                const result = await response.json();
                
                if (result.success) {
                    displayUsers(result.data);
                    displayPagination(result.pagination);
                } else {
                    showAlert(result.message, 'error');
                    displayUsers([]);
                }
            } catch (error) {
                console.error('Load users error:', error);
                showAlert("เกิดข้อผิดพลาดในการโหลดข้อมูล", "error");
                displayUsers([]);
            } finally {
                showLoading(false);
            }
        }
        
        // Display users in table
        function displayUsers(users) {
            const tbody = document.getElementById('usersTableBody');
            const emptyState = document.getElementById('emptyState');
            const usersContent = document.getElementById('usersContent');
            
            if (users.length === 0) {
                usersContent.style.display = 'none';
                emptyState.style.display = 'block';
                return;
            }
            
            usersContent.style.display = 'block';
            emptyState.style.display = 'none';
            
            tbody.innerHTML = users.map(user => `
                <tr>
                    <td>${user.username}</td>
                    <td>${user.fullName}</td>
                    <td>${user.email || '-'}</td>
                    <td><span class="role-badge role-${user.role.toLowerCase()}">${user.role}</span></td>
                    <td><span class="status-badge status-${user.status == 1 ? 'active' : 'inactive'}">${user.statusText}</span></td>
                    <td>${user.lastLoginDate}</td>
                    <td>
                        <div class="actions">
                            <button class="btn btn-primary btn-sm" onclick="editUser(${user.id})">แก้ไข</button>
                            <button class="btn ${user.status == 1 ? 'btn-warning' : 'btn-success'} btn-sm" 
                                    onclick="toggleUserStatus(${user.id}, ${user.status})">
                                ${user.status == 1 ? "ปิดใช้งาน" : "เปิดใช้งาน"}
                            </button>
                        </div>
                    </td>
                </tr>
            `).join('');
        }
        
        // Display pagination
        function displayPagination(pagination) {
            const paginationDiv = document.getElementById('pagination');
            
            if (pagination.totalPages <= 1) {
                paginationDiv.style.display = 'none';
                return;
            }
            
            paginationDiv.style.display = 'flex';
            
            let paginationHTML = '';
            
            // Previous button
            paginationHTML += `
                <button ${!pagination.hasPrev ? 'disabled' : ''} 
                        onclick="loadUsers(${pagination.page - 1})">
                    ก่อนหน้า
                </button>
            `;
            
            // Page numbers
            const startPage = Math.max(1, pagination.page - 2);
            const endPage = Math.min(pagination.totalPages, pagination.page + 2);
            
            for (let i = startPage; i <= endPage; i++) {
                paginationHTML += `
                    <button class="${i === pagination.page ? 'current' : ''}" 
                            onclick="loadUsers(${i})">
                        ${i}
                    </button>
                `;
            }
            
            // Next button
            paginationHTML += `
                <button ${!pagination.hasNext ? 'disabled' : ''} 
                        onclick="loadUsers(${pagination.page + 1})">
                    ถัดไป
                </button>
            `;
            
            paginationDiv.innerHTML = paginationHTML;
        }
        
        // Show/hide loading
        function showLoading(show) {
            document.getElementById('loading').style.display = show ? 'block' : 'none';
        }
        
        // Show alert
        function showAlert(message, type = 'error') {
            const alert = document.getElementById('alert');
            alert.textContent = message;
            alert.className = 'alert ' + type;
            alert.style.display = 'block';
            
            setTimeout(() => {
                alert.style.display = 'none';
            }, 5000);
        }
        
        // Show create modal
        function showCreateModal() {
            isEditing = false;
            document.getElementById(\'modalTitle\').textContent = "เพิ่มผู้ใช้ใหม่";
            document.getElementById(\'userForm\').reset();
            document.getElementById(\'userId\').value = \'\';
            document.getElementById(\'password\').required = true;
            clearFormErrors();
            new bootstrap.Modal(document.getElementById(\'userModal\')).show();
        }
        
        // Edit user
        async function editUser(userId) {
            try {
                const response = await fetch(`${apiPath}users/detail.php?id=${userId}`);
                const result = await response.json();
                
                if (result.success) {
                    isEditing = true;
                    document.getElementById(\'modalTitle\').textContent = "แก้ไขผู้ใช้";
                    
                    const user = result.data;
                    document.getElementById(\'userId\').value = user.id;
                    document.getElementById(\'username\').value = user.username;
                    document.getElementById(\'firstName\').value = user.firstName;
                    document.getElementById(\'lastName\').value = user.lastName;
                    document.getElementById(\'email\').value = user.email || \'\';
                    document.getElementById(\'phone\').value = user.phone || \'\';
                    document.getElementById(\'companyCode\').value = user.companyCode || \'\';
                    document.getElementById(\'position\').value = user.position || \'\';
                    document.getElementById(\'role\').value = user.role;
                    
                    document.getElementById(\'password\').required = false;
                    document.getElementById(\'password\').placeholder = \'เว้นว่างหากไม่ต้องการเปลี่ยน\';
                    
                    clearFormErrors();
                    new bootstrap.Modal(document.getElementById(\'userModal\')).show();
                } else {
                    showAlert(result.message, \'error\');
                }
            } catch (error) {
                console.error(\'Edit user error:\', error);
                showAlert(\'เกิดข้อผิดพลาดในการโหลดข้อมูลผู้ใช้\', \'error\');
            }
        }
        
        // Toggle user status
        async function toggleUserStatus(userId, currentStatus) {
            const action = currentStatus == 1 ? "ปิดใช้งาน" : "เปิดใช้งาน";
            
            if (!confirm(`คุณต้องการ${action}ผู้ใช้นี้หรือไม่?`)) {
                return;
            }
            
            try {
                const response = await fetch(`${apiPath}users/toggle_status.php`, {
                    method: \'POST\',
                    headers: {
                        \'Content-Type\': \'application/json\',
                    },
                    body: JSON.stringify({
                        id: userId
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showAlert(result.message, 'success');
                    loadUsers(currentPage);
                } else {
                    showAlert(result.message, 'error');
                }
            } catch (error) {
                console.error('Toggle status error:', error);
                showAlert("เกิดข้อผิดพลาดในการเปลี่ยนสถานะ", "error");
            }
        }
        
        // Close modal
        function closeModal() {
            bootstrap.Modal.getInstance(document.getElementById(\'userModal\')).hide();
            document.getElementById(\'userForm\').reset();
            clearFormErrors();
        }
        
        // Clear form errors
        function clearFormErrors() {
            document.querySelectorAll(\'.text-danger\').forEach(error => {
                error.style.display = \'none\';
            });
            document.querySelectorAll(\'.is-invalid\').forEach(field => {
                field.classList.remove(\'is-invalid\');
            });
        }
        
        // Show field error
        function showFieldError(fieldName, message) {
            const field = document.getElementById(fieldName);
            const errorElement = document.getElementById(fieldName + \'-error\');
            
            if (field && errorElement) {
                field.classList.add(\'is-invalid\');
                errorElement.textContent = message;
                errorElement.style.display = \'block\';
            }
        }
        
        // Handle form submission
        document.getElementById(\'userForm\').addEventListener(\'submit\', async function(e) {
            e.preventDefault();
            
            clearFormErrors();
            
            const formData = new FormData(this);
            const userData = {};
            
            // Collect form data
            for (let [key, value] of formData.entries()) {
                if (key !== \'userId\') {
                    userData[key] = value.trim();
                }
            }
            
            if (isEditing) {
                userData.id = parseInt(document.getElementById(\'userId\').value);
            }
            
            const submitBtn = document.getElementById(\'submitBtn\');
            submitBtn.disabled = true;
            submitBtn.textContent = "กำลังบันทึก...";
            
            try {
                const url = isEditing ? `${apiPath}users/update.php` : `${apiPath}users/create.php`;
                const method = isEditing ? \'PUT\' : \'POST\';
                
                const response = await fetch(url, {
                    method: method,
                    headers: {
                        \'Content-Type\': \'application/json\',
                    },
                    body: JSON.stringify(userData)
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showAlert(result.message, \'success\');
                    closeModal();
                    loadUsers(currentPage);
                } else {
                    if (result.errors) {
                        result.errors.forEach(error => {
                            showAlert(error, \'error\');
                        });
                    } else {
                        showAlert(result.message, \'error\');
                    }
                }
            } catch (error) {
                console.error(\'Save user error:\', error);
                showAlert(\'เกิดข้อผิดพลาดในการบันทึกข้อมูล\', \'error\');
            } finally {
                submitBtn.disabled = false;
                submitBtn.textContent = "บันทึก";
            }
        });
        
        // Search on Enter key
        document.getElementById(\'searchInput\').addEventListener(\'keypress\', function(e) {
            if (e.key === \'Enter\') {
                loadUsers();
            }
        });
    </script>
JS;

// Render the page
echo renderAdminLayout($pageTitle, $content, $additionalCSS, $additionalJS);
?>