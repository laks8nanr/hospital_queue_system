-- =========================================================
-- SQL QUERIES FOR PREBOOKED PATIENTS FEATURE
-- Run these in phpMyAdmin MySQL
-- =========================================================

-- =========================================================
-- 1. CREATE PREBOOKED APPOINTMENTS TABLE
-- =========================================================
CREATE TABLE IF NOT EXISTS prebooked_appointments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id VARCHAR(20) NOT NULL UNIQUE,
    patient_name VARCHAR(100) NOT NULL,
    patient_age INT NOT NULL,
    patient_phone VARCHAR(20) NOT NULL,
    department_id INT NOT NULL,
    doctor_id INT NOT NULL,
    appointment_date DATE NOT NULL,
    appointment_time TIME NOT NULL,
    status ENUM('booked', 'confirmed', 'checked_in', 'completed', 'cancelled') DEFAULT 'booked',
    payment_status ENUM('pending', 'paid', 'refunded') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- =========================================================
-- 2. A-DD NEW COLUMNS TO TOKENS TABLE
-- Run each ALTER separately if one fails
-- =========================================================
ALTER TABLE tokens ADD COLUMN token_type VARCHAR(20) DEFAULT 'walkin';
ALTER TABLE tokens ADD COLUMN expected_time TIME;
ALTER TABLE tokens ADD COLUMN booking_id VARCHAR(20);

-- =========================================================
-- 3. CHECK YOUR DOCTORS FIRST
-- Run this to see available doctors and their IDs
-- =========================================================
SELECT id, name, department_id FROM doctors;
SELECT id, name FROM departments;

-- =========================================================
-- 4. INSERT 3 SAMPLE PREBOOKED PATIENTS
-- IMPORTANT: Change department_id and doctor_id based on step 3
-- =========================================================
INSERT INTO prebooked_appointments (booking_id, patient_name, patient_age, patient_phone, department_id, doctor_id, appointment_date, appointment_time, status)
VALUES 
('PB001', 'Priya Sharma', 28, '9876543210', 1, 1, CURDATE(), '10:00:00', 'booked'),
('PB002', 'Sneha Reddy', 35, '9876543211', 1, 1, CURDATE(), '10:30:00', 'booked'),
('PB003', 'Rahul Menon', 42, '9876543212', 1, 1, CURDATE(), '11:00:00', 'booked');

  ('PB004', 'Riya Patel', 23, '3445562757', 1, 2, '2026-02-03', '14:00:00', 'booked', 'pending'),
  ('PB005', 'Aakash Rao', 31, '9123456701', 1, 2, '2026-02-03', '14:30:00', 'booked', 'pending'),
  ('PB006', 'Meera Das', 27, '9876501234', 1, 2, '2026-02-03', '15:30:00', 'booked', 'pending'),
  ('PB007', 'Sandeep Jain', 45, '9988112233', 2, 3, '2026-02-03', '10:45:00', 'booked', 'pending'),
  ('PB008', 'Ishita Singh', 34, '9765432109', 2, 3, '2026-02-03', '11:00:00', 'booked', 'pending'),
  ('PB009', 'Harish Kumar', 29, '9345612870', 2, 3, '2026-02-03', '11:15:00', 'booked', 'pending'),
  ('PB010', 'Lakshmi Nair', 38, '9012345678', 2, 4, '2026-02-03', '14:30:00', 'booked', 'pending'),
  ('PB011', 'Ganesh Pillai', 41, '9234567890', 2, 4, '2026-02-03', '15:45:00', 'booked', 'pending'),
  ('PB012', 'Divya George', 26, '9456123789', 2, 4, '2026-02-03', '16:00:00', 'booked', 'pending'),
  ('PB013', 'Sameer Ali', 33, '9654321876', 3, 5, '2026-02-03', '09:15:00', 'booked', 'pending'),
  ('PB014', 'Ananya Bose', 28, '9786543210', 3, 5, '2026-02-03', '10:30:00', 'booked', 'pending'),
  ('PB015', 'Rahul Desai', 36, '9345678123', 3, 5, '2026-02-03', '11:45:00', 'booked', 'pending');
('PB013', 'Ali', 33, '9654321876', 3, 6, '2026-02-03', '09:15:00', 'booked', 'pending'),
  ('PB014', 'Anna', 28, '9786543210', 3, 6, '2026-02-03', '10:30:00', 'booked', 'pending'),
  ('PB015', 'Ram', 36, '9345678123', 3, 6, '2026-02-03', '11:45:00', 'booked', 'pending');

-- =========================================================
-- 5. VERIFY DATA WAS INSERTED
-- =========================================================
SELECT * FROM prebooked_appointments;

-- =========================================================
-- 6. FOR TESTING - RESET EVERYTHING
-- Use these when you want to test again
-- =========================================================
-- Delete today's tokens (for fresh testing with PB001 format)
DELETE FROM tokens WHERE DATE(created_at) = CURDATE();

-- Reset bookings to 'booked' status so they can check-in again
UPDATE prebooked_appointments SET status = 'booked' WHERE appointment_date = CURDATE();

-- Verify reset worked
SELECT * FROM tokens WHERE DATE(created_at) = CURDATE();
SELECT * FROM prebooked_appointments WHERE appointment_date = CURDATE();

-- =========================================================
-- 7. VIEW CURRENT QUEUE (shows all patients, excludes cancelled)
-- =========================================================
SELECT 
    t.token_number,
    t.patient_name,
    COALESCE(t.token_type, 'walkin') as token_type,
    t.status,
    t.created_at
FROM tokens t
WHERE DATE(t.created_at) = CURDATE()
AND t.status IN ('waiting', 'consulting')
ORDER BY 
    CASE 
        WHEN t.status = 'consulting' THEN 0
        WHEN t.token_type = 'prebooked' THEN 1
        WHEN t.token_type = 'review' THEN 2
        ELSE 3
    END,
    t.created_at ASC;

-- =========================================================
-- 8. COMBINED QUEUE (ordered by appointment time slot)
-- Prebooked patients are sorted by their booked time slot
-- Walk-ins come after all prebooked patients
-- =========================================================
SELECT * FROM (
    -- Checked-in PREBOOKED patients (use expected_time = appointment time)
    SELECT 
        t.token_number,
        t.patient_name,
        'prebooked' as token_type,
        t.status,
        COALESCE(t.expected_time, TIME(t.created_at)) as appointment_time,
        'checked_in' as checkin_status
    FROM tokens t
    WHERE DATE(t.created_at) = CURDATE()
    AND t.status IN ('waiting', 'consulting')
    AND (t.token_type = 'prebooked' OR t.token_number LIKE 'PB%')
    
    UNION ALL
    
    -- Checked-in WALK-IN patients
    SELECT 
        t.token_number,
        t.patient_name,
        COALESCE(t.token_type, 'walkin') as token_type,
        t.status,
        TIME(t.created_at) as appointment_time,
        'checked_in' as checkin_status
    FROM tokens t
    WHERE DATE(t.created_at) = CURDATE()
    AND t.status IN ('waiting', 'consulting')
    AND t.token_number NOT LIKE 'PB%'
    
    UNION ALL
    
    -- Prebooked patients who haven't checked in yet
    SELECT 
        pb.booking_id as token_number,
        pb.patient_name,
        'prebooked' as token_type,
        'waiting' as status,
        pb.appointment_time,
        'not_checked_in' as checkin_status
    FROM prebooked_appointments pb
    WHERE pb.appointment_date = CURDATE()
    AND pb.status = 'booked'
    AND NOT EXISTS (
        SELECT 1 FROM tokens t 
        WHERE t.token_number = pb.booking_id 
        AND DATE(t.created_at) = CURDATE()
        AND t.status != 'cancelled'
    )
) AS combined_queue
ORDER BY 
    CASE WHEN status = 'consulting' THEN 0 ELSE 1 END,
    CASE WHEN token_type = 'prebooked' OR token_number LIKE 'PB%' THEN 0 ELSE 1 END,
    appointment_time ASC;
