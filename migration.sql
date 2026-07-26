-- =============================================================
-- CRM Lead Tracker — Database Migration v2
-- Run this in your phpMyAdmin SQL tab on InfinityFree
-- SAFE: Only ADDs new columns and tables. No existing data lost.
-- =============================================================

-- 1. Add Follow-up columns to existing leads table
ALTER TABLE `leads`
    ADD COLUMN IF NOT EXISTS `followup_date`  DATE         NULL DEFAULT NULL COMMENT 'Scheduled follow-up date'        AFTER `notes`,
    ADD COLUMN IF NOT EXISTS `followup_notes` TEXT         NULL DEFAULT NULL COMMENT 'Follow-up reminder notes'         AFTER `followup_date`;

-- 2. Create Lead Activity Timeline table
CREATE TABLE IF NOT EXISTS `lead_activities` (
    `id`          INT(11)      NOT NULL AUTO_INCREMENT,
    `lead_id`     INT(11)      NOT NULL,
    `user_id`     INT(11)      NOT NULL,
    `action`      VARCHAR(50)  NOT NULL COMMENT 'e.g. created, edited, status_changed, note_added',
    `description` TEXT         NULL     COMMENT 'Human-readable description of the change',
    `created_at`  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_lead_id` (`lead_id`),
    KEY `idx_user_id` (`user_id`),
    CONSTRAINT `fk_activity_lead` FOREIGN KEY (`lead_id`) REFERENCES `leads`  (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_activity_user` FOREIGN KEY (`user_id`) REFERENCES `users`  (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Stores all activity history for leads (timeline)';

-- 3. Create Lead Notes table (multiple notes per lead)
CREATE TABLE IF NOT EXISTS `lead_notes` (
    `id`         INT(11)   NOT NULL AUTO_INCREMENT,
    `lead_id`    INT(11)   NOT NULL,
    `user_id`    INT(11)   NOT NULL,
    `note`       TEXT      NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_note_lead` (`lead_id`),
    CONSTRAINT `fk_note_lead` FOREIGN KEY (`lead_id`) REFERENCES `leads` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_note_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Stores multiple timestamped notes per lead';

-- =============================================================
-- Verification Queries (run after migration to confirm success)
-- =============================================================
-- SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='leads' AND COLUMN_NAME IN ('followup_date','followup_notes');
-- SHOW TABLES LIKE 'lead_%';
