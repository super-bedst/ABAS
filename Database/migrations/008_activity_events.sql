CREATE TABLE IF NOT EXISTS activity_events (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NULL,
    actor_username VARCHAR(255) NULL,
    category ENUM('service','auth','user','registration','installer','sms','system') NOT NULL,
    action VARCHAR(64) NOT NULL,
    object_type VARCHAR(64) NULL,
    object_id VARCHAR(64) NULL,
    object_label VARCHAR(512) NULL,
    details TEXT NULL,
    ip_address VARCHAR(45) NULL,
    related_s_ins INT NULL,
    related_deal_id VARCHAR(12) NULL,
    source VARCHAR(32) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_created (created_at),
    KEY idx_category_action (category, action),
    KEY idx_user (user_id),
    KEY idx_s_ins (related_s_ins),
    CONSTRAINT fk_ae_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Backfill eksisterende service-handlinger
INSERT INTO activity_events (
    user_id, actor_username, category, action, object_type, object_label, details,
    related_s_ins, related_deal_id, source, created_at
)
SELECT
    sa.user_id,
    u.username,
    'service',
    sa.action,
    'installation',
    CONCAT('Anlæg ', sa.s_ins, ' · ', sa.deal_id),
    NULLIF(TRIM(sa.comm), ''),
    sa.s_ins,
    sa.deal_id,
    sa.source,
    sa.created_at
FROM service_actions sa
LEFT JOIN users u ON u.id = sa.user_id
WHERE NOT EXISTS (
    SELECT 1 FROM activity_events ae
    WHERE ae.category = 'service'
      AND ae.action = sa.action
      AND ae.related_s_ins = sa.s_ins
      AND ae.related_deal_id = sa.deal_id
      AND ae.created_at = sa.created_at
      AND ae.user_id <=> sa.user_id
);
