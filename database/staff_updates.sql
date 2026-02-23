-- ============================================
-- Staff Table Updates for DORA Hospital Queue System
-- ============================================
-- Run this script to add doctor_id column to staff table
-- and create sample staff records for each doctor
-- ============================================

USE hospital_queue;

-- Add doctor_id column if it doesn't exist
SET @column_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = 'hospital_queue' 
    AND TABLE_NAME = 'staff' 
    AND COLUMN_NAME = 'doctor_id'
);

SET @sql = IF(@column_exists = 0, 
    'ALTER TABLE staff ADD COLUMN doctor_id INT DEFAULT NULL AFTER department',
    'SELECT "doctor_id column already exists"'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Update existing staff record to link to a doctor
UPDATE staff SET doctor_id = 1 WHERE staff_id = 'STAFF001';

-- Insert sample staff for each doctor (if not exists)
INSERT INTO staff (staff_id, password, name, department, doctor_id) 
SELECT 'STAFF002', 'password123', 'Staff - Dr. Sunita', 'Cardiology', 2
WHERE NOT EXISTS (SELECT 1 FROM staff WHERE staff_id = 'STAFF002');

INSERT INTO staff (staff_id, password, name, department, doctor_id) 
SELECT 'STAFF003', 'password123', 'Staff - Dr. Anil', 'Orthopedics', 3
WHERE NOT EXISTS (SELECT 1 FROM staff WHERE staff_id = 'STAFF003');

INSERT INTO staff (staff_id, password, name, department, doctor_id) 
SELECT 'STAFF004', 'password123', 'Staff - Dr. Priya', 'Pediatrics', 4
WHERE NOT EXISTS (SELECT 1 FROM staff WHERE staff_id = 'STAFF004');

INSERT INTO staff (staff_id, password, name, department, doctor_id) 
SELECT 'STAFF005', 'password123', 'Staff - Dr. Vikram', 'Dermatology', 5
WHERE NOT EXISTS (SELECT 1 FROM staff WHERE staff_id = 'STAFF005');

-- Show all staff members
SELECT * FROM staff;

-- ============================================
-- STAFF LOGIN CREDENTIALS
-- ============================================
/*
Staff ID: STAFF001  Password: password123  Doctor: Dr. Rajesh Kumar (General Medicine)
Staff ID: STAFF002  Password: password123  Doctor: Dr. Sunita Sharma (Cardiology)
Staff ID: STAFF003  Password: password123  Doctor: Dr. Anil Verma (Orthopedics)
Staff ID: STAFF004  Password: password123  Doctor: Dr. Priya Nair (Pediatrics)
Staff ID: STAFF005  Password: password123  Doctor: Dr. Vikram Singh (Dermatology)
*/
