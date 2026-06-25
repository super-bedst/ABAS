ALTER TABLE users
    ADD COLUMN last_login_at DATETIME NULL AFTER access_confirm_due_at,
    ADD KEY idx_last_login (last_login_at);
