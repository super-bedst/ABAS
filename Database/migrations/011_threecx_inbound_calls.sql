-- Indgående opkald fra 3CX (CFD HTTP webhook) til VC-service-panelet
CREATE TABLE IF NOT EXISTS threecx_inbound_calls (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    call_id VARCHAR(64) NOT NULL,
    caller_number VARCHAR(32) NOT NULL,
    caller_name VARCHAR(255) NULL,
    queue_name VARCHAR(100) NULL,
    did VARCHAR(32) NULL,
    status ENUM('ringing', 'connected', 'ended') NOT NULL DEFAULT 'ringing',
    matched_user_id INT UNSIGNED NULL,
    matched_role VARCHAR(32) NULL,
    first_seen_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    last_seen_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    ended_at DATETIME NULL,
    UNIQUE KEY uq_call_id (call_id),
    KEY idx_status_last_seen (status, last_seen_at),
    KEY idx_caller (caller_number),
    CONSTRAINT fk_threecx_matched_user FOREIGN KEY (matched_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
