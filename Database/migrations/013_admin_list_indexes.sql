-- Hurtigere admin-brugerliste og anlægs-tilknytninger
ALTER TABLE users ADD INDEX idx_users_role (role);
ALTER TABLE user_installations ADD INDEX idx_ui_user (user_id);
ALTER TABLE service_sessions ADD INDEX idx_ss_installation_status (installation_id, status);
