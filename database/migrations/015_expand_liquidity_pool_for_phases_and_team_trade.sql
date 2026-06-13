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

CALL add_liquidity_column_if_missing('liquidity_sessions', 'session_phase', "VARCHAR(30) NOT NULL DEFAULT 'regular' AFTER total_rounds");
CALL add_liquidity_column_if_missing('liquidity_teams', 'is_eliminated', 'TINYINT(1) NOT NULL DEFAULT 0 AFTER final_score');
CALL add_liquidity_column_if_missing('liquidity_teams', 'qualified_for_final', 'TINYINT(1) NOT NULL DEFAULT 0 AFTER is_eliminated');
CALL add_liquidity_column_if_missing('liquidity_team_actions', 'target_team_id', 'INT NULL AFTER quantity');
CALL add_liquidity_column_if_missing('liquidity_team_actions', 'price', 'DECIMAL(12,2) NULL AFTER target_team_id');

DROP PROCEDURE add_liquidity_column_if_missing;
