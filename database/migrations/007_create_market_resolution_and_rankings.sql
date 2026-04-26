CREATE TABLE IF NOT EXISTS market_resolutions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    market_id BIGINT UNSIGNED NOT NULL,
    winning_option_id BIGINT UNSIGNED NOT NULL,
    resolved_by BIGINT UNSIGNED NOT NULL,
    resolution_notes TEXT NULL,
    resolved_at DATETIME NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_market_resolutions_market FOREIGN KEY (market_id) REFERENCES markets(id) ON DELETE CASCADE,
    CONSTRAINT fk_market_resolutions_option FOREIGN KEY (winning_option_id) REFERENCES market_options(id) ON DELETE RESTRICT,
    CONSTRAINT fk_market_resolutions_user FOREIGN KEY (resolved_by) REFERENCES users(id) ON DELETE RESTRICT,
    UNIQUE KEY uq_market_resolutions_market (market_id),
    INDEX idx_market_resolutions_resolved_by (resolved_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS payouts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    market_id BIGINT UNSIGNED NOT NULL,
    position_id BIGINT UNSIGNED NULL,
    option_id BIGINT UNSIGNED NOT NULL,
    shares_amount DECIMAL(14,4) NOT NULL,
    gross_amount DECIMAL(14,2) NOT NULL,
    fee_amount DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    net_amount DECIMAL(14,2) NOT NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_payouts_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_payouts_market FOREIGN KEY (market_id) REFERENCES markets(id) ON DELETE CASCADE,
    CONSTRAINT fk_payouts_position FOREIGN KEY (position_id) REFERENCES positions(id) ON DELETE SET NULL,
    CONSTRAINT fk_payouts_option FOREIGN KEY (option_id) REFERENCES market_options(id) ON DELETE CASCADE,
    UNIQUE KEY uq_payouts_market_position (market_id, position_id),
    INDEX idx_payouts_user (user_id),
    INDEX idx_payouts_market (market_id),
    INDEX idx_payouts_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS rankings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL UNIQUE,
    total_payoff DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    total_markets_participated INT NOT NULL DEFAULT 0,
    total_markets_won INT NOT NULL DEFAULT 0,
    reputation_score DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    updated_at DATETIME NOT NULL,
    CONSTRAINT fk_rankings_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_rankings_score (reputation_score),
    INDEX idx_rankings_payoff (total_payoff)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS reputation_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    market_id BIGINT UNSIGNED NULL,
    reason VARCHAR(100) NOT NULL,
    points_delta DECIMAL(14,2) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_reputation_logs_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_reputation_logs_market FOREIGN KEY (market_id) REFERENCES markets(id) ON DELETE SET NULL,
    INDEX idx_reputation_logs_user (user_id),
    INDEX idx_reputation_logs_market (market_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
