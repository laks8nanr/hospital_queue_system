-- =====================================================
-- LAB BOOKING SYSTEM - DATABASE TABLES
-- Dora Hospital Queue Management System
-- =====================================================
-- Drop existing tables if they exist
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS lab_tokens;
DROP TABLE IF EXISTS lab_slot_availability;
DROP TABLE IF EXISTS prebooked_lab_appointments;
DROP TABLE IF EXISTS lab_tests;
SET FOREIGN_KEY_CHECKS = 1;
-- =====================================================
-- 1. LAB TESTS MASTER TABLE
-- =====================================================
CREATE TABLE lab_tests (
    test_id INT PRIMARY KEY AUTO_INCREMENT,
    test_code VARCHAR(10) NOT NULL UNIQUE,
    test_name VARCHAR(100) NOT NULL,
    duration_minutes INT NOT NULL,
    fee DECIMAL(10,2) NOT NULL,
    slot_duration INT NOT NULL COMMENT 'Duration per slot in minutes',
    daily_capacity INT NOT NULL,
    advance_booking_days INT DEFAULT 7,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert lab tests
INSERT INTO lab_tests (test_code, test_name, duration_minutes, fee, slot_duration, daily_capacity, advance_booking_days) VALUES
('MRI', 'MRI Scan', 45, 8000.00, 45, 12, 21),
('CT', 'CT Scan', 25, 5000.00, 30, 16, 14),
('BT', 'Blood Test', 10, 500.00, 10, 50, 7),
('XR', 'X-Ray', 15, 800.00, 15, 30, 7);

-- =====================================================
-- 2. PREBOOKED LAB APPOINTMENTS TABLE
-- =====================================================
CREATE TABLE prebooked_lab_appointments (
    booking_id INT PRIMARY KEY AUTO_INCREMENT,
    booking_code VARCHAR(20) NOT NULL UNIQUE COMMENT 'Format: LB001, LB002, etc.',
    test_id INT NOT NULL,
    patient_name VARCHAR(100) NOT NULL,
    patient_age INT NOT NULL,
    patient_gender ENUM('male', 'female', 'other') NOT NULL,
    patient_phone VARCHAR(15) NOT NULL,
    appointment_date DATE NOT NULL,
    appointment_time TIME NOT NULL,
    prescription_file VARCHAR(255) DEFAULT NULL,
    payment_status ENUM('pending', 'paid', 'refunded') DEFAULT 'paid',
    payment_amount DECIMAL(10,2) NOT NULL,
    booking_status ENUM('booked', 'checked_in', 'in_progress', 'completed', 'cancelled', 'no_show') DEFAULT 'booked',
    token_number VARCHAR(20) DEFAULT NULL,
    check_in_time DATETIME DEFAULT NULL,
    completed_time DATETIME DEFAULT NULL,
    notes TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (test_id) REFERENCES lab_tests(test_id)
);

-- =====================================================
-- 3. LAB TOKENS TABLE (For Walk-in & Checked-in Patients)
-- =====================================================
CREATE TABLE lab_tokens (
    token_id INT PRIMARY KEY AUTO_INCREMENT,
    token_number VARCHAR(20) NOT NULL,
    test_id INT NOT NULL,
    patient_name VARCHAR(100) NOT NULL,
    patient_age INT NOT NULL,
    patient_gender ENUM('male', 'female', 'other') NOT NULL,
    patient_phone VARCHAR(15) NOT NULL,
    patient_type ENUM('walkin', 'prebooked') NOT NULL,
    booking_id INT DEFAULT NULL COMMENT 'Reference to prebooked appointment if applicable',
    scheduled_date DATE NOT NULL,
    scheduled_time TIME DEFAULT NULL,
    prescription_file VARCHAR(255) DEFAULT NULL,
    payment_amount DECIMAL(10,2) NOT NULL,
    token_status ENUM('waiting', 'in_progress', 'completed', 'cancelled', 'no_show') DEFAULT 'waiting',
    queue_position INT DEFAULT NULL,
    called_time DATETIME DEFAULT NULL,
    start_time DATETIME DEFAULT NULL,
    end_time DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (test_id) REFERENCES lab_tests(test_id),
    FOREIGN KEY (booking_id) REFERENCES prebooked_lab_appointments(booking_id)
);

-- =====================================================
-- 4. LAB SLOT AVAILABILITY TABLE
-- =====================================================
CREATE TABLE lab_slot_availability (
    slot_id INT PRIMARY KEY AUTO_INCREMENT,
    test_id INT NOT NULL,
    slot_date DATE NOT NULL,
    slot_time TIME NOT NULL,
    total_capacity INT NOT NULL,
    booked_count INT DEFAULT 0,
    available_count INT GENERATED ALWAYS AS (total_capacity - booked_count) STORED,
    FOREIGN KEY (test_id) REFERENCES lab_tests(test_id),
    UNIQUE KEY unique_slot (test_id, slot_date, slot_time)
);

-- =====================================================
-- SAMPLE DATA: 3 PREBOOKED APPOINTMENTS PER TEST
-- =====================================================

-- Get current date for appointments
SET @today = CURDATE();
SET @tomorrow = DATE_ADD(@today, INTERVAL 1 DAY);
SET @day_after = DATE_ADD(@today, INTERVAL 2 DAY);

-- =====================================================
-- MRI SCAN PREBOOKINGS (3 patients)
-- =====================================================
INSERT INTO prebooked_lab_appointments 
(booking_code, test_id, patient_name, patient_age, patient_gender, patient_phone, appointment_date, appointment_time, payment_status, payment_amount, booking_status) 
VALUES
('LB001', 1, 'Rajesh Kumar', 45, 'male', '9876543210', @today, '09:00:00', 'paid', 8000.00, 'booked'),
('LB002', 1, 'Priya Sharma', 32, 'female', '9876543211', @today, '10:30:00', 'paid', 8000.00, 'booked'),
('LB003', 1, 'Amit Patel', 55, 'male', '9876543212', @tomorrow, '09:45:00', 'paid', 8000.00, 'booked');

-- =====================================================
-- CT SCAN PREBOOKINGS (3 patients)
-- =====================================================
INSERT INTO prebooked_lab_appointments 
(booking_code, test_id, patient_name, patient_age, patient_gender, patient_phone, appointment_date, appointment_time, payment_status, payment_amount, booking_status) 
VALUES
('LB004', 2, 'Sunita Devi', 48, 'female', '9876543213', @today, '11:00:00', 'paid', 5000.00, 'booked'),
('LB005', 2, 'Vikram Singh', 38, 'male', '9876543214', @today, '14:00:00', 'paid', 5000.00, 'booked'),
('LB006', 2, 'Meera Reddy', 29, 'female', '9876543215', @tomorrow, '10:00:00', 'paid', 5000.00, 'booked');

-- =====================================================
-- BLOOD TEST PREBOOKINGS (3 patients)
-- =====================================================
INSERT INTO prebooked_lab_appointments 
(booking_code, test_id, patient_name, patient_age, patient_gender, patient_phone, appointment_date, appointment_time, payment_status, payment_amount, booking_status) 
VALUES
('LB007', 3, 'Ananya Gupta', 25, 'female', '9876543216', @today, '09:10:00', 'paid', 500.00, 'booked'),
('LB008', 3, 'Rahul Verma', 42, 'male', '9876543217', @today, '09:30:00', 'paid', 500.00, 'booked'),
('LB009', 3, 'Kavita Nair', 35, 'female', '9876543218', @today, '10:00:00', 'paid', 500.00, 'booked');

-- =====================================================
-- X-RAY PREBOOKINGS (3 patients)
-- =====================================================
INSERT INTO prebooked_lab_appointments 
(booking_code, test_id, patient_name, patient_age, patient_gender, patient_phone, appointment_date, appointment_time, payment_status, payment_amount, booking_status) 
VALUES
('LB010', 4, 'Suresh Menon', 52, 'male', '9876543219', @today, '11:30:00', 'paid', 800.00, 'booked'),
('LB011', 4, 'Deepa Iyer', 28, 'female', '9876543220', @today, '13:00:00', 'paid', 800.00, 'booked'),
('LB012', 4, 'Manoj Tiwari', 60, 'male', '9876543221', @tomorrow, '09:15:00', 'paid', 800.00, 'booked');

-- =====================================================
-- CREATE INDEXES FOR PERFORMANCE
-- =====================================================
CREATE INDEX idx_booking_code ON prebooked_lab_appointments(booking_code);
CREATE INDEX idx_booking_date ON prebooked_lab_appointments(appointment_date);
CREATE INDEX idx_booking_status ON prebooked_lab_appointments(booking_status);
CREATE INDEX idx_token_date ON lab_tokens(scheduled_date);
CREATE INDEX idx_token_status ON lab_tokens(token_status);
CREATE INDEX idx_token_number ON lab_tokens(token_number);

-- =====================================================
-- VIEW: Today's Lab Queue
-- =====================================================
CREATE OR REPLACE VIEW today_lab_queue AS
SELECT 
    lt.token_id,
    lt.token_number,
    ltest.test_name,
    lt.patient_name,
    lt.patient_type,
    lt.scheduled_time,
    lt.token_status,
    lt.queue_position,
    lt.created_at
FROM lab_tokens lt
JOIN lab_tests ltest ON lt.test_id = ltest.test_id
WHERE lt.scheduled_date = CURDATE()
AND lt.token_status IN ('waiting', 'in_progress')
ORDER BY lt.queue_position ASC;

-- =====================================================
-- VIEW: Today's Prebooked Appointments
-- =====================================================
CREATE OR REPLACE VIEW today_prebooked_labs AS
SELECT 
    pla.booking_id,
    pla.booking_code,
    ltest.test_name,
    ltest.test_code,
    pla.patient_name,
    pla.patient_phone,
    pla.appointment_time,
    pla.booking_status,
    pla.token_number
FROM prebooked_lab_appointments pla
JOIN lab_tests ltest ON pla.test_id = ltest.test_id
WHERE pla.appointment_date = CURDATE()
ORDER BY pla.appointment_time ASC;

-- =====================================================
-- USEFUL QUERIES
-- =====================================================

-- Query 1: Get all prebooked appointments for today
-- SELECT * FROM today_prebooked_labs;

-- Query 2: Verify a booking by booking code
-- SELECT * FROM prebooked_lab_appointments WHERE booking_code = 'LB001';

-- Query 3: Get queue for a specific test today
-- SELECT * FROM lab_tokens WHERE test_id = 1 AND scheduled_date = CURDATE() AND token_status = 'waiting' ORDER BY queue_position;

-- Query 4: Get slot availability for a date
-- SELECT * FROM lab_slot_availability WHERE test_id = 1 AND slot_date = CURDATE();

-- Query 5: Count appointments by test type for today
-- SELECT ltest.test_name, COUNT(*) as total_bookings 
-- FROM prebooked_lab_appointments pla 
-- JOIN lab_tests ltest ON pla.test_id = ltest.test_id 
-- WHERE pla.appointment_date = CURDATE() 
-- GROUP BY ltest.test_name;

SELECT 'Lab tables created successfully!' AS status;
SELECT '12 prebooked appointments inserted (3 per test type)' AS info;

-- Show the inserted data
SELECT booking_code, 
       (SELECT test_name FROM lab_tests WHERE test_id = pla.test_id) as test_name,
       patient_name, 
       appointment_date, 
       appointment_time, 
       payment_amount,
       booking_status
FROM prebooked_lab_appointments pla
ORDER BY test_id, appointment_date, appointment_time;
