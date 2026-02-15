-- Migration: Add patient_id to tokens and lab_tokens tables
-- This enables tracking tokens by patient account, not just phone number

-- Add patient_id column to tokens table
ALTER TABLE tokens 
ADD COLUMN patient_id INT DEFAULT NULL AFTER patient_phone,
ADD INDEX idx_patient_id (patient_id);

-- Add patient_id column to lab_tokens table  
ALTER TABLE lab_tokens 
ADD COLUMN patient_id INT DEFAULT NULL AFTER patient_phone,
ADD INDEX idx_lab_patient_id (patient_id);

-- Note: Run this SQL in phpMyAdmin or MySQL command line
