<?php
// web/api/telemetry-stats.php
// Public, read-only aggregate telemetry stats for display on the product
// site. GET, no auth (this is intentionally public data). Always returns
// the disclaimer alongside the numbers — the two are meant to travel
// together everywhere this is shown, so a frontend can't render the figures
// without also having the "opted-in users only" caveat in the same payload.

const MIN_COHORT_SIZE = 10; // don't publish anything below this many distinct opted-in users in the window

if (PHP_SAPI === 'cli') {
    return;
}

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: public, max-age=3600'); // aggregate stats, fine to cache an hour

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

    $stmt = $pdo->query("
        SELECT
            COUNT(DISTINCT user_id)      AS cohort_size,
            SUM(total_input_events)      AS total_input_events,
            SUM(total_uptime_seconds)    AS total_uptime_seconds
        FROM telemetry_daily_active
        WHERE day >= CURDATE() - INTERVAL 30 DAY
    ");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'DB error']);
    exit;
}

$disclaimer = 'Based only on users who opted in to sharing telemetry data — not all ClickyKeys users.';

if (!$row || (int) $row['cohort_size'] < MIN_COHORT_SIZE) {
    // Not enough opted-in users yet to publish without re-identification risk.
    echo json_encode([
        'available'  => false,
        'disclaimer' => $disclaimer,
    ]);
    exit;
}

echo json_encode([
    'available'            => true,
    'window_days'          => 30,
    'cohort_size'          => (int) $row['cohort_size'],
    'total_input_events'   => (int) $row['total_input_events'],
    'total_uptime_hours'   => (int) round($row['total_uptime_seconds'] / 3600),
    'disclaimer'           => $disclaimer,
]);
