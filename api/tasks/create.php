<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if (session_status() == PHP_SESSION_NONE) { session_start(); }

// Check authentication
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'status' => 'error',
        'message' => 'Method not allowed'
    ]);
    exit;
}

try {
    require_once '../../config/database.php';
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => 'Invalid JSON data'
        ]);
        exit;
    }
    
    // Validate required fields
    if (empty($input['CustomerCode'])) {
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => 'Customer code is required'
        ]);
        exit;
    }
    
    if (empty($input['FollowupDate'])) {
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => 'Follow-up date is required'
        ]);
        exit;
    }
    
    // Prepare task data using correct column names
    $customerCode = trim($input['CustomerCode']);
    $followupDate = trim($input['FollowupDate']);
    $remarks = !empty($input['Remarks']) ? trim($input['Remarks']) : '';
    $status = !empty($input['Status']) ? trim($input['Status']) : 'รอดำเนินการ';
    $createdBy = $_SESSION['username'] ?? $_SESSION['Username'] ?? '';
    
    // Insert task
    $sql = "INSERT INTO tasks (CustomerCode, FollowupDate, Remarks, Status, CreatedBy, CreatedDate) 
            VALUES (?, ?, ?, ?, ?, NOW())";
    
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([$customerCode, $followupDate, $remarks, $status, $createdBy]);
    
    if ($result) {
        $taskId = $pdo->lastInsertId();
        
        echo json_encode([
            'status' => 'success',
            'data' => [
                'task_id' => $taskId,
                'CustomerCode' => $customerCode,
                'FollowupDate' => $followupDate,
                'Remarks' => $remarks,
                'Status' => $status
            ],
            'message' => 'Task created successfully'
        ], JSON_PRETTY_PRINT);
    } else {
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'message' => 'Failed to create task'
        ]);
    }
    
} catch(Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage(),
        'file' => __FILE__,
        'line' => __LINE__
    ], JSON_PRETTY_PRINT);
}
?>