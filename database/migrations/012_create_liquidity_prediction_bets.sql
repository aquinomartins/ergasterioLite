CREATE TABLE IF NOT EXISTS liquidity_prediction_bets (
  id INT AUTO_INCREMENT PRIMARY KEY,
  market_id INT NOT NULL,
  option_id INT NOT NULL,
  session_id INT NOT NULL,
  team_id INT NOT NULL,
  amount DECIMAL(12,2) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_lpb_market FOREIGN KEY (market_id) REFERENCES liquidity_prediction_markets(id) ON DELETE CASCADE,
  CONSTRAINT fk_lpb_option FOREIGN KEY (option_id) REFERENCES liquidity_prediction_options(id) ON DELETE CASCADE,
  CONSTRAINT fk_lpb_session FOREIGN KEY (session_id) REFERENCES liquidity_sessions(id) ON DELETE CASCADE,
  CONSTRAINT fk_lpb_team FOREIGN KEY (team_id) REFERENCES liquidity_teams(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
