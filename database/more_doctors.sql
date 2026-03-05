-- Add more departments and doctors for MediNova Hospital
-- Run this script after the initial database setup

-- First, ensure all departments exist
INSERT IGNORE INTO departments (id, name) VALUES
(1, 'General Medicine'),
(2, 'Pediatrics'),
(3, 'Orthopedics'),
(4, 'Cardiology'),
(5, 'Dermatology'),
(6, 'ENT'),
(7, 'Neurology'),
(8, 'Ophthalmology'),
(9, 'Psychiatry'),
(10, 'Urology');

-- Add doctors for each department (multiple doctors per department)

-- General Medicine (dept_id = 1)
INSERT INTO doctors (name, department_id, time_slot, qualification, fees, floor_block, wing, room_number) VALUES
('Dr. Rajesh Kumar', 1, '9:00 AM - 1:00 PM', 'MBBS, MD (Medicine)', 300, 'Block A', 'Ground Floor', 'Room 101'),
('Dr. Priya Sharma', 1, '2:00 PM - 6:00 PM', 'MBBS, MD, FICP', 350, 'Block A', 'Ground Floor', 'Room 102'),
('Dr. Anil Verma', 1, '10:00 AM - 2:00 PM', 'MBBS, DNB (Medicine)', 300, 'Block A', 'Ground Floor', 'Room 103')
ON DUPLICATE KEY UPDATE name=VALUES(name);

-- Pediatrics (dept_id = 2)
INSERT INTO doctors (name, department_id, time_slot, qualification, fees, floor_block, wing, room_number) VALUES
('Dr. Meena Patel', 2, '9:00 AM - 1:00 PM', 'MBBS, MD (Pediatrics)', 350, 'Block B', '1st Floor', 'Room 201'),
('Dr. Suresh Nair', 2, '2:00 PM - 6:00 PM', 'MBBS, DCH, DNB', 400, 'Block B', '1st Floor', 'Room 202'),
('Dr. Kavita Rao', 2, '10:00 AM - 4:00 PM', 'MBBS, MD (Peds), Fellowship', 450, 'Block B', '1st Floor', 'Room 203')
ON DUPLICATE KEY UPDATE name=VALUES(name);

-- Orthopedics (dept_id = 3)
INSERT INTO doctors (name, department_id, time_slot, qualification, fees, floor_block, wing, room_number) VALUES
('Dr. Vikram Singh', 3, '9:00 AM - 2:00 PM', 'MBBS, MS (Ortho), MCh', 500, 'Block C', '2nd Floor', 'Room 301'),
('Dr. Sunita Reddy', 3, '3:00 PM - 7:00 PM', 'MBBS, DNB (Ortho)', 450, 'Block C', '2nd Floor', 'Room 302'),
('Dr. Mahesh Gupta', 3, '11:00 AM - 5:00 PM', 'MBBS, MS, FICS', 500, 'Block C', '2nd Floor', 'Room 303')
ON DUPLICATE KEY UPDATE name=VALUES(name);

-- Cardiology (dept_id = 4)
INSERT INTO doctors (name, department_id, time_slot, qualification, fees, floor_block, wing, room_number) VALUES
('Dr. Ramesh Iyer', 4, '9:00 AM - 1:00 PM', 'MBBS, MD (Cardio), DM', 700, 'Block D', '3rd Floor', 'Room 401'),
('Dr. Anjali Mehta', 4, '2:00 PM - 6:00 PM', 'MBBS, DNB (Cardio)', 650, 'Block D', '3rd Floor', 'Room 402'),
('Dr. Karthik Menon', 4, '10:00 AM - 4:00 PM', 'MBBS, DM, FACC', 800, 'Block D', '3rd Floor', 'Room 403')
ON DUPLICATE KEY UPDATE name=VALUES(name);

-- Dermatology (dept_id = 5)
INSERT INTO doctors (name, department_id, time_slot, qualification, fees, floor_block, wing, room_number) VALUES
('Dr. Neha Kapoor', 5, '9:00 AM - 1:00 PM', 'MBBS, MD (Dermato)', 400, 'Block A', '1st Floor', 'Room 111'),
('Dr. Sanjay Mishra', 5, '2:00 PM - 6:00 PM', 'MBBS, DVD, DNB', 350, 'Block A', '1st Floor', 'Room 112'),
('Dr. Ritu Agarwal', 5, '11:00 AM - 5:00 PM', 'MBBS, MD, Fellowship', 450, 'Block A', '1st Floor', 'Room 113')
ON DUPLICATE KEY UPDATE name=VALUES(name);

-- ENT (dept_id = 6)
INSERT INTO doctors (name, department_id, time_slot, qualification, fees, floor_block, wing, room_number) VALUES
('Dr. Prakash Joshi', 6, '9:00 AM - 2:00 PM', 'MBBS, MS (ENT)', 400, 'Block B', '2nd Floor', 'Room 211'),
('Dr. Geeta Saxena', 6, '3:00 PM - 7:00 PM', 'MBBS, DNB (ENT)', 350, 'Block B', '2nd Floor', 'Room 212'),
('Dr. Arun Bhat', 6, '10:00 AM - 4:00 PM', 'MBBS, MS, Fellowship', 450, 'Block B', '2nd Floor', 'Room 213')
ON DUPLICATE KEY UPDATE name=VALUES(name);

-- Neurology (dept_id = 7)
INSERT INTO doctors (name, department_id, time_slot, qualification, fees, floor_block, wing, room_number) VALUES
('Dr. Vivek Desai', 7, '9:00 AM - 1:00 PM', 'MBBS, MD, DM (Neuro)', 800, 'Block D', '4th Floor', 'Room 501'),
('Dr. Lakshmi Nair', 7, '2:00 PM - 6:00 PM', 'MBBS, DNB (Neuro)', 750, 'Block D', '4th Floor', 'Room 502'),
('Dr. Siddharth Roy', 7, '10:00 AM - 4:00 PM', 'MBBS, DM, FRCP', 900, 'Block D', '4th Floor', 'Room 503')
ON DUPLICATE KEY UPDATE name=VALUES(name);

-- Ophthalmology (dept_id = 8)
INSERT INTO doctors (name, department_id, time_slot, qualification, fees, floor_block, wing, room_number) VALUES
('Dr. Amit Khanna', 8, '9:00 AM - 1:00 PM', 'MBBS, MS (Ophth)', 400, 'Block C', '1st Floor', 'Room 121'),
('Dr. Pooja Bhatt', 8, '2:00 PM - 6:00 PM', 'MBBS, DNB (Eye)', 350, 'Block C', '1st Floor', 'Room 122'),
('Dr. Raghav Sinha', 8, '10:00 AM - 4:00 PM', 'MBBS, MS, Fellowship', 500, 'Block C', '1st Floor', 'Room 123')
ON DUPLICATE KEY UPDATE name=VALUES(name);

-- Psychiatry (dept_id = 9)
INSERT INTO doctors (name, department_id, time_slot, qualification, fees, floor_block, wing, room_number) VALUES
('Dr. Manish Tiwari', 9, '9:00 AM - 1:00 PM', 'MBBS, MD (Psych)', 600, 'Block E', 'Ground Floor', 'Room 601'),
('Dr. Smita Kulkarni', 9, '2:00 PM - 6:00 PM', 'MBBS, DPM, DNB', 550, 'Block E', 'Ground Floor', 'Room 602'),
('Dr. Nikhil Sen', 9, '11:00 AM - 5:00 PM', 'MBBS, MD, MRCPsych', 700, 'Block E', 'Ground Floor', 'Room 603')
ON DUPLICATE KEY UPDATE name=VALUES(name);

-- Urology (dept_id = 10)
INSERT INTO doctors (name, department_id, time_slot, qualification, fees, floor_block, wing, room_number) VALUES
('Dr. Harish Shah', 10, '9:00 AM - 2:00 PM', 'MBBS, MS, MCh (Uro)', 600, 'Block D', '2nd Floor', 'Room 321'),
('Dr. Tanuja Das', 10, '3:00 PM - 7:00 PM', 'MBBS, DNB (Uro)', 550, 'Block D', '2nd Floor', 'Room 322'),
('Dr. Rohit Malhotra', 10, '10:00 AM - 4:00 PM', 'MBBS, MCh, FACS', 700, 'Block D', '2nd Floor', 'Room 323')
ON DUPLICATE KEY UPDATE name=VALUES(name);

-- Show what was added
SELECT d.name as doctor, dep.name as department, d.qualification, d.fees, d.time_slot 
FROM doctors d 
JOIN departments dep ON d.department_id = dep.id 
ORDER BY dep.name, d.name;
