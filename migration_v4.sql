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
    `assigned_by` INT(11)      NOT NULL,
    `assigned_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `notes`       VARCHAR(255) NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_la_lead`    (`lead_id`),
    KEY `idx_la_to`      (`assigned_to`),
    CONSTRAINT `fk_la_lead` FOREIGN KEY (`lead_id`)     REFERENCES `leads` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_la_to`   FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE CASCADE
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
