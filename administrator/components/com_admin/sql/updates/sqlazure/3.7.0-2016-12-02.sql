-- Add failed attempts counter to update sites.
ALTER TABLE [#__update_sites] ADD [protected] [smallint] DEFAULT 0 NOT NULL;
ALTER TABLE [#__update_sites] ADD [failed_attempts] [int] DEFAULT 0 NOT NULL;

-- Enable and protect joomla core update sites
UPDATE [#__update_sites]
SET [enabled] = 1, [protected] = 1
WHERE (
	([name] = 'Joomla! Core' AND [type] = 'collection')
	OR ([name] = 'Joomla! Extension Directory' AND [type] = 'collection')
	OR ([name] = 'Joomla! Update Component Update Site' AND [type] = 'extension')
	OR ([name] = 'Accredited Joomla! Translations' AND [type] = 'collection')
);
