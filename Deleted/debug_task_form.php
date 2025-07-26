<?php
if (session_status() == PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['user_id'])) {
    echo "Please login first: <a href='universal_login.php'>Login</a>";
    exit;
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>🔍 Debug Task Form</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .form-group { margin: 10px 0; }
        .form-group label { display: block; margin-bottom: 5px; }
        .form-group input, .form-group textarea { width: 300px; padding: 5px; }
        .btn { background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 3px; cursor: pointer; }
        .result { margin: 10px 0; padding: 10px; background: #f8f9fa; border-radius: 3px; }
    </style>
</head>
<body>
    <h1>🔍 Debug Task Form</h1>
    <p>User: <?= $_SESSION['username'] ?></p>
    
    <form id="task-form">
        <div class="form-group">
            <label for="followup-date">วันที่นัดหมาย *</label>
            <input type="datetime-local" id="followup-date" name="followup_date" required>
        </div>
        
        <div class="form-group">
            <label for="task-remarks">หมายเหตุ</label>
            <textarea id="task-remarks" name="remarks" rows="3" placeholder="รายละเอียดการนัดหมาย..."></textarea>
        </div>
        
        <button type="submit" class="btn">สร้างนัดหมาย</button>
    </form>
    
    <div id="form-data-result" class="result" style="display:none;"></div>
    <div id="api-result" class="result" style="display:none;"></div>

    <script>
        // Set default datetime
        const now = new Date();
        const localDateTime = new Date(now.getTime() - now.getTimezoneOffset() * 60000).toISOString().slice(0, 16);
        document.getElementById('followup-date').value = localDateTime;
        
        document.getElementById('task-form').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const data = {};
            formData.forEach((value, key) => {
                data[key] = value;
            });
            
            // Map form fields to API expected fields
            data.CustomerCode = 'CUST001';
            data.FollowupDate = data.followup_date || data.FollowupDate;
            data.Remarks = data.remarks || data.Remarks;
            
            // Remove underscore versions
            delete data.followup_date;
            delete data.remarks;
            
            // Show form data
            document.getElementById('form-data-result').style.display = 'block';
            document.getElementById('form-data-result').innerHTML = '<h3>Form Data:</h3><pre>' + JSON.stringify(data, null, 2) + '</pre>';
            
            // Test API
            try {
                const response = await fetch('/crm_system/Kiro_CRM_production/api/tasks/create.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(data)
                });
                
                const result = await response.json();
                
                document.getElementById('api-result').style.display = 'block';
                document.getElementById('api-result').innerHTML = '<h3>API Response (' + response.status + '):</h3><pre>' + JSON.stringify(result, null, 2) + '</pre>';
                
                if (result.status === 'success') {
                    alert('✅ Task created successfully!');
                } else {
                    alert('❌ Error: ' + result.message);
                }
            } catch (error) {
                document.getElementById('api-result').style.display = 'block';
                document.getElementById('api-result').innerHTML = '<h3>Fetch Error:</h3><pre>' + error.message + '</pre>';
            }
        });
    </script>
</body>
</html>