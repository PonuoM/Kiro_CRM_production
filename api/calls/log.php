<?php
/**
 * Call Log API Endpoint
 * Handles creating and managing call logs
 */

session_start();
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/CallLog.php';

// Set JSON response header
header('Content-Type: application/json; charset=utf-8');

// Check authentication
if (!isLoggedIn()) {
    sendJsonResponse(['success' => false, 'message' => 'กรุณาเข้าสู่ระบบ'], 401);
}

// Handle different HTTP methods
$method = $_SERVER['REQUEST_METHOD'];

try {
    $callLog = new CallLog();
    
    switch ($method) {
        case 'POST':
            handleCreateCallLog($callLog);
            break;
            
        case 'GET':
            handleGetCallLogs($callLog);
            break;
            
        default:
            sendJsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
    }
    
} catch (Exception $e) {
    error_log("Call Log API Error: " . $e->getMessage());
    sendJsonResponse(['success' => false, 'message' => 'เกิดข้อผิดพลาดในระบบ'], 500);
}

/**
 * Handle creating new call log
 * @param CallLog $callLog
 */
function handleCreateCallLog($callLog) {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        sendJsonResponse(['success' => false, 'message' => 'ข้อมูลไม่ถูกต้อง'], 400);
    }
    
    // Validate CSRF token if provided
    if (isset($input['csrf_token']) && !verifyCSRFToken($input['csrf_token'])) {
        sendJsonResponse(['success' => false, 'message' => 'Invalid CSRF token'], 403);
    }
    
    // Prepare call data
    $callData = [
        'CustomerCode' => $input['customer_code'] ?? '',
        'CallDate' => $input['call_date'] ?? date('Y-m-d H:i:s'),
        'CallTime' => $input['call_time'] ?? '',
        'CallMinutes' => $input['call_minutes'] ?? null,
        'CallStatus' => $input['call_status'] ?? '',
        'CallReason' => $input['call_reason'] ?? '',
        'TalkStatus' => $input['talk_status'] ?? '',
        'TalkReason' => $input['talk_reason'] ?? '',
        'Remarks' => $input['remarks'] ?? ''
    ];
    
    // Remove empty values
    $callData = array_filter($callData, function($value) {
        return $value !== '' && $value !== null;
    });
    
    try {
        // Begin transaction
        $callLog->beginTransaction();
        
        // Create call log
        $callLogId = $callLog->createCallLog($callData);
        
        if ($callLogId) {
            // Update customer's last contact information
            $callLog->updateCustomerLastContact($callData['CustomerCode'], $callData);
            
            // Commit transaction
            $callLog->commit();
            
            // Log activity
            logActivity('CREATE_CALL_LOG', "Call log created for customer: {$callData['CustomerCode']}");
            
            sendJsonResponse([
                'success' => true, 
                'message' => 'บันทึกการโทรสำเร็จ',
                'call_log_id' => $callLogId
            ]);
        } else {
            $callLog->rollback();
            sendJsonResponse(['success' => false, 'message' => 'ไม่สามารถบันทึกการโทรได้'], 500);
        }
        
    } catch (InvalidArgumentException $e) {
        $callLog->rollback();
        sendJsonResponse(['success' => false, 'message' => $e->getMessage()], 400);
    } catch (Exception $e) {
        $callLog->rollback();
        error_log("Create call log error: " . $e->getMessage());
        sendJsonResponse(['success' => false, 'message' => 'เกิดข้อผิดพลาดในการบันทึก'], 500);
    }
}

/**
 * Handle getting call logs
 * @param CallLog $callLog
 */
function handleGetCallLogs($callLog) {
    $customerCode = $_GET['customer_code'] ?? '';
    $dateFrom = $_GET['date_from'] ?? '';
    $dateTo = $_GET['date_to'] ?? '';
    $callStatus = $_GET['call_status'] ?? '';
    $talkStatus = $_GET['talk_status'] ?? '';
    $limit = (int)($_GET['limit'] ?? 50);
    $offset = (int)($_GET['offset'] ?? 0);
    $getStats = $_GET['get_stats'] ?? false;
    
    try {
        if ($customerCode) {
            // Get call logs for specific customer
            $filters = [];
            if ($dateFrom) $filters['date_from'] = $dateFrom;
            if ($dateTo) $filters['date_to'] = $dateTo;
            if ($callStatus) $filters['call_status'] = $callStatus;
            if ($talkStatus) $filters['talk_status'] = $talkStatus;
            
            $callLogs = $callLog->getCallLogsByCustomer($customerCode, $filters, 'CallDate DESC', $limit, $offset);
            $totalCount = $callLog->countCallLogsByCustomer($customerCode, $filters);
            
            $response = [
                'success' => true,
                'data' => $callLogs,
                'total_count' => $totalCount,
                'limit' => $limit,
                'offset' => $offset
            ];
            
            // Add statistics if requested
            if ($getStats) {
                $response['statistics'] = $callLog->getCallStatistics($customerCode, $dateFrom, $dateTo);
            }
            
            sendJsonResponse($response);
            
        } else {
            // Get recent call logs across all customers
            $filters = [];
            if ($dateFrom) $filters['date_from'] = $dateFrom;
            if ($dateTo) $filters['date_to'] = $dateTo;
            if ($callStatus) $filters['call_status'] = $callStatus;
            
            // Filter by current user if not admin
            if (getCurrentUserRole() === 'Sale') {
                $filters['created_by'] = getCurrentUsername();
            }
            
            $callLogs = $callLog->getRecentCallLogs($filters, 'CallDate DESC', $limit, $offset);
            
            sendJsonResponse([
                'success' => true,
                'data' => $callLogs,
                'limit' => $limit,
                'offset' => $offset
            ]);
        }
        
    } catch (Exception $e) {
        error_log("Get call logs error: " . $e->getMessage());
        sendJsonResponse(['success' => false, 'message' => 'เกิดข้อผิดพลาดในการดึงข้อมูล'], 500);
    }
}
?>