-- Migration for ITMS v1.5.1 (2025-08-07)
-- Adds model and notes columns to asset list display
-- No schema changes required if previous migrations are applied
-- If you are upgrading from a version before v1.5.0, ensure all previous migrations are applied

-- Example: If you need to ensure model_id and notes columns exist (for safety)
ALTER TABLE assets 
  ADD COLUMN IF NOT EXISTS model_id INT NULL,
  ADD COLUMN IF NOT EXISTS notes TEXT NULL;

-- Add index for model_id if not exists
CREATE INDEX IF NOT EXISTS idx_assets_model_id ON assets(model_id);

-- No destructive changes in this migration
-- You may safely re-run this script
