-- ABA Service schema
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS system_settings (
    `key` VARCHAR(64) NOT NULL PRIMARY KEY,
    `value` VARCHAR(255) NOT NULL,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO system_settings (`key`, `value`) VALUES
    ('access_confirm_months', '3'),
    ('password_reset_ttl_hours', '24'),
    ('welcome_token_ttl_hours', '72'),
    ('support_email', 'alarmadm@trekantbrand.dk'),
    ('mfa_required', '1')
ON DUPLICATE KEY UPDATE `value` = VALUES(`value`);

CREATE TABLE IF NOT EXISTS approved_installers (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    company_name VARCHAR(255) NOT NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    approved_at DATETIME NULL,
    approved_by_user_id INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS approved_installer_domains (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    installer_id INT UNSIGNED NOT NULL,
    email_domain VARCHAR(255) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_email_domain (email_domain),
    KEY idx_installer (installer_id),
    CONSTRAINT fk_aid_installer FOREIGN KEY (installer_id) REFERENCES approved_installers(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    username VARCHAR(100) NOT NULL,
    password_hash VARCHAR(255) NULL,
    role ENUM('admin','vagtcentral','montor','anlaegsejer','anlaegsafprover','virksomhedsadmin') NOT NULL,
    phone VARCHAR(32) NULL,
    trekant_userid VARCHAR(8) NULL,
    sms_secret_hash VARCHAR(255) NULL,
    sms_service_allowed TINYINT(1) NOT NULL DEFAULT 0,
    installer_id INT UNSIGNED NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    registration_status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'approved',
    registration_type ENUM('montor','anlaegsejer','anlaegsafprover') NULL,
    registration_requested_company_name VARCHAR(255) NULL,
    registration_requested_at DATETIME NULL,
    registration_reviewed_at DATETIME NULL,
    registration_reviewed_by_user_id INT UNSIGNED NULL,
    password_set_at DATETIME NULL,
    access_confirmed_at DATETIME NULL,
    access_confirm_due_at DATETIME NULL,
    responsibility_ack_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_by_user_id INT UNSIGNED NULL,
    UNIQUE KEY uq_email (email),
    UNIQUE KEY uq_username (username),
    KEY idx_role (role),
    KEY idx_installer (installer_id),
    CONSTRAINT fk_users_installer FOREIGN KEY (installer_id) REFERENCES approved_installers(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS bas_user_links (
    aba_user_id INT UNSIGNED NOT NULL,
    bas_username VARCHAR(100) NOT NULL,
    PRIMARY KEY (aba_user_id),
    UNIQUE KEY uq_bas_username (bas_username),
    CONSTRAINT fk_bas_user FOREIGN KEY (aba_user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS password_flow_tokens (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    token_hash CHAR(64) NOT NULL,
    kind ENUM('welcome','reset','vc_invite') NOT NULL,
    expires_at DATETIME NOT NULL,
    used_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_token_hash (token_hash),
    KEY idx_user (user_id),
    CONSTRAINT fk_pft_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS installations (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    s_ins INT NOT NULL,
    deal_id VARCHAR(12) NOT NULL,
    ins_no VARCHAR(32) NULL,
    miscno2 VARCHAR(32) NULL,
    name VARCHAR(255) NULL,
    address VARCHAR(512) NULL,
    city VARCHAR(128) NULL,
    mon_stat VARCHAR(16) NULL,
    last_synced_at DATETIME NULL,
    UNIQUE KEY uq_s_ins_deal (s_ins, deal_id),
    KEY idx_miscno2 (miscno2),
    KEY idx_ins_no (ins_no)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sync_prefixes (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    prefix VARCHAR(32) NOT NULL,
    min_suffix INT UNSIGNED NOT NULL DEFAULT 0,
    max_suffix INT UNSIGNED NOT NULL DEFAULT 9999,
    batch_size INT UNSIGNED NOT NULL DEFAULT 100,
    active TINYINT(1) NOT NULL DEFAULT 1,
    last_sync_at DATETIME NULL,
    last_sync_count INT UNSIGNED NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_prefix (prefix)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS installation_sync_runs (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    sync_prefix_id INT UNSIGNED NOT NULL,
    started_at DATETIME NOT NULL,
    finished_at DATETIME NULL,
    batches_requested INT UNSIGNED NOT NULL DEFAULT 0,
    rows_received INT UNSIGNED NOT NULL DEFAULT 0,
    rows_upserted INT UNSIGNED NOT NULL DEFAULT 0,
    status ENUM('running','success','partial','failed') NOT NULL DEFAULT 'running',
    error_message TEXT NULL,
    KEY idx_prefix (sync_prefix_id),
    CONSTRAINT fk_isr_prefix FOREIGN KEY (sync_prefix_id) REFERENCES sync_prefixes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS user_installations (
    user_id INT UNSIGNED NOT NULL,
    installation_id INT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, installation_id),
    CONSTRAINT fk_ui_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_ui_installation FOREIGN KEY (installation_id) REFERENCES installations(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS service_sessions (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    on_behalf_of_user_id INT UNSIGNED NULL,
    installation_id INT UNSIGNED NOT NULL,
    s_inc INT NULL,
    started_at DATETIME NOT NULL,
    expires_at DATETIME NULL,
    duration_hours DECIMAL(8,2) NULL,
    unlimited TINYINT(1) NOT NULL DEFAULT 0,
    warning_sent_at DATETIME NULL,
    ended_at DATETIME NULL,
    status ENUM('active','ended','expired') NOT NULL DEFAULT 'active',
    source ENUM('web','sms','api','cron') NOT NULL DEFAULT 'web',
    KEY idx_installation (installation_id),
    KEY idx_status_expires (status, expires_at),
    CONSTRAINT fk_ss_user FOREIGN KEY (user_id) REFERENCES users(id),
    CONSTRAINT fk_ss_on_behalf FOREIGN KEY (on_behalf_of_user_id) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_ss_installation FOREIGN KEY (installation_id) REFERENCES installations(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS service_actions (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    on_behalf_of_user_id INT UNSIGNED NULL,
    session_id INT UNSIGNED NULL,
    s_ins INT NOT NULL,
    deal_id VARCHAR(12) NOT NULL,
    action ENUM('start_service','stop_service','extend_service','add_comment') NOT NULL,
    test_time VARCHAR(13) NULL,
    comm VARCHAR(255) NULL,
    responsibility_ack_at DATETIME NULL,
    source ENUM('web','sms','api','bas_sso','cron') NOT NULL DEFAULT 'web',
    trekant_return_code INT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_s_ins (s_ins),
    CONSTRAINT fk_sa_user FOREIGN KEY (user_id) REFERENCES users(id),
    CONSTRAINT fk_sa_on_behalf FOREIGN KEY (on_behalf_of_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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

CREATE TABLE IF NOT EXISTS api_tokens (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    token_hash CHAR(64) NOT NULL,
    role ENUM('vagtcentral','montor','admin') NOT NULL DEFAULT 'vagtcentral',
    allowed_ips TEXT NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_token_hash (token_hash)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS montor_outreach_log (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    vc_user_id INT UNSIGNED NOT NULL,
    phone VARCHAR(32) NOT NULL,
    message TEXT NOT NULL,
    miscno2 VARCHAR(32) NULL,
    installation_id INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_mol_vc FOREIGN KEY (vc_user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sms_inbound_log (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    from_number VARCHAR(32) NOT NULL,
    body TEXT NOT NULL,
    parsed_command VARCHAR(255) NULL,
    user_id INT UNSIGNED NULL,
    result TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sms_outbound_log (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    to_number VARCHAR(32) NOT NULL,
    body TEXT NOT NULL,
    trigger_type VARCHAR(32) NOT NULL,
    session_id INT UNSIGNED NULL,
    status VARCHAR(32) NOT NULL DEFAULT 'queued',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed admin (password: admin123 — SKIFT I PRODUKTION)
INSERT INTO users (email, username, password_hash, role, active, registration_status, password_set_at, access_confirmed_at, access_confirm_due_at)
SELECT 'admin@trekantbrand.dk', 'admin', '$2y$10$9FGUS7MEwUvmHpY91XlkaewV.H09u0J.uJkXT88xNZ67CJAJSizHS', 'admin', 1, 'approved', NOW(), NOW(), DATE_ADD(NOW(), INTERVAL 3 MONTH)
WHERE NOT EXISTS (SELECT 1 FROM users WHERE role = 'admin');

SET FOREIGN_KEY_CHECKS = 1;
