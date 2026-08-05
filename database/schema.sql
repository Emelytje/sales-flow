-- =============================================================================
--  SalesFlow Enterprise — Database schema (MySQL 8, InnoDB, utf8mb4)
--  Normalized, foreign-keyed and indexed for performance.
--  Run once on an empty database (the install wizard does this for you).
-- =============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;
SET time_zone = '+00:00';

-- -----------------------------------------------------------------------------
--  Users, roles & permissions
-- -----------------------------------------------------------------------------

CREATE TABLE users (
    id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name            VARCHAR(120) NOT NULL,
    email           VARCHAR(190) NOT NULL,
    password_hash   VARCHAR(255) NOT NULL,
    role            ENUM('admin','manager','sales') NOT NULL DEFAULT 'sales',
    phone           VARCHAR(40) DEFAULT NULL,
    job_title       VARCHAR(120) DEFAULT NULL,
    avatar          VARCHAR(255) DEFAULT NULL,
    signature       TEXT DEFAULT NULL,
    booking_slug    VARCHAR(80) DEFAULT NULL,
    daily_call_goal INT UNSIGNED NOT NULL DEFAULT 40,
    color           VARCHAR(9) NOT NULL DEFAULT '#E98CAB',
    theme           ENUM('light','dark','system') NOT NULL DEFAULT 'system',
    locale          VARCHAR(5) NOT NULL DEFAULT 'nl',
    two_factor_secret   VARCHAR(255) DEFAULT NULL,
    two_factor_enabled  TINYINT(1) NOT NULL DEFAULT 0,
    status          ENUM('active','suspended') NOT NULL DEFAULT 'active',
    last_login_at   DATETIME DEFAULT NULL,
    created_at      DATETIME NOT NULL,
    updated_at      DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_email (email),
    UNIQUE KEY uq_users_booking_slug (booking_slug),
    KEY idx_users_role (role),
    KEY idx_users_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE permissions (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    slug        VARCHAR(80) NOT NULL,
    label       VARCHAR(120) NOT NULL,
    module      VARCHAR(60) NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_permissions_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE role_permissions (
    role            ENUM('admin','manager','sales') NOT NULL,
    permission_id   INT UNSIGNED NOT NULL,
    PRIMARY KEY (role, permission_id),
    CONSTRAINT fk_rp_permission FOREIGN KEY (permission_id) REFERENCES permissions (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE remember_tokens (
    id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id         BIGINT UNSIGNED NOT NULL,
    selector        CHAR(16) NOT NULL,
    validator_hash  CHAR(64) NOT NULL,
    expires_at      DATETIME NOT NULL,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_remember_selector (selector),
    KEY idx_remember_user (user_id),
    CONSTRAINT fk_remember_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE password_resets (
    id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    email       VARCHAR(190) NOT NULL,
    token_hash  CHAR(64) NOT NULL,
    expires_at  DATETIME NOT NULL,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_pwreset_email (email),
    KEY idx_pwreset_token (token_hash)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE rate_limits (
    id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    bucket      CHAR(64) NOT NULL,
    hits        INT UNSIGNED NOT NULL DEFAULT 0,
    reset_at    INT UNSIGNED NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_rate_bucket (bucket)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE audit_logs (
    id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id     BIGINT UNSIGNED DEFAULT NULL,
    action      VARCHAR(120) NOT NULL,
    entity_type VARCHAR(60) DEFAULT NULL,
    entity_id   BIGINT UNSIGNED DEFAULT NULL,
    ip_address  VARCHAR(45) DEFAULT NULL,
    user_agent  VARCHAR(255) DEFAULT NULL,
    meta        JSON DEFAULT NULL,
    created_at  DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY idx_audit_user (user_id),
    KEY idx_audit_action (action),
    KEY idx_audit_entity (entity_type, entity_id),
    KEY idx_audit_created (created_at),
    CONSTRAINT fk_audit_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE api_keys (
    id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id     BIGINT UNSIGNED NOT NULL,
    name        VARCHAR(120) NOT NULL,
    token_hash  CHAR(64) NOT NULL,
    prefix      CHAR(8) NOT NULL,
    last_used_at DATETIME DEFAULT NULL,
    revoked_at  DATETIME DEFAULT NULL,
    created_at  DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_apikey_token (token_hash),
    KEY idx_apikey_user (user_id),
    CONSTRAINT fk_apikey_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE oauth_tokens (
    id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id         BIGINT UNSIGNED NOT NULL,
    provider        ENUM('google','microsoft') NOT NULL,
    account_email   VARCHAR(190) DEFAULT NULL,
    access_token    TEXT NOT NULL,
    refresh_token   TEXT DEFAULT NULL,
    scopes          TEXT DEFAULT NULL,
    expires_at      DATETIME DEFAULT NULL,
    created_at      DATETIME NOT NULL,
    updated_at      DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_oauth_user_provider (user_id, provider),
    CONSTRAINT fk_oauth_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
--  Taxonomy: sectors & labels
-- -----------------------------------------------------------------------------

CREATE TABLE sectors (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name        VARCHAR(120) NOT NULL,
    created_at  DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_sectors_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE labels (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name        VARCHAR(80) NOT NULL,
    color       VARCHAR(9) NOT NULL DEFAULT '#E98CAB',
    created_at  DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_labels_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
--  Projects
-- -----------------------------------------------------------------------------

CREATE TABLE projects (
    id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name            VARCHAR(160) NOT NULL,
    description     TEXT DEFAULT NULL,
    logo            VARCHAR(255) DEFAULT NULL,
    color           VARCHAR(9) NOT NULL DEFAULT '#B33B62',
    call_script     MEDIUMTEXT DEFAULT NULL,
    faq             MEDIUMTEXT DEFAULT NULL,
    demo_video_url  VARCHAR(255) DEFAULT NULL,
    status          ENUM('active','archived') NOT NULL DEFAULT 'active',
    created_by      BIGINT UNSIGNED DEFAULT NULL,
    created_at      DATETIME NOT NULL,
    updated_at      DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY idx_projects_status (status),
    CONSTRAINT fk_projects_creator FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
--  Customers (companies) & contacts (people)
-- -----------------------------------------------------------------------------

CREATE TABLE customers (
    id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    company_name    VARCHAR(190) NOT NULL,
    vat_number      VARCHAR(40) DEFAULT NULL,
    kbo_number      VARCHAR(40) DEFAULT NULL,
    email           VARCHAR(190) DEFAULT NULL,
    phone           VARCHAR(40) DEFAULT NULL,
    website         VARCHAR(190) DEFAULT NULL,
    linkedin        VARCHAR(190) DEFAULT NULL,
    logo            VARCHAR(255) DEFAULT NULL,
    address         VARCHAR(190) DEFAULT NULL,
    postal_code     VARCHAR(20) DEFAULT NULL,
    city            VARCHAR(120) DEFAULT NULL,
    country         VARCHAR(80) NOT NULL DEFAULT 'België',
    latitude        DECIMAL(10,7) DEFAULT NULL,
    longitude       DECIMAL(10,7) DEFAULT NULL,
    google_place_id VARCHAR(255) DEFAULT NULL,
    sector_id       INT UNSIGNED DEFAULT NULL,
    project_id      BIGINT UNSIGNED DEFAULT NULL,
    owner_id        BIGINT UNSIGNED DEFAULT NULL,
    pipeline_stage  ENUM('lead','contacted','qualified','proposal','won','lost') NOT NULL DEFAULT 'lead',
    status          ENUM('prospect','customer','inactive') NOT NULL DEFAULT 'prospect',
    priority        ENUM('low','medium','high') NOT NULL DEFAULT 'medium',
    lead_score      TINYINT UNSIGNED NOT NULL DEFAULT 0,
    estimated_value DECIMAL(12,2) NOT NULL DEFAULT 0,
    lost_reason     VARCHAR(255) DEFAULT NULL,
    next_action_at  DATETIME DEFAULT NULL,
    last_contact_at DATETIME DEFAULT NULL,
    notes           TEXT DEFAULT NULL,
    created_by      BIGINT UNSIGNED DEFAULT NULL,
    created_at      DATETIME NOT NULL,
    updated_at      DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY idx_customers_company (company_name),
    KEY idx_customers_owner (owner_id),
    KEY idx_customers_stage (pipeline_stage),
    KEY idx_customers_status (status),
    KEY idx_customers_sector (sector_id),
    KEY idx_customers_project (project_id),
    KEY idx_customers_next_action (next_action_at),
    KEY idx_customers_geo (latitude, longitude),
    CONSTRAINT fk_customers_sector FOREIGN KEY (sector_id) REFERENCES sectors (id) ON DELETE SET NULL,
    CONSTRAINT fk_customers_project FOREIGN KEY (project_id) REFERENCES projects (id) ON DELETE SET NULL,
    CONSTRAINT fk_customers_owner FOREIGN KEY (owner_id) REFERENCES users (id) ON DELETE SET NULL,
    CONSTRAINT fk_customers_creator FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE customer_labels (
    customer_id BIGINT UNSIGNED NOT NULL,
    label_id    INT UNSIGNED NOT NULL,
    PRIMARY KEY (customer_id, label_id),
    CONSTRAINT fk_cl_customer FOREIGN KEY (customer_id) REFERENCES customers (id) ON DELETE CASCADE,
    CONSTRAINT fk_cl_label FOREIGN KEY (label_id) REFERENCES labels (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE contacts (
    id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    customer_id BIGINT UNSIGNED NOT NULL,
    first_name  VARCHAR(80) NOT NULL,
    last_name   VARCHAR(80) DEFAULT NULL,
    job_title   VARCHAR(120) DEFAULT NULL,
    email       VARCHAR(190) DEFAULT NULL,
    phone       VARCHAR(40) DEFAULT NULL,
    mobile      VARCHAR(40) DEFAULT NULL,
    linkedin    VARCHAR(190) DEFAULT NULL,
    is_primary  TINYINT(1) NOT NULL DEFAULT 0,
    created_at  DATETIME NOT NULL,
    updated_at  DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY idx_contacts_customer (customer_id),
    KEY idx_contacts_email (email),
    CONSTRAINT fk_contacts_customer FOREIGN KEY (customer_id) REFERENCES customers (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE custom_fields (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    entity      ENUM('customer','contact','project') NOT NULL DEFAULT 'customer',
    field_key   VARCHAR(80) NOT NULL,
    label       VARCHAR(120) NOT NULL,
    type        ENUM('text','number','date','select','boolean') NOT NULL DEFAULT 'text',
    options     JSON DEFAULT NULL,
    sort_order  INT NOT NULL DEFAULT 0,
    created_at  DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_custom_field (entity, field_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE custom_field_values (
    id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    field_id    INT UNSIGNED NOT NULL,
    entity_id   BIGINT UNSIGNED NOT NULL,
    value       TEXT DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_cfv (field_id, entity_id),
    CONSTRAINT fk_cfv_field FOREIGN KEY (field_id) REFERENCES custom_fields (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
--  Notes, activities, calls (timeline)
-- -----------------------------------------------------------------------------

CREATE TABLE notes (
    id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    customer_id BIGINT UNSIGNED NOT NULL,
    user_id     BIGINT UNSIGNED DEFAULT NULL,
    body        TEXT NOT NULL,
    pinned      TINYINT(1) NOT NULL DEFAULT 0,
    created_at  DATETIME NOT NULL,
    updated_at  DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY idx_notes_customer (customer_id),
    CONSTRAINT fk_notes_customer FOREIGN KEY (customer_id) REFERENCES customers (id) ON DELETE CASCADE,
    CONSTRAINT fk_notes_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE activities (
    id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    customer_id BIGINT UNSIGNED DEFAULT NULL,
    contact_id  BIGINT UNSIGNED DEFAULT NULL,
    user_id     BIGINT UNSIGNED DEFAULT NULL,
    type        ENUM('call','email','meeting','note','task','quotation','status_change','system') NOT NULL,
    subject     VARCHAR(190) DEFAULT NULL,
    body        TEXT DEFAULT NULL,
    outcome     VARCHAR(60) DEFAULT NULL,
    duration_seconds INT UNSIGNED DEFAULT NULL,
    occurred_at DATETIME NOT NULL,
    created_at  DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY idx_activities_customer (customer_id),
    KEY idx_activities_user (user_id),
    KEY idx_activities_type (type),
    KEY idx_activities_occurred (occurred_at),
    CONSTRAINT fk_activities_customer FOREIGN KEY (customer_id) REFERENCES customers (id) ON DELETE CASCADE,
    CONSTRAINT fk_activities_contact FOREIGN KEY (contact_id) REFERENCES contacts (id) ON DELETE SET NULL,
    CONSTRAINT fk_activities_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
--  Attachments (polymorphic) with version history
-- -----------------------------------------------------------------------------

CREATE TABLE attachments (
    id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    entity_type ENUM('customer','contact','project','quotation') NOT NULL,
    entity_id   BIGINT UNSIGNED NOT NULL,
    folder      VARCHAR(120) DEFAULT NULL,
    original_name VARCHAR(255) NOT NULL,
    stored_name VARCHAR(255) NOT NULL,
    mime_type   VARCHAR(120) NOT NULL,
    size_bytes  BIGINT UNSIGNED NOT NULL DEFAULT 0,
    version     INT UNSIGNED NOT NULL DEFAULT 1,
    uploaded_by BIGINT UNSIGNED DEFAULT NULL,
    created_at  DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY idx_attachments_entity (entity_type, entity_id),
    CONSTRAINT fk_attachments_user FOREIGN KEY (uploaded_by) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
--  Quotations
-- -----------------------------------------------------------------------------

CREATE TABLE quotations (
    id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    number          VARCHAR(40) NOT NULL,
    customer_id     BIGINT UNSIGNED NOT NULL,
    project_id      BIGINT UNSIGNED DEFAULT NULL,
    user_id         BIGINT UNSIGNED DEFAULT NULL,
    title           VARCHAR(190) NOT NULL,
    intro           TEXT DEFAULT NULL,
    terms           TEXT DEFAULT NULL,
    currency        VARCHAR(3) NOT NULL DEFAULT 'EUR',
    subtotal        DECIMAL(12,2) NOT NULL DEFAULT 0,
    discount        DECIMAL(12,2) NOT NULL DEFAULT 0,
    tax_rate        DECIMAL(5,2) NOT NULL DEFAULT 21.00,
    tax_amount      DECIMAL(12,2) NOT NULL DEFAULT 0,
    total           DECIMAL(12,2) NOT NULL DEFAULT 0,
    status          ENUM('draft','sent','viewed','accepted','rejected','expired') NOT NULL DEFAULT 'draft',
    valid_until     DATE DEFAULT NULL,
    signed_at       DATETIME DEFAULT NULL,
    signer_name     VARCHAR(160) DEFAULT NULL,
    signature_data  MEDIUMTEXT DEFAULT NULL,
    public_token    CHAR(40) DEFAULT NULL,
    sent_at         DATETIME DEFAULT NULL,
    created_at      DATETIME NOT NULL,
    updated_at      DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_quotation_number (number),
    UNIQUE KEY uq_quotation_token (public_token),
    KEY idx_quotations_customer (customer_id),
    KEY idx_quotations_status (status),
    CONSTRAINT fk_quotations_customer FOREIGN KEY (customer_id) REFERENCES customers (id) ON DELETE CASCADE,
    CONSTRAINT fk_quotations_project FOREIGN KEY (project_id) REFERENCES projects (id) ON DELETE SET NULL,
    CONSTRAINT fk_quotations_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE quotation_items (
    id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    quotation_id BIGINT UNSIGNED NOT NULL,
    description VARCHAR(255) NOT NULL,
    quantity    DECIMAL(10,2) NOT NULL DEFAULT 1,
    unit_price  DECIMAL(12,2) NOT NULL DEFAULT 0,
    discount    DECIMAL(5,2) NOT NULL DEFAULT 0,
    line_total  DECIMAL(12,2) NOT NULL DEFAULT 0,
    sort_order  INT NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    KEY idx_qitems_quotation (quotation_id),
    CONSTRAINT fk_qitems_quotation FOREIGN KEY (quotation_id) REFERENCES quotations (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
--  Agenda, availability, bookings
-- -----------------------------------------------------------------------------

CREATE TABLE agenda_events (
    id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id         BIGINT UNSIGNED NOT NULL,
    customer_id     BIGINT UNSIGNED DEFAULT NULL,
    title           VARCHAR(190) NOT NULL,
    description     TEXT DEFAULT NULL,
    type            ENUM('meeting','call','task','visit','vacation','other') NOT NULL DEFAULT 'meeting',
    location        VARCHAR(255) DEFAULT NULL,
    starts_at       DATETIME NOT NULL,
    ends_at         DATETIME NOT NULL,
    all_day         TINYINT(1) NOT NULL DEFAULT 0,
    travel_minutes  INT UNSIGNED NOT NULL DEFAULT 0,
    color           VARCHAR(9) DEFAULT NULL,
    visibility      ENUM('private','shared') NOT NULL DEFAULT 'shared',
    status          ENUM('confirmed','tentative','cancelled','done') NOT NULL DEFAULT 'confirmed',
    recurrence_rule VARCHAR(190) DEFAULT NULL,
    google_event_id VARCHAR(190) DEFAULT NULL,
    teams_join_url  VARCHAR(500) DEFAULT NULL,
    reminder_minutes INT UNSIGNED NOT NULL DEFAULT 30,
    created_at      DATETIME NOT NULL,
    updated_at      DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY idx_events_user (user_id),
    KEY idx_events_range (starts_at, ends_at),
    KEY idx_events_customer (customer_id),
    CONSTRAINT fk_events_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT fk_events_customer FOREIGN KEY (customer_id) REFERENCES customers (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE availability_rules (
    id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id     BIGINT UNSIGNED NOT NULL,
    weekday     TINYINT UNSIGNED NOT NULL, -- 0=Sunday .. 6=Saturday
    start_time  TIME NOT NULL,
    end_time    TIME NOT NULL,
    slot_minutes INT UNSIGNED NOT NULL DEFAULT 30,
    PRIMARY KEY (id),
    KEY idx_avail_user (user_id),
    CONSTRAINT fk_avail_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE bookings (
    id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id         BIGINT UNSIGNED NOT NULL,
    customer_id     BIGINT UNSIGNED DEFAULT NULL,
    guest_name      VARCHAR(160) NOT NULL,
    guest_email     VARCHAR(190) NOT NULL,
    guest_phone     VARCHAR(40) DEFAULT NULL,
    guest_company   VARCHAR(190) DEFAULT NULL,
    subject         VARCHAR(190) DEFAULT NULL,
    message         TEXT DEFAULT NULL,
    starts_at       DATETIME NOT NULL,
    ends_at         DATETIME NOT NULL,
    status          ENUM('pending','approved','rejected','cancelled') NOT NULL DEFAULT 'pending',
    teams_join_url  VARCHAR(500) DEFAULT NULL,
    event_id        BIGINT UNSIGNED DEFAULT NULL,
    confirm_token   CHAR(40) NOT NULL,
    created_at      DATETIME NOT NULL,
    updated_at      DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_booking_token (confirm_token),
    KEY idx_bookings_user (user_id),
    KEY idx_bookings_status (status),
    CONSTRAINT fk_bookings_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT fk_bookings_customer FOREIGN KEY (customer_id) REFERENCES customers (id) ON DELETE SET NULL,
    CONSTRAINT fk_bookings_event FOREIGN KEY (event_id) REFERENCES agenda_events (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
--  Email: templates, messages, tracking, campaigns
-- -----------------------------------------------------------------------------

CREATE TABLE email_templates (
    id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    project_id  BIGINT UNSIGNED DEFAULT NULL,
    name        VARCHAR(160) NOT NULL,
    subject     VARCHAR(255) NOT NULL,
    body        MEDIUMTEXT NOT NULL,
    created_by  BIGINT UNSIGNED DEFAULT NULL,
    created_at  DATETIME NOT NULL,
    updated_at  DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY idx_templates_project (project_id),
    CONSTRAINT fk_templates_project FOREIGN KEY (project_id) REFERENCES projects (id) ON DELETE SET NULL,
    CONSTRAINT fk_templates_user FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE campaigns (
    id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name        VARCHAR(160) NOT NULL,
    template_id BIGINT UNSIGNED DEFAULT NULL,
    status      ENUM('draft','scheduled','sending','sent','paused') NOT NULL DEFAULT 'draft',
    scheduled_at DATETIME DEFAULT NULL,
    created_by  BIGINT UNSIGNED DEFAULT NULL,
    created_at  DATETIME NOT NULL,
    updated_at  DATETIME NOT NULL,
    PRIMARY KEY (id),
    CONSTRAINT fk_campaigns_template FOREIGN KEY (template_id) REFERENCES email_templates (id) ON DELETE SET NULL,
    CONSTRAINT fk_campaigns_user FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE email_messages (
    id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id         BIGINT UNSIGNED DEFAULT NULL,
    customer_id     BIGINT UNSIGNED DEFAULT NULL,
    contact_id      BIGINT UNSIGNED DEFAULT NULL,
    campaign_id     BIGINT UNSIGNED DEFAULT NULL,
    direction       ENUM('outbound','inbound') NOT NULL DEFAULT 'outbound',
    to_email        VARCHAR(190) NOT NULL,
    from_email      VARCHAR(190) DEFAULT NULL,
    subject         VARCHAR(255) NOT NULL,
    body            MEDIUMTEXT NOT NULL,
    status          ENUM('draft','queued','sent','failed','scheduled') NOT NULL DEFAULT 'draft',
    tracking_token  CHAR(40) DEFAULT NULL,
    opened_at       DATETIME DEFAULT NULL,
    open_count      INT UNSIGNED NOT NULL DEFAULT 0,
    click_count     INT UNSIGNED NOT NULL DEFAULT 0,
    provider_message_id VARCHAR(255) DEFAULT NULL,
    scheduled_at    DATETIME DEFAULT NULL,
    sent_at         DATETIME DEFAULT NULL,
    created_at      DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_email_track (tracking_token),
    KEY idx_email_user (user_id),
    KEY idx_email_customer (customer_id),
    KEY idx_email_status (status),
    CONSTRAINT fk_email_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL,
    CONSTRAINT fk_email_customer FOREIGN KEY (customer_id) REFERENCES customers (id) ON DELETE SET NULL,
    CONSTRAINT fk_email_contact FOREIGN KEY (contact_id) REFERENCES contacts (id) ON DELETE SET NULL,
    CONSTRAINT fk_email_campaign FOREIGN KEY (campaign_id) REFERENCES campaigns (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE email_events (
    id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    message_id  BIGINT UNSIGNED NOT NULL,
    type        ENUM('open','click') NOT NULL,
    url         VARCHAR(500) DEFAULT NULL,
    ip_address  VARCHAR(45) DEFAULT NULL,
    created_at  DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY idx_eevents_message (message_id),
    CONSTRAINT fk_eevents_message FOREIGN KEY (message_id) REFERENCES email_messages (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
--  Notifications, push subscriptions, chat, webhooks, settings, tasks
-- -----------------------------------------------------------------------------

CREATE TABLE notifications (
    id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id     BIGINT UNSIGNED NOT NULL,
    type        VARCHAR(60) NOT NULL,
    title       VARCHAR(190) NOT NULL,
    body        VARCHAR(500) DEFAULT NULL,
    link        VARCHAR(255) DEFAULT NULL,
    icon        VARCHAR(60) DEFAULT NULL,
    read_at     DATETIME DEFAULT NULL,
    created_at  DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY idx_notif_user (user_id, read_at),
    CONSTRAINT fk_notif_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE push_subscriptions (
    id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id     BIGINT UNSIGNED NOT NULL,
    endpoint    VARCHAR(500) NOT NULL,
    p256dh      VARCHAR(255) NOT NULL,
    auth        VARCHAR(255) NOT NULL,
    created_at  DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_push_endpoint (endpoint),
    KEY idx_push_user (user_id),
    CONSTRAINT fk_push_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE chat_channels (
    id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name        VARCHAR(120) DEFAULT NULL,
    is_direct   TINYINT(1) NOT NULL DEFAULT 0,
    created_at  DATETIME NOT NULL,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE chat_members (
    channel_id  BIGINT UNSIGNED NOT NULL,
    user_id     BIGINT UNSIGNED NOT NULL,
    last_read_at DATETIME DEFAULT NULL,
    PRIMARY KEY (channel_id, user_id),
    CONSTRAINT fk_cm_channel FOREIGN KEY (channel_id) REFERENCES chat_channels (id) ON DELETE CASCADE,
    CONSTRAINT fk_cm_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE chat_messages (
    id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    channel_id  BIGINT UNSIGNED NOT NULL,
    user_id     BIGINT UNSIGNED NOT NULL,
    body        TEXT DEFAULT NULL,
    attachment  VARCHAR(255) DEFAULT NULL,
    created_at  DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY idx_chatmsg_channel (channel_id, created_at),
    CONSTRAINT fk_chatmsg_channel FOREIGN KEY (channel_id) REFERENCES chat_channels (id) ON DELETE CASCADE,
    CONSTRAINT fk_chatmsg_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE webhooks (
    id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id     BIGINT UNSIGNED DEFAULT NULL,
    url         VARCHAR(500) NOT NULL,
    event       VARCHAR(80) NOT NULL,
    secret      VARCHAR(120) DEFAULT NULL,
    active      TINYINT(1) NOT NULL DEFAULT 1,
    created_at  DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY idx_webhooks_event (event),
    CONSTRAINT fk_webhooks_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE tasks (
    id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id     BIGINT UNSIGNED NOT NULL,
    customer_id BIGINT UNSIGNED DEFAULT NULL,
    title       VARCHAR(190) NOT NULL,
    description TEXT DEFAULT NULL,
    due_at      DATETIME DEFAULT NULL,
    priority    ENUM('low','medium','high') NOT NULL DEFAULT 'medium',
    status      ENUM('open','done') NOT NULL DEFAULT 'open',
    completed_at DATETIME DEFAULT NULL,
    created_at  DATETIME NOT NULL,
    updated_at  DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY idx_tasks_user (user_id, status),
    KEY idx_tasks_due (due_at),
    CONSTRAINT fk_tasks_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT fk_tasks_customer FOREIGN KEY (customer_id) REFERENCES customers (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE settings (
    setting_key VARCHAR(80) NOT NULL,
    value       TEXT DEFAULT NULL,
    updated_at  DATETIME NOT NULL,
    PRIMARY KEY (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
