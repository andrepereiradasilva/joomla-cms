-- Add asset name and parent id unique index
ALTER TABLE `#__assets` ADD UNIQUE KEY `idx_name_parent_id` (`name`, `parent_id`);
