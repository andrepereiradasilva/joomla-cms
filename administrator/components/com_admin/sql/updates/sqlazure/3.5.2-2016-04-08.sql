SET IDENTITY_INSERT [#__extensions]  ON;

INSERT [#__extensions] ([extension_id], [name], [type], [element], [folder], [client_id], [enabled], [access], [protected], [manifest_cache], [params], [custom_data], [system_data], [checked_out], [checked_out_time], [ordering], [state])
SELECT 602, 'English (United Kingdom)', 'package', 'en-GB', '', 0, 1, 1, 1, '', '', '', '', 0, '1900-01-01 00:00:00', 0, 0;

SET IDENTITY_INSERT [#__extensions]  OFF;

UPDATE [#__update_sites_extensions] SET [extension_id] = 602 WHERE [update_site_id] = 8 AND [extension_id] = 600;
