CREATE TABLE IF NOT EXISTS liquidity_prediction_markets (
  id INT AUTO_INCREMENT PRIMARY KEY,
  session_id INT NOT NULL,
  question VARCHAR(255) NOT NULL,
  description TEXT NULL,
  market_type VARCHAR(50) NOT NULL DEFAULT 'binary',
  status VARCHAR(30) NOT NULL DEFAULT 'open',
  closes_round INT NULL,
  resolved_option_id INT NULL,
  created_by INT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL,
  CONSTRAINT fk_lpm_session FOREIGN KEY (session_id) REFERENCES liquidity_sessions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
