<?php
/**
 * Customer CSV Import API
 * Handles CSV file upload and customer data import with Thai language support
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if (session_status() == PHP_SESSION_NONE) { session_start(); }

// Check authentication and permission
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

require_once '../../includes/permissions.php';
if (!Permissions::hasPermission('import_customers')) {
    http_response_code(403);
    echo json_encode(['error' => 'Permission denied. Admin only.']);
    exit;
}

try {
    require_once '../../config/database.php';
    
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        exit;
    }
    
    // Check if file was uploaded
    if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
        http_response_code(400);
        echo json_encode(['error' => 'No file uploaded or upload error']);
        exit;
    }
    
    $file = $_FILES['csv_file'];
    $updateExisting = isset($_POST['update_existing']) && $_POST['update_existing'] === 'true';
    
    // Validate file type and size
    if ($file['type'] !== 'text/csv' && $file['type'] !== 'application/csv') {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mime, ['text/csv', 'text/plain', 'application/csv'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid file type. Please upload CSV file only.']);
            exit;
        }
    }
    
    if ($file['size'] > 10 * 1024 * 1024) { // 10MB limit
        http_response_code(400);
        echo json_encode(['error' => 'File too large. Maximum size is 10MB.']);
        exit;
    }
    
    // Read and process CSV file with Thai language support
    $csvData = [];
    $handle = fopen($file['tmp_name'], 'r');
    
    if ($handle === false) {
        http_response_code(500);
        echo json_encode(['error' => 'Cannot read uploaded file']);
        exit;
    }
    
    // Read header row
    $headers = fgetcsv($handle);
    if ($headers === false) {
        fclose($handle);
        http_response_code(400);
        echo json_encode(['error' => 'Empty or invalid CSV file']);
        exit;
    }
    
    // Convert headers to expected format
    $headerMap = [
        'customer_name' => 'CustomerName',
        'customer_tel' => 'CustomerTel', 
        'customer_email' => 'CustomerEmail',
        'customer_address' => 'CustomerAddress',
        'customer_status' => 'CustomerStatus',
        'customer_province' => 'CustomerProvince'
    ];
    
    // Read data rows
    $rowCount = 0;
    while (($row = fgetcsv($handle)) !== false) {
        $rowCount++;
        
        if (count($row) < 2) {
            continue; // Skip incomplete rows
        }
        
        $customerData = [];
        for ($i = 0; $i < count($headers); $i++) {
            $header = strtolower(trim($headers[$i]));
            if (isset($headerMap[$header]) && isset($row[$i])) {
                $customerData[$headerMap[$header]] = trim($row[$i]);
            }
        }
        
        // Validate required fields
        if (empty($customerData['CustomerName']) || empty($customerData['CustomerTel'])) {
            continue; // Skip rows with missing required data
        }
        
        // Set defaults
        $customerData['CustomerEmail'] = $customerData['CustomerEmail'] ?? '';
        $customerData['CustomerAddress'] = $customerData['CustomerAddress'] ?? '';
        $customerData['CustomerStatus'] = $customerData['CustomerStatus'] ?? 'ลูกค้าใหม่';
        $customerData['CustomerProvince'] = $customerData['CustomerProvince'] ?? '';
        
        $csvData[] = $customerData;
    }
    
    fclose($handle);
    
    if (empty($csvData)) {
        http_response_code(400);
        echo json_encode(['error' => 'No valid customer data found in CSV file']);
        exit;
    }
    
    // Process import
    $imported = 0;
    $updated = 0;
    $errors = [];
    $currentUser = Permissions::getCurrentUser();
    
    foreach ($csvData as $index => $customer) {
        try {
            // Generate customer code
            $customerCode = generateCustomerCode($pdo);
            
            // Check if customer exists (by phone number)
            $checkSql = "SELECT CustomerCode FROM customers WHERE CustomerTel = ?";
            $checkStmt = $pdo->prepare($checkSql);
            $checkStmt->execute([$customer['CustomerTel']]);
            $existing = $checkStmt->fetch();
            
            if ($existing && $updateExisting) {
                // Update existing customer
                $updateSql = "UPDATE customers SET 
                                CustomerName = ?,
                                CustomerEmail = ?,
                                CustomerAddress = ?,
                                CustomerStatus = ?,
                                CustomerProvince = ?,
                                ModifiedDate = NOW(),
                                ModifiedBy = ?
                              WHERE CustomerTel = ?";
                
                $updateStmt = $pdo->prepare($updateSql);
                $updateStmt->execute([
                    $customer['CustomerName'],
                    $customer['CustomerEmail'],
                    $customer['CustomerAddress'],
                    $customer['CustomerStatus'],
                    $customer['CustomerProvince'],
                    $currentUser,
                    $customer['CustomerTel']
                ]);
                
                $updated++;
                
            } elseif (!$existing) {
                // Insert new customer
                $insertSql = "INSERT INTO customers (
                                CustomerCode,
                                CustomerName,
                                CustomerTel,
                                CustomerEmail,
                                CustomerAddress,
                                CustomerStatus,
                                CustomerProvince,
                                CustomerGrade,
                                CustomerTemperature,
                                TotalPurchase,
                                CreatedDate,
                                CreatedBy
                              ) VALUES (?, ?, ?, ?, ?, ?, ?, 'D', 'WARM', 0.00, NOW(), ?)";
                
                $insertStmt = $pdo->prepare($insertSql);
                $insertStmt->execute([
                    $customerCode,
                    $customer['CustomerName'],
                    $customer['CustomerTel'],
                    $customer['CustomerEmail'],
                    $customer['CustomerAddress'],
                    $customer['CustomerStatus'],
                    $customer['CustomerProvince'],
                    $currentUser
                ]);
                
                $imported++;
            }
            
        } catch (Exception $e) {
            $errors[] = "Row " . ($index + 2) . ": " . $e->getMessage();
        }
    }
    
    // Log import activity
    try {
        $logStmt = $pdo->prepare("INSERT INTO system_logs (LogType, Action, Details, AffectedCount, CreatedBy, CreatedDate) VALUES (?, ?, ?, ?, ?, NOW())");
        $logDetails = "CSV Import: $imported new, $updated updated, " . count($errors) . " errors";
        $logStmt->execute(['IMPORT', 'CUSTOMER_CSV_IMPORT', $logDetails, $imported + $updated, $currentUser]);
    } catch (Exception $e) {
        // Log error doesn't affect import result
    }
    
    echo json_encode([
        'status' => 'success',
        'message' => 'CSV import completed successfully',
        'results' => [
            'total_processed' => count($csvData),
            'imported' => $imported,
            'updated' => $updated,
            'errors' => count($errors),
            'error_details' => $errors
        ]
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage(),
        'file' => __FILE__,
        'line' => __LINE__
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}

/**
 * Generate unique customer code
 */
function generateCustomerCode($pdo) {
    $year = date('Y');
    $month = date('m');
    
    // Find the highest code for current year-month
    $sql = "SELECT CustomerCode FROM customers 
            WHERE CustomerCode LIKE ? 
            ORDER BY CustomerCode DESC 
            LIMIT 1";
    
    $prefix = "C{$year}{$month}";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(["{$prefix}%"]);
    $result = $stmt->fetch();
    
    if ($result) {
        $lastCode = $result['CustomerCode'];
        $lastNumber = intval(substr($lastCode, -4));
        $newNumber = $lastNumber + 1;
    } else {
        $newNumber = 1;
    }
    
    return $prefix . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
}
/**
 * Customer CSV Import API Endpoint
 * POST /api/customers/import.php
 * 
 * Expected CSV columns:
 * CallDate, CallTime, CallStatus, CustomerStatus, Note, Agriculture, 
 * LastOrderDate, PaymentMethod, Products, Quantity, Price, CustomerName, 
 * CustomerTel, CustomerAddress, CustomerProvince, CustomerPostalCode
 * 
 * Parameters:
 * - csv_file: CSV file upload
 * - update_existing: Whether to update existing customers (default: true)
 */

session_start();
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/Customer.php';

// Check authentication
if (!isLoggedIn()) {
    sendJsonResponse(['success' => false, 'message' => 'กรุณาเข้าสู่ระบบ'], 401);
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
}

// Check permissions - Only Admin and Supervisor can import
if (!hasRole('Admin') && !hasRole('Supervisor')) {
    sendJsonResponse(['success' => false, 'message' => 'คุณไม่มีสิทธิ์นำเข้าข้อมูล'], 403);
}

try {
    // Check if file was uploaded
    if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
        sendJsonResponse(['success' => false, 'message' => 'กรุณาเลือกไฟล์ CSV'], 400);
    }
    
    $file = $_FILES['csv_file'];
    
    // Validate file type
    $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if ($fileExtension !== 'csv') {
        sendJsonResponse(['success' => false, 'message' => 'กรุณาเลือกไฟล์ CSV เท่านั้น'], 400);
    }
    
    // Validate file size (max 10MB)
    if ($file['size'] > 10 * 1024 * 1024) {
        sendJsonResponse(['success' => false, 'message' => 'ไฟล์มีขนาดใหญ่เกินไป (สูงสุด 10MB)'], 400);
    }
    
    $updateExisting = isset($_POST['update_existing']) ? (bool)$_POST['update_existing'] : true;
    
    // Process CSV file
    $importResult = processCustomerCSV($file['tmp_name'], $updateExisting);
    
    // Log activity
    logActivity('IMPORT_CUSTOMERS', "Imported {$importResult['created']} new customers, updated {$importResult['updated']} existing customers");
    
    sendJsonResponse([
        'success' => true,
        'message' => 'นำเข้าข้อมูลสำเร็จ',
        'data' => $importResult
    ]);
    
} catch (Exception $e) {
    error_log("Customer import API error: " . $e->getMessage());
    sendJsonResponse(['success' => false, 'message' => 'เกิดข้อผิดพลาดในการนำเข้าข้อมูล: ' . $e->getMessage()], 500);
}

/**
 * Process customer CSV file
 * @param string $filePath Path to CSV file
 * @param bool $updateExisting Whether to update existing customers
 * @return array Import results
 */
function processCustomerCSV($filePath, $updateExisting = true) {
    $customer = new Customer();
    $db = getDB();
    
    $results = [
        'total_rows' => 0,
        'created' => 0,
        'updated' => 0,
        'skipped' => 0,
        'errors' => []
    ];
    
    // Open CSV file
    if (($handle = fopen($filePath, 'r')) === false) {
        throw new Exception('ไม่สามารถเปิดไฟล์ CSV ได้');
    }
    
    // Expected CSV columns
    $expectedColumns = [
        'CallDate', 'CallTime', 'CallStatus', 'CustomerStatus', 'Note', 'Agriculture',
        'LastOrderDate', 'PaymentMethod', 'Products', 'Quantity', 'Price', 
        'CustomerName', 'CustomerTel', 'CustomerAddress', 'CustomerProvince', 'CustomerPostalCode'
    ];
    
    $rowNumber = 0;
    $headers = [];
    
    try {
        $db->beginTransaction();
        
        while (($data = fgetcsv($handle, 1000, ',')) !== false) {
            $rowNumber++;
            $results['total_rows']++;
            
            // First row should be headers
            if ($rowNumber === 1) {
                $headers = array_map('trim', $data);
                
                // Validate headers
                $missingColumns = array_diff($expectedColumns, $headers);
                if (!empty($missingColumns)) {
                    throw new Exception('ไฟล์ CSV ขาดคอลัมน์: ' . implode(', ', $missingColumns));
                }
                
                $results['total_rows']--; // Don't count header row
                continue;
            }
            
            // Skip empty rows
            if (empty(array_filter($data))) {
                $results['skipped']++;
                continue;
            }
            
            try {
                // Map data to associative array
                $rowData = array_combine($headers, $data);
                
                // Process customer data
                $processResult = processCustomerRow($rowData, $customer, $updateExisting);
                
                if ($processResult['action'] === 'created') {
                    $results['created']++;
                } elseif ($processResult['action'] === 'updated') {
                    $results['updated']++;
                } else {
                    $results['skipped']++;
                }
                
            } catch (Exception $e) {
                $results['errors'][] = "แถว {$rowNumber}: " . $e->getMessage();
                $results['skipped']++;
            }
        }
        
        $db->commit();
        
    } catch (Exception $e) {
        $db->rollback();
        throw $e;
    } finally {
        fclose($handle);
    }
    
    return $results;
}

/**
 * Process single customer row from CSV
 * @param array $rowData Row data from CSV
 * @param Customer $customer Customer model instance
 * @param bool $updateExisting Whether to update existing customers
 * @return array Processing result
 */
function processCustomerRow($rowData, $customer, $updateExisting) {
    // Clean and validate required fields
    $customerName = trim($rowData['CustomerName'] ?? '');
    $customerTel = trim($rowData['CustomerTel'] ?? '');
    
    if (empty($customerName) || empty($customerTel)) {
        throw new Exception('ชื่อลูกค้าและเบอร์โทรศัพท์เป็นข้อมูลที่จำเป็น');
    }
    
    // Normalize phone number
    $customerTel = preg_replace('/[^0-9]/', '', $customerTel);
    
    if (!validatePhoneNumber($customerTel)) {
        throw new Exception('รูปแบบเบอร์โทรศัพท์ไม่ถูกต้อง: ' . $customerTel);
    }
    
    // Check if customer exists
    $existingCustomer = $customer->findByPhone($customerTel);
    
    if ($existingCustomer) {
        if (!$updateExisting) {
            return ['action' => 'skipped', 'reason' => 'Customer exists'];
        }
        
        // Update existing customer
        $updateData = buildCustomerUpdateData($rowData, $existingCustomer);
        
        if (!empty($updateData)) {
            $customer->updateCustomer($existingCustomer['CustomerCode'], $updateData);
            
            // Create order if order data exists
            if (hasOrderData($rowData)) {
                createOrderFromCSV($rowData, $existingCustomer['CustomerCode']);
            }
        }
        
        return ['action' => 'updated', 'customer_code' => $existingCustomer['CustomerCode']];
        
    } else {
        // Create new customer
        $customerData = buildCustomerData($rowData);
        $customerCode = $customer->createCustomer($customerData);
        
        if (!$customerCode) {
            throw new Exception('ไม่สามารถสร้างลูกค้าได้');
        }
        
        // Create order if order data exists
        if (hasOrderData($rowData)) {
            createOrderFromCSV($rowData, $customerCode);
        }
        
        // Create call log if call data exists
        if (hasCallData($rowData)) {
            createCallLogFromCSV($rowData, $customerCode);
        }
        
        return ['action' => 'created', 'customer_code' => $customerCode];
    }
}

/**
 * Build customer data from CSV row
 * @param array $rowData CSV row data
 * @return array Customer data
 */
function buildCustomerData($rowData) {
    $customerData = [
        'CustomerName' => sanitizeInput($rowData['CustomerName']),
        'CustomerTel' => preg_replace('/[^0-9]/', '', $rowData['CustomerTel']),
        'CustomerAddress' => sanitizeInput($rowData['CustomerAddress'] ?? ''),
        'CustomerProvince' => sanitizeInput($rowData['CustomerProvince'] ?? ''),
        'CustomerPostalCode' => sanitizeInput($rowData['CustomerPostalCode'] ?? ''),
        'Agriculture' => sanitizeInput($rowData['Agriculture'] ?? ''),
    ];
    
    // Set customer status
    if (!empty($rowData['CustomerStatus'])) {
        $status = sanitizeInput($rowData['CustomerStatus']);
        $validStatuses = ['ลูกค้าใหม่', 'ลูกค้าติดตาม', 'ลูกค้าเก่า'];
        if (in_array($status, $validStatuses)) {
            $customerData['CustomerStatus'] = $status;
        }
    }
    
    // Set call status if available
    if (!empty($rowData['CallStatus'])) {
        $customerData['CallStatus'] = sanitizeInput($rowData['CallStatus']);
    }
    
    return $customerData;
}

/**
 * Build customer update data from CSV row
 * @param array $rowData CSV row data
 * @param array $existingCustomer Existing customer data
 * @return array Update data
 */
function buildCustomerUpdateData($rowData, $existingCustomer) {
    $updateData = [];
    
    // Update fields if they have values and are different
    $fieldsToUpdate = [
        'CustomerName' => 'CustomerName',
        'CustomerAddress' => 'CustomerAddress',
        'CustomerProvince' => 'CustomerProvince',
        'CustomerPostalCode' => 'CustomerPostalCode',
        'Agriculture' => 'Agriculture'
    ];
    
    foreach ($fieldsToUpdate as $csvField => $dbField) {
        if (!empty($rowData[$csvField])) {
            $newValue = sanitizeInput($rowData[$csvField]);
            if ($newValue !== $existingCustomer[$dbField]) {
                $updateData[$dbField] = $newValue;
            }
        }
    }
    
    // Update customer status if provided
    if (!empty($rowData['CustomerStatus'])) {
        $status = sanitizeInput($rowData['CustomerStatus']);
        $validStatuses = ['ลูกค้าใหม่', 'ลูกค้าติดตาม', 'ลูกค้าเก่า'];
        if (in_array($status, $validStatuses) && $status !== $existingCustomer['CustomerStatus']) {
            $updateData['CustomerStatus'] = $status;
        }
    }
    
    // Update call status if provided
    if (!empty($rowData['CallStatus']) && $rowData['CallStatus'] !== $existingCustomer['CallStatus']) {
        $updateData['CallStatus'] = sanitizeInput($rowData['CallStatus']);
    }
    
    return $updateData;
}

/**
 * Check if row has order data
 * @param array $rowData CSV row data
 * @return bool
 */
function hasOrderData($rowData) {
    return !empty($rowData['Products']) || !empty($rowData['LastOrderDate']);
}

/**
 * Check if row has call data
 * @param array $rowData CSV row data
 * @return bool
 */
function hasCallData($rowData) {
    return !empty($rowData['CallDate']) || !empty($rowData['CallStatus']);
}

/**
 * Create order from CSV data
 * @param array $rowData CSV row data
 * @param string $customerCode Customer code
 */
function createOrderFromCSV($rowData, $customerCode) {
    if (empty($rowData['Products'])) {
        return;
    }
    
    $db = getDB();
    
    $orderData = [
        'DocumentNo' => generateDocumentNo(),
        'CustomerCode' => $customerCode,
        'DocumentDate' => !empty($rowData['LastOrderDate']) ? date('Y-m-d H:i:s', strtotime($rowData['LastOrderDate'])) : date('Y-m-d H:i:s'),
        'PaymentMethod' => sanitizeInput($rowData['PaymentMethod'] ?? ''),
        'Products' => sanitizeInput($rowData['Products']),
        'Quantity' => !empty($rowData['Quantity']) ? floatval($rowData['Quantity']) : 1,
        'Price' => !empty($rowData['Price']) ? floatval($rowData['Price']) : 0,
        'OrderBy' => getCurrentUsername(),
        'CreatedDate' => date('Y-m-d H:i:s'),
        'CreatedBy' => getCurrentUsername()
    ];
    
    $fields = array_keys($orderData);
    $placeholders = array_fill(0, count($fields), '?');
    
    $sql = "INSERT INTO orders (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $placeholders) . ")";
    $db->execute($sql, array_values($orderData));
    
    // Update customer order date
    $customer = new Customer();
    $customer->updateCustomer($customerCode, [
        'OrderDate' => $orderData['DocumentDate'],
        'CustomerStatus' => 'ลูกค้าเก่า'
    ]);
}

/**
 * Create call log from CSV data
 * @param array $rowData CSV row data
 * @param string $customerCode Customer code
 */
function createCallLogFromCSV($rowData, $customerCode) {
    if (empty($rowData['CallDate']) && empty($rowData['CallStatus'])) {
        return;
    }
    
    $db = getDB();
    
    $callData = [
        'CustomerCode' => $customerCode,
        'CallDate' => !empty($rowData['CallDate']) ? date('Y-m-d H:i:s', strtotime($rowData['CallDate'])) : date('Y-m-d H:i:s'),
        'CallTime' => sanitizeInput($rowData['CallTime'] ?? ''),
        'CallStatus' => !empty($rowData['CallStatus']) ? sanitizeInput($rowData['CallStatus']) : 'ติดต่อได้',
        'Remarks' => sanitizeInput($rowData['Note'] ?? ''),
        'CreatedDate' => date('Y-m-d H:i:s'),
        'CreatedBy' => getCurrentUsername()
    ];
    
    $fields = array_keys($callData);
    $placeholders = array_fill(0, count($fields), '?');
    
    $sql = "INSERT INTO call_logs (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $placeholders) . ")";
    $db->execute($sql, array_values($callData));
}
?>