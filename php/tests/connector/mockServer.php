<?php
// Local mock upstream for connector smoke tests. Run: php -S 127.0.0.1:8099 php/tests/connector/mockServer.php
if (PHP_SAPI !== 'cli-server') {
    exit;
}

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

if (strpos($path, '/echo') === 0) {
    header('Content-Type: application/json');
    echo json_encode([
        'method' => $_SERVER['REQUEST_METHOD'],
        'headers' => array_change_key_case(getallheaders(), CASE_LOWER),
        'query' => $_GET,
        'body' => file_get_contents('php://input'),
    ]);
    return true;
}

if (preg_match('#^/status/(\d{3})$#', $path, $m)) {
    http_response_code((int)$m[1]);
    header('Content-Type: application/json');
    // Echo the key back so the redaction check has something to catch
    echo json_encode(['error' => ['detail' => 'status ' . $m[1] . ' ' . ($_SERVER['HTTP_X_API_KEY'] ?? '')]]);
    return true;
}

if ($path === '/slow') {
    sleep(3);
    echo 'late';
    return true;
}

if ($path === '/redirect') {
    header('Location: http://example.com/');
    http_response_code(302);
    return true;
}

http_response_code(404);
echo 'not found';
return true;
