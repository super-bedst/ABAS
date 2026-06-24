-- Sync-prefix: start-suffix for at springe tomme 100-blokke over (fx start 5000 → fab50)
ALTER TABLE sync_prefixes
    ADD COLUMN min_suffix INT UNSIGNED NOT NULL DEFAULT 0 AFTER prefix;
