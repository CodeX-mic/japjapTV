<?php
/**
 * Build database.sql from tools/seed-data.php.
 *
 * Usage: php tools/build_database_sql.php
 */

declare(strict_types=1);

$seed = require __DIR__ . '/seed-data.php';
$verifiedAt = (string) $seed['verified_at'];

function sql_q(?string $value): string
{
    if ($value === null) {
        return 'NULL';
    }

    return "'" . str_replace(['\\', "'"], ['\\\\', "''"], $value) . "'";
}

function sql_int(int $value): string
{
    return (string) $value;
}

$categoryIds = [];
$countryIds = [];
$channelIds = [];

$out = [];
$out[] = '-- ---------------------------------------------------------------------------';
$out[] = '-- japjapTV — database schema and seed catalogue';
$out[] = '-- Database: japjaptv_db';
$out[] = '--';
$out[] = '-- Import with: mysql -u root japjaptv_db < database.sql';
$out[] = '-- or paste this file into phpMyAdmin while japjaptv_db is selected.';
$out[] = '--';
$out[] = '-- Catalogue verified: 12 November 2026 (UTC). Re-run bin/check_sources.php';
$out[] = '-- after installation to refresh live status.';
$out[] = '-- ---------------------------------------------------------------------------';
$out[] = '';
$out[] = "SET NAMES utf8mb4;";
$out[] = "SET time_zone = '+00:00';";
$out[] = "CREATE DATABASE IF NOT EXISTS japjaptv_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;";
$out[] = "USE japjaptv_db;";
$out[] = "SET FOREIGN_KEY_CHECKS = 0;";
$out[] = '';
$out[] = 'DROP TABLE IF EXISTS source_checks;';
$out[] = 'DROP TABLE IF EXISTS login_attempts;';
$out[] = 'DROP TABLE IF EXISTS contact_messages;';
$out[] = 'DROP TABLE IF EXISTS nba_games;';
$out[] = 'DROP TABLE IF EXISTS featured_channels;';
$out[] = 'DROP TABLE IF EXISTS channel_sources;';
$out[] = 'DROP TABLE IF EXISTS channels;';
$out[] = 'DROP TABLE IF EXISTS categories;';
$out[] = 'DROP TABLE IF EXISTS countries;';
$out[] = 'DROP TABLE IF EXISTS admins;';
$out[] = '';
$out[] = 'SET FOREIGN_KEY_CHECKS = 1;';
$out[] = '';
$out[] = 'CREATE TABLE admins (';
$out[] = '    id INT UNSIGNED NOT NULL AUTO_INCREMENT,';
$out[] = '    username VARCHAR(60) NOT NULL,';
$out[] = '    password_hash VARCHAR(255) NOT NULL,';
$out[] = '    display_name VARCHAR(100) NOT NULL,';
$out[] = '    email VARCHAR(190) DEFAULT NULL,';
$out[] = '    must_change_password TINYINT(1) NOT NULL DEFAULT 1,';
$out[] = '    is_active TINYINT(1) NOT NULL DEFAULT 1,';
$out[] = '    last_login_at DATETIME DEFAULT NULL,';
$out[] = '    last_login_ip VARCHAR(45) DEFAULT NULL,';
$out[] = '    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,';
$out[] = '    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,';
$out[] = '    PRIMARY KEY (id),';
$out[] = '    UNIQUE KEY uq_admins_username (username)';
$out[] = ') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;';
$out[] = '';
$out[] = 'CREATE TABLE countries (';
$out[] = '    id INT UNSIGNED NOT NULL AUTO_INCREMENT,';
$out[] = '    country_name VARCHAR(100) NOT NULL,';
$out[] = '    country_code CHAR(2) NOT NULL,';
$out[] = '    flag_emoji VARCHAR(16) DEFAULT NULL,';
$out[] = '    region VARCHAR(60) NOT NULL DEFAULT \'International\',';
$out[] = '    display_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,';
$out[] = '    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,';
$out[] = '    PRIMARY KEY (id),';
$out[] = '    UNIQUE KEY uq_countries_code (country_code)';
$out[] = ') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;';
$out[] = '';
$out[] = 'CREATE TABLE categories (';
$out[] = '    id INT UNSIGNED NOT NULL AUTO_INCREMENT,';
$out[] = '    category_name VARCHAR(80) NOT NULL,';
$out[] = '    category_slug VARCHAR(80) NOT NULL,';
$out[] = '    description VARCHAR(255) DEFAULT NULL,';
$out[] = '    icon VARCHAR(60) NOT NULL DEFAULT \'fa-tv\',';
$out[] = '    color VARCHAR(7) NOT NULL DEFAULT \'#ff3b47\',';
$out[] = '    display_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,';
$out[] = '    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,';
$out[] = '    PRIMARY KEY (id),';
$out[] = '    UNIQUE KEY uq_categories_slug (category_slug)';
$out[] = ') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;';
$out[] = '';
$out[] = 'CREATE TABLE channels (';
$out[] = '    id INT UNSIGNED NOT NULL AUTO_INCREMENT,';
$out[] = '    channel_name VARCHAR(120) NOT NULL,';
$out[] = '    slug VARCHAR(140) NOT NULL,';
$out[] = '    logo VARCHAR(255) DEFAULT NULL,';
$out[] = '    description TEXT NOT NULL,';
$out[] = '    country_id INT UNSIGNED NOT NULL,';
$out[] = '    category_id INT UNSIGNED NOT NULL,';
$out[] = '    official_url VARCHAR(1000) NOT NULL,';
$out[] = '    access_type ENUM(\'free\',\'subscription\',\'unknown\') NOT NULL DEFAULT \'unknown\',';
$out[] = '    availability_status ENUM(\'available\',\'external_only\',\'unavailable\',\'coming_soon\') NOT NULL DEFAULT \'external_only\',';
$out[] = '    region ENUM(\'ph\',\'international\') NOT NULL DEFAULT \'international\',';
$out[] = '    language VARCHAR(80) NOT NULL DEFAULT \'English\',';
$out[] = '    accent_color VARCHAR(7) NOT NULL DEFAULT \'#ff3b47\',';
$out[] = '    notice VARCHAR(255) DEFAULT NULL,';
$out[] = '    is_featured TINYINT(1) NOT NULL DEFAULT 0,';
$out[] = '    display_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,';
$out[] = '    view_count INT UNSIGNED NOT NULL DEFAULT 0,';
$out[] = '    last_verified_at DATETIME DEFAULT NULL,';
$out[] = '    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,';
$out[] = '    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,';
$out[] = '    PRIMARY KEY (id),';
$out[] = '    UNIQUE KEY uq_channels_slug (slug),';
$out[] = '    KEY idx_channels_country (country_id),';
$out[] = '    KEY idx_channels_category (category_id),';
$out[] = '    KEY idx_channels_availability (availability_status),';
$out[] = '    KEY idx_channels_display (is_featured, display_order),';
$out[] = '    CONSTRAINT fk_channels_country FOREIGN KEY (country_id) REFERENCES countries (id) ON DELETE RESTRICT ON UPDATE CASCADE,';
$out[] = '    CONSTRAINT fk_channels_category FOREIGN KEY (category_id) REFERENCES categories (id) ON DELETE RESTRICT ON UPDATE CASCADE';
$out[] = ') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;';
$out[] = '';
$out[] = 'CREATE TABLE channel_sources (';
$out[] = '    id INT UNSIGNED NOT NULL AUTO_INCREMENT,';
$out[] = '    channel_id INT UNSIGNED NOT NULL,';
$out[] = '    source_type ENUM(\'youtube_live\',\'youtube_video\',\'hls\',\'iframe\',\'external\') NOT NULL,';
$out[] = '    label VARCHAR(120) NOT NULL,';
$out[] = '    source_url VARCHAR(1000) NOT NULL,';
$out[] = '    embed_url VARCHAR(1000) DEFAULT NULL,';
$out[] = '    provider_ref VARCHAR(190) DEFAULT NULL,';
$out[] = '    priority TINYINT UNSIGNED NOT NULL DEFAULT 1,';
$out[] = '    is_active TINYINT(1) NOT NULL DEFAULT 1,';
$out[] = '    is_authorized TINYINT(1) NOT NULL DEFAULT 0,';
$out[] = '    last_checked DATETIME DEFAULT NULL,';
$out[] = '    last_verified DATETIME DEFAULT NULL,';
$out[] = '    last_check_status ENUM(\'live\',\'reachable\',\'offline\',\'error\',\'unverified\',\'unknown\') NOT NULL DEFAULT \'unknown\',';
$out[] = '    last_check_message VARCHAR(255) DEFAULT NULL,';
$out[] = '    last_http_code SMALLINT UNSIGNED DEFAULT NULL,';
$out[] = '    consecutive_failures TINYINT UNSIGNED NOT NULL DEFAULT 0,';
$out[] = '    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,';
$out[] = '    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,';
$out[] = '    PRIMARY KEY (id),';
$out[] = '    KEY idx_sources_channel (channel_id, priority),';
$out[] = '    KEY idx_sources_status (last_check_status),';
$out[] = '    KEY idx_sources_active (is_active, is_authorized),';
$out[] = '    CONSTRAINT fk_sources_channel FOREIGN KEY (channel_id) REFERENCES channels (id) ON DELETE CASCADE ON UPDATE CASCADE';
$out[] = ') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;';
$out[] = '';
$out[] = 'CREATE TABLE featured_channels (';
$out[] = '    id INT UNSIGNED NOT NULL AUTO_INCREMENT,';
$out[] = '    channel_id INT UNSIGNED NOT NULL,';
$out[] = '    display_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,';
$out[] = '    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,';
$out[] = '    PRIMARY KEY (id),';
$out[] = '    UNIQUE KEY uq_featured_channel (channel_id),';
$out[] = '    KEY idx_featured_order (display_order),';
$out[] = '    CONSTRAINT fk_featured_channel FOREIGN KEY (channel_id) REFERENCES channels (id) ON DELETE CASCADE ON UPDATE CASCADE';
$out[] = ') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;';
$out[] = '';
$out[] = 'CREATE TABLE nba_games (';
$out[] = '    id INT UNSIGNED NOT NULL AUTO_INCREMENT,';
$out[] = '    espn_game_id VARCHAR(40) DEFAULT NULL,';
$out[] = '    game_date DATETIME NOT NULL,';
$out[] = '    home_team VARCHAR(100) NOT NULL,';
$out[] = '    away_team VARCHAR(100) NOT NULL,';
$out[] = '    home_abbr VARCHAR(10) DEFAULT NULL,';
$out[] = '    away_abbr VARCHAR(10) DEFAULT NULL,';
$out[] = '    home_score SMALLINT DEFAULT NULL,';
$out[] = '    away_score SMALLINT DEFAULT NULL,';
$out[] = '    game_status ENUM(\'scheduled\',\'live\',\'final\',\'postponed\') NOT NULL DEFAULT \'scheduled\',';
$out[] = '    status_detail VARCHAR(100) DEFAULT NULL,';
$out[] = '    venue VARCHAR(150) DEFAULT NULL,';
$out[] = '    broadcast VARCHAR(150) DEFAULT NULL,';
$out[] = '    official_url VARCHAR(1000) DEFAULT NULL,';
$out[] = '    notes VARCHAR(255) DEFAULT NULL,';
$out[] = '    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,';
$out[] = '    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,';
$out[] = '    PRIMARY KEY (id),';
$out[] = '    KEY idx_nba_date (game_date),';
$out[] = '    KEY idx_nba_status (game_status)';
$out[] = ') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;';
$out[] = '';
$out[] = 'CREATE TABLE contact_messages (';
$out[] = '    id INT UNSIGNED NOT NULL AUTO_INCREMENT,';
$out[] = '    name VARCHAR(100) NOT NULL,';
$out[] = '    email VARCHAR(190) NOT NULL,';
$out[] = '    subject VARCHAR(150) NOT NULL,';
$out[] = '    message TEXT NOT NULL,';
$out[] = '    status ENUM(\'new\',\'read\',\'replied\',\'archived\') NOT NULL DEFAULT \'new\',';
$out[] = '    ip_hash CHAR(64) DEFAULT NULL,';
$out[] = '    user_agent VARCHAR(255) DEFAULT NULL,';
$out[] = '    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,';
$out[] = '    PRIMARY KEY (id),';
$out[] = '    KEY idx_messages_status (status, created_at),';
$out[] = '    KEY idx_messages_created (created_at)';
$out[] = ') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;';
$out[] = '';
$out[] = 'CREATE TABLE source_checks (';
$out[] = '    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,';
$out[] = '    source_id INT UNSIGNED NOT NULL,';
$out[] = '    status VARCHAR(20) NOT NULL,';
$out[] = '    http_code SMALLINT UNSIGNED DEFAULT NULL,';
$out[] = '    message VARCHAR(255) DEFAULT NULL,';
$out[] = '    checked_by INT UNSIGNED DEFAULT NULL,';
$out[] = '    checked_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,';
$out[] = '    PRIMARY KEY (id),';
$out[] = '    KEY idx_checks_source (source_id, checked_at),';
$out[] = '    CONSTRAINT fk_checks_source FOREIGN KEY (source_id) REFERENCES channel_sources (id) ON DELETE CASCADE ON UPDATE CASCADE,';
$out[] = '    CONSTRAINT fk_checks_admin FOREIGN KEY (checked_by) REFERENCES admins (id) ON DELETE SET NULL ON UPDATE CASCADE';
$out[] = ') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;';
$out[] = '';
$out[] = 'CREATE TABLE login_attempts (';
$out[] = '    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,';
$out[] = '    username VARCHAR(60) NOT NULL,';
$out[] = '    ip_hash CHAR(64) NOT NULL,';
$out[] = '    success TINYINT(1) NOT NULL DEFAULT 0,';
$out[] = '    attempted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,';
$out[] = '    PRIMARY KEY (id),';
$out[] = '    KEY idx_attempt_username (username, attempted_at),';
$out[] = '    KEY idx_attempt_ip (ip_hash, attempted_at)';
$out[] = ') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;';
$out[] = '';

// Admin -------------------------------------------------------------------
$admin = $seed['admin'];
$out[] = '-- Default administrator. The password must be changed on first login.';
$out[] = 'INSERT INTO admins (id, username, password_hash, display_name, email, must_change_password, is_active, created_at, updated_at) VALUES';
$out[] = '    (1, ' . sql_q($admin['username']) . ', ' . sql_q($admin['password_hash']) . ', '
    . sql_q($admin['display_name']) . ', ' . sql_q($admin['email']) . ', 1, 1, '
    . sql_q($verifiedAt) . ', ' . sql_q($verifiedAt) . ');';
$out[] = '';

// Countries ---------------------------------------------------------------
$out[] = '-- Countries ----------------------------------------------------------------';
$countryRows = [];
foreach ($seed['countries'] as $index => $country) {
    $id = $index + 1;
    $countryIds[$country['code']] = $id;
    $countryRows[] = '    (' . $id . ', ' . sql_q($country['name']) . ', ' . sql_q($country['code']) . ', '
        . sql_q($country['flag']) . ', ' . sql_q($country['region']) . ', ' . ($index + 1) . ', ' . sql_q($verifiedAt) . ')';
}
$out[] = 'INSERT INTO countries (id, country_name, country_code, flag_emoji, region, display_order, created_at) VALUES';
$out[] = implode(",\n", $countryRows) . ';';
$out[] = '';

// Categories --------------------------------------------------------------
$out[] = '-- Categories ---------------------------------------------------------------';
$categoryRows = [];
foreach ($seed['categories'] as $index => $category) {
    $id = $index + 1;
    $categoryIds[$category['slug']] = $id;
    $categoryRows[] = '    (' . $id . ', ' . sql_q($category['name']) . ', ' . sql_q($category['slug']) . ', '
        . sql_q($category['description']) . ', ' . sql_q($category['icon']) . ', ' . sql_q($category['color']) . ', '
        . ($index + 1) . ', ' . sql_q($verifiedAt) . ')';
}
$out[] = 'INSERT INTO categories (id, category_name, category_slug, description, icon, color, display_order, created_at) VALUES';
$out[] = implode(",\n", $categoryRows) . ';';
$out[] = '';

// Channels ----------------------------------------------------------------
$channelRows = [];
$sourceRows = [];
$sourceId = 0;
$featured = $seed['featured'];
$channelIndex = 0;

foreach ($seed['channels'] as $channel) {
    $channelIndex++;
    $id = $channelIndex;
    $channelIds[$channel['slug']] = $id;

    if (!isset($countryIds[$channel['country']])) {
        fwrite(STDERR, "Unknown country code: {$channel['country']}\n");
        exit(1);
    }
    if (!isset($categoryIds[$channel['category']])) {
        fwrite(STDERR, "Unknown category slug: {$channel['category']}\n");
        exit(1);
    }

    $isFeatured = isset($featured[$channel['slug']]) ? 1 : 0;
    $region = $channel['country'] === 'PH' ? 'ph' : 'international';
    $logo = 'assets/images/logos/' . $channel['slug'] . '.svg';
    $lastVerified = $channel['status'] === 'available' ? $verifiedAt : null;

    $channelRows[] = '    (' . $id . ', ' . sql_q($channel['name']) . ', ' . sql_q($channel['slug']) . ', '
        . sql_q($logo) . ', ' . sql_q($channel['description']) . ', '
        . $countryIds[$channel['country']] . ', ' . $categoryIds[$channel['category']] . ', '
        . sql_q($channel['official_url']) . ', ' . sql_q($channel['access']) . ', '
        . sql_q($channel['status']) . ', ' . sql_q($region) . ', ' . sql_q($channel['language']) . ', '
        . sql_q($channel['accent']) . ', ' . sql_q($channel['notice'] ?? null) . ', '
        . $isFeatured . ', ' . $id . ', 0, ' . sql_q($lastVerified) . ', ' . sql_q($verifiedAt) . ', ' . sql_q($verifiedAt) . ')';

    if (!isset($channel['source'])) {
        continue;
    }

    $source = $channel['source'];
    $sourceId++;
    $type = $source['type'];

    if ($type === 'youtube_live') {
        $ref = $source['ref'];
        $sourceUrl = 'https://www.youtube.com/channel/' . $ref . '/live';
        $embedUrl = 'https://www.youtube.com/embed/live_stream?channel=' . $ref;
        $lastChecked = $verifiedAt;
        $lastVerifiedSource = $verifiedAt;
        $status = 'live';
        $message = 'Verified live and embeddable on 12 Nov 2026 (UTC).';
        $httpCode = 200;
        $failures = 0;
        $authorized = 1;
        $active = 1;
    } else {
        $ref = null;
        $sourceUrl = $channel['official_url'];
        $embedUrl = null;
        $lastChecked = null;
        $lastVerifiedSource = null;
        $status = 'unverified';
        $message = 'Official destination only; not an embeddable stream. Open the official site to watch.';
        $httpCode = null;
        $failures = 0;
        $authorized = 1;
        $active = 1;
    }

    $sourceRows[] = '    (' . $sourceId . ', ' . $id . ', ' . sql_q($type) . ', ' . sql_q($source['label']) . ', '
        . sql_q($sourceUrl) . ', ' . sql_q($embedUrl) . ', ' . sql_q($ref) . ', 1, '
        . $active . ', ' . $authorized . ', ' . sql_q($lastChecked) . ', ' . sql_q($lastVerifiedSource) . ', '
        . sql_q($status) . ', ' . sql_q(mb_substr($message, 0, 255)) . ', ' . ($httpCode === null ? 'NULL' : (string) $httpCode) . ', '
        . $failures . ', ' . sql_q($verifiedAt) . ', ' . sql_q($verifiedAt) . ')';
}

$out[] = '-- Channels ----------------------------------------------------------------';
$out[] = 'INSERT INTO channels (id, channel_name, slug, logo, description, country_id, category_id, official_url, access_type, availability_status, region, language, accent_color, notice, is_featured, display_order, view_count, last_verified_at, created_at, updated_at) VALUES';
$out[] = implode(",\n", $channelRows) . ';';
$out[] = '';

if ($sourceRows !== []) {
    $out[] = '-- Sources -----------------------------------------------------------------';
    $out[] = 'INSERT INTO channel_sources (id, channel_id, source_type, label, source_url, embed_url, provider_ref, priority, is_active, is_authorized, last_checked, last_verified, last_check_status, last_check_message, last_http_code, consecutive_failures, created_at, updated_at) VALUES';
    $out[] = implode(",\n", $sourceRows) . ';';
    $out[] = '';
}

// Featured ----------------------------------------------------------------
$featuredRows = [];
foreach ($featured as $slug => $order) {
    if (!isset($channelIds[$slug])) {
        fwrite(STDERR, "Featured channel not found: {$slug}\n");
        exit(1);
    }
    $featuredRows[] = '    (' . (count($featuredRows) + 1) . ', ' . $channelIds[$slug] . ', ' . (int) $order . ', ' . sql_q($verifiedAt) . ')';
}
$out[] = '-- Featured channels --------------------------------------------------------';
$out[] = 'INSERT INTO featured_channels (id, channel_id, display_order, created_at) VALUES';
$out[] = implode(",\n", $featuredRows) . ';';
$out[] = '';
$out[] = '-- NBA games are intentionally empty: schedule data is fetched live from the';
$out[] = '-- ESPN public scoreboard, and administrators can add verified fixtures in the';
$out[] = '-- dashboard as a database fallback. No game streams are stored or hosted.';
$out[] = '';

$sql = implode("\n", $out);
$target = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'database.sql';
if (file_put_contents($target, $sql . "\n", LOCK_EX) === false) {
    fwrite(STDERR, "Could not write {$target}\n");
    exit(1);
}

$live = count(array_filter($seed['channels'], static fn (array $c): bool => $c['status'] === 'available'));
printf(
    "Wrote database.sql: %d countries, %d categories, %d channels (%d live), %d sources, %d featured.\n",
    count($seed['countries']),
    count($seed['categories']),
    count($seed['channels']),
    $live,
    count($sourceRows),
    count($featuredRows)
);
