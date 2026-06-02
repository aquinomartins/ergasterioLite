ALTER TABLE liquidity_teams
    ADD COLUMN IF NOT EXISTS final_status VARCHAR(40) NULL AFTER qualified_for_final;
