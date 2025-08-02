/**
 * Call Log Popup Manager
 * จัดการ popup form สำหรับบันทึกการโทร
 */

class CallLogPopup {
    constructor() {
        this.currentCustomer = null;
        this.isSubmitting = false;
        this.setupStyles();
    }

    /**
     * เพิ่ม CSS styles สำหรับ popup
     */
    setupStyles() {
        if (document.getElementById('call-log-popup-styles')) return;

        const styles = `
            <style id="call-log-popup-styles">
                /* Call Log Popup Overlay */
                .call-log-overlay {
                    position: fixed;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    background-color: rgba(0, 0, 0, 0.5);
                    z-index: 9999;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    backdrop-filter: blur(4px);
                    opacity: 0;
                    transition: opacity 0.3s ease;
                    pointer-events: none;
                }

                .call-log-overlay.show {
                    opacity: 1;
                    pointer-events: all;
                }

                /* Call Log Modal */
                .call-log-modal {
                    background: white;
                    border-radius: 12px;
                    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
                    width: 90%;
                    max-width: 500px;
                    max-height: 90vh;
                    overflow-y: auto;
                    transform: scale(0.9) translateY(20px);
                    transition: all 0.3s ease;
                }

                .call-log-overlay.show .call-log-modal {
                    transform: scale(1) translateY(0);
                }

                /* Modal Header */
                .call-log-header {
                    padding: 24px 24px 0 24px;
                    border-bottom: 1px solid #e2e8f0;
                    margin-bottom: 24px;
                }

                .call-log-title {
                    font-size: 1.5rem;
                    font-weight: 600;
                    color: #0f172a;
                    margin: 0 0 8px 0;
                    display: flex;
                    align-items: center;
                    gap: 12px;
                }

                .call-log-customer {
                    font-size: 0.875rem;
                    color: #64748b;
                    margin: 0;
                    display: flex;
                    align-items: center;
                    gap: 8px;
                }

                .call-log-close {
                    position: absolute;
                    top: 16px;
                    right: 16px;
                    background: none;
                    border: none;
                    font-size: 1.25rem;
                    color: #64748b;
                    cursor: pointer;
                    width: 32px;
                    height: 32px;
                    border-radius: 6px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    transition: all 0.2s ease;
                }

                .call-log-close:hover {
                    background-color: #f1f5f9;
                    color: #0f172a;
                }

                /* Modal Body */
                .call-log-body {
                    padding: 0 24px 24px 24px;
                }

                /* Form Groups */
                .form-group {
                    margin-bottom: 20px;
                }

                .form-label {
                    display: block;
                    font-weight: 500;
                    color: #374151;
                    margin-bottom: 8px;
                    font-size: 0.875rem;
                }

                .form-label.required::after {
                    content: ' *';
                    color: #ef4444;
                }

                .form-control {
                    width: 100%;
                    padding: 12px 16px;
                    border: 1px solid #d1d5db;
                    border-radius: 8px;
                    font-size: 0.875rem;
                    transition: all 0.2s ease;
                    background-color: #ffffff;
                }

                .form-control:focus {
                    outline: none;
                    border-color: #76BC43;
                    box-shadow: 0 0 0 3px rgba(118, 188, 67, 0.1);
                }

                .form-control.error {
                    border-color: #ef4444;
                    box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1);
                }

                /* Select Styling */
                .form-select {
                    appearance: none;
                    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='m6 8 4 4 4-4'/%3e%3c/svg%3e");
                    background-position: right 12px center;
                    background-repeat: no-repeat;
                    background-size: 16px 16px;
                    padding-right: 48px;
                }

                /* Two Column Layout */
                .form-row {
                    display: grid;
                    grid-template-columns: 1fr 1fr;
                    gap: 16px;
                }

                @media (max-width: 480px) {
                    .form-row {
                        grid-template-columns: 1fr;
                    }
                }

                /* Textarea */
                textarea.form-control {
                    resize: vertical;
                    min-height: 80px;
                }

                /* Error Messages */
                .error-message {
                    color: #ef4444;
                    font-size: 0.75rem;
                    margin-top: 4px;
                    display: none;
                }

                .error-message.show {
                    display: block;
                }

                /* Action Buttons */
                .call-log-actions {
                    display: flex;
                    gap: 12px;
                    justify-content: flex-end;
                    margin-top: 32px;
                    padding-top: 24px;
                    border-top: 1px solid #e2e8f0;
                }

                .btn-call-log {
                    padding: 12px 24px;
                    border-radius: 8px;
                    font-weight: 500;
                    font-size: 0.875rem;
                    transition: all 0.2s ease;
                    cursor: pointer;
                    border: 1px solid transparent;
                    display: flex;
                    align-items: center;
                    gap: 8px;
                }

                .btn-call-log:disabled {
                    opacity: 0.6;
                    cursor: not-allowed;
                }

                .btn-call-log-primary {
                    background-color: #76BC43;
                    color: white;
                    border-color: #76BC43;
                }

                .btn-call-log-primary:hover:not(:disabled) {
                    background-color: #5da832;
                    border-color: #5da832;
                }

                .btn-call-log-secondary {
                    background-color: #f8fafc;
                    color: #475569;
                    border-color: #e2e8f0;
                }

                .btn-call-log-secondary:hover:not(:disabled) {
                    background-color: #f1f5f9;
                    border-color: #cbd5e1;
                }

                /* Loading Spinner */
                .spinner {
                    width: 16px;
                    height: 16px;
                    border: 2px solid transparent;
                    border-top: 2px solid currentColor;
                    border-radius: 50%;
                    animation: spin 1s linear infinite;
                }

                @keyframes spin {
                    to {
                        transform: rotate(360deg);
                    }
                }

                /* Quick Actions */
                .quick-actions {
                    margin-bottom: 24px;
                    padding: 16px;
                    background-color: #f8fafc;
                    border-radius: 8px;
                    border: 1px solid #e2e8f0;
                }

                .quick-actions-title {
                    font-size: 0.875rem;
                    font-weight: 500;
                    color: #374151;
                    margin: 0 0 12px 0;
                }

                .quick-buttons {
                    display: flex;
                    gap: 8px;
                    flex-wrap: wrap;
                }

                .btn-quick {
                    padding: 8px 16px;
                    border-radius: 6px;
                    font-size: 0.75rem;
                    font-weight: 500;
                    border: 1px solid #e2e8f0;
                    background-color: white;
                    color: #374151;
                    cursor: pointer;
                    transition: all 0.2s ease;
                }

                .btn-quick:hover {
                    background-color: #76BC43;
                    color: white;
                    border-color: #76BC43;
                }

                .btn-quick.success {
                    background-color: #10b981;
                    color: white;
                    border-color: #10b981;
                }

                .btn-quick.danger {
                    background-color: #ef4444;
                    color: white;
                    border-color: #ef4444;
                }
            </style>
        `;

        document.head.insertAdjacentHTML('beforeend', styles);
    }

    /**
     * แสดง popup สำหรับบันทึกการโทร
     * @param {Object} customer - ข้อมูลลูกค้า
     */
    show(customer) {
        this.currentCustomer = customer;
        
        const popupHTML = `
            <div class="call-log-overlay" id="callLogOverlay">
                <div class="call-log-modal">
                    <button class="call-log-close" onclick="callLogPopup.hide()">
                        <i class="fas fa-times"></i>
                    </button>
                    
                    <div class="call-log-header">
                        <h3 class="call-log-title">
                            <i class="fas fa-phone text-success"></i>
                            บันทึกการโทร
                        </h3>
                        <p class="call-log-customer">
                            <i class="fas fa-user"></i>
                            <strong>${this.escapeHtml(customer.CustomerName || 'ลูกค้า')}</strong>
                            <span class="mx-2">•</span>
                            <i class="fas fa-phone"></i>
                            ${this.escapeHtml(customer.CustomerTel || 'ไม่ระบุ')}
                        </p>
                    </div>

                    <div class="call-log-body">
                        <!-- Quick Actions -->
                        <div class="quick-actions">
                            <p class="quick-actions-title">การกรอกข้อมูลรวดเร็ว:</p>
                            <div class="quick-buttons">
                                <button class="btn-quick success" onclick="callLogPopup.quickFill('success')">
                                    ✅ ติดต่อได้ คุยจบ
                                </button>
                                <button class="btn-quick danger" onclick="callLogPopup.quickFill('busy')">
                                    📞 สายไม่ว่าง
                                </button>
                                <button class="btn-quick danger" onclick="callLogPopup.quickFill('no_answer')">
                                    📵 ไม่รับสาย
                                </button>
                                <button class="btn-quick" onclick="callLogPopup.quickFill('callback')">
                                    🔄 ขอโทรกลับ
                                </button>
                            </div>
                        </div>

                        <form id="callLogForm">
                            <input type="hidden" id="customerCode" value="${this.escapeHtml(customer.CustomerCode || '')}">
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label required">วันที่โทร</label>
                                    <input type="datetime-local" id="callDate" class="form-control" required>
                                    <div class="error-message" id="callDateError">กรุณาระบุวันที่โทร</div>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">ระยะเวลา (นาที)</label>
                                    <input type="number" id="callMinutes" class="form-control" min="0" max="999" placeholder="เช่น 5">
                                    <div class="error-message" id="callMinutesError">ระยะเวลาไม่ถูกต้อง</div>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label required">สถานะการโทร</label>
                                    <select id="callStatus" class="form-control form-select" required>
                                        <option value="">-- เลือกสถานะ --</option>
                                        <option value="ติดต่อได้">ติดต่อได้</option>
                                        <option value="ติดต่อไม่ได้">ติดต่อไม่ได้</option>
                                    </select>
                                    <div class="error-message" id="callStatusError">กรุณาเลือกสถานะการโทร</div>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">สถานะการคุย</label>
                                    <select id="talkStatus" class="form-control form-select">
                                        <option value="">-- เลือกสถานะ --</option>
                                        <option value="คุยจบ">คุยจบ</option>
                                        <option value="คุยไม่จบ">คุยไม่จบ</option>
                                    </select>
                                    <div class="error-message" id="talkStatusError">กรุณาเลือกสถานะการคุย</div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="form-label">หมายเหตุการโทร</label>
                                <textarea id="callRemarks" class="form-control" rows="3" placeholder="บันทึกรายละเอียดการโทร เช่น ความสนใจ ข้อกังวล หรือข้อเสนอแนะ"></textarea>
                            </div>

                            <div class="call-log-actions">
                                <button type="button" class="btn-call-log btn-call-log-secondary" onclick="callLogPopup.hide()">
                                    ยกเลิก
                                </button>
                                <button type="submit" class="btn-call-log btn-call-log-primary" id="submitBtn">
                                    <i class="fas fa-save"></i>
                                    บันทึกการโทร
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        `;

        // Remove existing popup if any
        this.hide();

        // Add popup to body
        document.body.insertAdjacentHTML('beforeend', popupHTML);

        // Set current date/time
        const now = new Date();
        const localDateTime = new Date(now.getTime() - now.getTimezoneOffset() * 60000)
            .toISOString().slice(0, 16);
        document.getElementById('callDate').value = localDateTime;

        // Setup form handlers
        this.setupFormHandlers();

        // Show popup with animation
        setTimeout(() => {
            document.getElementById('callLogOverlay').classList.add('show');
        }, 10);

        // Focus first input
        setTimeout(() => {
            document.getElementById('callDate').focus();
        }, 300);
    }

    /**
     * ซ่อน popup
     */
    hide() {
        const overlay = document.getElementById('callLogOverlay');
        if (overlay) {
            overlay.classList.remove('show');
            setTimeout(() => {
                overlay.remove();
            }, 300);
        }
        this.currentCustomer = null;
        this.isSubmitting = false;
    }

    /**
     * กรอกข้อมูลรวดเร็วตามสถานการณ์
     */
    quickFill(type) {
        const callStatus = document.getElementById('callStatus');
        const talkStatus = document.getElementById('talkStatus');
        const remarks = document.getElementById('callRemarks');
        const minutes = document.getElementById('callMinutes');

        switch (type) {
            case 'success':
                callStatus.value = 'ติดต่อได้';
                talkStatus.value = 'คุยจบ';
                minutes.value = '5';
                remarks.value = 'ติดต่อได้ คุยจบเรียบร้อย';
                break;
            case 'busy':
                callStatus.value = 'ติดต่อไม่ได้';
                talkStatus.value = '';
                minutes.value = '1';
                remarks.value = 'สายไม่ว่าง';
                break;
            case 'no_answer':
                callStatus.value = 'ติดต่อไม่ได้';
                talkStatus.value = '';
                minutes.value = '0';
                remarks.value = 'ไม่รับสาย';
                break;
            case 'callback':
                callStatus.value = 'ติดต่อได้';
                talkStatus.value = 'คุยไม่จบ';
                minutes.value = '2';
                remarks.value = 'ขอให้โทรกลับภายหลัง';
                break;
        }

        // Clear any existing errors
        this.clearErrors();
    }

    /**
     * Setup form event handlers
     */
    setupFormHandlers() {
        const form = document.getElementById('callLogForm');
        const callStatus = document.getElementById('callStatus');
        const talkStatus = document.getElementById('talkStatus');

        // Handle call status change
        callStatus.addEventListener('change', () => {
            if (callStatus.value === 'ติดต่อไม่ได้') {
                talkStatus.value = '';
                talkStatus.disabled = true;
            } else {
                talkStatus.disabled = false;
            }
            this.clearFieldError('callStatus');
        });

        // Clear field errors on input
        const fields = ['callDate', 'callMinutes', 'talkStatus', 'callRemarks'];
        fields.forEach(fieldId => {
            const field = document.getElementById(fieldId);
            if (field) {
                field.addEventListener('input', () => {
                    this.clearFieldError(fieldId);
                });
            }
        });

        // Handle form submission
        form.addEventListener('submit', (e) => {
            e.preventDefault();
            this.submitForm();
        });

        // Handle ESC key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && document.getElementById('callLogOverlay')) {
                this.hide();
            }
        });

        // Handle overlay click
        document.getElementById('callLogOverlay').addEventListener('click', (e) => {
            if (e.target === e.currentTarget) {
                this.hide();
            }
        });
    }

    /**
     * Validate form data
     */
    validateForm() {
        const errors = [];
        
        // Required fields
        const callDate = document.getElementById('callDate').value.trim();
        const callStatus = document.getElementById('callStatus').value.trim();
        
        if (!callDate) {
            this.showFieldError('callDate', 'กรุณาระบุวันที่โทร');
            errors.push('callDate');
        }
        
        if (!callStatus) {
            this.showFieldError('callStatus', 'กรุณาเลือกสถานะการโทร');
            errors.push('callStatus');
        }
        
        // Validate call minutes
        const callMinutes = document.getElementById('callMinutes').value;
        if (callMinutes && (isNaN(callMinutes) || parseInt(callMinutes) < 0)) {
            this.showFieldError('callMinutes', 'ระยะเวลาต้องเป็นตัวเลขที่มากกว่าหรือเท่ากับ 0');
            errors.push('callMinutes');
        }
        
        // If call status is "ติดต่อได้", talk status should be provided
        const talkStatus = document.getElementById('talkStatus').value.trim();
        if (callStatus === 'ติดต่อได้' && !talkStatus) {
            this.showFieldError('talkStatus', 'กรุณาระบุสถานะการคุยเมื่อติดต่อได้');
            errors.push('talkStatus');
        }
        
        return errors.length === 0;
    }

    /**
     * Submit form data
     */
    async submitForm() {
        if (this.isSubmitting) return;
        
        if (!this.validateForm()) {
            return;
        }
        
        this.isSubmitting = true;
        const submitBtn = document.getElementById('submitBtn');
        const originalText = submitBtn.innerHTML;
        
        // Show loading state
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<div class="spinner"></div> กำลังบันทึก...';
        
        try {
            const formData = {
                customer_code: document.getElementById('customerCode').value,
                call_date: document.getElementById('callDate').value,
                call_minutes: document.getElementById('callMinutes').value || null,
                call_status: document.getElementById('callStatus').value,
                talk_status: document.getElementById('talkStatus').value || null,
                remarks: document.getElementById('callRemarks').value.trim() || null
            };
            
            const response = await fetch('../api/calls/log.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(formData)
            });
            
            const result = await response.json();
            
            if (result.success || result.status === 'success') {
                // Success
                this.showSuccess('บันทึกการโทรสำเร็จ!');
                
                // Refresh call history if function exists
                if (typeof window.callHistoryManager !== 'undefined' && 
                    typeof window.callHistoryManager.refreshHistory === 'function') {
                    window.callHistoryManager.refreshHistory();
                }
                
                // Close popup after short delay
                setTimeout(() => {
                    this.hide();
                }, 1500);
                
            } else {
                throw new Error(result.message || 'เกิดข้อผิดพลาดในการบันทึก');
            }
            
        } catch (error) {
            console.error('Submit error:', error);
            this.showError('เกิดข้อผิดพลาด: ' + error.message);
            
            // Reset button
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
            this.isSubmitting = false;
        }
    }

    /**
     * Show field error
     */
    showFieldError(fieldId, message) {
        const field = document.getElementById(fieldId);
        const error = document.getElementById(fieldId + 'Error');
        
        if (field) field.classList.add('error');
        if (error) {
            error.textContent = message;
            error.classList.add('show');
        }
    }

    /**
     * Clear field error
     */
    clearFieldError(fieldId) {
        const field = document.getElementById(fieldId);
        const error = document.getElementById(fieldId + 'Error');
        
        if (field) field.classList.remove('error');
        if (error) error.classList.remove('show');
    }

    /**
     * Clear all errors
     */
    clearErrors() {
        const errorFields = ['callDate', 'callMinutes', 'callStatus', 'talkStatus'];
        errorFields.forEach(fieldId => {
            this.clearFieldError(fieldId);
        });
    }

    /**
     * Show success message
     */
    showSuccess(message) {
        // Simple alert for now - can be enhanced with toast notifications
        const alertDiv = document.createElement('div');
        alertDiv.className = 'alert alert-success position-fixed';
        alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 10000; min-width: 300px;';
        alertDiv.innerHTML = `<i class="fas fa-check-circle"></i> ${message}`;
        
        document.body.appendChild(alertDiv);
        
        setTimeout(() => {
            alertDiv.remove();
        }, 3000);
    }

    /**
     * Show error message
     */
    showError(message) {
        const alertDiv = document.createElement('div');
        alertDiv.className = 'alert alert-danger position-fixed';
        alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 10000; min-width: 300px;';
        alertDiv.innerHTML = `<i class="fas fa-exclamation-triangle"></i> ${message}`;
        
        document.body.appendChild(alertDiv);
        
        setTimeout(() => {
            alertDiv.remove();
        }, 5000);
    }

    /**
     * Escape HTML to prevent XSS
     */
    escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

// Initialize global instance
window.callLogPopup = new CallLogPopup();