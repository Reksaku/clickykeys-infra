<?php
// web/api/telemetry.php
// Opt-in, two-tier product telemetry. Accepts one JSON event per request.
//
//   level "basic" -> upserts telemetry_daily_active only (identity fields).
//   level "full"  -> also upserts telemetry_feature_daily (usage snapshot),
//                    keyed the same way (day, user_id) so the two tables
//                    join trivially and right-to-erasure only needs one
//                    DELETE (see the FK ON DELETE CASCADE in the schema).
//
// Unlike releases.php/sponsorship.php/changelog.php this is a POST with a
// JSON body, not a GET read from User-Agent tokens.

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

/**
 * Validates the top-level envelope. Returns sanitized fields on success, or
 * null if anything required is missing/malformed. Pure function — testable
 * under CLI with no DB/network involved.
 */
function telemetry_validate(array $body): ?array
{
    $userId = $body['user_id'] ?? null;
    if (!is_string($userId) || !preg_match('/^[a-f0-9]{32}$/i', $userId)) {
        return null; // must look like a GUID with dashes stripped ("N" format)
    }

    $eventType = $body['event_type'] ?? null;
    if (!in_array($eventType, ['app_start'], true)) {
        return null; // allow-list; extend deliberately, not by accident
    }

    $level = $body['level'] ?? 'basic';
    if (!in_array($level, ['basic', 'full'], true)) {
        return null;
    }

    $appVersion = $body['app_version'] ?? '';
    $distribution = $body['distribution'] ?? '';
    $osVersion = $body['os_version'] ?? '';
    $language = $body['language'] ?? '';

    if (!is_string($appVersion) || !is_string($distribution)
        || !is_string($osVersion) || !is_string($language)) {
        return null;
    }

    // Two aggregate counters now sent at BOTH basic and full tiers. Optional and
    // clamped: older clients omit them, so a missing/malformed value defaults to
    // 0 (matching the NOT NULL DEFAULT 0 columns) rather than rejecting the event.
    $int = static fn($v, $min, $max, $default = 0) =>
        is_numeric($v) ? max($min, min($max, (int) $v)) : $default;

    return [
        'user_id'              => strtolower($userId),
        'event_type'           => $eventType,
        'level'                => $level,
        'app_version'          => substr($appVersion, 0, 20),
        'distribution'         => substr($distribution, 0, 10),
        'os_version'           => substr($osVersion, 0, 100),
        'language'             => substr($language, 0, 10),
        'total_input_events'   => $int($body['total_input_events'] ?? null, 0, PHP_INT_MAX),
        'total_uptime_seconds' => $int($body['total_uptime_seconds'] ?? null, 0, PHP_INT_MAX),
    ];
}

/**
 * Validates the optional Full-tier "feature" object. Every field is
 * coerced to its expected scalar type with a sane clamp/range — this is
 * the one place a malicious or buggy client could send oversized strings
 * or wildly out-of-range numbers, so nothing here is trusted as-is.
 * Returns null if the object is missing entirely (caller only invokes this
 * when level === 'full', where it's required).
 */
function telemetry_validate_feature(mixed $feature): ?array
{
    if (!is_array($feature)) {
        return null;
    }

    $int = static fn($v, $min, $max, $default = 0) =>
        is_numeric($v) ? max($min, min($max, (int) $v)) : $default;
    $bool = static fn($v) => (int) (bool) ($v ?? false);

    return [
        'grid_rows'                  => $int($feature['grid_rows'] ?? null, 1, 20),
        'grid_columns'               => $int($feature['grid_columns'] ?? null, 1, 20),
        'colors_customized'          => $bool($feature['colors_customized'] ?? null),
        'fonts_customized'           => $bool($feature['fonts_customized'] ?? null),
        'rainbow_background_enabled' => $bool($feature['rainbow_background_enabled'] ?? null),
        'rainbow_speed_seconds'      => $int($feature['rainbow_speed_seconds'] ?? null, 1, 10),
        'panels_configured_count'    => $int($feature['panels_configured_count'] ?? null, 0, 100),
        'panels_using_key_input'     => $int($feature['panels_using_key_input'] ?? null, 0, 100),
        'panels_using_mouse_input'   => $int($feature['panels_using_mouse_input'] ?? null, 0, 100),
        'panels_using_wheel_input'   => $int($feature['panels_using_wheel_input'] ?? null, 0, 100),
        'panels_using_gamepad_input' => $int($feature['panels_using_gamepad_input'] ?? null, 0, 100),
        'gamepad_trigger_threshold'  => $int($feature['gamepad_trigger_threshold'] ?? null, 1, 100, 25),
        'autostart_enabled'          => $bool($feature['autostart_enabled'] ?? null),
        'start_minimized'            => $bool($feature['start_minimized'] ?? null),
        'shortcuts_customized'       => $bool($feature['shortcuts_customized'] ?? null),
        'tutorial_completed'         => $bool($feature['tutorial_completed'] ?? null),
        'total_key_presses'          => $int($feature['total_key_presses'] ?? null, 0, PHP_INT_MAX),
        'total_mouse_clicks'         => $int($feature['total_mouse_clicks'] ?? null, 0, PHP_INT_MAX),
        'total_wheel_ticks'          => $int($feature['total_wheel_ticks'] ?? null, 0, PHP_INT_MAX),
        'total_gamepad_presses'      => $int($feature['total_gamepad_presses'] ?? null, 0, PHP_INT_MAX),
        'total_uptime_seconds'       => $int($feature['total_uptime_seconds'] ?? null, 0, PHP_INT_MAX),
    ];
}

// ---------------------------------------------------------------------------
// Request handling — skipped when running under CLI (e.g. PHPUnit)
// ---------------------------------------------------------------------------

if (PHP_SAPI === 'cli') {
    return;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit;
}

header('Content-Type: application/json; charset=utf-8');

// Reject oversized bodies before even attempting to decode. Basic payloads
// are well under 1 KB; Full's feature object is still only a few hundred
// bytes, so 8 KB is generous headroom, not a real limit.
$raw = file_get_contents('php://input', false, null, 0, 8192);
if ($raw === false || $raw === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Empty body']);
    exit;
}

$decoded = json_decode($raw, true);
if (!is_array($decoded)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON']);
    exit;
}

$fields = telemetry_validate($decoded);
if ($fields === null) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid payload']);
    exit;
}

$feature = null;
if ($fields['level'] === 'full') {
    $feature = telemetry_validate_feature($decoded['feature'] ?? null);
    if ($feature === null) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing or invalid feature object for level=full']);
        exit;
    }
}

// ---------------------------------------------------------------------------
// Database connection (same env vars every other endpoint uses)
// ---------------------------------------------------------------------------

$host = getenv('MYSQL_HOST');
$db   = getenv('MYSQL_DATABASE');
$user = getenv('MYSQL_USER');
$pass = getenv('MYSQL_PASSWORD');

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$db;charset=utf8mb4",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'DB error']);
    exit;
}

// ---------------------------------------------------------------------------
// Upserts. (day, user_id) is the primary key on both tables, so a relaunch
// later the same day just refreshes the rows in place — no duplicate rows,
// no separate rate-limit table needed for the common case.
// ---------------------------------------------------------------------------

$pdo->beginTransaction();
try {
    $stmt = $pdo->prepare("
        INSERT INTO telemetry_daily_active
            (day, user_id, level, app_version, distribution, os_version, language,
             total_input_events, total_uptime_seconds)
        VALUES
            (CURDATE(), :user_id, :level, :app_version, :distribution, :os_version, :language,
             :total_input_events, :total_uptime_seconds)
        ON DUPLICATE KEY UPDATE
            level                = VALUES(level),
            app_version          = VALUES(app_version),
            distribution         = VALUES(distribution),
            os_version           = VALUES(os_version),
            language             = VALUES(language),
            total_input_events   = VALUES(total_input_events),
            total_uptime_seconds = VALUES(total_uptime_seconds)
    ");
    $stmt->execute([
        ':user_id'              => $fields['user_id'],
        ':level'                => $fields['level'],
        ':app_version'          => $fields['app_version'],
        ':distribution'         => $fields['distribution'],
        ':os_version'           => $fields['os_version'],
        ':language'             => $fields['language'],
        ':total_input_events'   => $fields['total_input_events'],
        ':total_uptime_seconds' => $fields['total_uptime_seconds'],
    ]);

    if ($feature !== null) {
        $stmt = $pdo->prepare("
            INSERT INTO telemetry_feature_daily (
                day, user_id, grid_rows, grid_columns, colors_customized,
                fonts_customized, rainbow_background_enabled, rainbow_speed_seconds,
                panels_configured_count, panels_using_key_input, panels_using_mouse_input,
                panels_using_wheel_input, panels_using_gamepad_input,
                gamepad_trigger_threshold, autostart_enabled, start_minimized,
                shortcuts_customized, tutorial_completed, total_key_presses,
                total_mouse_clicks, total_wheel_ticks, total_gamepad_presses,
                total_uptime_seconds
            ) VALUES (
                CURDATE(), :user_id, :grid_rows, :grid_columns, :colors_customized,
                :fonts_customized, :rainbow_background_enabled, :rainbow_speed_seconds,
                :panels_configured_count, :panels_using_key_input, :panels_using_mouse_input,
                :panels_using_wheel_input, :panels_using_gamepad_input,
                :gamepad_trigger_threshold, :autostart_enabled, :start_minimized,
                :shortcuts_customized, :tutorial_completed, :total_key_presses,
                :total_mouse_clicks, :total_wheel_ticks, :total_gamepad_presses,
                :total_uptime_seconds
            )
            ON DUPLICATE KEY UPDATE
                grid_rows = VALUES(grid_rows), grid_columns = VALUES(grid_columns),
                colors_customized = VALUES(colors_customized),
                fonts_customized = VALUES(fonts_customized),
                rainbow_background_enabled = VALUES(rainbow_background_enabled),
                rainbow_speed_seconds = VALUES(rainbow_speed_seconds),
                panels_configured_count = VALUES(panels_configured_count),
                panels_using_key_input = VALUES(panels_using_key_input),
                panels_using_mouse_input = VALUES(panels_using_mouse_input),
                panels_using_wheel_input = VALUES(panels_using_wheel_input),
                panels_using_gamepad_input = VALUES(panels_using_gamepad_input),
                gamepad_trigger_threshold = VALUES(gamepad_trigger_threshold),
                autostart_enabled = VALUES(autostart_enabled),
                start_minimized = VALUES(start_minimized),
                shortcuts_customized = VALUES(shortcuts_customized),
                tutorial_completed = VALUES(tutorial_completed),
                total_key_presses = VALUES(total_key_presses),
                total_mouse_clicks = VALUES(total_mouse_clicks),
                total_wheel_ticks = VALUES(total_wheel_ticks),
                total_gamepad_presses = VALUES(total_gamepad_presses),
                total_uptime_seconds = VALUES(total_uptime_seconds)
        ");
        $stmt->execute([':user_id' => $fields['user_id'], ...$feature]);
    }

    $pdo->commit();
} catch (Throwable $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['error' => 'DB error']);
    exit;
}

http_response_code(204);

// NOTE: deliberately NOT logging this request into api_requests like
// releases.php/sponsorship.php/changelog.php do. That table exists to
// count basic API traffic regardless of consent; this endpoint only ever
// fires when the user opted in, and already writes its own row(s) with
// richer data. Inserting into both would double-count the same event under
// two different privacy stories.
