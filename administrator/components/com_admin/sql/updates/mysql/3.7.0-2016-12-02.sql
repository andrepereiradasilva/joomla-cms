-- Add failed attempts counter to update sites.
ALTER TABLE `#__update_sites` ADD COLUMN `protected` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Flag to indicate if the update site is protected. Protected update sites cannot be disabled.';
ALTER TABLE `#__update_sites` ADD COLUMN `failed_attempts` int(11) NOT NULL DEFAULT 0 COMMENT 'Counter with failed update sites connection attempts.';

-- Enable and protect joomla core update sites
UPDATE `#__update_sites`
SET `enabled` = 1, `protected` = 1
WHERE (
	(`name` = 'Joomla! Core' AND `type` = 'collection')
	OR (`name` = 'Joomla! Extension Directory' AND `type` = 'collection')
	OR (`name` = 'Joomla! Update Component Update Site' AND `type` = 'extension')
	OR (`name` = 'Accredited Joomla! Translations' AND `type` = 'collection')
);
