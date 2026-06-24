-- Platform extensions: registration approval, new roles, MFA, responsibility ack
SET NAMES utf8mb4;

ALTER TABLE users
    MODIFY role ENUM('admin','vagtcentral','montor','anlaegsejer','anlaegsafprover','virksomhedsadmin') NOT NULL;

ALTER TABLE users
    ADD COLUMN registration_status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'approved' AFTER active,
    ADD COLUMN registration_type ENUM('montor','anlaegsejer','anlaegsafprover') NULL AFTER registration_status,
    ADD COLUMN registration_requested_at DATETIME NULL AFTER registration_type,
    ADD COLUMN registration_reviewed_at DATETIME NULL AFTER registration_requested_at,
    ADD COLUMN registration_reviewed_by_user_id INT UNSIGNED NULL AFTER registration_reviewed_at,
    ADD COLUMN sms_service_allowed TINYINT(1) NOT NULL DEFAULT 0 AFTER sms_secret_hash,
    ADD COLUMN responsibility_ack_at DATETIME NULL AFTER access_confirm_due_at;

CREATE TABLE IF NOT EXISTS registration_installation_requests (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    miscno2 VARCHAR(32) NOT NULL,
    installation_id INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_user (user_id),
    CONSTRAINT fk_rir_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_rir_installation FOREIGN KEY (installation_id) REFERENCES installations(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE service_actions
    ADD COLUMN responsibility_ack_at DATETIME NULL AFTER comm;

CREATE TABLE IF NOT EXISTS user_mfa (
    user_id INT UNSIGNED NOT NULL PRIMARY KEY,
    method ENUM('passkey','sms_otp') NOT NULL DEFAULT 'passkey',
    enrolled_at DATETIME NULL,
    admin_override TINYINT(1) NOT NULL DEFAULT 0,
    CONSTRAINT fk_umfa_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS webauthn_credentials (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    credential_id VARBINARY(255) NOT NULL,
    public_key TEXT NOT NULL,
    sign_count INT UNSIGNED NOT NULL DEFAULT 0,
    label VARCHAR(100) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_credential_id (credential_id),
    KEY idx_user (user_id),
    CONSTRAINT fk_wac_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS mfa_ip_whitelist (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    ip_cidr VARCHAR(64) NOT NULL,
    label VARCHAR(100) NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_by_user_id INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_ip_cidr (ip_cidr)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS mfa_otp_challenges (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    code_hash CHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    used_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_user (user_id),
    CONSTRAINT fk_moc_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO system_settings (`key`, `value`) VALUES
    ('support_email', 'alarmadm@trekantbrand.dk'),
    ('mfa_required', '1')
ON DUPLICATE KEY UPDATE `value` = VALUES(`value`);
