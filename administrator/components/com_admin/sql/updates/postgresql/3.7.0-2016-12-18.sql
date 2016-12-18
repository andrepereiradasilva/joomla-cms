-- Add asset name and parent id unique index
ALTER TABLE "#__assets" ADD CONSTRAINT "#__assets_idx_name_parent_id" UNIQUE ("name", "parent_id");
