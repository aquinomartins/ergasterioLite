CREATE TABLE IF NOT EXISTS liquidity_games (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    invite_code VARCHAR(50) NOT NULL UNIQUE,
    owner_user_id BIGINT UNSIGNED NOT NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'waiting',
    mode VARCHAR(30) NOT NULL DEFAULT 'individual',
    max_participants INT NULL,
    max_rounds INT NOT NULL DEFAULT 6,
    current_round INT NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL,
    CONSTRAINT fk_liquidity_games_owner FOREIGN KEY (owner_user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE liquidity_teams
    ADD COLUMN IF NOT EXISTS game_id INT NULL AFTER session_id,
    ADD COLUMN IF NOT EXISTS status VARCHAR(30) NOT NULL DEFAULT 'active' AFTER pool_shares;

ALTER TABLE liquidity_teams
    ADD CONSTRAINT fk_liquidity_teams_game FOREIGN KEY (game_id) REFERENCES liquidity_games(id) ON DELETE CASCADE;

CREATE TABLE IF NOT EXISTS liquidity_participants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    game_id INT NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    team_id INT NULL,
    role VARCHAR(30) NOT NULL DEFAULT 'player',
    status VARCHAR(30) NOT NULL DEFAULT 'pending',
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    approved_at TIMESTAMP NULL,
    CONSTRAINT fk_liquidity_participants_game FOREIGN KEY (game_id) REFERENCES liquidity_games(id) ON DELETE CASCADE,
    CONSTRAINT fk_liquidity_participants_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_liquidity_participants_team FOREIGN KEY (team_id) REFERENCES liquidity_teams(id) ON DELETE SET NULL,
    UNIQUE KEY uq_liquidity_participants_game_user (game_id, user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
