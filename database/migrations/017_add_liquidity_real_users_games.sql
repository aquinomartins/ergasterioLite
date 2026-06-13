CREATE TABLE IF NOT EXISTS liquidity_games (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    invite_code VARCHAR(50) NOT NULL UNIQUE,
    owner_user_id BIGINT UNSIGNED NOT NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'waiting',
    mode VARCHAR(30) NOT NULL DEFAULT 'individual',
    max_participants INT NULL,
    max_rounds INT NOT NULL DEFAULT 6,
    current_round INT NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL,
    CONSTRAINT fk_liquidity_games_owner FOREIGN KEY (owner_user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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

CREATE PROCEDURE add_liquidity_fk_if_missing(
    IN p_constraint_name VARCHAR(64),
    IN p_ddl TEXT
)
BEGIN
    IF NOT EXISTS (
        SELECT 1
        FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
        WHERE TABLE_SCHEMA = DATABASE()
          AND CONSTRAINT_NAME = p_constraint_name
    ) THEN
        SET @ddl = p_ddl;
        PREPARE stmt FROM @ddl;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END$$

DELIMITER ;

CALL add_liquidity_column_if_missing('liquidity_teams', 'game_id', 'INT NULL AFTER session_id');
CALL add_liquidity_column_if_missing('liquidity_teams', 'status', "VARCHAR(30) NOT NULL DEFAULT 'active' AFTER pool_shares");
CALL add_liquidity_fk_if_missing('fk_liquidity_teams_game', 'ALTER TABLE liquidity_teams ADD CONSTRAINT fk_liquidity_teams_game FOREIGN KEY (game_id) REFERENCES liquidity_games(id) ON DELETE CASCADE');

DROP PROCEDURE add_liquidity_fk_if_missing;
DROP PROCEDURE add_liquidity_column_if_missing;

CREATE TABLE IF NOT EXISTS liquidity_participants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    game_id INT NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    team_id INT NULL,
    role VARCHAR(30) NOT NULL DEFAULT 'player',
    status VARCHAR(30) NOT NULL DEFAULT 'pending',
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    approved_at TIMESTAMP NULL,
    CONSTRAINT fk_liquidity_participants_game FOREIGN KEY (game_id) REFERENCES liquidity_games(id) ON DELETE CASCADE,
    CONSTRAINT fk_liquidity_participants_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_liquidity_participants_team FOREIGN KEY (team_id) REFERENCES liquidity_teams(id) ON DELETE SET NULL,
    UNIQUE KEY uq_liquidity_participants_game_user (game_id, user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
