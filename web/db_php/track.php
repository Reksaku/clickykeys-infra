<?php
// track.php
$host = getenv('MYSQL_HOST');
$db   = getenv('MYSQL_DATABASE');
$user = getenv('MYSQL_USER');
$pass = getenv('MYSQL_PASSWORD');

$dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";

try {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo 'DB error';
    exit;
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

if (!is_array($data)) {
    http_response_code(400);
    echo 'Bad JSON';
    exit;
}

$ip = $_SERVER['HTTP_X_REAL_IP'] ?? null;

function anonymize_ip($ip) {
    if (!$ip) return null;

    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
        $parts = explode('.', $ip);
        if (count($parts) === 4) {
            $parts[3] = 'x';
            return implode('.', $parts);
        }
    }


    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
        $parts = explode(':', $ip);
        $parts[count($parts)-1] = '0000';
        return implode(':', $parts);
    }

    return null;
}

$anonIp = anonymize_ip($ip);

$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';

function detect_device_type($ua) {
    $ua = strtolower($ua);
    if (strpos($ua, 'mobile') !== false) return 'mobile';
    if (strpos($ua, 'tablet') !== false || strpos($ua, 'ipad') !== false) return 'tablet';
    return 'desktop';
}

$deviceType = detect_device_type($userAgent);

$browser = null;
if (stripos($userAgent, 'firefox') !== false) $browser = 'Firefox';
elseif (stripos($userAgent, 'chrome') !== false) $browser = 'Chrome';
elseif (stripos($userAgent, 'safari') !== false) $browser = 'Safari';

$os = null;
if (stripos($userAgent, 'windows') !== false) $os = 'Windows';
elseif (stripos($userAgent, 'linux') !== false) $os = 'Linux';
elseif (stripos($userAgent, 'mac os') !== false || stripos($userAgent, 'macintosh') !== false) $os = 'macOS';

$path = $data['path'] ?? '/';
if (
    !(
        str_starts_with($path, "/pl/") ||
        str_starts_with($path, "/?utm_source") ||
	str_starts_with($path, "/qr") ||
	str_starts_with($path, "/update") ||
	$path === "/"
    )
) {
    $path = "modified";
}

$sessionId      = $data['session_id']      ?? null;
$referrer        = $data['referrer']        ?? null;
$viewport_width  = $data['viewport_width']  ?? null;
$viewport_height = $data['viewport_height'] ?? null;
$load_time_ms    = $data['load_time_ms']    ?? null;
$js_enabled      = 1; 


$sql = "
    INSERT INTO page_views (
        visited_at,
        session_id,
        path,
        referrer,
        anon_ip,
        device_type,
        browser,
        os,
        viewport_width,
        viewport_height,
        load_time_ms,
        js_enabled
    )
    VALUES (
        NOW(),
        :session_id,
        :path,
        :referrer,
        :anon_ip,
        :device_type,
        :browser,
        :os,
        :viewport_width,
        :viewport_height,
        :load_time_ms,
        :js_enabled
    )
";

$stmt = $pdo->prepare($sql);
$stmt->execute([
    ':session_id'      => $sessionId,
    ':path'            => $path,
    ':referrer'        => $referrer,
    ':anon_ip'         => $anonIp,
    ':device_type'     => $deviceType,
    ':browser'         => $browser,
    ':os'              => $os,
    ':viewport_width'  => $viewport_width,
    ':viewport_height' => $viewport_height,
    ':load_time_ms'    => $load_time_ms,
    ':js_enabled'      => $js_enabled
]);


$pageViewId = $pdo->lastInsertId();

header('Content-Type: application/json');
echo json_encode([
    'status'        => 'ok',
    'page_view_id'  => (int)$pageViewId,
]);
