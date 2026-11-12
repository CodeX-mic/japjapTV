-- ---------------------------------------------------------------------------
-- japjapTV — database schema and seed catalogue
-- Database: japjaptv_db
--
-- Import with: mysql -u root japjaptv_db < database.sql
-- or paste this file into phpMyAdmin while japjaptv_db is selected.
--
-- Catalogue verified: 12 November 2026 (UTC). Re-run bin/check_sources.php
-- after installation to refresh live status.
-- ---------------------------------------------------------------------------

SET NAMES utf8mb4;
SET time_zone = '+00:00';
CREATE DATABASE IF NOT EXISTS japjaptv_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE japjaptv_db;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS source_checks;
DROP TABLE IF EXISTS login_attempts;
DROP TABLE IF EXISTS contact_messages;
DROP TABLE IF EXISTS nba_games;
DROP TABLE IF EXISTS featured_channels;
DROP TABLE IF EXISTS channel_sources;
DROP TABLE IF EXISTS channels;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS countries;
DROP TABLE IF EXISTS admins;

SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE admins (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    username VARCHAR(60) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    display_name VARCHAR(100) NOT NULL,
    email VARCHAR(190) DEFAULT NULL,
    must_change_password TINYINT(1) NOT NULL DEFAULT 1,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    last_login_at DATETIME DEFAULT NULL,
    last_login_ip VARCHAR(45) DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_admins_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE countries (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    country_name VARCHAR(100) NOT NULL,
    country_code CHAR(2) NOT NULL,
    flag_emoji VARCHAR(16) DEFAULT NULL,
    region VARCHAR(60) NOT NULL DEFAULT 'International',
    display_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_countries_code (country_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE categories (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    category_name VARCHAR(80) NOT NULL,
    category_slug VARCHAR(80) NOT NULL,
    description VARCHAR(255) DEFAULT NULL,
    icon VARCHAR(60) NOT NULL DEFAULT 'fa-tv',
    color VARCHAR(7) NOT NULL DEFAULT '#ff3b47',
    display_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_categories_slug (category_slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE channels (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    channel_name VARCHAR(120) NOT NULL,
    slug VARCHAR(140) NOT NULL,
    logo VARCHAR(255) DEFAULT NULL,
    description TEXT NOT NULL,
    country_id INT UNSIGNED NOT NULL,
    category_id INT UNSIGNED NOT NULL,
    official_url VARCHAR(1000) NOT NULL,
    access_type ENUM('free','subscription','unknown') NOT NULL DEFAULT 'unknown',
    availability_status ENUM('available','external_only','unavailable','coming_soon') NOT NULL DEFAULT 'external_only',
    region ENUM('ph','international') NOT NULL DEFAULT 'international',
    language VARCHAR(80) NOT NULL DEFAULT 'English',
    accent_color VARCHAR(7) NOT NULL DEFAULT '#ff3b47',
    notice VARCHAR(255) DEFAULT NULL,
    is_featured TINYINT(1) NOT NULL DEFAULT 0,
    display_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    view_count INT UNSIGNED NOT NULL DEFAULT 0,
    last_verified_at DATETIME DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_channels_slug (slug),
    KEY idx_channels_country (country_id),
    KEY idx_channels_category (category_id),
    KEY idx_channels_availability (availability_status),
    KEY idx_channels_display (is_featured, display_order),
    CONSTRAINT fk_channels_country FOREIGN KEY (country_id) REFERENCES countries (id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_channels_category FOREIGN KEY (category_id) REFERENCES categories (id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE channel_sources (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    channel_id INT UNSIGNED NOT NULL,
    source_type ENUM('youtube_live','youtube_video','hls','iframe','external') NOT NULL,
    label VARCHAR(120) NOT NULL,
    source_url VARCHAR(1000) NOT NULL,
    embed_url VARCHAR(1000) DEFAULT NULL,
    provider_ref VARCHAR(190) DEFAULT NULL,
    priority TINYINT UNSIGNED NOT NULL DEFAULT 1,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    is_authorized TINYINT(1) NOT NULL DEFAULT 0,
    last_checked DATETIME DEFAULT NULL,
    last_verified DATETIME DEFAULT NULL,
    last_check_status ENUM('live','reachable','offline','error','unverified','unknown') NOT NULL DEFAULT 'unknown',
    last_check_message VARCHAR(255) DEFAULT NULL,
    last_http_code SMALLINT UNSIGNED DEFAULT NULL,
    consecutive_failures TINYINT UNSIGNED NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_sources_channel (channel_id, priority),
    KEY idx_sources_status (last_check_status),
    KEY idx_sources_active (is_active, is_authorized),
    CONSTRAINT fk_sources_channel FOREIGN KEY (channel_id) REFERENCES channels (id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE featured_channels (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    channel_id INT UNSIGNED NOT NULL,
    display_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_featured_channel (channel_id),
    KEY idx_featured_order (display_order),
    CONSTRAINT fk_featured_channel FOREIGN KEY (channel_id) REFERENCES channels (id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE nba_games (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    espn_game_id VARCHAR(40) DEFAULT NULL,
    game_date DATETIME NOT NULL,
    home_team VARCHAR(100) NOT NULL,
    away_team VARCHAR(100) NOT NULL,
    home_abbr VARCHAR(10) DEFAULT NULL,
    away_abbr VARCHAR(10) DEFAULT NULL,
    home_score SMALLINT DEFAULT NULL,
    away_score SMALLINT DEFAULT NULL,
    game_status ENUM('scheduled','live','final','postponed') NOT NULL DEFAULT 'scheduled',
    status_detail VARCHAR(100) DEFAULT NULL,
    venue VARCHAR(150) DEFAULT NULL,
    broadcast VARCHAR(150) DEFAULT NULL,
    official_url VARCHAR(1000) DEFAULT NULL,
    notes VARCHAR(255) DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_nba_date (game_date),
    KEY idx_nba_status (game_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE contact_messages (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(190) NOT NULL,
    subject VARCHAR(150) NOT NULL,
    message TEXT NOT NULL,
    status ENUM('new','read','replied','archived') NOT NULL DEFAULT 'new',
    ip_hash CHAR(64) DEFAULT NULL,
    user_agent VARCHAR(255) DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_messages_status (status, created_at),
    KEY idx_messages_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE source_checks (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    source_id INT UNSIGNED NOT NULL,
    status VARCHAR(20) NOT NULL,
    http_code SMALLINT UNSIGNED DEFAULT NULL,
    message VARCHAR(255) DEFAULT NULL,
    checked_by INT UNSIGNED DEFAULT NULL,
    checked_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_checks_source (source_id, checked_at),
    CONSTRAINT fk_checks_source FOREIGN KEY (source_id) REFERENCES channel_sources (id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_checks_admin FOREIGN KEY (checked_by) REFERENCES admins (id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE login_attempts (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    username VARCHAR(60) NOT NULL,
    ip_hash CHAR(64) NOT NULL,
    success TINYINT(1) NOT NULL DEFAULT 0,
    attempted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_attempt_username (username, attempted_at),
    KEY idx_attempt_ip (ip_hash, attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Default administrator. The password must be changed on first login.
INSERT INTO admins (id, username, password_hash, display_name, email, must_change_password, is_active, created_at, updated_at) VALUES
    (1, 'admin', '$2y$10$TJKZfj0mGPcNNORsmleNku.tOYgFFmwdb5hDgJN4YQKqiK7ZBOysu', 'Station Manager', NULL, 1, 1, '2026-11-12 00:00:00', '2026-11-12 00:00:00');

-- Countries ----------------------------------------------------------------
INSERT INTO countries (id, country_name, country_code, flag_emoji, region, display_order, created_at) VALUES
    (1, 'Philippines', 'PH', '🇵🇭', 'Southeast Asia', 1, '2026-11-12 00:00:00'),
    (2, 'United States', 'US', '🇺🇸', 'North America', 2, '2026-11-12 00:00:00'),
    (3, 'United Kingdom', 'GB', '🇬🇧', 'Europe', 3, '2026-11-12 00:00:00'),
    (4, 'Qatar', 'QA', '🇶🇦', 'Middle East', 4, '2026-11-12 00:00:00'),
    (5, 'Germany', 'DE', '🇩🇪', 'Europe', 5, '2026-11-12 00:00:00'),
    (6, 'France', 'FR', '🇫🇷', 'Europe', 6, '2026-11-12 00:00:00'),
    (7, 'Japan', 'JP', '🇯🇵', 'East Asia', 7, '2026-11-12 00:00:00'),
    (8, 'Singapore', 'SG', '🇸🇬', 'Southeast Asia', 8, '2026-11-12 00:00:00'),
    (9, 'Türkiye', 'TR', '🇹🇷', 'Europe / Asia', 9, '2026-11-12 00:00:00'),
    (10, 'Australia', 'AU', '🇦🇺', 'Oceania', 10, '2026-11-12 00:00:00'),
    (11, 'Netherlands', 'NL', '🇳🇱', 'Europe', 11, '2026-11-12 00:00:00');

-- Categories ---------------------------------------------------------------
INSERT INTO categories (id, category_name, category_slug, description, icon, color, display_order, created_at) VALUES
    (1, 'News', 'news', 'Rolling news, current affairs, and live coverage from official broadcasters.', 'fa-newspaper', '#3b82f6', 1, '2026-11-12 00:00:00'),
    (2, 'Entertainment', 'entertainment', 'General entertainment, variety shows, dramas, and network livestreams.', 'fa-masks-theater', '#ff3b47', 2, '2026-11-12 00:00:00'),
    (3, 'Sports', 'sports', 'Official sports destinations and authorized sports channels.', 'fa-trophy', '#f59e0b', 3, '2026-11-12 00:00:00'),
    (4, 'Kids', 'kids', 'Family-safe children’s programming and official kids destinations.', 'fa-child-reaching', '#22c55e', 4, '2026-11-12 00:00:00'),
    (5, 'Music', 'music', 'Official music livestreams, radio-style channels, and visual sets.', 'fa-music', '#a855f7', 5, '2026-11-12 00:00:00'),
    (6, 'Movies', 'movies', 'Movie channels and official film destinations.', 'fa-film', '#ec4899', 6, '2026-11-12 00:00:00'),
    (7, 'Documentary', 'documentary', 'Science, space, nature, history, and factual programming.', 'fa-earth-americas', '#14b8a6', 7, '2026-11-12 00:00:00'),
    (8, 'Business', 'business', 'Markets, finance, technology, and business news.', 'fa-chart-line', '#06b6d4', 8, '2026-11-12 00:00:00'),
    (9, 'Lifestyle', 'lifestyle', 'Food, home, travel, and everyday lifestyle programming.', 'fa-heart', '#f97316', 9, '2026-11-12 00:00:00'),
    (10, 'Education', 'education', 'Learning resources and educational broadcasters.', 'fa-graduation-cap', '#8b5cf6', 10, '2026-11-12 00:00:00'),
    (11, 'Religion', 'religion', 'Official faith-based broadcast destinations.', 'fa-hands-praying', '#eab308', 11, '2026-11-12 00:00:00');

-- Channels ----------------------------------------------------------------
INSERT INTO channels (id, channel_name, slug, logo, description, country_id, category_id, official_url, access_type, availability_status, region, language, accent_color, notice, is_featured, display_order, view_count, last_verified_at, created_at, updated_at) VALUES
    (1, 'GMA Network', 'gma-network', 'assets/images/logos/gma-network.svg', 'The flagship free-to-air network of GMA Network, Inc., with news, entertainment, and public affairs programming. Its official YouTube channel carries a 24/7 livestream.', 1, 2, 'https://www.gmanetwork.com/', 'free', 'available', 'ph', 'Filipino / English', '#e50914', NULL, 1, 1, 0, '2026-11-12 00:00:00', '2026-11-12 00:00:00', '2026-11-12 00:00:00'),
    (2, 'TV5', 'tv5', 'assets/images/logos/tv5.svg', 'The flagship channel of TV5 Network, Inc., offering news, entertainment, and sports. The official TV5 YouTube channel operates a 24/7 livestream.', 1, 2, 'https://www.tv5.com.ph/', 'free', 'available', 'ph', 'Filipino / English', '#0ea5e9', NULL, 1, 2, 0, '2026-11-12 00:00:00', '2026-11-12 00:00:00', '2026-11-12 00:00:00'),
    (3, 'PTV 4', 'ptv-4', 'assets/images/logos/ptv-4.svg', 'The Philippines’ state broadcaster, PTV 4, airs national news, public affairs, and government coverage. Its official YouTube channel carries a 24/7 livestream.', 1, 1, 'https://ptvnews.ph/', 'free', 'available', 'ph', 'Filipino / English', '#f59e0b', NULL, 1, 3, 0, '2026-11-12 00:00:00', '2026-11-12 00:00:00', '2026-11-12 00:00:00'),
    (4, 'IBC 13', 'ibc-13', 'assets/images/logos/ibc-13.svg', 'Intercontinental Broadcasting Corporation’s flagship channel, with entertainment, news, and public service programs. The official YouTube channel carries a 24/7 livestream.', 1, 2, 'https://ibctv13.com/', 'free', 'available', 'ph', 'Filipino / English', '#16a34a', NULL, 0, 4, 0, '2026-11-12 00:00:00', '2026-11-12 00:00:00', '2026-11-12 00:00:00'),
    (5, 'NET25', 'net25', 'assets/images/logos/net25.svg', 'Eagle Broadcasting Corporation’s NET25 delivers news, public affairs, entertainment, and religious programming. Its official YouTube channel carries a 24/7 livestream.', 1, 2, 'https://www.net25.com/', 'free', 'available', 'ph', 'Filipino / English', '#f97316', NULL, 0, 5, 0, '2026-11-12 00:00:00', '2026-11-12 00:00:00', '2026-11-12 00:00:00'),
    (6, 'UNTV', 'untv', 'assets/images/logos/untv.svg', 'UNTV News and Rescue’s public service channel, with news, rescue updates, and entertainment. Its official YouTube channel carries a 24/7 livestream.', 1, 1, 'https://untvweb.com/', 'free', 'available', 'ph', 'Filipino / English', '#0d9488', NULL, 0, 6, 0, '2026-11-12 00:00:00', '2026-11-12 00:00:00', '2026-11-12 00:00:00'),
    (7, 'Kapamilya Online Live', 'kapamilya-online-live', 'assets/images/logos/kapamilya-online-live.svg', 'ABS-CBN’s official online channel featuring Kapamilya entertainment programs plus news and current affairs. The official YouTube channel carries a 24/7 livestream.', 1, 2, 'https://www.youtube.com/channel/UCstEtN0pgOmCf02EdXsGChw', 'free', 'available', 'ph', 'Filipino / English', '#ef4444', NULL, 1, 7, 0, '2026-11-12 00:00:00', '2026-11-12 00:00:00', '2026-11-12 00:00:00'),
    (8, 'Al Jazeera English', 'al-jazeera-english', 'assets/images/logos/al-jazeera-english.svg', 'Al Jazeera Media Network’s English-language news channel, with rolling coverage and live events. The official YouTube channel carries a 24/7 livestream.', 4, 1, 'https://www.aljazeera.com/video/live', 'free', 'available', 'international', 'English', '#d4a13a', NULL, 1, 8, 0, '2026-11-12 00:00:00', '2026-11-12 00:00:00', '2026-11-12 00:00:00'),
    (9, 'BBC News', 'bbc-news', 'assets/images/logos/bbc-news.svg', 'The BBC’s rolling news service, covering UK and global stories. The official BBC News YouTube channel operates a 24/7 livestream.', 3, 1, 'https://www.bbc.com/news', 'free', 'available', 'international', 'English', '#bb1919', NULL, 1, 9, 0, '2026-11-12 00:00:00', '2026-11-12 00:00:00', '2026-11-12 00:00:00'),
    (10, 'DW English', 'dw-english', 'assets/images/logos/dw-english.svg', 'Deutsche Welle’s English-language news and information channel. Its official YouTube channel carries a 24/7 livestream.', 5, 1, 'https://www.dw.com/en/live-tv/channel-english', 'free', 'available', 'international', 'English', '#0b5cad', NULL, 0, 10, 0, '2026-11-12 00:00:00', '2026-11-12 00:00:00', '2026-11-12 00:00:00'),
    (11, 'France 24 English', 'france-24-english', 'assets/images/logos/france-24-english.svg', 'France Médias Monde’s English-language international news channel. The official France 24 English YouTube channel carries a 24/7 livestream.', 6, 1, 'https://www.france24.com/en/live', 'free', 'available', 'international', 'English', '#0ea5e9', NULL, 0, 11, 0, '2026-11-12 00:00:00', '2026-11-12 00:00:00', '2026-11-12 00:00:00'),
    (12, 'Sky News', 'sky-news', 'assets/images/logos/sky-news.svg', 'Sky’s 24-hour UK and international news service. The official Sky News YouTube channel carries a live feed.', 3, 1, 'https://news.sky.com/watch-live', 'free', 'available', 'international', 'English', '#0072c9', NULL, 0, 12, 0, '2026-11-12 00:00:00', '2026-11-12 00:00:00', '2026-11-12 00:00:00'),
    (13, 'NHK WORLD-JAPAN', 'nhk-world-japan', 'assets/images/logos/nhk-world-japan.svg', 'NHK’s English-language international service, with news, culture, and documentaries from Japan. The official YouTube channel carries a live feed.', 7, 1, 'https://www3.nhk.or.jp/nhkworld/en/live/', 'free', 'available', 'international', 'English', '#003399', NULL, 0, 13, 0, '2026-11-12 00:00:00', '2026-11-12 00:00:00', '2026-11-12 00:00:00'),
    (14, 'Bloomberg Television', 'bloomberg-television', 'assets/images/logos/bloomberg-television.svg', 'Bloomberg’s business and markets television service, with global finance coverage. The official Bloomberg YouTube channel carries a live feed.', 2, 8, 'https://www.bloomberg.com/live', 'free', 'available', 'international', 'English', '#111827', NULL, 0, 14, 0, '2026-11-12 00:00:00', '2026-11-12 00:00:00', '2026-11-12 00:00:00'),
    (15, 'CNBC Marathon', 'cnbc-marathon', 'assets/images/logos/cnbc-marathon.svg', 'CNBC’s YouTube channel featuring full-length business documentaries and marathon episodes. This is a supplementary official channel, not a substitute for the CNBC television feed.', 2, 8, 'https://www.cnbc.com/', 'free', 'available', 'international', 'English', '#005594', 'Supplemental CNBC YouTube programming. It is not the live CNBC television feed.', 0, 15, 0, '2026-11-12 00:00:00', '2026-11-12 00:00:00', '2026-11-12 00:00:00'),
    (16, 'Euronews English', 'euronews-english', 'assets/images/logos/euronews-english.svg', 'Euronews’ English-language European and world news service. The official Euronews YouTube channel carries a live feed.', 6, 1, 'https://www.euronews.com/live', 'free', 'available', 'international', 'English', '#003399', NULL, 0, 16, 0, '2026-11-12 00:00:00', '2026-11-12 00:00:00', '2026-11-12 00:00:00'),
    (17, 'ABC News', 'abc-news', 'assets/images/logos/abc-news.svg', 'ABC News’ streaming news coverage from the United States. The official ABC News YouTube channel carries a live feed.', 2, 1, 'https://abcnews.go.com/live', 'free', 'available', 'international', 'English', '#111827', NULL, 0, 17, 0, '2026-11-12 00:00:00', '2026-11-12 00:00:00', '2026-11-12 00:00:00'),
    (18, 'NBC News NOW', 'nbc-news-now', 'assets/images/logos/nbc-news-now.svg', 'NBC News’ streaming news service, with breaking stories and live coverage. The official NBC News YouTube channel carries a live feed.', 2, 1, 'https://www.nbcnews.com/now', 'free', 'available', 'international', 'English', '#5b21b6', NULL, 0, 18, 0, '2026-11-12 00:00:00', '2026-11-12 00:00:00', '2026-11-12 00:00:00'),
    (19, 'CBS News', 'cbs-news', 'assets/images/logos/cbs-news.svg', 'CBS News’ live and breaking news coverage from the United States. The official CBS News YouTube channel carries a live feed.', 2, 1, 'https://www.cbsnews.com/live/', 'free', 'available', 'international', 'English', '#1e40af', NULL, 0, 19, 0, '2026-11-12 00:00:00', '2026-11-12 00:00:00', '2026-11-12 00:00:00'),
    (20, 'CNA', 'cna', 'assets/images/logos/cna.svg', 'Channel NewsAsia’s English-language news service from Singapore, covering Asia and the world. The official CNA YouTube channel carries a live feed.', 8, 1, 'https://www.channelnewsasia.com/watch', 'free', 'available', 'international', 'English', '#b91c1c', NULL, 0, 20, 0, '2026-11-12 00:00:00', '2026-11-12 00:00:00', '2026-11-12 00:00:00'),
    (21, 'TRT World', 'trt-world', 'assets/images/logos/trt-world.svg', 'Türkiye’s international English-language news channel, with global reporting and live coverage. The official TRT World YouTube channel carries a live feed.', 9, 1, 'https://www.trtworld.com/', 'free', 'available', 'international', 'English', '#c8102e', NULL, 0, 21, 0, '2026-11-12 00:00:00', '2026-11-12 00:00:00', '2026-11-12 00:00:00'),
    (22, 'Scripps News', 'scripps-news', 'assets/images/logos/scripps-news.svg', 'Scripps News is a US news network with national and international coverage. The official Scripps News YouTube channel carries a live feed.', 2, 1, 'https://www.scrippsnews.com/live', 'free', 'available', 'international', 'English', '#0f766e', NULL, 0, 22, 0, '2026-11-12 00:00:00', '2026-11-12 00:00:00', '2026-11-12 00:00:00'),
    (23, 'ABC Australia', 'abc-australia', 'assets/images/logos/abc-australia.svg', 'The Australian Broadcasting Corporation’s news service, covering Australia, Asia, and the Pacific. The official ABC News Australia YouTube channel carries a live feed.', 10, 1, 'https://www.abc.net.au/news', 'free', 'available', 'international', 'English', '#1d4ed8', NULL, 0, 23, 0, '2026-11-12 00:00:00', '2026-11-12 00:00:00', '2026-11-12 00:00:00'),
    (24, 'NASA TV', 'nasa-tv', 'assets/images/logos/nasa-tv.svg', 'NASA’s official public affairs and mission coverage, including launches, spacewalks, and live events. The official NASA YouTube channel operates a live feed.', 2, 7, 'https://www.nasa.gov/live/', 'free', 'available', 'international', 'English', '#0b3d91', NULL, 1, 24, 0, '2026-11-12 00:00:00', '2026-11-12 00:00:00', '2026-11-12 00:00:00'),
    (25, 'Lofi Girl', 'lofi-girl', 'assets/images/logos/lofi-girl.svg', 'Lofi Girl’s official 24/7 lofi hip hop radio stream, curated for studying, relaxing, and focus. The official YouTube channel carries the livestream.', 6, 5, 'https://www.youtube.com/@LofiGirl', 'free', 'available', 'international', 'Instrumental', '#f472b6', NULL, 1, 25, 0, '2026-11-12 00:00:00', '2026-11-12 00:00:00', '2026-11-12 00:00:00'),
    (26, 'Chillhop Music', 'chillhop-music', 'assets/images/logos/chillhop-music.svg', 'Chillhop Music’s official 24/7 radio stream, featuring instrumental hip hop and electronic beats. The official YouTube channel carries the livestream.', 11, 5, 'https://www.youtube.com/@ChillhopMusic', 'free', 'available', 'international', 'Instrumental', '#f59e0b', NULL, 0, 26, 0, '2026-11-12 00:00:00', '2026-11-12 00:00:00', '2026-11-12 00:00:00'),
    (27, 'PBS KIDS', 'pbs-kids', 'assets/images/logos/pbs-kids.svg', 'PBS KIDS’ official children’s channel, with full episodes, clips, and learning-focused programming. The official YouTube channel carries a live feed.', 2, 4, 'https://pbskids.org/', 'free', 'available', 'international', 'English', '#22c55e', NULL, 0, 27, 0, '2026-11-12 00:00:00', '2026-11-12 00:00:00', '2026-11-12 00:00:00'),
    (28, 'GMA News', 'gma-news', 'assets/images/logos/gma-news.svg', 'GMA Network’s news division publishes reports, clips, and live event coverage on official GMA platforms. A verified 24/7 embeddable channel is not available.', 1, 1, 'https://www.gmanetwork.com/news/', 'free', 'external_only', 'ph', 'Filipino / English', '#dc2626', NULL, 0, 28, 0, NULL, '2026-11-12 00:00:00', '2026-11-12 00:00:00'),
    (29, 'ANC', 'anc', 'assets/images/logos/anc.svg', 'The ABS-CBN News Channel is a Philippine cable news network. Live viewing requires an authorized pay-TV or official streaming subscription.', 1, 1, 'https://news.abs-cbn.com/anc', 'subscription', 'external_only', 'ph', 'English', '#1d4ed8', NULL, 0, 29, 0, NULL, '2026-11-12 00:00:00', '2026-11-12 00:00:00'),
    (30, 'ABS-CBN News', 'abs-cbn-news', 'assets/images/logos/abs-cbn-news.svg', 'ABS-CBN’s news division, publishing reports and live event coverage. The official live page was offline at verification time, so no 24/7 embed is configured.', 1, 1, 'https://news.abs-cbn.com/', 'free', 'external_only', 'ph', 'Filipino / English', '#2563eb', 'The official live page was offline when checked on 12 Nov 2026.', 0, 30, 0, NULL, '2026-11-12 00:00:00', '2026-11-12 00:00:00'),
    (31, 'GTV', 'gtv', 'assets/images/logos/gtv.svg', 'GMA’s free-to-air general entertainment channel. GTV remains active, but no authorized 24/7 embed has been verified. Use the official GTV page for schedules and updates.', 1, 2, 'https://www.gmanetwork.com/gtv/', 'free', 'external_only', 'ph', 'Filipino / English', '#7c3aed', 'GTV is still active but has no verified embeddable 24/7 stream.', 0, 31, 0, NULL, '2026-11-12 00:00:00', '2026-11-12 00:00:00'),
    (32, 'One News', 'one-news', 'assets/images/logos/one-news.svg', 'One News is the news channel of MediaQuest and TV5. The official live page was offline at verification time, so no embed is configured. Confirm availability with the official site.', 1, 1, 'https://www.onenews.ph/', 'unknown', 'external_only', 'ph', 'English', '#f43f5e', 'The official live page was offline when checked on 12 Nov 2026.', 0, 32, 0, NULL, '2026-11-12 00:00:00', '2026-11-12 00:00:00'),
    (33, 'One Sports', 'one-sports', 'assets/images/logos/one-sports.svg', 'One Sports is TV5’s sports channel. Selected coverage is available through official partner platforms; no verified 24/7 embed is configured.', 1, 3, 'https://www.onesports.ph/', 'free', 'external_only', 'ph', 'Filipino / English', '#2563eb', NULL, 0, 33, 0, NULL, '2026-11-12 00:00:00', '2026-11-12 00:00:00'),
    (34, 'RPTV', 'rptv', 'assets/images/logos/rptv.svg', 'RPTV is a Philippine free-to-air news and entertainment channel operated by TV5 and Nine Media. No verified 24/7 embed is available; check the official site.', 1, 1, 'https://www.rptv.ph/', 'free', 'external_only', 'ph', 'Filipino / English', '#dc2626', NULL, 0, 34, 0, NULL, '2026-11-12 00:00:00', '2026-11-12 00:00:00'),
    (35, 'A2Z', 'a2z', 'assets/images/logos/a2z.svg', 'A2Z is a Philippine free-to-air channel from ABS-CBN and ZOE Broadcasting. No verified 24/7 embed is configured; check the official ABS-CBN destination for programming information.', 1, 2, 'https://www.abs-cbn.com/', 'free', 'external_only', 'ph', 'Filipino', '#9333ea', NULL, 0, 35, 0, NULL, '2026-11-12 00:00:00', '2026-11-12 00:00:00'),
    (36, 'SMNI', 'smni', 'assets/images/logos/smni.svg', 'Sonshine Media Network International is a Philippine broadcast network. Official live destinations are not verified for embedding; use the official site.', 1, 1, 'https://www.smni.com/', 'free', 'external_only', 'ph', 'Filipino / English', '#15803d', NULL, 0, 36, 0, NULL, '2026-11-12 00:00:00', '2026-11-12 00:00:00'),
    (37, 'Knowledge Channel', 'knowledge-channel', 'assets/images/logos/knowledge-channel.svg', 'Knowledge Channel Foundation’s educational channel offers learning resources and selected videos on official platforms. No verified 24/7 embed is configured.', 1, 10, 'https://www.knowledgechannel.org/', 'free', 'external_only', 'ph', 'Filipino / English', '#0891b2', NULL, 0, 37, 0, NULL, '2026-11-12 00:00:00', '2026-11-12 00:00:00'),
    (38, 'Heart of Asia', 'heart-of-asia', 'assets/images/logos/heart-of-asia.svg', 'GMA’s international channel featuring Asian dramas and entertainment. It is a pay-TV channel; use the official page for carriage and availability.', 1, 2, 'https://www.gmanetwork.com/entertainment/tv/heart_of_asia_channel/', 'subscription', 'external_only', 'ph', 'Filipino / English', '#db2777', NULL, 0, 38, 0, NULL, '2026-11-12 00:00:00', '2026-11-12 00:00:00'),
    (39, 'Hallypop', 'hallypop', 'assets/images/logos/hallypop.svg', 'Hallypop was GMA’s K-pop and Asian pop culture channel. It ceased broadcasting in September 2024, so no player is available.', 1, 2, 'https://www.gmanetwork.com/entertainment/', 'unknown', 'unavailable', 'ph', 'Filipino / English', '#64748b', 'Ceased broadcasting in September 2024. Retained as a reference entry.', 0, 39, 0, NULL, '2026-11-12 00:00:00', '2026-11-12 00:00:00'),
    (40, 'PBA', 'pba', 'assets/images/logos/pba.svg', 'The Philippine Basketball Association’s official league site with schedules, news, and broadcast information. Game coverage is available through official partners; japjapTV does not stream PBA games.', 1, 3, 'https://www.pba.ph/', 'unknown', 'external_only', 'ph', 'Filipino / English', '#f97316', 'No game streams are hosted. Check official broadcast partners.', 0, 40, 0, NULL, '2026-11-12 00:00:00', '2026-11-12 00:00:00'),
    (41, 'PBA Rush', 'pba-rush', 'assets/images/logos/pba-rush.svg', 'PBA Rush is the league’s pay-TV channel carried on authorized providers such as Cignal. A subscription and provider login are required.', 1, 3, 'https://www.cignalplay.com/', 'subscription', 'external_only', 'ph', 'English', '#ea580c', 'Subscription and provider login required.', 0, 41, 0, NULL, '2026-11-12 00:00:00', '2026-11-12 00:00:00'),
    (42, 'NBA TV Philippines', 'nba-tv-philippines', 'assets/images/logos/nba-tv-philippines.svg', 'NBA TV Philippines is a pay-TV channel distributed by Cignal. Access requires a qualifying subscription and provider login.', 1, 3, 'https://www.cignalplay.com/', 'subscription', 'external_only', 'ph', 'English', '#1d4ed8', 'Subscription and provider login required. No NBA game streams are hosted here.', 0, 42, 0, NULL, '2026-11-12 00:00:00', '2026-11-12 00:00:00'),
    (43, 'Cinema One', 'cinema-one', 'assets/images/logos/cinema-one.svg', 'ABS-CBN’s Filipino movie channel. It is a pay-TV channel; use official provider listings for availability and schedules.', 1, 6, 'https://www.abs-cbn.com/', 'subscription', 'external_only', 'ph', 'Filipino / English', '#be123c', NULL, 0, 43, 0, NULL, '2026-11-12 00:00:00', '2026-11-12 00:00:00'),
    (44, 'CNN International', 'cnn-international', 'assets/images/logos/cnn-international.svg', 'CNN International’s live TV page requires a participating TV provider login. No free 24/7 embed is configured.', 2, 1, 'https://edition.cnn.com/videos/live', 'subscription', 'external_only', 'international', 'English', '#b91c1c', NULL, 0, 44, 0, NULL, '2026-11-12 00:00:00', '2026-11-12 00:00:00'),
    (45, 'MS NOW', 'ms-now', 'assets/images/logos/ms-now.svg', 'MS NOW (formerly MSNBC) is a US news and opinion network owned by Versant. Live viewing requires a pay-TV provider login or an official streaming subscription.', 2, 1, 'https://www.ms.now/', 'subscription', 'external_only', 'international', 'English', '#1e40af', 'Formerly MSNBC. Pay-TV login or official membership required.', 0, 45, 0, NULL, '2026-11-12 00:00:00', '2026-11-12 00:00:00'),
    (46, 'Fox News', 'fox-news', 'assets/images/logos/fox-news.svg', 'Fox News Channel’s live stream requires a participating TV provider login. No free 24/7 embed is configured.', 2, 1, 'https://www.foxnews.com/video/5614615980001', 'subscription', 'external_only', 'international', 'English', '#0c4a6e', NULL, 0, 46, 0, NULL, '2026-11-12 00:00:00', '2026-11-12 00:00:00'),
    (47, 'ESPN', 'espn', 'assets/images/logos/espn.svg', 'ESPN’s live events require a participating TV provider or ESPN subscription. No ESPN linear feed is embedded here.', 2, 3, 'https://www.espn.com/watch/', 'subscription', 'external_only', 'international', 'English', '#b91c1c', 'Subscription or TV provider login required.', 0, 47, 0, NULL, '2026-11-12 00:00:00', '2026-11-12 00:00:00'),
    (48, 'NBA TV', 'nba-tv', 'assets/images/logos/nba-tv.svg', 'NBA TV’s live stream requires an authorized NBA subscription or participating TV provider. japjapTV does not host NBA game streams.', 2, 3, 'https://www.nba.com/watch/nba-tv', 'subscription', 'external_only', 'international', 'English', '#1d4ed8', 'Subscription required. No NBA game streams are hosted here.', 0, 48, 0, NULL, '2026-11-12 00:00:00', '2026-11-12 00:00:00'),
    (49, 'beIN SPORTS', 'bein-sports', 'assets/images/logos/bein-sports.svg', 'beIN SPORTS live coverage requires an authorized subscription. Channels and sports rights vary by country.', 4, 3, 'https://www.beinsports.com/en-asia', 'subscription', 'external_only', 'international', 'English', '#7c3aed', NULL, 0, 49, 0, NULL, '2026-11-12 00:00:00', '2026-11-12 00:00:00'),
    (50, 'Cartoon Network', 'cartoon-network', 'assets/images/logos/cartoon-network.svg', 'Cartoon Network’s official destination offers selected episodes and games. Live channel access requires a participating TV provider.', 2, 4, 'https://www.cartoonnetwork.com/', 'subscription', 'external_only', 'international', 'English', '#3b82f6', NULL, 0, 50, 0, NULL, '2026-11-12 00:00:00', '2026-11-12 00:00:00'),
    (51, 'Nickelodeon', 'nickelodeon', 'assets/images/logos/nickelodeon.svg', 'Nickelodeon’s official destination offers selected episodes and games. Live channel access requires a participating TV provider.', 2, 4, 'https://www.nick.com/', 'subscription', 'external_only', 'international', 'English', '#f97316', NULL, 0, 51, 0, NULL, '2026-11-12 00:00:00', '2026-11-12 00:00:00'),
    (52, 'Disney Channel', 'disney-channel', 'assets/images/logos/disney-channel.svg', 'Disney Channel’s linear viewing requires a participating TV provider. Selected content is available on Disney+ with a subscription.', 2, 4, 'https://disneynow.com/', 'subscription', 'external_only', 'international', 'English', '#1e40af', NULL, 0, 52, 0, NULL, '2026-11-12 00:00:00', '2026-11-12 00:00:00'),
    (53, 'HBO', 'hbo', 'assets/images/logos/hbo.svg', 'HBO programming is available through Max and authorized TV providers. A subscription is required.', 2, 6, 'https://www.max.com/', 'subscription', 'external_only', 'international', 'English', '#7c3aed', NULL, 0, 53, 0, NULL, '2026-11-12 00:00:00', '2026-11-12 00:00:00'),
    (54, 'Star Movies', 'star-movies', 'assets/images/logos/star-movies.svg', 'Star Movies is a pay-TV movie channel. Availability depends on your region and provider. Use the official destination for current listings.', 2, 6, 'https://www.starmovies.com/', 'subscription', 'external_only', 'international', 'English', '#db2777', NULL, 0, 54, 0, NULL, '2026-11-12 00:00:00', '2026-11-12 00:00:00'),
    (55, 'MTV', 'mtv', 'assets/images/logos/mtv.svg', 'MTV’s official destination offers selected music, reality, and entertainment content. Linear channel access depends on your provider.', 2, 5, 'https://www.mtv.com/', 'subscription', 'external_only', 'international', 'English', '#e11d48', NULL, 0, 55, 0, NULL, '2026-11-12 00:00:00', '2026-11-12 00:00:00'),
    (56, 'Discovery Channel', 'discovery-channel', 'assets/images/logos/discovery-channel.svg', 'Discovery Channel’s factual and adventure programming. Live channel access requires a participating TV provider or official streaming subscription.', 2, 7, 'https://www.discovery.com/', 'subscription', 'external_only', 'international', 'English', '#0d9488', NULL, 0, 56, 0, NULL, '2026-11-12 00:00:00', '2026-11-12 00:00:00'),
    (57, 'National Geographic', 'national-geographic', 'assets/images/logos/national-geographic.svg', 'National Geographic’s documentary, science, and exploration programming. Live channel access requires a participating TV provider.', 2, 7, 'https://www.nationalgeographic.com/tv/', 'subscription', 'external_only', 'international', 'English', '#eab308', NULL, 0, 57, 0, NULL, '2026-11-12 00:00:00', '2026-11-12 00:00:00'),
    (58, 'TLC', 'tlc', 'assets/images/logos/tlc.svg', 'TLC’s lifestyle and reality programming. Live channel access requires a participating TV provider or official streaming subscription.', 2, 9, 'https://go.tlc.com/', 'subscription', 'external_only', 'international', 'English', '#ec4899', NULL, 0, 58, 0, NULL, '2026-11-12 00:00:00', '2026-11-12 00:00:00'),
    (59, 'Food Network', 'food-network', 'assets/images/logos/food-network.svg', 'Food Network’s cooking and food entertainment programming. Live channel access requires a participating TV provider or official streaming subscription.', 2, 9, 'https://www.foodnetwork.com/', 'subscription', 'external_only', 'international', 'English', '#dc2626', NULL, 0, 59, 0, NULL, '2026-11-12 00:00:00', '2026-11-12 00:00:00'),
    (60, 'EWTN', 'ewtn', 'assets/images/logos/ewtn.svg', 'EWTN’s official live page offers free live coverage of EWTN television in many regions. No iframe embed is configured; use the official page.', 2, 11, 'https://www.ewtn.com/live', 'free', 'external_only', 'international', 'English', '#1e40af', NULL, 0, 60, 0, NULL, '2026-11-12 00:00:00', '2026-11-12 00:00:00');

-- Sources -----------------------------------------------------------------
INSERT INTO channel_sources (id, channel_id, source_type, label, source_url, embed_url, provider_ref, priority, is_active, is_authorized, last_checked, last_verified, last_check_status, last_check_message, last_http_code, consecutive_failures, created_at, updated_at) VALUES
    (1, 1, 'youtube_live', 'Official GMA Network YouTube livestream', 'https://www.youtube.com/channel/UCKL5hAuzgFQsyrsQKgU0Qng/live', 'https://www.youtube.com/embed/live_stream?channel=UCKL5hAuzgFQsyrsQKgU0Qng', 'UCKL5hAuzgFQsyrsQKgU0Qng', 1, 1, 1, '2026-11-12 00:00:00', '2026-11-12 00:00:00', 'live', 'Verified live and embeddable on 12 Nov 2026 (UTC).', 200, 0, '2026-11-12 00:00:00', '2026-11-12 00:00:00'),
    (2, 2, 'youtube_live', 'Official TV5 YouTube livestream', 'https://www.youtube.com/channel/UCiFx0kZFjR29u0uJxTSq5MQ/live', 'https://www.youtube.com/embed/live_stream?channel=UCiFx0kZFjR29u0uJxTSq5MQ', 'UCiFx0kZFjR29u0uJxTSq5MQ', 1, 1, 1, '2026-11-12 00:00:00', '2026-11-12 00:00:00', 'live', 'Verified live and embeddable on 12 Nov 2026 (UTC).', 200, 0, '2026-11-12 00:00:00', '2026-11-12 00:00:00'),
    (3, 3, 'youtube_live', 'Official PTV YouTube livestream', 'https://www.youtube.com/channel/UCJCUbMaY593_4SN1QPG7NFQ/live', 'https://www.youtube.com/embed/live_stream?channel=UCJCUbMaY593_4SN1QPG7NFQ', 'UCJCUbMaY593_4SN1QPG7NFQ', 1, 1, 1, '2026-11-12 00:00:00', '2026-11-12 00:00:00', 'live', 'Verified live and embeddable on 12 Nov 2026 (UTC).', 200, 0, '2026-11-12 00:00:00', '2026-11-12 00:00:00'),
    (4, 4, 'youtube_live', 'Official IBC 13 YouTube livestream', 'https://www.youtube.com/channel/UCQ5jV7tnWIISDf2ylEj560A/live', 'https://www.youtube.com/embed/live_stream?channel=UCQ5jV7tnWIISDf2ylEj560A', 'UCQ5jV7tnWIISDf2ylEj560A', 1, 1, 1, '2026-11-12 00:00:00', '2026-11-12 00:00:00', 'live', 'Verified live and embeddable on 12 Nov 2026 (UTC).', 200, 0, '2026-11-12 00:00:00', '2026-11-12 00:00:00'),
    (5, 5, 'youtube_live', 'Official NET25 YouTube livestream', 'https://www.youtube.com/channel/UC5IF8NlxMW1GkEUUqWJJBIw/live', 'https://www.youtube.com/embed/live_stream?channel=UC5IF8NlxMW1GkEUUqWJJBIw', 'UC5IF8NlxMW1GkEUUqWJJBIw', 1, 1, 1, '2026-11-12 00:00:00', '2026-11-12 00:00:00', 'live', 'Verified live and embeddable on 12 Nov 2026 (UTC).', 200, 0, '2026-11-12 00:00:00', '2026-11-12 00:00:00'),
    (6, 6, 'youtube_live', 'Official UNTV YouTube livestream', 'https://www.youtube.com/channel/UC3XaG-7UVi2vD8ZZEMNnnpw/live', 'https://www.youtube.com/embed/live_stream?channel=UC3XaG-7UVi2vD8ZZEMNnnpw', 'UC3XaG-7UVi2vD8ZZEMNnnpw', 1, 1, 1, '2026-11-12 00:00:00', '2026-11-12 00:00:00', 'live', 'Verified live and embeddable on 12 Nov 2026 (UTC).', 200, 0, '2026-11-12 00:00:00', '2026-11-12 00:00:00'),
    (7, 7, 'youtube_live', 'Official Kapamilya Online Live YouTube livestream', 'https://www.youtube.com/channel/UCstEtN0pgOmCf02EdXsGChw/live', 'https://www.youtube.com/embed/live_stream?channel=UCstEtN0pgOmCf02EdXsGChw', 'UCstEtN0pgOmCf02EdXsGChw', 1, 1, 1, '2026-11-12 00:00:00', '2026-11-12 00:00:00', 'live', 'Verified live and embeddable on 12 Nov 2026 (UTC).', 200, 0, '2026-11-12 00:00:00', '2026-11-12 00:00:00'),
    (8, 8, 'youtube_live', 'Official Al Jazeera English YouTube livestream', 'https://www.youtube.com/channel/UCNye-wNBqNL5ZzHSJj3l8Bg/live', 'https://www.youtube.com/embed/live_stream?channel=UCNye-wNBqNL5ZzHSJj3l8Bg', 'UCNye-wNBqNL5ZzHSJj3l8Bg', 1, 1, 1, '2026-11-12 00:00:00', '2026-11-12 00:00:00', 'live', 'Verified live and embeddable on 12 Nov 2026 (UTC).', 200, 0, '2026-11-12 00:00:00', '2026-11-12 00:00:00'),
    (9, 9, 'youtube_live', 'Official BBC News YouTube livestream', 'https://www.youtube.com/channel/UC16niRr50-MSBwiO3YDb3RA/live', 'https://www.youtube.com/embed/live_stream?channel=UC16niRr50-MSBwiO3YDb3RA', 'UC16niRr50-MSBwiO3YDb3RA', 1, 1, 1, '2026-11-12 00:00:00', '2026-11-12 00:00:00', 'live', 'Verified live and embeddable on 12 Nov 2026 (UTC).', 200, 0, '2026-11-12 00:00:00', '2026-11-12 00:00:00'),
    (10, 10, 'youtube_live', 'Official DW English YouTube livestream', 'https://www.youtube.com/channel/UCknLrEdhRCp1aegoMqRaCZg/live', 'https://www.youtube.com/embed/live_stream?channel=UCknLrEdhRCp1aegoMqRaCZg', 'UCknLrEdhRCp1aegoMqRaCZg', 1, 1, 1, '2026-11-12 00:00:00', '2026-11-12 00:00:00', 'live', 'Verified live and embeddable on 12 Nov 2026 (UTC).', 200, 0, '2026-11-12 00:00:00', '2026-11-12 00:00:00'),
    (11, 11, 'youtube_live', 'Official France 24 English YouTube livestream', 'https://www.youtube.com/channel/UCQfwfsi5VrQ8yKZ-UWmAEFg/live', 'https://www.youtube.com/embed/live_stream?channel=UCQfwfsi5VrQ8yKZ-UWmAEFg', 'UCQfwfsi5VrQ8yKZ-UWmAEFg', 1, 1, 1, '2026-11-12 00:00:00', '2026-11-12 00:00:00', 'live', 'Verified live and embeddable on 12 Nov 2026 (UTC).', 200, 0, '2026-11-12 00:00:00', '2026-11-12 00:00:00'),
    (12, 12, 'youtube_live', 'Official Sky News YouTube livestream', 'https://www.youtube.com/channel/UCoMdktPbSTixAyNGwb-UYkQ/live', 'https://www.youtube.com/embed/live_stream?channel=UCoMdktPbSTixAyNGwb-UYkQ', 'UCoMdktPbSTixAyNGwb-UYkQ', 1, 1, 1, '2026-11-12 00:00:00', '2026-11-12 00:00:00', 'live', 'Verified live and embeddable on 12 Nov 2026 (UTC).', 200, 0, '2026-11-12 00:00:00', '2026-11-12 00:00:00'),
    (13, 13, 'youtube_live', 'Official NHK WORLD-JAPAN YouTube livestream', 'https://www.youtube.com/channel/UCSPEjw8F2nQDtmUKPFNF7_A/live', 'https://www.youtube.com/embed/live_stream?channel=UCSPEjw8F2nQDtmUKPFNF7_A', 'UCSPEjw8F2nQDtmUKPFNF7_A', 1, 1, 1, '2026-11-12 00:00:00', '2026-11-12 00:00:00', 'live', 'Verified live and embeddable on 12 Nov 2026 (UTC).', 200, 0, '2026-11-12 00:00:00', '2026-11-12 00:00:00'),
    (14, 14, 'youtube_live', 'Official Bloomberg Television YouTube livestream', 'https://www.youtube.com/channel/UCIALMKvObZNtJ6AmdCLP7Lg/live', 'https://www.youtube.com/embed/live_stream?channel=UCIALMKvObZNtJ6AmdCLP7Lg', 'UCIALMKvObZNtJ6AmdCLP7Lg', 1, 1, 1, '2026-11-12 00:00:00', '2026-11-12 00:00:00', 'live', 'Verified live and embeddable on 12 Nov 2026 (UTC).', 200, 0, '2026-11-12 00:00:00', '2026-11-12 00:00:00'),
    (15, 15, 'youtube_live', 'Official CNBC Marathon YouTube livestream', 'https://www.youtube.com/channel/UCvJJ_dzjViJCoLf5uKUTwoA/live', 'https://www.youtube.com/embed/live_stream?channel=UCvJJ_dzjViJCoLf5uKUTwoA', 'UCvJJ_dzjViJCoLf5uKUTwoA', 1, 1, 1, '2026-11-12 00:00:00', '2026-11-12 00:00:00', 'live', 'Verified live and embeddable on 12 Nov 2026 (UTC).', 200, 0, '2026-11-12 00:00:00', '2026-11-12 00:00:00'),
    (16, 16, 'youtube_live', 'Official Euronews English YouTube livestream', 'https://www.youtube.com/channel/UCSrZ3UV4jOidv8ppoVuvW9Q/live', 'https://www.youtube.com/embed/live_stream?channel=UCSrZ3UV4jOidv8ppoVuvW9Q', 'UCSrZ3UV4jOidv8ppoVuvW9Q', 1, 1, 1, '2026-11-12 00:00:00', '2026-11-12 00:00:00', 'live', 'Verified live and embeddable on 12 Nov 2026 (UTC).', 200, 0, '2026-11-12 00:00:00', '2026-11-12 00:00:00'),
    (17, 17, 'youtube_live', 'Official ABC News YouTube livestream', 'https://www.youtube.com/channel/UCBi2mrWuNuyYy4gbM6fU18Q/live', 'https://www.youtube.com/embed/live_stream?channel=UCBi2mrWuNuyYy4gbM6fU18Q', 'UCBi2mrWuNuyYy4gbM6fU18Q', 1, 1, 1, '2026-11-12 00:00:00', '2026-11-12 00:00:00', 'live', 'Verified live and embeddable on 12 Nov 2026 (UTC).', 200, 0, '2026-11-12 00:00:00', '2026-11-12 00:00:00'),
    (18, 18, 'youtube_live', 'Official NBC News NOW YouTube livestream', 'https://www.youtube.com/channel/UCeY0bbntWzzVIaj2z3QigXg/live', 'https://www.youtube.com/embed/live_stream?channel=UCeY0bbntWzzVIaj2z3QigXg', 'UCeY0bbntWzzVIaj2z3QigXg', 1, 1, 1, '2026-11-12 00:00:00', '2026-11-12 00:00:00', 'live', 'Verified live and embeddable on 12 Nov 2026 (UTC).', 200, 0, '2026-11-12 00:00:00', '2026-11-12 00:00:00'),
    (19, 19, 'youtube_live', 'Official CBS News YouTube livestream', 'https://www.youtube.com/channel/UC8p1vwvWtl6T73JiExfWs1g/live', 'https://www.youtube.com/embed/live_stream?channel=UC8p1vwvWtl6T73JiExfWs1g', 'UC8p1vwvWtl6T73JiExfWs1g', 1, 1, 1, '2026-11-12 00:00:00', '2026-11-12 00:00:00', 'live', 'Verified live and embeddable on 12 Nov 2026 (UTC).', 200, 0, '2026-11-12 00:00:00', '2026-11-12 00:00:00'),
    (20, 20, 'youtube_live', 'Official CNA YouTube livestream', 'https://www.youtube.com/channel/UC83jt4dlz1Gjl58fzQrrKZg/live', 'https://www.youtube.com/embed/live_stream?channel=UC83jt4dlz1Gjl58fzQrrKZg', 'UC83jt4dlz1Gjl58fzQrrKZg', 1, 1, 1, '2026-11-12 00:00:00', '2026-11-12 00:00:00', 'live', 'Verified live and embeddable on 12 Nov 2026 (UTC).', 200, 0, '2026-11-12 00:00:00', '2026-11-12 00:00:00'),
    (21, 21, 'youtube_live', 'Official TRT World YouTube livestream', 'https://www.youtube.com/channel/UC7fWeaHhqgM4Ry-RMpM2YYw/live', 'https://www.youtube.com/embed/live_stream?channel=UC7fWeaHhqgM4Ry-RMpM2YYw', 'UC7fWeaHhqgM4Ry-RMpM2YYw', 1, 1, 1, '2026-11-12 00:00:00', '2026-11-12 00:00:00', 'live', 'Verified live and embeddable on 12 Nov 2026 (UTC).', 200, 0, '2026-11-12 00:00:00', '2026-11-12 00:00:00'),
    (22, 22, 'youtube_live', 'Official Scripps News YouTube livestream', 'https://www.youtube.com/channel/UCTln5ss6h6L_xNfMeujfPbg/live', 'https://www.youtube.com/embed/live_stream?channel=UCTln5ss6h6L_xNfMeujfPbg', 'UCTln5ss6h6L_xNfMeujfPbg', 1, 1, 1, '2026-11-12 00:00:00', '2026-11-12 00:00:00', 'live', 'Verified live and embeddable on 12 Nov 2026 (UTC).', 200, 0, '2026-11-12 00:00:00', '2026-11-12 00:00:00'),
    (23, 23, 'youtube_live', 'Official ABC Australia YouTube livestream', 'https://www.youtube.com/channel/UCVgO39Bk5sMo66-6o6Spn6Q/live', 'https://www.youtube.com/embed/live_stream?channel=UCVgO39Bk5sMo66-6o6Spn6Q', 'UCVgO39Bk5sMo66-6o6Spn6Q', 1, 1, 1, '2026-11-12 00:00:00', '2026-11-12 00:00:00', 'live', 'Verified live and embeddable on 12 Nov 2026 (UTC).', 200, 0, '2026-11-12 00:00:00', '2026-11-12 00:00:00'),
    (24, 24, 'youtube_live', 'Official NASA YouTube livestream', 'https://www.youtube.com/channel/UCLA_DiR1FfKNvjuUpBHmylQ/live', 'https://www.youtube.com/embed/live_stream?channel=UCLA_DiR1FfKNvjuUpBHmylQ', 'UCLA_DiR1FfKNvjuUpBHmylQ', 1, 1, 1, '2026-11-12 00:00:00', '2026-11-12 00:00:00', 'live', 'Verified live and embeddable on 12 Nov 2026 (UTC).', 200, 0, '2026-11-12 00:00:00', '2026-11-12 00:00:00'),
    (25, 25, 'youtube_live', 'Official Lofi Girl YouTube livestream', 'https://www.youtube.com/channel/UCSJ4gkVC6NrvII8umztf0Ow/live', 'https://www.youtube.com/embed/live_stream?channel=UCSJ4gkVC6NrvII8umztf0Ow', 'UCSJ4gkVC6NrvII8umztf0Ow', 1, 1, 1, '2026-11-12 00:00:00', '2026-11-12 00:00:00', 'live', 'Verified live and embeddable on 12 Nov 2026 (UTC).', 200, 0, '2026-11-12 00:00:00', '2026-11-12 00:00:00'),
    (26, 26, 'youtube_live', 'Official Chillhop Music YouTube livestream', 'https://www.youtube.com/channel/UCOxqgCwgOqC2lMqC5PYz_Dg/live', 'https://www.youtube.com/embed/live_stream?channel=UCOxqgCwgOqC2lMqC5PYz_Dg', 'UCOxqgCwgOqC2lMqC5PYz_Dg', 1, 1, 1, '2026-11-12 00:00:00', '2026-11-12 00:00:00', 'live', 'Verified live and embeddable on 12 Nov 2026 (UTC).', 200, 0, '2026-11-12 00:00:00', '2026-11-12 00:00:00'),
    (27, 27, 'youtube_live', 'Official PBS KIDS YouTube livestream', 'https://www.youtube.com/channel/UCrNnk0wFBnCS1awGjq_ijGQ/live', 'https://www.youtube.com/embed/live_stream?channel=UCrNnk0wFBnCS1awGjq_ijGQ', 'UCrNnk0wFBnCS1awGjq_ijGQ', 1, 1, 1, '2026-11-12 00:00:00', '2026-11-12 00:00:00', 'live', 'Verified live and embeddable on 12 Nov 2026 (UTC).', 200, 0, '2026-11-12 00:00:00', '2026-11-12 00:00:00');

-- Featured channels --------------------------------------------------------
INSERT INTO featured_channels (id, channel_id, display_order, created_at) VALUES
    (1, 1, 1, '2026-11-12 00:00:00'),
    (2, 2, 2, '2026-11-12 00:00:00'),
    (3, 7, 3, '2026-11-12 00:00:00'),
    (4, 3, 4, '2026-11-12 00:00:00'),
    (5, 8, 5, '2026-11-12 00:00:00'),
    (6, 9, 6, '2026-11-12 00:00:00'),
    (7, 24, 7, '2026-11-12 00:00:00'),
    (8, 25, 8, '2026-11-12 00:00:00');

-- NBA games are intentionally empty: schedule data is fetched live from the
-- ESPN public scoreboard, and administrators can add verified fixtures in the
-- dashboard as a database fallback. No game streams are stored or hosted.

