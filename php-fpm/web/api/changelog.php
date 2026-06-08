<?php
// web/api/changelog.php
// Returns changelog entries newer than the version provided via ?since=x.y.z
// Only GET requests are accepted. Every request is logged to api_requests.

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

/**
 * Parses and validates a semver string (x.y.z).
 * Returns [major, minor, patch] on success, null on invalid input,
 * or [0, 0, 0] when no version is provided (returns full history).
 */
function changelog_parse_since(?string $since): ?array
{
    if ($since === null) {
        return [0, 0, 0];
    }

    if (!preg_match('/^\d{1,4}\.\d{1,4}\.\d{1,4}$/', $since)) {
        return null;
    }

    return array_map('intval', explode('.', $since));
}

/**
 * Queries the changelog table for all published entries with a version
 * strictly greater than the given major.minor.patch triple.
 *
 * Semver comparison is done across three separate INT columns so the
 * WHERE clause is index-friendly — no string tricks needed.
 *
 * Rows are grouped by version and returned newest-first.
 */
function changelog_fetch(PDO $pdo, int $major, int $minor, int $patch): array
{
    $sql = "
        SELECT version, release_date, change_type, summary, detail
        FROM   changelog
        WHERE  is_published = 1
          AND (
              version_major >  :maj
              OR (version_major = :maj AND version_minor >  :min)
              OR (version_major = :maj AND version_minor = :min AND version_patch > :pat)
          )
        ORDER BY version_major DESC, version_minor DESC, version_patch DESC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':maj' => $major, ':min' => $minor, ':pat' => $patch]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Group individual change rows under their parent version object
    $versionsMap = [];
    foreach ($rows as $row) {
        $v = $row['version'];

        if (!isset($versionsMap[$v])) {
            $versionsMap[$v] = [
                'version'      => $v,
                'release_date' => $row['release_date'],
                'changes'      => [],
            ];
        }

        $entry = [
            'type'    => $row['change_type'],
            'summary' => $row['summary'],
        ];

        // Include detail only when present — keeps the payload lean
        if (!empty($row['detail'])) {
            $entry['detail'] = $row['detail'];
        }

        $versionsMap[$v]['changes'][] = $entry;
    }

    return array_values($versionsMap);
}

/**
 * Masks the last octet of an IPv4 address or the last group of an IPv6
 * address before storing it, in line with privacy requirements.
 */
function anonymize_ip(?string $ip): ?string
{
    if (!$ip) {
        return null;
    }

    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
        $parts = explode('.', $ip);
        if (count($parts) === 4) {
            $parts[3] = 'x';
            return implode('.', $parts);
        }
    }

    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
        $parts = explode(':', $ip);
        $parts[count($parts) - 1] = '0000';
        return implode(':', $parts);
    }

    return null;
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
// Input validation
// ---------------------------------------------------------------------------

$since = $_GET['since'] ?? null;
$parts = changelog_parse_since($since);

if ($parts === null) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid version format. Expected x.y.z']);
    exit;
}

[$maj, $min, $pat] = $parts;

// ---------------------------------------------------------------------------
// Fetch changelog entries
// ---------------------------------------------------------------------------

$result = changelog_fetch($pdo, $maj, $min, $pat);

// ---------------------------------------------------------------------------
// Log the request to api_requests (same pattern as releases.php)
// ---------------------------------------------------------------------------

$requestPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$rawIp       = $_SERVER['HTTP_X_REAL_IP'] ?? null;
$userAgent   = $_SERVER['HTTP_USER_AGENT'] ?? '';

// Determine client type from User-Agent
$clientType = 'other';
if (stripos($userAgent, 'firefox')   !== false
 || stripos($userAgent, 'chrome')    !== false
 || stripos($userAgent, 'safari')    !== false
 || stripos($userAgent, 'macintosh') !== false) {
    $clientType = 'browser';
} elseif (stripos($userAgent, 'application') !== false) {
    $clientType = 'application';
}

// Extract app version from "ClickyKeysApp/x.y.z"
$clientVersion = null;
if (preg_match('/ClickyKeysApp\/([0-9\.]+)/', $userAgent, $m)) {
    $clientVersion = $m[1];
}

// Extract distribution channel from "Distro/xxx"
$clientDistribution = null;
if (preg_match('/Distro\/([A-Za-z]+)/', $userAgent, $m)) {
    if (in_array($m[1], ['github', 'store', 'dev'])) {
        $clientDistribution = $m[1];
    }
}

$logStmt = $pdo->prepare("
    INSERT INTO api_requests (requested_at, path, anon_ip, client_type, version, distribution, trigger_type)
    VALUES (NOW(), :path, :anon_ip, :client_type, :version, :distribution, :trigger_type)
");

$logStmt->execute([
    ':path'         => $requestPath,
    ':anon_ip'      => anonymize_ip($rawIp),
    ':client_type'  => $clientType,
    ':version'      => $clientVersion,
    ':distribution' => $clientDistribution,
    ':trigger_type' => 'update',
]);

// ---------------------------------------------------------------------------
// Response
// ---------------------------------------------------------------------------

echo json_encode([
    'since'   => $since ?? '0.0.0',
    'count'   => count($result),
    'entries' => $result,
], JSON_UNESCAPED_UNICODE);