ALTER TABLE liquidity_rounds
    ADD COLUMN IF NOT EXISTS closed_at TIMESTAMP NULL AFTER ended_at,
    ADD COLUMN IF NOT EXISTS maintenance_fee_applied TINYINT(1) NOT NULL DEFAULT 0 AFTER closed_at,
    ADD COLUMN IF NOT EXISTS dividends_applied TINYINT(1) NOT NULL DEFAULT 0 AFTER maintenance_fee_applied,
    ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP NULL AFTER dividends_applied;

UPDATE liquidity_rounds
SET closed_at = ended_at,
    maintenance_fee_applied = IF(status = 'closed', 1, maintenance_fee_applied),
    dividends_applied = IF(status = 'closed', 1, dividends_applied)
WHERE status = 'closed' AND closed_at IS NULL;
