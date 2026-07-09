-- mysql-init/01-schema.sql
--
-- Environment-agnostic schema. Do NOT hardcode a database name here 

CREATE TABLE IF NOT EXISTS page_views (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    visited_at DATETIME NOT NULL,
    session_id CHAR(36) DEFAULT NULL,
    path VARCHAR(255) NOT NULL,
    referrer VARCHAR(512) DEFAULT NULL,
    anon_ip VARCHAR(32) DEFAULT NULL,
    country CHAR(2) DEFAULT NULL,
    device_type ENUM('desktop', 'mobile', 'tablet', 'other') DEFAULT 'other',
    browser VARCHAR(50) DEFAULT NULL,
    os VARCHAR(50) DEFAULT NULL,
    viewport_width INT UNSIGNED DEFAULT NULL,
    viewport_height INT UNSIGNED DEFAULT NULL,
    load_time_ms INT UNSIGNED DEFAULT NULL,
    js_enabled TINYINT(1) DEFAULT 1,
    INDEX idx_session_id (session_id),
    INDEX idx_visited_at (visited_at),
    INDEX idx_path (path),
    INDEX idx_anon_ip (anon_ip),
    INDEX idx_country (country)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS click_events (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    occurred_at DATETIME NOT NULL,
    session_id CHAR(36) DEFAULT NULL,
    page_view_id INT UNSIGNED DEFAULT NULL,
    path VARCHAR(255) NOT NULL,
    event_type VARCHAR(50) NOT NULL,       
    element_id VARCHAR(100) DEFAULT NULL,  
    label VARCHAR(255) DEFAULT NULL,       
    extra JSON DEFAULT NULL,               
    FOREIGN KEY (page_view_id)
      REFERENCES page_views(id)
      ON DELETE SET NULL,
    INDEX idx_session_id (session_id),
    INDEX idx_event_type (event_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS release_library (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  release_id VARCHAR(10) NOT NULL,
  safety_signature CHAR(36) DEFAULT NULL,
  release_date DATETIME DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS api_requests (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  requested_at DATETIME NOT NULL,
  path VARCHAR(255) NOT NULL,
  anon_ip VARCHAR(32) DEFAULT NULL,
  client_type ENUM('browser', 'application', 'other') DEFAULT 'other',
  version VARCHAR(10) DEFAULT NULL,
  distribution VARCHAR(10) DEFAULT NULL,
  trigger_type ENUM('auto_start', 'user_start', 'update', 'sponsorship', 'not specified') NOT NULL DEFAULT 'not specified'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS changelog (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    version         VARCHAR(10)  NOT NULL,
    version_major   TINYINT UNSIGNED NOT NULL,
    version_minor   TINYINT UNSIGNED NOT NULL,
    version_patch   TINYINT UNSIGNED NOT NULL,
    release_date    DATE         NOT NULL,
    change_type     ENUM('new', 'fix', 'change', 'breaking', 'security', 'info') NOT NULL DEFAULT 'new',
    summary         VARCHAR(255) NOT NULL,
    detail          TEXT         DEFAULT NULL,
    is_published    TINYINT(1)   NOT NULL DEFAULT 1,
    INDEX idx_semver      (version_major, version_minor, version_patch),
    INDEX idx_release_date (release_date),
    INDEX idx_published   (is_published)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
ALTER TABLE changelog
    MODIFY COLUMN is_published BOOLEAN NOT NULL DEFAULT 1;

CREATE TABLE IF NOT EXISTS sponsorship (
		id              	INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    publication_date  DATE         NOT NULL,
    service          	TEXT         DEFAULT NULL,
    link          		TEXT         DEFAULT NULL
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Opt-in, two-tier app telemetry. Identity always gets a row (Basic or Full both write it);
CREATE TABLE IF NOT EXISTS telemetry_daily_active (
    day            DATE NOT NULL,
    user_id        CHAR(32) NOT NULL,
    level          ENUM('basic', 'full') NOT NULL DEFAULT 'basic',
    app_version    VARCHAR(20) NOT NULL,
    distribution   VARCHAR(10) NOT NULL,
    os_version     VARCHAR(100) NOT NULL,
    language       VARCHAR(10) NOT NULL,
    total_input_events   BIGINT UNSIGNED NOT NULL DEFAULT 0,
    total_uptime_seconds BIGINT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (day, user_id),
    INDEX idx_day (day)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS telemetry_feature_daily (
    day                          DATE NOT NULL,
    user_id                      CHAR(32) NOT NULL,
    grid_rows                    SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    grid_columns                 SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    colors_customized            TINYINT(1) NOT NULL DEFAULT 0,
    fonts_customized             TINYINT(1) NOT NULL DEFAULT 0,
    rainbow_background_enabled   TINYINT(1) NOT NULL DEFAULT 0,
    rainbow_speed_seconds        SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    panels_configured_count      INT UNSIGNED NOT NULL DEFAULT 0,
    panels_using_key_input       INT UNSIGNED NOT NULL DEFAULT 0,
    panels_using_mouse_input     INT UNSIGNED NOT NULL DEFAULT 0,
    panels_using_wheel_input     INT UNSIGNED NOT NULL DEFAULT 0,
    panels_using_gamepad_input   INT UNSIGNED NOT NULL DEFAULT 0,
    gamepad_trigger_threshold    SMALLINT UNSIGNED NOT NULL DEFAULT 25,
    autostart_enabled            TINYINT(1) NOT NULL DEFAULT 0,
    start_minimized              TINYINT(1) NOT NULL DEFAULT 0,
    shortcuts_customized         TINYINT(1) NOT NULL DEFAULT 0,
    tutorial_completed           TINYINT(1) NOT NULL DEFAULT 0,
    total_key_presses            BIGINT UNSIGNED NOT NULL DEFAULT 0,
    total_mouse_clicks           BIGINT UNSIGNED NOT NULL DEFAULT 0,
    total_wheel_ticks            BIGINT UNSIGNED NOT NULL DEFAULT 0,
    total_gamepad_presses        BIGINT UNSIGNED NOT NULL DEFAULT 0,
    total_uptime_seconds         BIGINT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (day, user_id),
    CONSTRAINT fk_telemetry_feature_daily_identity
        FOREIGN KEY (day, user_id)
        REFERENCES telemetry_daily_active (day, user_id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS messages (
    id                   INT UNSIGNED NOT NULL AUTO_INCREMENT,
    type                 ENUM('announcement','news') NOT NULL DEFAULT 'announcement',
    title                VARCHAR(200)  NOT NULL,
    body                 TEXT          NOT NULL,
    is_published         TINYINT UNSIGNED	NOT NULL DEFAULT 0,
    publish_at           DATETIME      NOT NULL,
    expires_at           DATETIME      NULL DEFAULT NULL,
    target_distributions SET('store','github','dev') NULL DEFAULT NULL,

    version_min_major    INT UNSIGNED  NULL DEFAULT NULL,
    version_min_minor    INT UNSIGNED  NULL DEFAULT NULL,
    version_min_patch    INT UNSIGNED  NULL DEFAULT NULL,

    version_max_major    INT UNSIGNED  NULL DEFAULT NULL,
    version_max_minor    INT UNSIGNED  NULL DEFAULT NULL,
    version_max_patch    INT UNSIGNED  NULL DEFAULT NULL,

    link_label           VARCHAR(100)  NULL DEFAULT NULL,
    link_url             VARCHAR(500)  NULL DEFAULT NULL,

    created_at           TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    KEY idx_delivery (is_published, publish_at),
    KEY idx_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS web_path (
	path VARCHAR(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;