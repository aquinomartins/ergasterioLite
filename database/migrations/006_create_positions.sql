CREATE TABLE IF NOT EXISTS user_balances (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL UNIQUE,
    balance DECIMAL(14,2) NOT NULL DEFAULT 1000.00,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_user_balances_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_balances_balance (balance)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS positions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    market_id BIGINT UNSIGNED NOT NULL,
    option_id BIGINT UNSIGNED NOT NULL,
    shares_amount DECIMAL(14,4) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_positions_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_positions_market FOREIGN KEY (market_id) REFERENCES markets(id) ON DELETE CASCADE,
    CONSTRAINT fk_positions_option FOREIGN KEY (option_id) REFERENCES market_options(id) ON DELETE CASCADE,
    INDEX idx_positions_user_market (user_id, market_id),
    INDEX idx_positions_market (market_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS trades (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    market_id BIGINT UNSIGNED NOT NULL,
    option_id BIGINT UNSIGNED NOT NULL,
    shares_amount DECIMAL(14,4) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_trades_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_trades_market FOREIGN KEY (market_id) REFERENCES markets(id) ON DELETE CASCADE,
    CONSTRAINT fk_trades_option FOREIGN KEY (option_id) REFERENCES market_options(id) ON DELETE CASCADE,
    INDEX idx_trades_market (market_id),
    INDEX idx_trades_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO user_balances (user_id, balance)
SELECT u.id, 1000.00
FROM users u
LEFT JOIN user_balances ub ON ub.user_id = u.id
WHERE ub.id IS NULL;
