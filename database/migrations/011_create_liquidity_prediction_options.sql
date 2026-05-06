CREATE TABLE IF NOT EXISTS liquidity_prediction_options (
  id INT AUTO_INCREMENT PRIMARY KEY,
  market_id INT NOT NULL,
  label VARCHAR(255) NOT NULL,
  weight_value DECIMAL(12,4) NOT NULL DEFAULT 1.0000,
  probability_value DECIMAL(12,6) NOT NULL DEFAULT 0.000000,
  sort_order INT NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL,
  CONSTRAINT fk_lpo_market FOREIGN KEY (market_id) REFERENCES liquidity_prediction_markets(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
