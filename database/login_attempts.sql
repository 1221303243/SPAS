-- Login attempts tracking table for security monitoring
-- This table tracks all login attempts (successful and failed) for security analysis

CREATE TABLE IF NOT EXISTS login_attempts (
    attempt_id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_agent TEXT,
    attempt_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    success TINYINT(1) DEFAULT 0,
    failure_reason VARCHAR(255) DEFAULT NULL,
    INDEX idx_attempt_time (attempt_time),
    INDEX idx_email (email),
    INDEX idx_success (success),
    INDEX idx_ip_address (ip_address)
);

-- Add some sample data for testing (you can remove this in production)
INSERT INTO login_attempts (email, ip_address, user_agent, attempt_time, success, failure_reason) VALUES
('student@university.edu.my', '192.168.1.100', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)', NOW() - INTERVAL 2 HOUR, 1, NULL),
('hacker@gmail.com', '192.168.1.200', 'Mozilla/5.0 (Linux)', NOW() - INTERVAL 1 HOUR, 0, 'Invalid credentials'),
('admin@spas.com', '192.168.1.100', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)', NOW() - INTERVAL 30 MINUTE, 1, NULL),
('test@badactor.com', '192.168.1.300', 'curl/7.68.0', NOW() - INTERVAL 15 MINUTE, 0, 'Invalid credentials'),
('test@badactor.com', '192.168.1.300', 'curl/7.68.0', NOW() - INTERVAL 10 MINUTE, 0, 'Invalid credentials'),
('lecturer@university.edu.my', '192.168.1.150', 'Mozilla/5.0 (Mac)', NOW() - INTERVAL 5 MINUTE, 1, NULL); 