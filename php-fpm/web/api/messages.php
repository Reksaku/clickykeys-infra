<?php
// web/api/messages.php
// Returns announcement/news messages newer than the signed cursor provided via
// ?cursor=... , filtered to the caller's distribution channel and app version
// (read from the User-Agent). Only GET requests are accepted. Request logging
// to api_requests is handled by a separate function, so it is not repeated here.
//
// Incremental protocol: the client stores the opaque, server-signed cursor it
// received last time and echoes it back. The server returns only messages with
// publish_at after that cursor's watermark and issues a fresh cursor. No cursor
// (first run) opens the window at "now", so a brand-new user gets no backlog.
// See MESSAGES_BACKEND.md.

// ---------------------------------------------------------------------------
// Cursor helpers — opaque token signed with HMAC-SHA256 (secret is server-only)
// ---------------------------------------------------------------------------

function messages_b64url_encode(string $bin): string
{
    return rtrim(strtr(base64_encode($bin), '+/', '-_'), '=');
}

function messages_b64url_decode(string $s): string
{
    return base64_decode(strtr($s, '-_', '+/')) ?: '';
}

/**
 * Builds an opaque cursor carrying a UTC watermark (unix seconds), signed so
 * the client cannot forge or shift it.
 */
function messages_make_cursor(int $watermark, string $secret): string
{
    $payload = messages_b64url_encode(
        json_encode(['v' => 1, 'w' => $watermark], JSON_UNESCAPED_SLASHES)
    );
    $sig = messages_b64url_encode(hash_hmac('sha256', $payload, $secret, true));

    return "v1.$payload.$sig";
}

/**
 * Verifies a cursor and returns its watermark (unix seconds), or null when the
 * cursor is absent, malformed, or fails the signature check (tampered / issued
 * with a different secret).
 */
function messages_parse_cursor(?string $cursor, string $secret): ?int
{
    if (!$cursor) {
        return null;
    }

    $parts = explode('.', $cursor);
    if (count($parts) !== 3 || $parts[0] !== 'v1') {
        return null;
    }

    [, $payload, $sig] = $parts;

    $expected = messages_b64url_encode(hash_hmac('sha256', $payload, $secret, true));
    if (!hash_equals($expected, $sig)) {
        return null;
    }

    $data = json_decode(messages_b64url_decode($payload), true);
    if (!is_array($data) || !isset($data['w'])) {
        return null;
    }

    return (int) $data['w'];
}

// ---------------------------------------------------------------------------
// Version helpers
// ---------------------------------------------------------------------------

/**
 * Parses a semver string (x.y.z) into [major, minor, patch], or null when the
 * input is missing or malformed. Mirrors changelog_parse_since's validation.
 */
function messages_parse_version(?string $version): ?array
{
    if ($version === null || !preg_match('/^\d{1,4}\.\d{1,4}\.\d{1,4}$/', $version)) {
        return null;
    }

    return array_map('intval', explode('.', $version));
}

/**
 * Turns a row's version-range columns back into the compact string the client
 * understands: "2.4.2" (exact), "2.4.0+" (that version or newer), or ""
 * (no lower bound the client can express — any max is still enforced in SQL).
 */
function messages_target_version(array $row): string
{
    $hasMin = $row['version_min_major'] !== null;
    $hasMax = $row['version_max_major'] !== null;

    $min = $hasMin ? "{$row['version_min_major']}.{$row['version_min_minor']}.{$row['version_min_patch']}" : null;
    $max = $hasMax ? "{$row['version_max_major']}.{$row['version_max_minor']}.{$row['version_max_patch']}" : null;

    if ($min !== null && $max !== null && $min === $max) {
        return $min;                 // exact
    }
    if ($min !== null) {
        return $min . '+';           // lower bound (with or without an upper one)
    }

    return '';                       // max-only or unrestricted → "all" for the client
}

// ---------------------------------------------------------------------------
// Query
// ---------------------------------------------------------------------------

/**
 * Fetches published, non-expired messages whose publish_at is strictly after
 * the watermark and not in the future, targeted at the caller's distribution
 * channel and app version.
 *
 * Version range is compared across separate INT columns (like changelog), so
 * the WHERE clause stays index-friendly and semver-correct. dev builds bypass
 * distribution targeting so a local tester can preview everything.
 *
 * @param array|null $ver [major, minor, patch] of the client, or null if unknown.
 */
function messages_fetch(PDO $pdo, string $sinceUtc, string $nowUtc, ?string $distro, ?array $ver): array
{
    $params = [
        ':since' => $sinceUtc,
        ':now'   => $nowUtc,
        ':now2'  => $nowUtc,
    ];

    $sql = "
        SELECT id, type, title, body, publish_at, expires_at,
               target_distributions,
               version_min_major, version_min_minor, version_min_patch,
               version_max_major, version_max_minor, version_max_patch,
               link_label, link_url
        FROM   messages
        WHERE  is_published = 1
          AND  publish_at >  :since
          AND  publish_at <= :now
          AND (expires_at IS NULL OR expires_at > :now2)
    ";

    // Distribution targeting: untargeted rows go to everyone; targeted rows must
    // list the caller's channel. dev sees all.
    if ($distro !== 'dev') {
        $sql .= "
          AND (target_distributions IS NULL
               OR target_distributions = ''
               OR FIND_IN_SET(:distro, target_distributions))
        ";
        $params[':distro'] = $distro ?? '';
    }

    // Version targeting, evaluated against the three INT columns.
    if ($ver !== null) {
        // Lower bound satisfied: min is NULL, or client >= min.
        $sql .= "
          AND (version_min_major IS NULL
               OR :cmaj >  version_min_major
               OR (:cmaj = version_min_major AND :cmin >  version_min_minor)
               OR (:cmaj = version_min_major AND :cmin = version_min_minor AND :cpat >= version_min_patch))
        ";
        // Upper bound satisfied: max is NULL, or client <= max.
        $sql .= "
          AND (version_max_major IS NULL
               OR :cmaj2 <  version_max_major
               OR (:cmaj2 = version_max_major AND :cmin2 <  version_max_minor)
               OR (:cmaj2 = version_max_major AND :cmin2 = version_max_minor AND :cpat2 <= version_max_patch))
        ";
        [$params[':cmaj'], $params[':cmin'], $params[':cpat']] = $ver;
        [$params[':cmaj2'], $params[':cmin2'], $params[':cpat2']] = $ver;
    } else {
        // Unknown client version → only messages with no version restriction.
        $sql .= " AND version_min_major IS NULL AND version_max_major IS NULL ";
    }

    $sql .= " ORDER BY publish_at ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $messages = [];
    foreach ($rows as $row) {
        $dists = ($row['target_distributions'] ?? '') === ''
            ? []
            : explode(',', $row['target_distributions']);

        $entry = [
            'id'         => (int) $row['id'],
            'type'       => $row['type'],
            'title'      => $row['title'],
            'body'       => $row['body'],
            'publish_at' => gmdate('Y-m-d\TH:i:s\Z', strtotime($row['publish_at'] . ' UTC')),
            'expires_at' => $row['expires_at']
                ? gmdate('Y-m-d\TH:i:s\Z', strtotime($row['expires_at'] . ' UTC'))
                : null,
            'target'     => [
                'distributions' => $dists,
                'version'       => messages_target_version($row),
            ],
        ];

        // Include the CTA only when a link is present — keeps the payload lean.
        if (!empty($row['link_url'])) {
            $entry['link'] = [
                'label' => $row['link_label'] ?? '',
                'url'   => $row['link_url'],
            ];
        }

        $messages[] = $entry;
    }

    return $messages;
}

// ---------------------------------------------------------------------------
// Request handling — skipped when running under CLI (e.g. unit tests)
// ---------------------------------------------------------------------------

if (PHP_SAPI === 'cli') {
    return;
}

// Only GET is supported
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    header('Allow: GET');
    exit;
}

header('Content-Type: application/json; charset=utf-8');

// ---------------------------------------------------------------------------
// Cursor secret
// ---------------------------------------------------------------------------

$cursorSecret = getenv('MESSAGES_CURSOR_SECRET');
if (!$cursorSecret) {
    http_response_code(500);
    echo json_encode(['error' => 'Server misconfigured']);
    exit;
}

// ---------------------------------------------------------------------------
// Database connection
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
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'DB error']);
    exit;
}

// ---------------------------------------------------------------------------
// Resolve the delivery window from the cursor
// ---------------------------------------------------------------------------

$now      = new DateTimeImmutable('now', new DateTimeZone('UTC'));
$nowUtc   = $now->format('Y-m-d H:i:s');
$nowEpoch = $now->getTimestamp();

// Valid cursor → its watermark; otherwise first run → open the window at "now".
$watermark = messages_parse_cursor($_GET['cursor'] ?? null, $cursorSecret) ?? $nowEpoch;
$sinceUtc  = gmdate('Y-m-d H:i:s', $watermark);

// ---------------------------------------------------------------------------
// Identify the caller from the User-Agent (version + distribution channel)
// ---------------------------------------------------------------------------

$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';

// Extract app version from "ClickyKeysApp/x.y.z"
$clientVersion = null;
if (preg_match('/ClickyKeysApp\/([0-9\.]+)/', $userAgent, $m)) {
    $clientVersion = $m[1];
}
$clientVersionParts = messages_parse_version($clientVersion);

// Extract distribution channel from "Distro/xxx"
$clientDistribution = null;
if (preg_match('/Distro\/([A-Za-z]+)/', $userAgent, $m)) {
    if (in_array($m[1], ['github', 'store', 'dev'])) {
        $clientDistribution = $m[1];
    }
}

// ---------------------------------------------------------------------------
// Fetch messages
// ---------------------------------------------------------------------------

$result = messages_fetch($pdo, $sinceUtc, $nowUtc, $clientDistribution, $clientVersionParts);

// ---------------------------------------------------------------------------
// Response
// ---------------------------------------------------------------------------
// New watermark = this request's time: monotonic and immune to the client
// clock. On any earlier fatal error we already exited above; the client keeps
// its stored cursor in that case, so nothing is lost.

echo json_encode([
    'cursor'   => messages_make_cursor($nowEpoch, $cursorSecret),
    'count'    => count($result),
    'messages' => $result,
], JSON_UNESCAPED_UNICODE);
