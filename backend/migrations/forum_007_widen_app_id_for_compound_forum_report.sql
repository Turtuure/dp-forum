-- Widen admin_application_dismissals.app_id to fit compound forum_report keys
-- like 'post:<uuid-36>' (41 chars) or 'topic:<uuid-36>' (42 chars).
-- Member / supporter / project_proposal dismissals still fit (36-char UUID).
--
-- Conditional ALTER (target table is core, see forum_006 for rationale).
SET @t := (SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'admin_application_dismissals');
SET @sql := IF(@t > 0,
    "ALTER TABLE admin_application_dismissals MODIFY COLUMN app_id VARCHAR(64) NOT NULL",
    "DO 0");
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
