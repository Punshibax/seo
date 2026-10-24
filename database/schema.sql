-- Select your application database before importing this file.
CREATE TABLE IF NOT EXISTS users (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(254) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS experts (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(254) NOT NULL,
    specialty VARCHAR(100) NOT NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_experts_active (active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS leads (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NULL,
    expert_id BIGINT UNSIGNED NULL,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(254) NOT NULL,
    interest VARCHAR(80) NOT NULL,
    message TEXT NULL,
    status ENUM('new', 'contacted') NOT NULL DEFAULT 'new',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_leads_user (user_id),
    KEY idx_leads_expert (expert_id),
    KEY idx_leads_created_at (created_at),
    KEY idx_leads_status (status),
    KEY idx_leads_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add these columns when upgrading an earlier installation of the site.
SET @needs_user_id = (SELECT COUNT(*) = 0 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'leads' AND COLUMN_NAME = 'user_id');
SET @sql = IF(@needs_user_id, 'ALTER TABLE leads ADD COLUMN user_id BIGINT UNSIGNED NULL AFTER id, ADD KEY idx_leads_user (user_id)', 'SELECT 1');
PREPARE add_user_id FROM @sql; EXECUTE add_user_id; DEALLOCATE PREPARE add_user_id;
SET @needs_expert_id = (SELECT COUNT(*) = 0 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'leads' AND COLUMN_NAME = 'expert_id');
SET @sql = IF(@needs_expert_id, 'ALTER TABLE leads ADD COLUMN expert_id BIGINT UNSIGNED NULL AFTER user_id, ADD KEY idx_leads_expert (expert_id)', 'SELECT 1');
PREPARE add_expert_id FROM @sql; EXECUTE add_expert_id; DEALLOCATE PREPARE add_expert_id;

CREATE TABLE IF NOT EXISTS lead_messages (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    lead_id BIGINT UNSIGNED NOT NULL,
    sender_type ENUM('student', 'admin') NOT NULL,
    body TEXT NOT NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_messages_lead (lead_id, created_at),
    KEY idx_messages_unread (sender_type, is_read)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS counselling_slots (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    expert_id BIGINT UNSIGNED NULL,
    starts_at DATETIME NOT NULL,
    status ENUM('available', 'booked') NOT NULL DEFAULT 'available',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_slot_expert_time (expert_id, starts_at),
    KEY idx_slots_available (status, starts_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS bookings (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NULL,
    expert_id BIGINT UNSIGNED NULL,
    slot_id BIGINT UNSIGNED NULL,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(254) NOT NULL,
    interest VARCHAR(80) NOT NULL,
    preferred_at DATETIME NOT NULL,
    note VARCHAR(1000) NULL,
    status ENUM('pending', 'confirmed', 'completed', 'cancelled') NOT NULL DEFAULT 'pending',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_bookings_slot (slot_id),
    KEY idx_bookings_user (user_id),
    KEY idx_bookings_expert (expert_id),
    KEY idx_bookings_preferred (preferred_at),
    KEY idx_bookings_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET @needs_slot_id = (SELECT COUNT(*) = 0 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'bookings' AND COLUMN_NAME = 'slot_id');
SET @sql = IF(@needs_slot_id, 'ALTER TABLE bookings ADD COLUMN slot_id BIGINT UNSIGNED NULL AFTER expert_id, ADD KEY idx_bookings_slot (slot_id)', 'SELECT 1');
PREPARE add_slot_id FROM @sql; EXECUTE add_slot_id; DEALLOCATE PREPARE add_slot_id;

CREATE TABLE IF NOT EXISTS app_settings (
    setting_key VARCHAR(100) NOT NULL,
    setting_value MEDIUMTEXT NOT NULL,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS blog_posts (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    title VARCHAR(200) NOT NULL,
    slug VARCHAR(220) NOT NULL,
    excerpt VARCHAR(500) NOT NULL DEFAULT '',
    body MEDIUMTEXT NOT NULL,
    seo_title VARCHAR(200) NOT NULL DEFAULT '',
    seo_description VARCHAR(320) NOT NULL DEFAULT '',
    seo_keywords VARCHAR(500) NOT NULL DEFAULT '',
    robots VARCHAR(40) NOT NULL DEFAULT 'index,follow',
    status ENUM('draft', 'published') NOT NULL DEFAULT 'draft',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    published_at DATETIME NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_blog_slug (slug),
    KEY idx_blog_status_date (status, published_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS video_testimonials (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    student_name VARCHAR(120) NOT NULL,
    story_title VARCHAR(180) NOT NULL,
    video_url VARCHAR(500) NOT NULL,
    quote VARCHAR(500) NOT NULL DEFAULT '',
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_video_active (active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS admins (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    email VARCHAR(254) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_admins_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO app_settings (setting_key, setting_value) VALUES
('site_url', ''),
('seo_title', 'You Aspirant — Career guidance that starts with you'),
('seo_description', 'Explore career guidance and practical training with You Aspirant. Get support to understand your strengths, build useful skills, and choose a next step that feels right for you.'),
('seo_keywords', 'career guidance, career counselling, student training, career assessment'),
('robots_directive', 'index,follow'),
('whatsapp_number', ''),
('google_site_verification', ''),
('google_analytics_id', ''),
('ad_head_code', ''),
('ad_body_code', '');
