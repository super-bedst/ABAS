-- Anlægsgrupper: grupper, medlemmer, bruger-tilknytning, montør adgangsbegrænsning
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS installation_groups (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    public_id CHAR(36) NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by_user_id INT UNSIGNED NULL,
    UNIQUE KEY uq_installation_groups_public_id (public_id),
    KEY idx_installation_groups_name (name),
    CONSTRAINT fk_ig_created_by FOREIGN KEY (created_by_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS installation_group_members (
    group_id INT UNSIGNED NOT NULL,
    installation_id INT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (group_id, installation_id),
    KEY idx_igm_installation (installation_id),
    CONSTRAINT fk_igm_group FOREIGN KEY (group_id) REFERENCES installation_groups(id) ON DELETE CASCADE,
    CONSTRAINT fk_igm_installation FOREIGN KEY (installation_id) REFERENCES installations(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS user_installation_groups (
    user_id INT UNSIGNED NOT NULL,
    group_id INT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, group_id),
    KEY idx_uig_group (group_id),
    CONSTRAINT fk_uig_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_uig_group FOREIGN KEY (group_id) REFERENCES installation_groups(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE users
    ADD COLUMN montor_scoped_access TINYINT(1) NOT NULL DEFAULT 0 AFTER sms_service_allowed;
