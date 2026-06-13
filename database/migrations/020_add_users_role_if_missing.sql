DELIMITER $$

CREATE PROCEDURE add_users_role_if_missing()
BEGIN
    IF NOT EXISTS (
        SELECT 1
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'users'
          AND COLUMN_NAME = 'role'
    ) THEN
        ALTER TABLE users ADD COLUMN role VARCHAR(50) NOT NULL DEFAULT 'user' AFTER status;
    END IF;
END$$

DELIMITER ;

CALL add_users_role_if_missing();

DROP PROCEDURE add_users_role_if_missing;
