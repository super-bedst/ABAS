-- Udvid activity_events med api-kategori til REST API-audit
ALTER TABLE activity_events
    MODIFY category ENUM('service','auth','user','registration','installer','sms','system','api') NOT NULL;
