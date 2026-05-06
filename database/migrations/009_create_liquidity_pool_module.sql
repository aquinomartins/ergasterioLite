CREATE TABLE IF NOT EXISTS liquidity_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    access_code VARCHAR(50) NOT NULL UNIQUE,
    current_round INT NOT NULL DEFAULT 1,
    total_rounds INT NOT NULL DEFAULT 6,
    initial_cash DECIMAL(12,2) NOT NULL DEFAULT 1600.00,
    initial_nfts INT NOT NULL DEFAULT 1,
    round_fee DECIMAL(12,2) NOT NULL DEFAULT 100.00,
    nft_pool_value DECIMAL(12,2) NOT NULL DEFAULT 2000.00,
    pool_yield_rate DECIMAL(8,4) NOT NULL DEFAULT 0.1000,
    btc_deposit_reward DECIMAL(12,2) NOT NULL DEFAULT 10.00,
    btc_withdraw_cost DECIMAL(12,2) NOT NULL DEFAULT 11.00,
    cash_withdraw_cost DECIMAL(12,2) NOT NULL DEFAULT 2000.00,
    btc_buy_price DECIMAL(12,2) NOT NULL DEFAULT 120.00,
    btc_sell_price DECIMAL(12,2) NOT NULL DEFAULT 100.00,
    nft_sell_price DECIMAL(12,2) NOT NULL DEFAULT 1800.00,
    share_sell_price DECIMAL(12,2) NOT NULL DEFAULT 500.00,
    status VARCHAR(30) NOT NULL DEFAULT 'draft',
    created_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS liquidity_teams (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    login_code VARCHAR(50) NOT NULL,
    cash_balance DECIMAL(12,2) NOT NULL DEFAULT 1600.00,
    btc_balance DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    nft_balance INT NOT NULL DEFAULT 1,
    pool_shares INT NOT NULL DEFAULT 0,
    final_score DECIMAL(12,2) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL,
    CONSTRAINT fk_liquidity_teams_session FOREIGN KEY (session_id) REFERENCES liquidity_sessions(id) ON DELETE CASCADE,
    UNIQUE KEY uq_liquidity_teams_session_login (session_id, login_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS liquidity_pool_state (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id INT NOT NULL UNIQUE,
    pool_nfts INT NOT NULL DEFAULT 0,
    total_shares INT NOT NULL DEFAULT 0,
    total_value DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    yield_per_share DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    status VARCHAR(30) NOT NULL DEFAULT 'empty',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_liquidity_pool_state_session FOREIGN KEY (session_id) REFERENCES liquidity_sessions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS liquidity_rounds (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id INT NOT NULL,
    round_number INT NOT NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'open',
    started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ended_at TIMESTAMP NULL,
    CONSTRAINT fk_liquidity_rounds_session FOREIGN KEY (session_id) REFERENCES liquidity_sessions(id) ON DELETE CASCADE,
    UNIQUE KEY uq_liquidity_rounds_session_round (session_id, round_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS liquidity_team_actions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id INT NOT NULL,
    round_number INT NOT NULL,
    team_id INT NOT NULL,
    action_type VARCHAR(50) NOT NULL,
    quantity DECIMAL(12,2) NOT NULL DEFAULT 1.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_liquidity_actions_session FOREIGN KEY (session_id) REFERENCES liquidity_sessions(id) ON DELETE CASCADE,
    CONSTRAINT fk_liquidity_actions_team FOREIGN KEY (team_id) REFERENCES liquidity_teams(id) ON DELETE CASCADE,
    UNIQUE KEY uq_liquidity_actions_round_team (session_id, round_number, team_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS liquidity_events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id INT NOT NULL,
    round_number INT NOT NULL,
    team_id INT NULL,
    event_type VARCHAR(50) NOT NULL,
    cash_delta DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    btc_delta DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    nft_delta INT NOT NULL DEFAULT 0,
    share_delta INT NOT NULL DEFAULT 0,
    description TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_liquidity_events_session FOREIGN KEY (session_id) REFERENCES liquidity_sessions(id) ON DELETE CASCADE,
    CONSTRAINT fk_liquidity_events_team FOREIGN KEY (team_id) REFERENCES liquidity_teams(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
