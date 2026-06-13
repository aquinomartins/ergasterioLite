DELIMITER $$
CREATE PROCEDURE add_column_if_missing(IN p_table VARCHAR(64), IN p_col VARCHAR(64), IN p_def TEXT)
BEGIN
  IF NOT EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = p_table AND COLUMN_NAME = p_col) THEN
    SET @ddl = CONCAT('ALTER TABLE ', p_table, ' ADD COLUMN ', p_col, ' ', p_def);
    PREPARE stmt FROM @ddl; EXECUTE stmt; DEALLOCATE PREPARE stmt;
  END IF;
END$$
DELIMITER ;
CALL add_column_if_missing('users', 'role', "VARCHAR(50) NOT NULL DEFAULT 'user' AFTER status");
DROP PROCEDURE add_column_if_missing;

CREATE TABLE IF NOT EXISTS liquidity_trade_proposals (
  id INT AUTO_INCREMENT PRIMARY KEY,
  game_id INT NOT NULL,
  round_number INT NOT NULL,
  proposer_team_id INT NOT NULL,
  counterparty_team_id INT NOT NULL,
  proposer_user_id BIGINT UNSIGNED NULL,
  action_type VARCHAR(10) NOT NULL,
  asset_type VARCHAR(20) NOT NULL,
  quantity DECIMAL(12,4) NOT NULL,
  unit_price DECIMAL(12,2) NOT NULL,
  total_price DECIMAL(12,2) NOT NULL,
  status VARCHAR(40) NOT NULL DEFAULT 'pending_counterparty',
  proposer_approved_at TIMESTAMP NULL,
  counterparty_approved_at TIMESTAMP NULL,
  master_approved_at TIMESTAMP NULL,
  rejected_at TIMESTAMP NULL,
  executed_at TIMESTAMP NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_ltp_game_round (game_id, round_number),
  INDEX idx_ltp_proposer (proposer_team_id),
  INDEX idx_ltp_counterparty (counterparty_team_id),
  CONSTRAINT fk_ltp_game FOREIGN KEY (game_id) REFERENCES liquidity_games(id) ON DELETE CASCADE,
  CONSTRAINT fk_ltp_proposer_team FOREIGN KEY (proposer_team_id) REFERENCES liquidity_teams(id) ON DELETE CASCADE,
  CONSTRAINT fk_ltp_counterparty_team FOREIGN KEY (counterparty_team_id) REFERENCES liquidity_teams(id) ON DELETE CASCADE,
  CONSTRAINT fk_ltp_user FOREIGN KEY (proposer_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
