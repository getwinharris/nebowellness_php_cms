-- Update admin email from sripanchamispiritual@gmail.com to nebolifestyleclinic@gmail.com
-- Run this SQL on the production database after deployment
-- Password remains: ChangeThisAdmin123!

UPDATE users 
SET 
    email = 'nebolifestyleclinic@gmail.com',
    username = 'nebolifestyleclinic@gmail.com'
WHERE 
    email = 'sripanchamispiritual@gmail.com' 
    AND role = 'admin';

-- Verify the update
SELECT id, email, username, role, created_at 
FROM users 
WHERE role = 'admin';
