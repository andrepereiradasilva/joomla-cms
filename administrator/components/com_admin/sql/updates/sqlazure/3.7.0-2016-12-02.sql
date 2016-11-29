-- Add failed attempts counter to update sites.
ALTER TABLE [#__update_sites] ADD [failed_attempts] [int] DEFAULT 0 NOT NULL;
