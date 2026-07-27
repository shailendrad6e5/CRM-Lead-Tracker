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
