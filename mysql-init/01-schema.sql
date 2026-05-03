-- mysql-init/01-schema.sql

CREATE DATABASE IF NOT EXISTS clickykeys
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE clickykeys;

CREATE TABLE IF NOT EXISTS page_views (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    visited_at DATETIME NOT NULL,
    session_id CHAR(36) DEFAULT NULL,
    path VARCHAR(255) NOT NULL,
    referrer VARCHAR(512) DEFAULT NULL,
    anon_ip VARCHAR(32) DEFAULT NULL,
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
    INDEX idx_anon_ip (anon_ip)
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
  distribution VARCHAR(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
