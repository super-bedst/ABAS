-- Koblede anlæg (fx fab7001 ↔ fab7002) til fælles VC-service
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS installation_links (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    installation_id_lo INT UNSIGNED NOT NULL,
    installation_id_hi INT UNSIGNED NOT NULL,
    note VARCHAR(255) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_by_user_id INT UNSIGNED NULL,
    UNIQUE KEY uq_installation_link_pair (installation_id_lo, installation_id_hi),
    KEY idx_il_hi (installation_id_hi),
    CONSTRAINT fk_il_lo FOREIGN KEY (installation_id_lo) REFERENCES installations(id) ON DELETE CASCADE,
    CONSTRAINT fk_il_hi FOREIGN KEY (installation_id_hi) REFERENCES installations(id) ON DELETE CASCADE,
    CONSTRAINT fk_il_created_by FOREIGN KEY (created_by_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
