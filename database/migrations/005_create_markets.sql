CREATE TABLE IF NOT EXISTS markets (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(180) NOT NULL,
    slug VARCHAR(200) NOT NULL UNIQUE,
    description TEXT NOT NULL,
    market_type VARCHAR(40) NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'draft',
    resolution_mode VARCHAR(20) NOT NULL DEFAULT 'manual',
    opens_at DATETIME NULL,
    closes_at DATETIME NOT NULL,
    resolved_option_id BIGINT UNSIGNED NULL,
    created_by BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_markets_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_markets_status (status),
    INDEX idx_markets_type (market_type),
    INDEX idx_markets_closes_at (closes_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS market_options (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    market_id BIGINT UNSIGNED NOT NULL,
    option_type VARCHAR(40) NOT NULL,
    artwork_id INT UNSIGNED NULL,
    artist_id INT UNSIGNED NULL,
    label VARCHAR(180) NOT NULL,
    weight_value DECIMAL(12,4) NOT NULL DEFAULT 1.0000,
    probability_value DECIMAL(12,6) NOT NULL DEFAULT 0.000000,
    sort_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_market_options_market FOREIGN KEY (market_id) REFERENCES markets(id) ON DELETE CASCADE,
    CONSTRAINT fk_market_options_artwork FOREIGN KEY (artwork_id) REFERENCES artworks(id) ON DELETE SET NULL,
    CONSTRAINT fk_market_options_artist FOREIGN KEY (artist_id) REFERENCES artists(id) ON DELETE SET NULL,
    INDEX idx_market_options_market (market_id),
    INDEX idx_market_options_type (option_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE markets
    ADD CONSTRAINT fk_markets_resolved_option FOREIGN KEY (resolved_option_id) REFERENCES market_options(id) ON DELETE SET NULL;

CREATE TABLE IF NOT EXISTS market_snapshots (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    market_id BIGINT UNSIGNED NOT NULL,
    snapshot_json LONGTEXT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_market_snapshots_market FOREIGN KEY (market_id) REFERENCES markets(id) ON DELETE CASCADE,
    INDEX idx_market_snapshots_market (market_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
