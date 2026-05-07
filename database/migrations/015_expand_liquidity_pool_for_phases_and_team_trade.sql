ALTER TABLE liquidity_sessions
    ADD COLUMN IF NOT EXISTS session_phase VARCHAR(30) NOT NULL DEFAULT 'regular' AFTER total_rounds;

ALTER TABLE liquidity_teams
    ADD COLUMN IF NOT EXISTS is_eliminated TINYINT(1) NOT NULL DEFAULT 0 AFTER final_score,
    ADD COLUMN IF NOT EXISTS qualified_for_final TINYINT(1) NOT NULL DEFAULT 0 AFTER is_eliminated;

ALTER TABLE liquidity_team_actions
    ADD COLUMN IF NOT EXISTS target_team_id INT NULL AFTER quantity,
    ADD COLUMN IF NOT EXISTS price DECIMAL(12,2) NULL AFTER target_team_id;
