-- Ekstern testkø (VC/Trekant uden ABAS-session) opdaget via reconcile-poller

CREATE TABLE IF NOT EXISTS installation_external_testqueue (
    installation_id INT UNSIGNED NOT NULL PRIMARY KEY,
    s_inc INT UNSIGNED NOT NULL DEFAULT 0,
    trekant_user_id VARCHAR(16) NULL,
    queue_comment VARCHAR(255) NULL,
    end_at DATETIME NULL,
    detected_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_iet_installation FOREIGN KEY (installation_id) REFERENCES installations(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
