-- Create permanent admin user
INSERT INTO users (email, password, user_type)
VALUES ('admin@tpos.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Get the user ID of the newly created admin
SET @admin_user_id = LAST_INSERT_ID();

-- Create admin profile
INSERT INTO admins (user_id, name, contact)
VALUES (@admin_user_id, 'System Administrator', '+1234567890'); 