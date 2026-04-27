-- Conditional ALTER: extends admin_application_dismissals.app_type enum to include
-- 'forum_report'. The target table lives in core (created by core migration 040).
-- Module migrations always load regardless of test runMigrationsUpTo() cap, so guard
-- on table existence — if the test runs migrations only up to e.g. 22, this becomes
-- a no-op rather than an error.
SET @t := (SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'admin_application_dismissals');
SET @sql := IF(@t > 0,
    "ALTER TABLE admin_application_dismissals MODIFY COLUMN app_type ENUM('member','supporter','project_proposal','forum_report') NOT NULL",
    "DO 0");
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
