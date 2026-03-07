-- ============================================
-- ADD COUNTER INFO TO PHARMACY STAFF
-- Run this to add counter columns to pharmacy_staff table
-- ============================================

-- Add counter_number column (e.g., 1, 2, A, B)
ALTER TABLE pharmacy_staff 
ADD COLUMN counter_number VARCHAR(10) DEFAULT '1' AFTER pharmacy_id;

-- Add counter_name column (e.g., "Counter 1", "Cash Counter A")
ALTER TABLE pharmacy_staff 
ADD COLUMN counter_name VARCHAR(50) DEFAULT 'Counter 1' AFTER counter_number;

-- Add counter_type column (cash/online)
ALTER TABLE pharmacy_staff 
ADD COLUMN counter_type ENUM('cash', 'online', 'both') DEFAULT 'both' AFTER counter_name;

-- Update existing staff with counter assignments
UPDATE pharmacy_staff SET counter_number = '1', counter_name = 'Counter 1', counter_type = 'online' WHERE staff_id = 'PHARM001';
UPDATE pharmacy_staff SET counter_number = 'A', counter_name = 'Counter A', counter_type = 'cash' WHERE staff_id = 'PHARM002';
UPDATE pharmacy_staff SET counter_number = '2', counter_name = 'Counter 2', counter_type = 'online' WHERE staff_id = 'PHARM003';
UPDATE pharmacy_staff SET counter_number = 'B', counter_name = 'Counter B', counter_type = 'cash' WHERE staff_id = 'PHARM004';

-- Add index for counter lookups
ALTER TABLE pharmacy_staff ADD INDEX idx_counter (pharmacy_id, counter_number);
