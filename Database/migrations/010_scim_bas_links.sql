-- SCIM provisioning fra BAS: OIDC sub + SCIM id på bas_user_links
ALTER TABLE bas_user_links
    ADD COLUMN bas_oidc_sub VARCHAR(64) NULL AFTER bas_username,
    ADD COLUMN scim_id VARCHAR(64) NULL AFTER bas_oidc_sub;

ALTER TABLE bas_user_links
    ADD UNIQUE KEY uq_bas_oidc_sub (bas_oidc_sub),
    ADD UNIQUE KEY uq_scim_id (scim_id);
