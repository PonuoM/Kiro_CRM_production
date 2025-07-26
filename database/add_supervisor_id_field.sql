-- Add supervisor_id field to users table for team hierarchy
-- Execute this SQL in MySQL to add supervisor-sales relationship

USE primacom_CRM;

-- Add supervisor_id column to users table
ALTER TABLE users 
ADD COLUMN supervisor_id INT NULL AFTER Role,
ADD INDEX idx_supervisor_id (supervisor_id),
ADD CONSTRAINT fk_users_supervisor 
    FOREIGN KEY (supervisor_id) REFERENCES users(id) 
    ON DELETE SET NULL ON UPDATE CASCADE;

-- Update existing data with sample team structure
-- Note: Adjust these values based on your actual user IDs

-- Set supervisor (ID=2) for sales users
UPDATE users 
SET supervisor_id = 2 
WHERE Role = 'Sales' AND id IN (3, 4);

-- Admin and Supervisor users have no supervisor (NULL)
UPDATE users 
SET supervisor_id = NULL 
WHERE Role IN ('Admin', 'Supervisor');

-- Verify the changes
SELECT 
    id,
    Username,
    Role,
    supervisor_id,
    CASE 
        WHEN supervisor_id IS NULL THEN 'No Supervisor'
        ELSE CONCAT('Reports to User ID: ', supervisor_id)
    END as ReportsTo
FROM users
ORDER BY Role, id;

-- Show team hierarchy
SELECT 
    s.id as supervisor_id,
    s.Username as supervisor_name,
    s.Role as supervisor_role,
    COUNT(t.id) as team_size,
    GROUP_CONCAT(t.Username ORDER BY t.Username SEPARATOR ', ') as team_members
FROM users s
LEFT JOIN users t ON s.id = t.supervisor_id
WHERE s.Role = 'Supervisor'
GROUP BY s.id, s.Username, s.Role

UNION ALL

SELECT 
    NULL as supervisor_id,
    'Admin Users' as supervisor_name,
    'Admin' as supervisor_role,
    COUNT(*) as team_size,
    GROUP_CONCAT(Username ORDER BY Username SEPARATOR ', ') as team_members
FROM users 
WHERE Role = 'Admin'

ORDER BY supervisor_role, supervisor_id;