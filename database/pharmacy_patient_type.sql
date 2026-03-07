-- ============================================
-- PHARMACY PATIENT TYPE TRACKING
-- Adds columns to track today patient vs outside patient
-- and link to completed consultation tokens
-- ============================================

-- Add patient_type and consultation reference to pharmacy_tokens
ALTER TABLE pharmacy_tokens 
ADD COLUMN patient_type ENUM('today_patient', 'outside_patient') DEFAULT 'outside_patient' AFTER patient_id,
ADD COLUMN consultation_token_id INT NULL AFTER patient_type,
ADD COLUMN consultation_token_number VARCHAR(20) NULL AFTER consultation_token_id;

-- Add index for consultation lookups
ALTER TABLE pharmacy_tokens ADD INDEX idx_consultation_token (consultation_token_id);
