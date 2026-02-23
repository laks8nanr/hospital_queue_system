-- =====================================================
-- ADD LOCATION COLUMNS TO DOCTORS AND LAB_TESTS TABLES
-- Format: Floor/Block, Wing, Room Number
-- =====================================================

-- Add location columns to doctors table
ALTER TABLE doctors
ADD COLUMN IF NOT EXISTS floor_block VARCHAR(20) DEFAULT NULL COMMENT 'Floor/Block e.g., "Ground Floor", "Block A"',
ADD COLUMN IF NOT EXISTS wing VARCHAR(20) DEFAULT NULL COMMENT 'Wing e.g., "East Wing", "North Wing"',
ADD COLUMN IF NOT EXISTS room_number VARCHAR(20) DEFAULT NULL COMMENT 'Room number e.g., "101", "OPD-5"';

-- Add location columns to lab_tests table
ALTER TABLE lab_tests
ADD COLUMN IF NOT EXISTS floor_block VARCHAR(20) DEFAULT NULL COMMENT 'Floor/Block e.g., "2nd Floor", "Block B"',
ADD COLUMN IF NOT EXISTS wing VARCHAR(20) DEFAULT NULL COMMENT 'Wing e.g., "Diagnostic Wing"',
ADD COLUMN IF NOT EXISTS room_number VARCHAR(20) DEFAULT NULL COMMENT 'Room number e.g., "LAB-01", "MRI Room"';

-- =====================================================
-- UPDATE EXISTING DOCTORS WITH SAMPLE LOCATIONS
-- =====================================================
UPDATE doctors SET floor_block = 'Ground Floor', wing = 'East Wing', room_number = 'OPD-101' WHERE id = 1;
UPDATE doctors SET floor_block = 'Ground Floor', wing = 'East Wing', room_number = 'OPD-102' WHERE id = 2;
UPDATE doctors SET floor_block = '1st Floor', wing = 'West Wing', room_number = 'OPD-201' WHERE id = 3;
UPDATE doctors SET floor_block = '1st Floor', wing = 'West Wing', room_number = 'OPD-202' WHERE id = 4;
UPDATE doctors SET floor_block = '2nd Floor', wing = 'North Wing', room_number = 'OPD-301' WHERE id = 5;

-- =====================================================
-- UPDATE LAB TESTS WITH SAMPLE LOCATIONS
-- =====================================================
UPDATE lab_tests SET floor_block = 'Basement', wing = 'Diagnostic Wing', room_number = 'MRI-01' WHERE test_code = 'MRI';
UPDATE lab_tests SET floor_block = 'Basement', wing = 'Diagnostic Wing', room_number = 'CT-01' WHERE test_code = 'CT';
UPDATE lab_tests SET floor_block = 'Ground Floor', wing = 'Lab Wing', room_number = 'LAB-01' WHERE test_code = 'BT';
UPDATE lab_tests SET floor_block = 'Ground Floor', wing = 'Diagnostic Wing', room_number = 'XRAY-01' WHERE test_code = 'XR';
