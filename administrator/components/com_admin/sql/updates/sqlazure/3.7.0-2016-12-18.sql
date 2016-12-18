-- Add asset name and parent id unique index
ALTER TABLE [#__assets] ADD CONSTRAINT [#__assets$idx_name_parent_id] UNIQUE CLUSTERED ([name] ASC, [parent_id] ASC);
