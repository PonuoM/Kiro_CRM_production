<?php
/**
 * Enhanced Import System
 * Support both Lead Import and First-Time Order Import
 */

require_once 'config/database.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');
header('Access-Control-Allow-Credentials: true');

if (session_status() == PHP_SESSION_NONE) { session_start(); }

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'error' => 'Method not allowed. Only POST requests allowed.']);
    exit;
}

// Check authentication
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'error' => 'Unauthorized']);
    exit;
}

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    // Check if file was uploaded
    if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'error' => 'No file uploaded']);
        exit;
    }
    
    $file = $_FILES['csv_file'];
    $importType = $_POST['import_type'] ?? 'leads'; // 'leads' or 'orders'
    $updateExisting = $_POST['update_existing'] ?? true;
    $currentUser = $_SESSION['username'] ?? 'admin';
    
    // Validate file
    if ($file['size'] > 10 * 1024 * 1024) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'error' => 'File too large (max 10MB)']);
        exit;
    }
    
    // Read CSV
    $handle = fopen($file['tmp_name'], 'r');
    if ($handle === false) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'error' => 'Cannot read file']);
        exit;
    }
    
    // Read headers
    $headers = fgetcsv($handle);
    if ($headers === false) {
        fclose($handle);
        http_response_code(400);
        echo json_encode(['status' => 'error', 'error' => 'Invalid CSV file']);
        exit;
    }
    
    // Process based on import type
    if ($importType === 'leads') {
        $result = processLeadImport($pdo, $handle, $headers, $updateExisting, $currentUser);
    } else {
        $result = processOrderImport($pdo, $handle, $headers, $updateExisting, $currentUser);
    }
    
    fclose($handle);
    
    echo json_encode($result);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'error' => $e->getMessage()
    ]);
}

/**
 * Process Lead Import (customers only)
 */
function processLeadImport($pdo, $handle, $headers, $updateExisting, $currentUser) {
    $results = [
        'status' => 'success',
        'message' => 'Lead import completed',
        'type' => 'leads',
        'imported' => 0,
        'updated' => 0,
        'skipped' => 0,
        'errors' => []
    ];
    
    // Expected columns for leads
    $expectedColumns = [
        'CustomerCode', 'CustomerName', 'CustomerTel', 'CustomerAddress', 
        'CustomerProvince', 'CustomerPostalCode', 'Agriculture', 'CustomerStatus'
    ];
    
    $columnMap = array_flip($headers);
    
    $pdo->beginTransaction();
    
    try {
        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) < 3) continue; // Skip incomplete rows
            
            $customerData = [];
            
            // Map CSV data to database fields
            foreach ($expectedColumns as $column) {
                if (isset($columnMap[$column]) && isset($row[$columnMap[$column]])) {
                    $customerData[$column] = trim($row[$columnMap[$column]]);
                }
            }
            
            // Validate required fields
            if (empty($customerData['CustomerName']) || empty($customerData['CustomerTel'])) {
                $results['errors'][] = "Missing required fields: CustomerName or CustomerTel";
                $results['skipped']++;
                continue;
            }
            
            // Generate CustomerCode if not provided
            if (empty($customerData['CustomerCode'])) {
                $customerData['CustomerCode'] = generateCustomerCode($pdo);
            }
            
            // Set defaults
            $customerData['CustomerStatus'] = $customerData['CustomerStatus'] ?? 'ลูกค้าใหม่';
            $customerData['CustomerGrade'] = 'D'; // Default grade for imports
            $customerData['CustomerTemperature'] = 'HOT'; // Default temperature for imports
            $customerData['CartStatus'] = 'ตะกร้าแจก'; // Ready for assignment
            $customerData['CartStatusDate'] = date('Y-m-d H:i:s');
            $customerData['CreatedDate'] = date('Y-m-d H:i:s');
            $customerData['CreatedBy'] = $currentUser;
            $customerData['TotalPurchase'] = 0.00;
            $customerData['AssignmentCount'] = 0;
            
            // Check if customer exists
            $checkSql = "SELECT CustomerCode FROM customers WHERE CustomerCode = ? OR CustomerTel = ?";
            $checkStmt = $pdo->prepare($checkSql);
            $checkStmt->execute([$customerData['CustomerCode'], $customerData['CustomerTel']]);
            $existing = $checkStmt->fetch();
            
            if ($existing && $updateExisting) {
                // Update existing customer
                $updateFields = [];
                $updateValues = [];
                
                foreach (['CustomerName', 'CustomerAddress', 'CustomerProvince', 'CustomerPostalCode', 'Agriculture', 'CustomerStatus'] as $field) {
                    if (!empty($customerData[$field])) {
                        $updateFields[] = "$field = ?";
                        $updateValues[] = $customerData[$field];
                    }
                }
                
                if (!empty($updateFields)) {
                    $updateFields[] = "ModifiedDate = NOW()";
                    $updateFields[] = "ModifiedBy = ?";
                    $updateValues[] = $currentUser;
                    $updateValues[] = $existing['CustomerCode'];
                    
                    $updateSql = "UPDATE customers SET " . implode(', ', $updateFields) . " WHERE CustomerCode = ?";
                    $updateStmt = $pdo->prepare($updateSql);
                    $updateStmt->execute($updateValues);
                    $results['updated']++;
                }
                
            } elseif (!$existing) {
                // Insert new customer
                $fields = array_keys($customerData);
                $placeholders = array_fill(0, count($fields), '?');
                
                $insertSql = "INSERT INTO customers (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $placeholders) . ")";
                $insertStmt = $pdo->prepare($insertSql);
                $insertStmt->execute(array_values($customerData));
                $results['imported']++;
                
            } else {
                $results['skipped']++;
            }
        }
        
        $pdo->commit();
        
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
    
    return $results;
}

/**
 * Process Order Import (customers + orders)
 */
function processOrderImport($pdo, $handle, $headers, $updateExisting, $currentUser) {
    $results = [
        'status' => 'success',
        'message' => 'Order import completed',
        'type' => 'orders',
        'customers_imported' => 0,
        'customers_updated' => 0,
        'orders_imported' => 0,
        'skipped' => 0,
        'errors' => []
    ];
    
    // Expected columns for orders
    $customerColumns = [
        'CustomerCode', 'CustomerName', 'CustomerTel', 'CustomerAddress', 
        'CustomerProvince', 'CustomerPostalCode', 'Agriculture', 'CustomerStatus'
    ];
    
    $orderColumns = [
        'DocumentDate', 'Products', 'Quantity', 'Price', 'DiscountAmount', 'DiscountPercent'
    ];
    
    $columnMap = array_flip($headers);
    
    $pdo->beginTransaction();
    
    try {
        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) < 5) continue; // Skip incomplete rows
            
            $customerData = [];
            $orderData = [];
            
            // Map customer data
            foreach ($customerColumns as $column) {
                if (isset($columnMap[$column]) && isset($row[$columnMap[$column]])) {
                    $customerData[$column] = trim($row[$columnMap[$column]]);
                }
            }
            
            // Map order data
            foreach ($orderColumns as $column) {
                if (isset($columnMap[$column]) && isset($row[$columnMap[$column]])) {
                    $orderData[$column] = trim($row[$columnMap[$column]]);
                }
            }
            
            // Validate required fields
            if (empty($customerData['CustomerName']) || empty($customerData['CustomerTel']) || 
                empty($orderData['Products']) || empty($orderData['Quantity']) || empty($orderData['Price'])) {
                $results['errors'][] = "Missing required fields in row";
                $results['skipped']++;
                continue;
            }
            
            // Generate CustomerCode if not provided
            if (empty($customerData['CustomerCode'])) {
                $customerData['CustomerCode'] = generateCustomerCode($pdo);
            }
            
            // Check if customer exists
            $checkSql = "SELECT CustomerCode FROM customers WHERE CustomerCode = ? OR CustomerTel = ?";
            $checkStmt = $pdo->prepare($checkSql);
            $checkStmt->execute([$customerData['CustomerCode'], $customerData['CustomerTel']]);
            $existing = $checkStmt->fetch();
            
            if (!$existing) {
                // Create new customer
                $customerData['CustomerStatus'] = $customerData['CustomerStatus'] ?? 'ลูกค้าใหม่';
                $customerData['CustomerGrade'] = 'D'; // Will be recalculated after order
                $customerData['CustomerTemperature'] = 'HOT';
                $customerData['CartStatus'] = 'ลูกค้าแจกแล้ว'; // Has order, so assigned
                $customerData['CartStatusDate'] = date('Y-m-d H:i:s');
                $customerData['CreatedDate'] = date('Y-m-d H:i:s');
                $customerData['CreatedBy'] = $currentUser;
                $customerData['TotalPurchase'] = 0.00; // Will be updated by trigger
                $customerData['AssignmentCount'] = 0;
                
                $fields = array_keys($customerData);
                $placeholders = array_fill(0, count($fields), '?');
                
                $insertSql = "INSERT INTO customers (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $placeholders) . ")";
                $insertStmt = $pdo->prepare($insertSql);
                $insertStmt->execute(array_values($customerData));
                $results['customers_imported']++;
                
                $finalCustomerCode = $customerData['CustomerCode'];
                
            } else {
                $finalCustomerCode = $existing['CustomerCode'];
                
                if ($updateExisting) {
                    // Update customer info if needed
                    $updateFields = [];
                    $updateValues = [];
                    
                    foreach (['CustomerName', 'CustomerAddress', 'CustomerProvince', 'CustomerPostalCode', 'Agriculture'] as $field) {
                        if (!empty($customerData[$field])) {
                            $updateFields[] = "$field = ?";
                            $updateValues[] = $customerData[$field];
                        }
                    }
                    
                    if (!empty($updateFields)) {
                        $updateFields[] = "ModifiedDate = NOW()";
                        $updateFields[] = "ModifiedBy = ?";
                        $updateValues[] = $currentUser;
                        $updateValues[] = $finalCustomerCode;
                        
                        $updateSql = "UPDATE customers SET " . implode(', ', $updateFields) . " WHERE CustomerCode = ?";
                        $updateStmt = $pdo->prepare($updateSql);
                        $updateStmt->execute($updateValues);
                        $results['customers_updated']++;
                    }
                }
            }
            
            // Create order
            $orderData['DocumentNo'] = generateDocumentNo($pdo);
            $orderData['CustomerCode'] = $finalCustomerCode;
            $orderData['DocumentDate'] = !empty($orderData['DocumentDate']) ? 
                date('Y-m-d H:i:s', strtotime($orderData['DocumentDate'])) : 
                date('Y-m-d H:i:s');
            $orderData['DiscountAmount'] = $orderData['DiscountAmount'] ?? 0.00;
            $orderData['DiscountPercent'] = $orderData['DiscountPercent'] ?? 0.00;
            $orderData['OrderBy'] = $currentUser;
            $orderData['CreatedDate'] = date('Y-m-d H:i:s');
            $orderData['CreatedBy'] = $currentUser;
            // SubtotalAmount will be calculated by trigger
            
            $orderFields = array_keys($orderData);
            $orderPlaceholders = array_fill(0, count($orderFields), '?');
            
            $orderInsertSql = "INSERT INTO orders (" . implode(', ', $orderFields) . ") VALUES (" . implode(', ', $orderPlaceholders) . ")";
            $orderInsertStmt = $pdo->prepare($orderInsertSql);
            $orderInsertStmt->execute(array_values($orderData));
            $results['orders_imported']++;
        }
        
        $pdo->commit();
        
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
    
    return $results;
}

/**
 * Generate unique customer code
 */
function generateCustomerCode($pdo) {
    $year = date('Y');
    $month = date('m');
    
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
 * Generate unique document number
 */
function generateDocumentNo($pdo) {
    $year = date('Y');
    $month = date('m');
    
    $sql = "SELECT DocumentNo FROM orders 
            WHERE DocumentNo LIKE ? 
            ORDER BY DocumentNo DESC 
            LIMIT 1";
    
    $prefix = "ORD{$year}{$month}";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(["{$prefix}%"]);
    $result = $stmt->fetch();
    
    if ($result) {
        $lastDoc = $result['DocumentNo'];
        $lastNumber = intval(substr($lastDoc, -4));
        $newNumber = $lastNumber + 1;
    } else {
        $newNumber = 1;
    }
    
    return $prefix . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
}
?>