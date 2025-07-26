<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เกิดข้อผิดพลาด - CRM System</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .error-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            padding: 40px;
            text-align: center;
            max-width: 500px;
            margin: 20px;
        }
        
        .error-icon {
            font-size: 64px;
            color: #e74c3c;
            margin-bottom: 20px;
        }
        
        .error-title {
            font-size: 24px;
            color: #2c3e50;
            margin-bottom: 15px;
            font-weight: 600;
        }
        
        .error-message {
            font-size: 16px;
            color: #7f8c8d;
            margin-bottom: 30px;
            line-height: 1.6;
        }
        
        .error-actions {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 5px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .btn-primary {
            background-color: #3498db;
            color: white;
        }
        
        .btn-primary:hover {
            background-color: #2980b9;
            transform: translateY(-2px);
        }
        
        .btn-secondary {
            background-color: #95a5a6;
            color: white;
        }
        
        .btn-secondary:hover {
            background-color: #7f8c8d;
            transform: translateY(-2px);
        }
        
        .error-code {
            font-size: 12px;
            color: #bdc3c7;
            margin-top: 20px;
            font-family: monospace;
        }
        
        @media (max-width: 480px) {
            .error-container {
                padding: 30px 20px;
            }
            
            .error-actions {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-icon">⚠️</div>
        <h1 class="error-title">เกิดข้อผิดพลาดในระบบ</h1>
        <p class="error-message">
            ขออภัยในความไม่สะดวก ระบบกำลังประสบปัญหาชั่วคราว<br>
            กรุณาลองใหม่อีกครั้งในภายหลัง หรือติดต่อผู้ดูแลระบบ
        </p>
        
        <div class="error-actions">
            <a href="javascript:history.back()" class="btn btn-secondary">กลับหน้าก่อนหน้า</a>
            <a href="/" class="btn btn-primary">กลับหน้าหลัก</a>
        </div>
        
        <div class="error-code">
            Error Code: <?php echo http_response_code(); ?> | 
            Time: <?php echo date('Y-m-d H:i:s'); ?>
        </div>
    </div>
    
    <script>
        // Auto refresh after 30 seconds
        setTimeout(function() {
            if (confirm('ต้องการรีเฟรชหน้าเว็บหรือไม่?')) {
                location.reload();
            }
        }, 30000);
        
        // Log error to console for debugging (only in development)
        <?php if (defined('APP_ENV') && APP_ENV !== 'production'): ?>
        console.error('Application Error:', {
            code: <?php echo http_response_code(); ?>,
            timestamp: '<?php echo date('c'); ?>',
            url: window.location.href,
            userAgent: navigator.userAgent
        });
        <?php endif; ?>
    </script>
</body>
</html>