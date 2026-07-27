-- =============================================================
-- CRM Lead Tracker — Database Migration v3
-- Follow-up Management System
-- SAFE: Only ADDs new columns. No existing data lost.
-- =============================================================

ALTER TABLE `leads`
    ADD COLUMN IF NOT EXISTS `followup_date`  DATE         NULL DEFAULT NULL COMMENT 'Scheduled follow-up date' AFTER `notes`,
    ADD COLUMN IF NOT EXISTS `followup_notes` TEXT         NULL DEFAULT NULL COMMENT 'Follow-up reminder notes' AFTER `followup_date`,
    ADD COLUMN IF NOT EXISTS `followup_time`  TIME         NULL DEFAULT NULL COMMENT 'Scheduled time for follow-up' AFTER `followup_date`,
    ADD COLUMN IF NOT EXISTS `followup_status` ENUM('Pending','Completed','Missed') NOT NULL DEFAULT 'Pending' COMMENT 'Status of the follow-up' AFTER `followup_time`,
    ADD COLUMN IF NOT EXISTS `followup_priority` ENUM('Low','Medium','High') NOT NULL DEFAULT 'Medium' COMMENT 'Priority of follow-up' AFTER `followup_status`,
    ADD COLUMN IF NOT EXISTS `completed_at`   DATETIME     NULL DEFAULT NULL COMMENT 'When the follow-up was completed' AFTER `followup_priority`;
