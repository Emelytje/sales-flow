-- Migration: CTI screen-pop queue + default telephony settings.
-- Safe to run on an existing installation.

CREATE TABLE IF NOT EXISTS cti_calls (
    id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id         BIGINT UNSIGNED NOT NULL,
    direction       ENUM('inbound','outbound') NOT NULL DEFAULT 'inbound',
    caller_number   VARCHAR(40) NOT NULL,
    called_number   VARCHAR(40) DEFAULT NULL,
    caller_name     VARCHAR(190) DEFAULT NULL,
    customer_id     BIGINT UNSIGNED DEFAULT NULL,
    contact_id      BIGINT UNSIGNED DEFAULT NULL,
    status          ENUM('ringing','answered','missed','ended') NOT NULL DEFAULT 'ringing',
    delivered_at    DATETIME DEFAULT NULL,
    created_at      DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY idx_cti_user (user_id, status, delivered_at),
    KEY idx_cti_created (created_at),
    CONSTRAINT fk_cti_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT fk_cti_customer FOREIGN KEY (customer_id) REFERENCES customers (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO settings (setting_key, value, updated_at) VALUES
    ('general_number', '', NOW()),
    ('sales_number',   '', NOW()),
    ('cti_webhook_secret', '', NOW())
ON DUPLICATE KEY UPDATE setting_key = setting_key;
