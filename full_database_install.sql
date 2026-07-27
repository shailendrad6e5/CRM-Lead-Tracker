-- Database: crm_lead_tracker

-- Database statements removed for shared hosting compatibility
CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Local reviewer account. The application blocks this password outside the
-- local environment.
INSERT IGNORE INTO `users` (`name`, `email`, `password`) VALUES
('Admin User', 'admin@example.com', '$2y$10$WOsb9oes4tsoJZ4WmcRqkuDzEhLBcRPfVS6xBeFQb2kixAZ8MBVMS');

CREATE TABLE IF NOT EXISTS `leads` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `company` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `source` varchar(50) DEFAULT NULL,
  `status` enum('New','Contacted','Qualified','Proposal Sent','Won','Lost') DEFAULT 'New',
  `priority` enum('Low','Medium','High') DEFAULT 'Medium',
  `assigned_to` int(11) DEFAULT NULL,
  `assigned_by` int(11) DEFAULT NULL,
  `assigned_at` datetime DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp DEFAULT current_timestamp(),
  `updated_at` timestamp DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `assigned_to` (`assigned_to`),
  KEY `assigned_by` (`assigned_by`),
  CONSTRAINT `fk_assigned_user` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_leads_assigned_by` FOREIGN KEY (`assigned_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Production data is intentionally not seeded with sample leads.
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
-- =============================================================
-- CRM Lead Tracker — Database Migration v4
-- Multi-User Team Management with RBAC
-- SAFE: Only ADDs new columns/tables. No existing data lost.
-- Run this in phpMyAdmin SQL tab.
-- =============================================================

-- 1. Add RBAC columns to users table
ALTER TABLE `users`
    ADD COLUMN IF NOT EXISTS `role`        ENUM('admin','manager','sales_rep') NOT NULL DEFAULT 'sales_rep' COMMENT 'User role for RBAC' AFTER `email`,
    ADD COLUMN IF NOT EXISTS `department`  VARCHAR(100)  NULL DEFAULT NULL COMMENT 'Department name' AFTER `role`,
    ADD COLUMN IF NOT EXISTS `phone`       VARCHAR(30)   NULL DEFAULT NULL COMMENT 'Contact phone' AFTER `department`,
    ADD COLUMN IF NOT EXISTS `job_title`   VARCHAR(100)  NULL DEFAULT NULL COMMENT 'Job title' AFTER `phone`,
    ADD COLUMN IF NOT EXISTS `avatar`      VARCHAR(255)  NULL DEFAULT NULL COMMENT 'Avatar filename' AFTER `job_title`,
    ADD COLUMN IF NOT EXISTS `status`      ENUM('active','inactive','away') NOT NULL DEFAULT 'active' COMMENT 'Account status' AFTER `avatar`,
    ADD COLUMN IF NOT EXISTS `last_login`  DATETIME      NULL DEFAULT NULL COMMENT 'Last login timestamp' AFTER `status`,
    ADD COLUMN IF NOT EXISTS `created_by`  INT(11)       NULL DEFAULT NULL COMMENT 'ID of user who created this account' AFTER `last_login`;

-- 2. Upgrade the first (oldest) user to Admin
UPDATE `users` SET `role` = 'admin', `status` = 'active' 
WHERE `id` = (SELECT min_id FROM (SELECT MIN(id) as min_id FROM `users`) as t);

-- 3. Add assignment tracking columns to leads table
ALTER TABLE `leads`
    ADD COLUMN IF NOT EXISTS `assigned_by`  INT(11)   NULL DEFAULT NULL COMMENT 'Who assigned this lead' AFTER `assigned_to`,
    ADD COLUMN IF NOT EXISTS `assigned_at`  DATETIME  NULL DEFAULT NULL COMMENT 'When lead was last assigned' AFTER `assigned_by`;

-- 4. Create lead assignment history table
CREATE TABLE IF NOT EXISTS `lead_assignments` (
    `id`          INT(11)      NOT NULL AUTO_INCREMENT,
    `lead_id`     INT(11)      NOT NULL,
    `assigned_to` INT(11)      NOT NULL,
    `assigned_by` INT(11)      NULL,
    `assigned_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `notes`       VARCHAR(255) NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_la_lead`    (`lead_id`),
    KEY `idx_la_to`      (`assigned_to`),
    CONSTRAINT `fk_la_lead` FOREIGN KEY (`lead_id`)     REFERENCES `leads` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_la_to`   FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_la_by`   FOREIGN KEY (`assigned_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Full history of lead assignments';

-- 5. Create notifications table
CREATE TABLE IF NOT EXISTS `user_notifications` (
    `id`         INT(11)      NOT NULL AUTO_INCREMENT,
    `user_id`    INT(11)      NOT NULL,
    `type`       VARCHAR(50)  NOT NULL COMMENT 'lead_assigned, password_reset, account_created, etc.',
    `title`      VARCHAR(150) NOT NULL,
    `message`    TEXT         NOT NULL,
    `link`       VARCHAR(255) NULL DEFAULT NULL COMMENT 'Optional action URL',
    `is_read`    TINYINT(1)   NOT NULL DEFAULT 0,
    `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_notif_user` (`user_id`),
    KEY `idx_notif_read` (`is_read`),
    CONSTRAINT `fk_notif_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='In-app notifications for users';

-- 6. Create avatars upload directory placeholder (handled by PHP)
-- Verification queries (uncomment to run):
-- SELECT id, name, email, role, status FROM users;
-- SHOW COLUMNS FROM users;
-- SHOW COLUMNS FROM leads LIKE 'assigned%';
-- SHOW TABLES LIKE '%lead_assign%';
-- SHOW TABLES LIKE '%notif%';
-- =============================================================
-- CRM Lead Tracker — Database Migration v5
-- Company-Only CRM Upgrade, Activity Logging, and Security
-- =============================================================

-- 1. Update `users` table
-- Change status ENUM to active, inactive, suspended
ALTER TABLE `users`
    MODIFY COLUMN `status` ENUM('active','inactive','suspended') NOT NULL DEFAULT 'active' COMMENT 'Account status',
    ADD COLUMN IF NOT EXISTS `requires_password_change` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Force password change on next login' AFTER `password`,
    ADD COLUMN IF NOT EXISTS `updated_at` DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP COMMENT 'Last profile update timestamp' AFTER `created_at`;

-- 2. Create `user_activities` table
CREATE TABLE IF NOT EXISTS `user_activities` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `user_id` INT(11) NULL,
    `action_type` VARCHAR(50) NOT NULL COMMENT 'e.g., Login, Logout, User Created, Lead Assigned',
    `description` TEXT NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_ua_user` (`user_id`),
    CONSTRAINT `fk_ua_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Audit log for user actions';

-- Keep the unchanged local reviewer account ready for local demonstrations.
-- Re-running this installer does not affect an account whose password changed.
UPDATE `users`
SET `requires_password_change` = 0
WHERE `email` = 'admin@example.com'
  AND `password` = '$2y$10$WOsb9oes4tsoJZ4WmcRqkuDzEhLBcRPfVS6xBeFQb2kixAZ8MBVMS';
