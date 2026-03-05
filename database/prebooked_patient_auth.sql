-- ============================================
-- Add Patient Authentication to Prebooked Appointments
-- ============================================
-- This adds patient_id, email, and password columns to prebooked_appointments
-- so prebooked patients can login using these credentials
-- ============================================

USE hospital_queue;

-- Add new columns for patient authentication
ALTER TABLE prebooked_appointments
ADD COLUMN patient_id VARCHAR(20) NULL AFTER id,
ADD COLUMN email VARCHAR(100) NULL AFTER patient_phone,
ADD COLUMN password VARCHAR(255) NULL AFTER email;

-- Add unique index for patient_id and email
ALTER TABLE prebooked_appointments
ADD UNIQUE INDEX idx_prebooked_patient_id (patient_id),
ADD INDEX idx_prebooked_email (email);

-- Update existing prebooked appointments with auto-generated patient_ids
-- Format: PRE001, PRE002, etc.
UPDATE prebooked_appointments 
SET patient_id = CONCAT('PRE', LPAD(id, 3, '0'))
WHERE patient_id IS NULL;

-- Set default password for existing records (hashed 'patient123')
-- In production, you should require patients to set their own password
UPDATE prebooked_appointments 
SET password = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'
WHERE password IS NULL;

-- Set sample emails for existing records (can be updated later)
UPDATE prebooked_appointments 
SET email = CONCAT(LOWER(REPLACE(patient_name, ' ', '.')), '@example.com')
WHERE email IS NULL;

-- ============================================
-- Sample data with authentication info
-- ============================================
-- You can add new prebooked appointments with full auth info like this:
-- INSERT INTO prebooked_appointments 
--   (patient_id, booking_id, patient_name, patient_age, patient_phone, email, password, department_id, doctor_id, appointment_date, appointment_time)
-- VALUES 
--   ('PRE020', 'PB020', 'Test Patient', 30, '9876543210', 'test@example.com', '$2y$10$...', 1, 1, CURDATE(), '10:00:00');
