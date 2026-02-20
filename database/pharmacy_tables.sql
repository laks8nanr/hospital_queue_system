-- ============================================
-- PHARMACY TABLES - Hospital Queue System
-- ============================================

-- ============================================
-- PHARMACIES TABLE
-- Stores pharmacy locations with block, floor, wing info
-- ============================================
CREATE TABLE IF NOT EXISTS pharmacies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    block VARCHAR(20) NOT NULL,
    floor VARCHAR(20) NOT NULL,
    wing VARCHAR(50) NOT NULL,
    description VARCHAR(255),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================
-- PHARMACY STAFF TABLE
-- Staff who manage pharmacy counters
-- ============================================
CREATE TABLE IF NOT EXISTS pharmacy_staff (
    id INT AUTO_INCREMENT PRIMARY KEY,
    staff_id VARCHAR(20) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    name VARCHAR(100) NOT NULL,
    pharmacy_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (pharmacy_id) REFERENCES pharmacies(id)
);

-- ============================================
-- PHARMACY TOKENS TABLE
-- Tokens generated for pharmacy queue
-- ============================================
CREATE TABLE IF NOT EXISTS pharmacy_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    token_number VARCHAR(20) NOT NULL,
    patient_name VARCHAR(100) NOT NULL,
    patient_phone VARCHAR(20),
    patient_id INT NULL,
    pharmacy_id INT NOT NULL,
    status ENUM('waiting', 'processing', 'completed', 'cancelled') DEFAULT 'waiting',
    payment_method ENUM('online', 'cash') DEFAULT 'cash',
    notes TEXT,
    cancelled_at TIMESTAMP NULL,
    cancellation_reason VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_pharmacy_date (pharmacy_id, created_at),
    INDEX idx_status (status),
    INDEX idx_token_number (token_number),
    INDEX idx_patient (patient_id),
    FOREIGN KEY (pharmacy_id) REFERENCES pharmacies(id)
);

-- ============================================
-- SAMPLE DATA - PHARMACIES
-- Two pharmacies at different locations
-- ============================================
INSERT INTO pharmacies (id, name, block, floor, wing, description, is_active) VALUES
(1, 'Main Pharmacy', 'A', '1', 'East', 'Main hospital pharmacy - General medications', TRUE),
(2, 'Emergency Pharmacy', 'B', 'Ground', 'West', 'Emergency and critical care pharmacy', TRUE)
ON DUPLICATE KEY UPDATE name = VALUES(name), block = VALUES(block), floor = VALUES(floor), wing = VALUES(wing);

-- ============================================
-- SAMPLE DATA - PHARMACY STAFF
-- ============================================
INSERT INTO pharmacy_staff (staff_id, password, name, pharmacy_id) VALUES
('PHARM001', 'pharm123', 'Ravi Kumar', 1),
('PHARM002', 'pharm123', 'Priya Sharma', 1),
('PHARM003', 'pharm123', 'Suresh Nair', 2),
('PHARM004', 'pharm123', 'Meena Das', 2)
ON DUPLICATE KEY UPDATE name = VALUES(name);

-- ============================================
-- SAMPLE PHARMACY TOKENS (for testing)
-- ============================================
DELETE FROM pharmacy_tokens WHERE DATE(created_at) = CURDATE();

INSERT INTO pharmacy_tokens (token_number, patient_name, patient_phone, pharmacy_id, status, payment_method) VALUES
('PH1-001', 'Amit Patel', '9876543001', 1, 'completed', 'cash'),
('PH1-002', 'Neha Gupta', '9876543002', 1, 'processing', 'online'),
('PH1-003', 'Raj Kumar', '9876543003', 1, 'waiting', 'cash'),
('PH1-004', 'Sita Devi', '9876543004', 1, 'waiting', 'online'),
('PH1-005', 'Vikram Singh', '9876543005', 1, 'waiting', 'cash'),
('PH2-001', 'Lakshmi Menon', '9876543006', 2, 'completed', 'cash'),
('PH2-002', 'Karthik Raj', '9876543007', 2, 'processing', 'online'),
('PH2-003', 'Divya Nair', '9876543008', 2, 'waiting', 'cash');
