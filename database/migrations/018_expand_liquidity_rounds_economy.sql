DELIMITER $$

CREATE PROCEDURE add_liquidity_column_if_missing(
    IN p_table_name VARCHAR(64),
    IN p_column_name VARCHAR(64),
    IN p_column_definition TEXT
)
BEGIN
    IF NOT EXISTS (
        SELECT 1
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = p_table_name
          AND COLUMN_NAME = p_column_name
    ) THEN
        SET @ddl = CONCAT('ALTER TABLE ', p_table_name, ' ADD COLUMN ', p_column_name, ' ', p_column_definition);
        PREPARE stmt FROM @ddl;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END$$

DELIMITER ;

CALL add_liquidity_column_if_missing('liquidity_rounds', 'closed_at', 'TIMESTAMP NULL AFTER ended_at');
CALL add_liquidity_column_if_missing('liquidity_rounds', 'maintenance_fee_applied', 'TINYINT(1) NOT NULL DEFAULT 0 AFTER closed_at');
CALL add_liquidity_column_if_missing('liquidity_rounds', 'dividends_applied', 'TINYINT(1) NOT NULL DEFAULT 0 AFTER maintenance_fee_applied');
CALL add_liquidity_column_if_missing('liquidity_rounds', 'updated_at', 'TIMESTAMP NULL AFTER dividends_applied');

DROP PROCEDURE add_liquidity_column_if_missing;

UPDATE liquidity_rounds
SET closed_at = ended_at,
    maintenance_fee_applied = IF(status = 'closed', 1, maintenance_fee_applied),
    dividends_applied = IF(status = 'closed', 1, dividends_applied)
WHERE status = 'closed' AND closed_at IS NULL;
