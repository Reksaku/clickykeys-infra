<?php

$pdo = new PDO(
    "mysql:host=" . getenv('MYSQL_HOST') . ";dbname=" . getenv('MYSQL_DATABASE') . ";charset=utf8mb4",
    getenv('MYSQL_USER'),
    getenv('MYSQL_PASSWORD'),
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

// GET
$ip        = $_SERVER['HTTP_X_REAL_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? null;
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
$path = $_SERVER['REQUEST_URI'];
$referrer  = $_SERVER['HTTP_REFERER'] ?? null;

// track.php 
function anonymize_ip($ip) {
    if (!$ip) return null;
    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
        $parts = explode('.', $ip);
        $parts[3] = 'x';
        return implode('.', $parts);
    }
    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
        $parts = explode(':', $ip);
        $parts[count($parts)-1] = '0000';
        return implode(':', $parts);
    }
    return null;
}

function detect_device_type($ua) {
    $ua = strtolower($ua);
    if (strpos($ua, 'mobile') !== false) return 'mobile';
    if (strpos($ua, 'tablet') !== false || strpos($ua, 'ipad') !== false) return 'tablet';
    return 'desktop';
}

$browser = null;
if (stripos($userAgent, 'firefox') !== false)     $browser = 'Firefox';
elseif (stripos($userAgent, 'chrome') !== false)  $browser = 'Chrome';
elseif (stripos($userAgent, 'safari') !== false)  $browser = 'Safari';

$os = null;
if (stripos($userAgent, 'windows') !== false)                                      $os = 'Windows';
elseif (stripos($userAgent, 'linux') !== false)                                    $os = 'Linux';
elseif (stripos($userAgent, 'mac os') !== false || stripos($userAgent, 'macintosh') !== false) $os = 'macOS';


$stmt = $pdo->prepare("
    INSERT INTO page_views (
        visited_at, session_id, path, referrer,
        anon_ip, device_type, browser, os, js_enabled
    ) VALUES (
        NOW(), :session_id, :path, :referrer,
        :anon_ip, :device_type, :browser, :os, 0
    )
");

$stmt->execute([
    ':session_id'  => $_GET['sid'] ?? null,
    ':path'        => $path,
    ':referrer'    => $referrer,
    ':anon_ip'     => anonymize_ip($ip),
    ':device_type' => detect_device_type($userAgent),
    ':browser'     => $browser,
    ':os'          => $os,
]);

// Redirect
$target = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');

if (empty($target)) {
    header('Location: https://github.com', true, 302);
} else {
    header('Location: https://github.com/' . $target, true, 302);
}
exit;