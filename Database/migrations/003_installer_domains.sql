-- Flere domæner pr. godkendt installatør + ansøgning om ny virksomhed ved registrering

CREATE TABLE IF NOT EXISTS approved_installer_domains (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    installer_id INT UNSIGNED NOT NULL,
    email_domain VARCHAR(255) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_email_domain (email_domain),
    KEY idx_installer (installer_id),
    CONSTRAINT fk_aid_installer FOREIGN KEY (installer_id) REFERENCES approved_installers(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO approved_installer_domains (installer_id, email_domain)
SELECT id, LOWER(TRIM(email_domain))
FROM approved_installers
WHERE email_domain IS NOT NULL AND TRIM(email_domain) <> '';

ALTER TABLE approved_installers
    DROP INDEX uq_email_domain;

ALTER TABLE approved_installers
    DROP COLUMN email_domain;

ALTER TABLE users
    ADD COLUMN registration_requested_company_name VARCHAR(255) NULL AFTER registration_type;
