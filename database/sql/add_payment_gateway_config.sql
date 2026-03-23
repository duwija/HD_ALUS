-- ================================================
-- Add Payment Gateway Configuration to Tenants
-- ================================================
-- This SQL adds payment gateway configuration columns
-- to the tenants table (if migration hasn't been run)

-- Add columns (skip if already exists)
ALTER TABLE `tenants` 
ADD COLUMN IF NOT EXISTS `payment_bumdes_enabled` TINYINT(1) DEFAULT 1 AFTER `features`,
ADD COLUMN IF NOT EXISTS `payment_winpay_enabled` TINYINT(1) DEFAULT 1 AFTER `payment_bumdes_enabled`,
ADD COLUMN IF NOT EXISTS `payment_tripay_enabled` TINYINT(1) DEFAULT 1 AFTER `payment_winpay_enabled`;

-- ================================================
-- Example Usage: Configure Payment Gateways
-- ================================================

-- Enable all gateways for a specific tenant
-- UPDATE tenants SET 
--     payment_bumdes_enabled = 1,
--     payment_winpay_enabled = 1,
--     payment_tripay_enabled = 1
-- WHERE domain = 'example.com';

-- Enable only Tripay for a tenant
-- UPDATE tenants SET 
--     payment_bumdes_enabled = 0,
--     payment_winpay_enabled = 0,
--     payment_tripay_enabled = 1
-- WHERE rescode = 'ABC123';

-- Disable all gateways for a tenant
-- UPDATE tenants SET 
--     payment_bumdes_enabled = 0,
--     payment_winpay_enabled = 0,
--     payment_tripay_enabled = 0
-- WHERE id = 1;

-- ================================================
-- Query to check current configuration
-- ================================================
-- SELECT 
--     domain,
--     rescode,
--     payment_bumdes_enabled,
--     payment_winpay_enabled,
--     payment_tripay_enabled
-- FROM tenants
-- WHERE is_active = 1;
