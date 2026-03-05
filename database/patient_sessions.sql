-- Patient Login/Logout Sessions Table
-- Tracks all login and logout activities for patients

CREATE TABLE IF NOT EXISTS patient_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id VARCHAR(50) NOT NULL COMMENT 'Patient ID (PRE001, PAT001, etc.)',
    patient_name VARCHAR(100) NOT NULL,
    patient_type ENUM('registered', 'prebooked', 'walkin') NOT NULL DEFAULT 'registered',
    login_time DATETIME NOT NULL,
    logout_time DATETIME DEFAULT NULL,
    session_duration_minutes INT DEFAULT NULL COMMENT 'Calculated on logout',
    ip_address VARCHAR(45) DEFAULT NULL,
    user_agent VARCHAR(255) DEFAULT NULL,
    status ENUM('active', 'logged_out', 'expired') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_patient_id (patient_id),
    INDEX idx_login_time (login_time),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
