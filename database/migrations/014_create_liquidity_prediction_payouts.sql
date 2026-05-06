CREATE TABLE IF NOT EXISTS liquidity_prediction_payouts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  market_id INT NOT NULL,
  bet_id INT NOT NULL,
  session_id INT NOT NULL,
  team_id INT NOT NULL,
  option_id INT NOT NULL,
  gross_amount DECIMAL(12,2) NOT NULL,
  fee_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  net_amount DECIMAL(12,2) NOT NULL,
  status VARCHAR(30) NOT NULL DEFAULT 'executed',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_market_bet (market_id, bet_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
