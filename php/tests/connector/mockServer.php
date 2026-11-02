<?php
// Local mock upstream for connector tests. ConnectorClientTest starts and stops it on 127.0.0.1:8099.
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

// JWT vendor. Logins and logouts are counted in a temp file, because each request is a new process here.
if (strpos($path, '/jwt/') === 0) {
    header('Content-Type: application/json');
    $stateFile = sys_get_temp_dir() . '/wms_connector_mock_jwt.json';
    $state = json_decode((string)@file_get_contents($stateFile), true) ?: ['logins' => 0, 'logouts' => 0, 'valid' => '', 'refuse_all' => false];

    if ($path === '/jwt/state') {
        echo json_encode($state);
    } elseif ($path === '/jwt/reset') {
        @unlink($stateFile);
        echo '{}';
    } elseif ($path === '/jwt/revoke') {
        $state['valid'] = '';
        file_put_contents($stateFile, json_encode($state));
        echo '{}';
    } elseif ($path === '/jwt/refuse_all') {
        $state['refuse_all'] = true;
        file_put_contents($stateFile, json_encode($state));
        echo '{}';
    } elseif (preg_match('#^/jwt/login/(\w+)$#', $path, $m)) {
        $input = json_decode(file_get_contents('php://input'), true);
        if (($input['user'] ?? '') !== 'alice' || ($input['pass'] ?? '') !== 'JWT-PASS-SECRET-1') {
            http_response_code(401);
            echo json_encode(['error' => 'bad login']);
            return true;
        }
        $state['logins']++;
        // Plain token unless the variant is about the exp claim
        $token = 'JWT-TOKEN-' . $state['logins'] . '-' . bin2hex(random_bytes(4));
        $reply = ['data' => []];
        if ($m[1] === 'date') {
            $reply['data']['expires_at'] = '2099-01-02 03:04:05';
        } elseif ($m[1] === 'seconds') {
            $reply['data']['expires_at'] = 7200;
        } elseif ($m[1] === 'exp') {
            $token = rtrim(strtr(base64_encode('{"alg":"none"}'), '+/', '-_'), '=') . '.' . rtrim(strtr(base64_encode(json_encode(['exp' => 4070908800])), '+/', '-_'), '=') . '.' . $state['logins'];
        }
        if ($m[1] !== 'notoken') {
            $reply['data']['token'] = $token;
            $state['valid'] = $token;
        }
        file_put_contents($stateFile, json_encode($state));
        echo json_encode($reply);
    } elseif ($path === '/jwt/data') {
        $sent = str_replace('Bearer ', '', $_SERVER['HTTP_AUTHORIZATION'] ?? '');
        if ($state['refuse_all'] || $sent === '' || $sent !== $state['valid']) {
            http_response_code(401);
            echo json_encode(['error' => 'token refused']);
            return true;
        }
        echo json_encode(['ok' => true, 'token_echo' => $sent]);
    } elseif ($path === '/jwt/logout') {
        $sent = str_replace('Bearer ', '', $_SERVER['HTTP_AUTHORIZATION'] ?? '');
        if ($sent === $state['valid'] && $sent !== '') {
            $state['logouts']++;
            $state['valid'] = '';
            file_put_contents($stateFile, json_encode($state));
        }
        echo '{}';
    } else {
        http_response_code(404);
        echo '{}';
    }
    return true;
}

if ($path === '/utf16') {
    header('Content-Type: application/json');
    echo "\xFE\xFF" . mb_convert_encoding('{"doc":"caf' . "\u{e9}" . '"}', 'UTF-16BE', 'UTF-8');
    return true;
}

if ($path === '/binary') {
    header('Content-Type: application/pdf');
    echo "%PDF-1.7 \xE2\xE3\xCF\xD3";
    return true;
}

if ($path === '/large') {
    header('Content-Type: application/json');
    echo json_encode(['items' => array_fill(0, 2000, 'ten chars.')]);
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
