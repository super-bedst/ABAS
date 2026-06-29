-- Pending OAuth state for BAS login (overlever passkey/2FA round-trip uden PHP-session)
CREATE TABLE IF NOT EXISTS bas_sso_oauth_states (
    state VARCHAR(64) NOT NULL PRIMARY KEY,
    redirect_uri VARCHAR(512) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME NOT NULL,
    INDEX idx_bas_sso_oauth_expires (expires_at)
);
