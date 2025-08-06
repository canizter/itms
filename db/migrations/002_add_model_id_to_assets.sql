-- Migration: Add model_id column to assets table
-- Run this script once per environment after updating code to v1.4+

ALTER TABLE assets ADD COLUMN model_id INT NULL AFTER vendor_id;

-- (Optional) Add foreign key constraint if models table exists:
-- ALTER TABLE assets ADD CONSTRAINT fk_assets_model_id FOREIGN KEY (model_id) REFERENCES models(id) ON DELETE SET NULL;
