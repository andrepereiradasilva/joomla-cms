-- Add failed attempts counter to update sites.
ALTER TABLE "#__update_sites" ADD COLUMN "protected" smallint DEFAULT 0 NOT NULL;
ALTER TABLE "#__update_sites" ADD COLUMN "failed_attempts" int DEFAULT 0 NOT NULL;

COMMENT ON COLUMN "#__update_sites"."protected" IS 'Flag to indicate if the update site is protected. Protected update sites cannot be disabled.';
COMMENT ON COLUMN "#__update_sites"."failed_attempts" IS 'Counter with failed update sites connection attempts.';

-- Enable and protect joomla core update sites
UPDATE "#__update_sites"
SET "enabled" = 1, "protected" = 1
WHERE (
		("name" = 'Joomla! Core' AND "type" = 'collection')
		OR ("name" = 'Joomla! Extension Directory' AND "type" = 'collection')
		OR ("name" = 'Joomla! Update Component Update Site' AND "type" = 'extension')
		OR ("name" = 'Accredited Joomla! Translations' AND "type" = 'collection')
);
