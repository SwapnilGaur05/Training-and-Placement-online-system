-- Insert company users
INSERT INTO `users` (`email`, `password`, `user_type`, `created_at`) VALUES
('hr@infosys.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'company', NOW()), -- password: password
('careers@tcs.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'company', NOW()), -- password: password
('recruitment@wipro.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'company', NOW()), -- password: password
('talent@accenture.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'company', NOW()); -- password: password

-- Get the inserted user IDs (you'll need to replace these with actual IDs in your system)
SET @infosys_user_id = (SELECT id FROM users WHERE email = 'hr@infosys.com');
SET @tcs_user_id = (SELECT id FROM users WHERE email = 'careers@tcs.com');
SET @wipro_user_id = (SELECT id FROM users WHERE email = 'recruitment@wipro.com');
SET @accenture_user_id = (SELECT id FROM users WHERE email = 'talent@accenture.com');

-- Insert companies
INSERT INTO `companies` (
    `user_id`, 
    `name`, 
    `description`, 
    `industry`, 
    `location`, 
    `website`, 
    `contact_person`, 
    `contact_email`, 
    `contact_phone`, 
    `established_year`, 
    `employee_count`, 
    `status`
) VALUES
(
    @infosys_user_id,
    'Infosys Limited',
    'Infosys is a global leader in next-generation digital services and consulting. We enable clients in more than 50 countries to navigate their digital transformation.',
    'Information Technology',
    'Bangalore, India',
    'https://www.infosys.com',
    'Rajesh Kumar',
    'hr@infosys.com',
    '+91-80-2852-0261',
    1981,
    300000,
    'active'
),
(
    @tcs_user_id,
    'Tata Consultancy Services',
    'TCS is an IT services, consulting and business solutions organization that has been partnering with many of the world's largest businesses in their transformation journeys.',
    'Information Technology',
    'Mumbai, India',
    'https://www.tcs.com',
    'Priya Sharma',
    'careers@tcs.com',
    '+91-22-6778-9999',
    1968,
    550000,
    'active'
),
(
    @wipro_user_id,
    'Wipro Limited',
    'Wipro is a leading global information technology, consulting and business process services company, delivering solutions to enable its clients do business better.',
    'Information Technology',
    'Bangalore, India',
    'https://www.wipro.com',
    'Arun Patel',
    'recruitment@wipro.com',
    '+91-80-2844-0011',
    1945,
    250000,
    'active'
),
(
    @accenture_user_id,
    'Accenture India',
    'Accenture is a global professional services company with leading capabilities in digital, cloud and security, helping clients build their digital core and optimize operations.',
    'Consulting & Technology',
    'Mumbai, India',
    'https://www.accenture.com/in-en',
    'Sarah Matthews',
    'talent@accenture.com',
    '+91-22-6123-4567',
    1989,
    200000,
    'active'
);

-- You can use these credentials to log in:
-- Email: hr@infosys.com
-- Email: careers@tcs.com
-- Email: recruitment@wipro.com
-- Email: talent@accenture.com
-- Password for all: password 