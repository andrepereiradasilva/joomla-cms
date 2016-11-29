-- Add failed attempts counter to update sites.
ALTER TABLE "#__update_sites" ADD COLUMN "failed_attempts" int DEFAULT 0 NOT NULL;

COMMENT ON COLUMN "#__update_sites"."failed_attempts" IS 'Counter with failed update sites connection attempts.';
