<?php
/**
 * Admin helpers: validation, uploads, and small query utilities.
 */

declare(strict_types=1);

function admin_verify_csrf(string $redirectPath): void
{
    if (!verify_csrf()) {
        csrf_failure($redirectPath);
    }
}

function admin_redirect(string $path): never
{
    redirect('admin/' . ltrim($path, '/'));
}

/**
 * @return array{0:array,1:array<int,string>}
 */
function admin_validate_channel(array $input, ?array $existing = null): array
{
    $data = [
        'channel_name'        => trim((string) ($input['channel_name'] ?? '')),
        'slug'                => trim((string) ($input['slug'] ?? '')),
        'description'         => trim((string) ($input['description'] ?? '')),
        'country_id'          => (int) ($input['country_id'] ?? 0),
        'category_id'         => (int) ($input['category_id'] ?? 0),
        'official_url'        => trim((string) ($input['official_url'] ?? '')),
        'access_type'         => (string) ($input['access_type'] ?? 'unknown'),
        'availability_status' => (string) ($input['availability_status'] ?? 'external_only'),
        'region'              => (string) ($input['region'] ?? 'international'),
        'language'            => trim((string) ($input['language'] ?? 'English')),
        'accent_color'        => strtolower(trim((string) ($input['accent_color'] ?? '#ff3b47'))),
        'notice'              => trim((string) ($input['notice'] ?? '')),
        'is_featured'         => isset($input['is_featured']) ? 1 : 0,
        'display_order'       => max(0, min(65535, (int) ($input['display_order'] ?? 0))),
    ];

    $errors = [];

    if (mb_strlen($data['channel_name']) < 2 || mb_strlen($data['channel_name']) > 120) {
        $errors[] = 'Channel name must be 2–120 characters.';
    }
    if ($data['description'] === '' || mb_strlen($data['description']) < 20) {
        $errors[] = 'Add a description of at least 20 characters.';
    }
    if (!valid_http_url($data['official_url'])) {
        $errors[] = 'Enter a valid official destination URL (http or https).';
    }
    if (!in_array($data['access_type'], ['free', 'subscription', 'unknown'], true)) {
        $errors[] = 'Choose a valid access type.';
    }
    if (!in_array($data['availability_status'], ['available', 'external_only', 'unavailable', 'coming_soon'], true)) {
        $errors[] = 'Choose a valid availability status.';
    } elseif ($data['availability_status'] === 'available') {
        if ($existing === null) {
            $errors[] = 'Save the channel with an “Official site” or “Unavailable” status first, then add an active authorized source. Only a verified source can mark a channel “Live now”.';
        } else {
            $stmt = db()->prepare('SELECT COUNT(*) FROM channel_sources
                WHERE channel_id = :id AND is_active = 1 AND is_authorized = 1');
            $stmt->execute(['id' => $existing['id']]);
            if ((int) $stmt->fetchColumn() === 0) {
                $errors[] = 'Add at least one active, authorized source before marking this channel “Live now”.';
            }
        }
    }
    if (!in_array($data['region'], ['ph', 'international'], true)) {
        $errors[] = 'Choose a valid region.';
    }
    if (mb_strlen($data['language']) < 2 || mb_strlen($data['language']) > 80) {
        $errors[] = 'Language must be 2–80 characters.';
    }
    if (preg_match('/^#[0-9a-f]{6}$/', $data['accent_color']) !== 1) {
        $errors[] = 'Accent colour must be a six-digit hex value such as #ff3b47.';
    }
    if (mb_strlen($data['notice']) > 255) {
        $errors[] = 'The notice must be 255 characters or fewer.';
    }

    $stmt = db()->prepare('SELECT COUNT(*) FROM countries WHERE id = :id');
    $stmt->execute(['id' => $data['country_id']]);
    if ((int) $stmt->fetchColumn() === 0) {
        $errors[] = 'Choose a valid country.';
    }

    $stmt = db()->prepare('SELECT COUNT(*) FROM categories WHERE id = :id');
    $stmt->execute(['id' => $data['category_id']]);
    if ((int) $stmt->fetchColumn() === 0) {
        $errors[] = 'Choose a valid category.';
    }

    $baseSlug = slugify($data['slug'] !== '' ? $data['slug'] : $data['channel_name']);
    $ignoreId = $existing !== null ? (int) $existing['id'] : null;
    $data['slug'] = unique_channel_slug($baseSlug, $ignoreId);

    if ($data['notice'] === '') {
        $data['notice'] = null;
    }

    return [$data, $errors];
}

/**
 * Validate a logo upload and return the stored relative path.
 *
 * @return array{path:?string,error:?string}
 */
function admin_handle_logo_upload(?array $file, string $slug): array
{
    $result = ['path' => null, 'error' => null];

    if ($file === null || !isset($file['error']) || (int) $file['error'] === UPLOAD_ERR_NO_FILE) {
        return $result;
    }

    if ((int) $file['error'] !== UPLOAD_ERR_OK) {
        $result['error'] = 'The logo upload failed. Check the file size and try again.';
        return $result;
    }

    if (!is_uploaded_file((string) $file['tmp_name'])) {
        $result['error'] = 'The upload could not be verified.';
        return $result;
    }

    $size = (int) ($file['size'] ?? 0);
    if ($size <= 0 || $size > MAX_LOGO_BYTES) {
        $result['error'] = 'The logo must be smaller than 2 MB.';
        return $result;
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = (string) $finfo->file((string) $file['tmp_name']);
    $allowed = [
        'image/png'  => 'png',
        'image/jpeg' => 'jpg',
        'image/webp' => 'webp',
    ];

    if (!isset($allowed[$mime])) {
        $result['error'] = 'Upload a PNG, JPEG, or WebP image. SVG uploads are not allowed.';
        return $result;
    }

    $imageInfo = @getimagesize((string) $file['tmp_name']);
    if ($imageInfo === false) {
        $result['error'] = 'The uploaded file is not a readable image.';
        return $result;
    }

    if ($imageInfo[0] > 4000 || $imageInfo[1] > 4000) {
        $result['error'] = 'The logo dimensions must be 4000 pixels or smaller.';
        return $result;
    }

    if (!is_dir(UPLOAD_DIR) && !@mkdir(UPLOAD_DIR, 0775, true)) {
        $result['error'] = 'The upload directory is not writable.';
        return $result;
    }

    $extension = $allowed[$mime];
    $filename = slugify($slug) . '-' . gmdate('YmdHis') . '-' . bin2hex(random_bytes(4)) . '.' . $extension;
    $destination = UPLOAD_DIR . DIRECTORY_SEPARATOR . $filename;

    if (!move_uploaded_file((string) $file['tmp_name'], $destination)) {
        $result['error'] = 'The logo could not be saved.';
        return $result;
    }

    @chmod($destination, 0644);
    $result['path'] = UPLOAD_URL . '/' . $filename;

    return $result;
}

function admin_remove_uploaded_logo(?string $path): void
{
    $path = trim((string) $path);
    if ($path === '' || !str_starts_with($path, UPLOAD_URL . '/')) {
        return;
    }

    $basename = basename($path);
    $full = UPLOAD_DIR . DIRECTORY_SEPARATOR . $basename;
    if (is_file($full)) {
        @unlink($full);
    }
}

/**
 * @return array{0:array,1:array<int,string>}
 */
function admin_validate_category(array $input, ?array $existing = null): array
{
    $data = [
        'category_name' => trim((string) ($input['category_name'] ?? '')),
        'category_slug' => slugify((string) ($input['category_slug'] ?? '')),
        'description'   => trim((string) ($input['description'] ?? '')),
        'icon'          => trim((string) ($input['icon'] ?? 'fa-tv')),
        'color'         => strtolower(trim((string) ($input['color'] ?? '#ff3b47'))),
        'display_order' => max(0, min(65535, (int) ($input['display_order'] ?? 0))),
    ];
    $errors = [];

    if (mb_strlen($data['category_name']) < 2 || mb_strlen($data['category_name']) > 80) {
        $errors[] = 'Category name must be 2–80 characters.';
    }
    if (mb_strlen($data['description']) > 255) {
        $errors[] = 'Description must be 255 characters or fewer.';
    }
    if (preg_match('/^fa-[a-z0-9-]+$/', $data['icon']) !== 1) {
        $errors[] = 'Icon must be a Font Awesome class such as fa-tv.';
    }
    if (preg_match('/^#[0-9a-f]{6}$/', $data['color']) !== 1) {
        $errors[] = 'Colour must be a six-digit hex value such as #3b82f6.';
    }

    $sql = 'SELECT COUNT(*) FROM categories WHERE category_slug = :slug';
    $params = ['slug' => $data['category_slug']];
    if ($existing !== null) {
        $sql .= ' AND id <> :id';
        $params['id'] = $existing['id'];
    }
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    if ((int) $stmt->fetchColumn() > 0) {
        $errors[] = 'That category slug is already in use.';
    }

    if ($data['description'] === '') {
        $data['description'] = null;
    }

    return [$data, $errors];
}

/**
 * @return array{0:array,1:array<int,string>}
 */
function admin_validate_country(array $input, ?array $existing = null): array
{
    $data = [
        'country_name'  => trim((string) ($input['country_name'] ?? '')),
        'country_code'  => strtoupper(trim((string) ($input['country_code'] ?? ''))),
        'flag_emoji'    => trim((string) ($input['flag_emoji'] ?? '')),
        'region'        => trim((string) ($input['region'] ?? 'International')),
        'display_order' => max(0, min(65535, (int) ($input['display_order'] ?? 0))),
    ];
    $errors = [];

    if (mb_strlen($data['country_name']) < 2 || mb_strlen($data['country_name']) > 100) {
        $errors[] = 'Country name must be 2–100 characters.';
    }
    if (preg_match('/^[A-Z]{2}$/', $data['country_code']) !== 1) {
        $errors[] = 'Country code must be two uppercase ISO letters, such as PH.';
    }
    if (mb_strlen($data['flag_emoji']) > 16) {
        $errors[] = 'The flag field is too long.';
    }
    if (mb_strlen($data['region']) < 2 || mb_strlen($data['region']) > 60) {
        $errors[] = 'Region must be 2–60 characters.';
    }

    $sql = 'SELECT COUNT(*) FROM countries WHERE country_code = :code';
    $params = ['code' => $data['country_code']];
    if ($existing !== null) {
        $sql .= ' AND id <> :id';
        $params['id'] = $existing['id'];
    }
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    if ((int) $stmt->fetchColumn() > 0) {
        $errors[] = 'That country code is already in use.';
    }

    return [$data, $errors];
}

/**
 * @return array{0:array,1:array<int,string>}
 */
function admin_validate_nba_game(array $input): array
{
    $data = [
        'espn_game_id'  => trim((string) ($input['espn_game_id'] ?? '')),
        'game_date'     => local_input_to_utc((string) ($input['game_date'] ?? '')),
        'home_team'     => trim((string) ($input['home_team'] ?? '')),
        'away_team'     => trim((string) ($input['away_team'] ?? '')),
        'home_abbr'     => strtoupper(trim((string) ($input['home_abbr'] ?? ''))),
        'away_abbr'     => strtoupper(trim((string) ($input['away_abbr'] ?? ''))),
        'home_score'    => trim((string) ($input['home_score'] ?? '')),
        'away_score'    => trim((string) ($input['away_score'] ?? '')),
        'game_status'   => (string) ($input['game_status'] ?? 'scheduled'),
        'status_detail' => trim((string) ($input['status_detail'] ?? '')),
        'venue'         => trim((string) ($input['venue'] ?? '')),
        'broadcast'     => trim((string) ($input['broadcast'] ?? '')),
        'official_url'  => trim((string) ($input['official_url'] ?? '')),
        'notes'         => trim((string) ($input['notes'] ?? '')),
    ];
    $errors = [];

    if ($data['game_date'] === null) {
        $errors[] = 'Choose a valid game date and tip-off time (Philippine time).';
    }
    if (mb_strlen($data['home_team']) < 2 || mb_strlen($data['home_team']) > 100) {
        $errors[] = 'Enter the home team name.';
    }
    if (mb_strlen($data['away_team']) < 2 || mb_strlen($data['away_team']) > 100) {
        $errors[] = 'Enter the away team name.';
    }
    if ($data['home_abbr'] !== '' && mb_strlen($data['home_abbr']) > 10) {
        $errors[] = 'Home abbreviation must be 10 characters or fewer.';
    }
    if ($data['away_abbr'] !== '' && mb_strlen($data['away_abbr']) > 10) {
        $errors[] = 'Away abbreviation must be 10 characters or fewer.';
    }
    if (!in_array($data['game_status'], ['scheduled', 'live', 'final', 'postponed'], true)) {
        $errors[] = 'Choose a valid game status.';
    }
    if (mb_strlen($data['status_detail']) > 100) {
        $errors[] = 'Status detail must be 100 characters or fewer.';
    }
    if (mb_strlen($data['venue']) > 150) {
        $errors[] = 'Venue must be 150 characters or fewer.';
    }
    if (mb_strlen($data['broadcast']) > 150) {
        $errors[] = 'Broadcast must be 150 characters or fewer.';
    }
    if ($data['notes'] !== '' && mb_strlen($data['notes']) > 255) {
        $errors[] = 'Notes must be 255 characters or fewer.';
    }
    if ($data['espn_game_id'] !== '' && mb_strlen($data['espn_game_id']) > 40) {
        $errors[] = 'ESPN game ID must be 40 characters or fewer.';
    }

    foreach (['home_score', 'away_score'] as $scoreKey) {
        if ($data[$scoreKey] !== '' && !preg_match('/^\d{1,4}$/', $data[$scoreKey])) {
            $errors[] = 'Scores must be blank or numeric.';
        }
    }

    if ($data['official_url'] === '') {
        $data['official_url'] = 'https://www.nba.com/games';
    } elseif (!valid_http_url($data['official_url'])) {
        $errors[] = 'Enter a valid official game or schedule URL.';
    }

    foreach (['espn_game_id', 'home_abbr', 'away_abbr', 'status_detail', 'venue', 'broadcast', 'notes'] as $nullable) {
        if ($data[$nullable] === '') {
            $data[$nullable] = null;
        }
    }

    $data['home_score'] = $data['home_score'] === '' ? null : (int) $data['home_score'];
    $data['away_score'] = $data['away_score'] === '' ? null : (int) $data['away_score'];

    return [$data, $errors];
}

function admin_message_count(): int
{
    return (int) db()->query("SELECT COUNT(*) FROM contact_messages WHERE status = 'new'")->fetchColumn();
}

/**
 * @return array{0:string,1:string,2:string}
 */
function admin_message_status_meta(string $status): array
{
    return match ($status) {
        'new'      => ['New', 'badge badge-live', 'fa-envelope'],
        'read'     => ['Read', 'badge badge-muted', 'fa-envelope-open'],
        'replied'  => ['Replied', 'badge badge-ok', 'fa-reply'],
        'archived' => ['Archived', 'badge badge-muted', 'fa-box-archive'],
        default    => ['Unknown', 'badge badge-muted', 'fa-circle-info'],
    };
}

/**
 * Keep channel availability honest after an admin changes its sources:
 * an “available” channel with no active authorized source is demoted.
 */
function admin_sync_channel_sources(int $channelId): void
{
    $stmt = db()->prepare('SELECT COUNT(*) FROM channel_sources
        WHERE channel_id = :id AND is_active = 1 AND is_authorized = 1');
    $stmt->execute(['id' => $channelId]);
    $active = (int) $stmt->fetchColumn();

    if ($active === 0) {
        $stmt = db()->prepare("UPDATE channels SET availability_status = 'external_only', updated_at = :updated_at
            WHERE id = :id AND availability_status = 'available'");
        $stmt->execute(['updated_at' => utc_now(), 'id' => $channelId]);
    } else {
        sync_channel_availability($channelId);
    }
}

function admin_source_status_meta(string $status): array
{
    return match ($status) {
        'live'       => ['Live', 'badge badge-live', 'fa-tower-broadcast'],
        'reachable'  => ['Reachable', 'badge badge-ok', 'fa-check'],
        'offline'    => ['Offline', 'badge badge-offline', 'fa-circle-slash'],
        'error'      => ['Error', 'badge badge-error', 'fa-triangle-exclamation'],
        'unverified' => ['Unverified', 'badge badge-muted', 'fa-circle-question'],
        default      => ['Unknown', 'badge badge-muted', 'fa-circle-info'],
    };
}

/**
 * @return array{page:int,pages:int,offset:int,limit:int,total:int}
 */
function admin_pagination(int $total, int $perPage = 25): array
{
    $perPage = max(1, min(100, $perPage));
    $pages = max(1, (int) ceil($total / $perPage));
    $page = max(1, min($pages, (int) (ctype_digit(get_string('page')) ? get_string('page') : 1)));

    return [
        'page'   => $page,
        'pages'  => $pages,
        'offset' => ($page - 1) * $perPage,
        'limit'  => $perPage,
        'total'  => $total,
    ];
}

function admin_page_url(array $pagination, int $page): string
{
    return query_url(['page' => max(1, min($pagination['pages'], $page))]);
}
