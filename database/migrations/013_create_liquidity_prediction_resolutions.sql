CREATE TABLE IF NOT EXISTS liquidity_prediction_resolutions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  market_id INT NOT NULL UNIQUE,
  winning_option_id INT NOT NULL,
  resolved_by INT NULL,
  resolution_notes TEXT NULL,
  resolved_at DATETIME NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_lpr_market FOREIGN KEY (market_id) REFERENCES liquidity_prediction_markets(id) ON DELETE CASCADE,
  CONSTRAINT fk_lpr_option FOREIGN KEY (winning_option_id) REFERENCES liquidity_prediction_options(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
