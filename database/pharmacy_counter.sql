-- =====================================================
-- ADD CASH COUNTER TO PHARMACIES TABLE
-- For cash payment patients to know which counter to go
-- =====================================================

-- Add cash_counter column to pharmacies table
ALTER TABLE pharmacies 
ADD COLUMN IF NOT EXISTS cash_counter VARCHAR(20) DEFAULT 'Counter A' 
COMMENT 'Cash payment counter number for this pharmacy';

-- Update existing pharmacies with counter numbers (A, B, C for cash payments)
UPDATE pharmacies SET cash_counter = 'Counter A' WHERE id = 1;
UPDATE pharmacies SET cash_counter = 'Counter B' WHERE id = 2;
