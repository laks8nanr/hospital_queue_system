-- ============================================
-- DORA Hospital Queue System - Database Schema
-- ============================================
-- This file documents the complete database structure
-- Run this to create/reset the database
-- ============================================

-- Create database
CREATE DATABASE IF NOT EXISTS hospital_queue;
USE hospital_queue;

-- ============================================
-- DEPARTMENTS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS departments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================
-- DOCTORS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS doctors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    qualification VARCHAR(100) DEFAULT 'MBBS',
    department_id INT NOT NULL,
    specialization VARCHAR(100),
    fees DECIMAL(10,2) DEFAULT 500.00,
    time_slot VARCHAR(50) DEFAULT '9:00 AM - 5:00 PM',
    available BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (department_id) REFERENCES departments(id)
);

-- ============================================
-- PREBOOKED APPOINTMENTS TABLE
-- Added by hospital reception
-- Patients verify and check-in through website
-- ============================================
CREATE TABLE IF NOT EXISTS prebooked_appointments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id VARCHAR(20) NOT NULL UNIQUE,           -- PB001, PB002, etc. (also used as token number)
    patient_name VARCHAR(100) NOT NULL,
    patient_age INT NOT NULL,
    patient_phone VARCHAR(20) NOT NULL,
    department_id INT NOT NULL,
    doctor_id INT NOT NULL,
    appointment_date DATE NOT NULL,
    appointment_time TIME NOT NULL,                   -- Time slot for queue ordering
    status ENUM('booked', 'confirmed', 'checked_in', 'completed', 'cancelled') DEFAULT 'booked',
    payment_status ENUM('pending', 'paid', 'refunded') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_date_doctor (appointment_date, doctor_id),
    INDEX idx_status (status),
    FOREIGN KEY (department_id) REFERENCES departments(id),
    FOREIGN KEY (doctor_id) REFERENCES doctors(id)
);

-- ============================================
-- TOKENS TABLE
-- Created when patient checks in (walk-in or prebooked)
-- ============================================
CREATE TABLE IF NOT EXISTS tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    token_number VARCHAR(20) NOT NULL,                -- P-001 (walk-in), PB001 (prebooked)
    patient_name VARCHAR(100),
    patient_age INT DEFAULT 0,
    patient_phone VARCHAR(15),
    department_id INT,
    doctor_id INT,
    status ENUM('waiting', 'consulting', 'completed', 'cancelled') DEFAULT 'waiting',
    type ENUM('walkin', 'prebooked', 'pharmacy', 'lab', 'review') DEFAULT 'walkin',
    token_type ENUM('walkin', 'prebooked', 'review') DEFAULT 'walkin',
    expected_time TIME,                               -- Appointment time for prebooked, generated for walk-ins
    booking_id VARCHAR(20),                           -- Links to prebooked_appointments.booking_id
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_date_doctor_status (created_at, doctor_id, status),
    INDEX idx_token_number (token_number),
    INDEX idx_booking_id (booking_id),
    FOREIGN KEY (department_id) REFERENCES departments(id),
    FOREIGN KEY (doctor_id) REFERENCES doctors(id)
);

-- ============================================
-- SAMPLE DATA - DEPARTMENTS
-- ============================================
INSERT INTO departments (id, name, description) VALUES
(1, 'General Medicine', 'General health consultations'),
(2, 'Cardiology', 'Heart-related treatments'),
(3, 'Orthopedics', 'Bone and joint care'),
(4, 'Pediatrics', 'Child healthcare'),
(5, 'Dermatology', 'Skin treatments')
ON DUPLICATE KEY UPDATE name = VALUES(name);

-- ============================================
-- SAMPLE DATA - DOCTORS
-- ============================================
INSERT INTO doctors (id, name, qualification, department_id, specialization, fees, time_slot, available) VALUES
(1, 'Dr. Rajesh Kumar', 'MBBS, MD', 1, 'General Physician', 300.00, '9:00 AM - 1:00 PM', TRUE),
(2, 'Dr. Sunita Sharma', 'MBBS, MD (Cardiology)', 2, 'Heart Specialist', 600.00, '10:00 AM - 4:00 PM', TRUE),
(3, 'Dr. Anil Verma', 'MBBS, MS (Ortho)', 3, 'Orthopedic Surgeon', 500.00, '9:00 AM - 2:00 PM', TRUE),
(4, 'Dr. Priya Nair', 'MBBS, MD (Pediatrics)', 4, 'Child Specialist', 400.00, '11:00 AM - 5:00 PM', TRUE),
(5, 'Dr. Vikram Singh', 'MBBS, MD (Dermatology)', 5, 'Skin Specialist', 450.00, '10:00 AM - 3:00 PM', TRUE)
ON DUPLICATE KEY UPDATE name = VALUES(name);

-- ============================================
-- SAMPLE PREBOOKED APPOINTMENTS
-- These are added by hospital reception
-- booking_id becomes the token number when patient checks in
-- ============================================

-- Clear existing prebooked data for today
DELETE FROM prebooked_appointments WHERE appointment_date = CURDATE();

-- Insert sample prebooked appointments for today
INSERT INTO prebooked_appointments (booking_id, patient_name, patient_age, patient_phone, department_id, doctor_id, appointment_date, appointment_time, status, payment_status) VALUES
-- Doctor 1 - General Medicine
('PB001', 'Priya Sharma', 28, '9876543210', 1, 1, CURDATE(), '10:00:00', 'booked', 'pending'),
('PB002', 'Sneha Reddy', 35, '9876543211', 1, 1, CURDATE(), '10:30:00', 'booked', 'pending'),
('PB003', 'Rahul Menon', 42, '9876543212', 1, 1, CURDATE(), '11:00:00', 'booked', 'pending'),
('PB004', 'Anita Desai', 55, '9876543213', 1, 1, CURDATE(), '11:30:00', 'booked', 'pending'),
-- Doctor 2 - Cardiology
('PB005', 'Ramesh Iyer', 60, '9876543214', 2, 2, CURDATE(), '10:00:00', 'booked', 'pending'),
('PB006', 'Lakshmi Nair', 48, '9876543215', 2, 2, CURDATE(), '10:45:00', 'booked', 'pending'),
('PB007', 'Suresh Pillai', 52, '9876543216', 2, 2, CURDATE(), '11:30:00', 'booked', 'pending'),
-- Doctor 3 - Orthopedics
('PB008', 'Vikram Patel', 38, '9876543217', 3, 3, CURDATE(), '09:30:00', 'booked', 'pending'),
('PB009', 'Meera Das', 45, '9876543218', 3, 3, CURDATE(), '10:15:00', 'booked', 'pending'),
('PB010', 'Karthik Raj', 33, '9876543219', 3, 3, CURDATE(), '11:00:00', 'booked', 'pending'),
-- Doctor 4 - Pediatrics
('PB011', 'Baby Arjun', 5, '9876543220', 4, 4, CURDATE(), '11:00:00', 'booked', 'pending'),
('PB012', 'Baby Riya', 3, '9876543221', 4, 4, CURDATE(), '11:30:00', 'booked', 'pending'),
('PB013', 'Baby Dev', 7, '9876543222', 4, 4, CURDATE(), '12:00:00', 'booked', 'pending'),
-- Doctor 5 - Dermatology
('PB014', 'Neha Gupta', 25, '9876543223', 5, 5, CURDATE(), '10:00:00', 'booked', 'pending'),
('PB015', 'Amit Shah', 30, '9876543224', 5, 5, CURDATE(), '10:30:00', 'booked', 'pending'),
('PB016', 'Divya Rao', 22, '9876543225', 5, 5, CURDATE(), '11:00:00', 'booked', 'pending')
ON DUPLICATE KEY UPDATE status = 'booked', payment_status = 'pending';

-- ============================================
-- CLEAR OLD TOKENS (for testing)
-- ============================================
DELETE FROM tokens WHERE DATE(created_at) = CURDATE();

-- ============================================
-- FLOW EXPLANATION
-- ============================================
/*
PREBOOKED PATIENT FLOW:
=======================
1. Hospital reception adds patient to prebooked_appointments table
   - Status: 'booked'
   - booking_id: PB001, PB002, etc. (this becomes the token number)

2. Patient visits prebooked.html and enters their booking_id
   - API: api/verify_booking.php
   - Shows patient details, doctor info, appointment time

3. Patient confirms details
   - Status remains 'booked'

4. Patient pays consultation fee
   - payment_status: 'pending' → 'paid'

5. Patient checks in
   - API: api/checkin_prebooked.php
   - Creates entry in tokens table with:
     - token_number = booking_id (PB001)
     - token_type = 'prebooked'
     - expected_time = appointment_time
   - Updates prebooked_appointments status: 'booked' → 'checked_in'
   - Patient sees their queue position

6. Patient can CANCEL their token anytime
   - API: api/cancel_token.php
   - Updates tokens.status = 'cancelled'
   - Updates prebooked_appointments.status = 'cancelled'
   - Queue automatically updates for other patients

QUEUE ORDERING:
===============
- Patients are ordered by appointment_time slot (not check-in time)
- PB001 (10:00 AM) always comes before PB003 (11:00 AM)
- Even if PB003 checks in first, they stay at position 3

REAL-TIME UPDATES:
==================
- Queue refreshes every 5 seconds
- When any patient cancels, they disappear from queue
- All other patients' positions update automatically

WALK-IN PATIENT FLOW:
=====================
1. Patient visits walkin.html
2. Selects department and doctor
3. Enters details and generates token (P-001, P-002, etc.)
4. Walk-ins are ordered by check-in time
5. Prebooked patients have priority over walk-ins

QUEUE API:
==========
- api/get_live_queue.php
- Returns combined queue of:
  - Checked-in prebooked patients (ordered by appointment_time)
  - Not-yet-checked-in prebooked patients (shown with "Not Arrived" status)
  - Walk-in patients (ordered by check-in time, after prebooked)
- Excludes all cancelled entries
*/
