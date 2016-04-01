--
-- Make #__user_keys.user_id fit to #__users.username
--

ALTER TABLE `#__user_keys` MODIFY `user_id` varchar(150) NOT NULL;

--
-- Update core #__update_sites entries to https
--

UPDATE `#__update_sites` SET `location` = 'https://update.joomla.org/core/list.xml' WHERE `name` = 'Joomla! Core' AND `type` = 'collection';
UPDATE `#__update_sites` SET `location` = 'https://update.joomla.org/jed/list.xml' WHERE `name` = 'Joomla! Extension Directory' AND `type` = 'collection';
UPDATE `#__update_sites` SET `location` = 'https://update.joomla.org/language/translationlist_3.xml' WHERE `name` = 'Accredited Joomla! Translations' AND `type` = 'collection';
UPDATE `#__update_sites` SET `location` = 'https://update.joomla.org/core/extensions/com_joomlaupdate.xml' WHERE `name` = 'Joomla! Update Component Update Site' AND `type` = 'extension';
