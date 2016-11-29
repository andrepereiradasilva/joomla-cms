-- Add failed attempts counter to update sites.
ALTER TABLE `#__update_sites` ADD COLUMN `failed_attempts` int(11) NOT NULL DEFAULT 0 COMMENT 'Counter with failed update sites connection attempts.';
