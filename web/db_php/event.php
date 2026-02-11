<?php
// Połączenie z bazą – to samo co w track.php
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

$sessionId   = $data['session_id']   ?? null;
$pageViewId  = $data['page_view_id'] ?? null; 
$path        = $data['path']         ?? '/';
$eventType   = $data['event_type']   ?? 'unknown';
$elementId   = $data['element_id']   ?? null;
$label       = $data['label']        ?? null;
$extra       = $data['extra']        ?? null;


$extraJson = null;
if (is_array($extra)) {
    $extraJson = json_encode($extra, JSON_UNESCAPED_UNICODE);
}


$sql = "
    INSERT INTO click_events (
        occurred_at,
        session_id,
        page_view_id,
        path,
        event_type,
        element_id,
        label,
        extra
    )
    VALUES (
        NOW(),
        :session_id,
        :page_view_id,
        :path,
        :event_type,
        :element_id,
        :label,
        CAST(:extra AS JSON)
    )
";

$stmt = $pdo->prepare($sql);
$stmt->execute([
    ':session_id'   => $sessionId,
    ':page_view_id' => $pageViewId,
    ':path'         => $path,
    ':event_type'   => $eventType,
    ':element_id'   => $elementId,
    ':label'        => $label,
    ':extra'        => $extraJson,
]);

header('Content-Type: application/json');
echo json_encode(['status' => 'ok']);
