<?php
// Połączenie z bazą
$host = getenv('MYSQL_HOST');
$db   = getenv('MYSQL_DATABASE');
$user = getenv('MYSQL_USER');
$pass = getenv('MYSQL_PASSWORD');

$dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";

header('Content-Type: application/json; charset=utf-8');

try {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo 'DB error';
    exit;
}

// Proste zapytanie
$stmt = $pdo->query("SELECT id, release_id, safety_signature, release_date FROM release_library");
$releases = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($releases as &$r) {
    if (!empty($r['release_date'])) {
        $r['release_date'] = date('Y-m-d\TH:i:s', strtotime($r['release_date']));
    }
}


$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);


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

$clientType = null;
if (stripos($userAgent, 'firefox') !== false || stripos($userAgent, 'chrome') !== false || stripos($userAgent, 'safari') !== false || stripos($userAgent, 'macintosh') !== false) $clientType = 'browser';
elseif (stripos($userAgent, 'application') !== false) $clientType = 'application';
else $clientType = 'other';

$clientVersion = null;
if (stripos($userAgent, 'ClickyKeys') !== false) {
    if (preg_match('/ClickyKeysApp\/([0-9\.]+)/', $userAgent, $matches)) {
        $clientVersion = $matches[1];
    }
}

$clientDistribution = null;
if (stripos($userAgent, 'Distro') !== false) {
    if (preg_match('/Distro\/([A-Za-z]+)/', $userAgent, $matches)) {
        if ($matches[1] == 'github' || $matches[1] == 'store' || $matches[1] == 'dev'){
            $clientDistribution = $matches[1];
        }
    }
}

$sql = "
    INSERT INTO api_requests (
        requested_at,
        path,
        anon_ip,
        client_type,
	    version,
        distribution
    )
    VALUES (
        NOW(),
        :path,
        :anon_ip,
        :client_type,
	    :version,
        :distribution
    )
";

$stmt = $pdo->prepare($sql);
$stmt->execute([
    ':path'            => $path,
    ':anon_ip'         => $anonIp,
    ':client_type'     => $clientType,
    ':version'	       => $clientVersion,
    ':distribution'    => $clientDistribution
]);


echo json_encode($releases);
