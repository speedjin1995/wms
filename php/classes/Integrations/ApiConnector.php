<?php
namespace App\Integrations;

/**
 * Calls any outside API from an api_integrations config (decrypted by IntegrationService).
 * No DB access. Never throws: failures come back as ok=false with a generic error.
 */
class ApiConnector
{
    /**
     * $request: operation (SOAP), path, http_method, query, json | form | body, headers
     * Returns ['ok' => bool, 'status' => int, 'body' => array|string|object|null, 'error' => string]
     */
    public function call(array $config, array $request): array
    {
        try {
            switch ($config['method'] ?? 'rest') {
                case 'rest':
                    return $this->rest($config, $request);

                case 'soap':
                    return $this->soap($config, $request);

                default:
                    return $this->fail('Unsupported method');
            }
        } catch (\Throwable $e) {
            error_log('ApiConnector ' . $this->host($config) . ' - ' . $e->getMessage());
            return $this->fail('Request failed');
        }
    }

    private function rest(array $config, array $request): array
    {
        $options = $config['options'] ?? [];
        $credentials = $config['credentials'] ?? [];

        $url = rtrim($config['base_url'], '/');
        if (!empty($request['path'])) {
            $url .= '/' . ltrim($request['path'], '/');
        }
        if (!empty($request['query'])) {
            $url .= (strpos($url, '?') === false ? '?' : '&') . http_build_query($request['query']);
        }

        $headers = array_merge($config['headers'] ?? [], $request['headers'] ?? []);

        switch ($config['auth_type'] ?? 'none') {
            case 'bearer':
                $headers['Authorization'] = 'Bearer ' . ($credentials['token'] ?? '');
                break;

            case 'api_key':
                // Header name differs per provider: x-api-key, x-goog-api-key, ...
                $headers[$credentials['header'] ?? 'x-api-key'] = $credentials['key'] ?? '';
                break;

            case 'header':
                foreach ($credentials as $name => $value) {
                    $headers[$name] = $value;
                }
                break;
        }

        $body = null;
        if (isset($request['json'])) {
            $body = json_encode($request['json']);
            $headers['Content-Type'] = 'application/json';
        } elseif (isset($request['form'])) {
            $body = http_build_query($request['form']);
            $headers['Content-Type'] = 'application/x-www-form-urlencoded';
        } elseif (isset($request['body'])) {
            $body = (string)$request['body'];
        }

        $lines = [];
        foreach ($headers as $name => $value) {
            $lines[] = $name . ': ' . $value;
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => strtoupper($request['http_method'] ?? ($body === null ? 'GET' : 'POST')),
            CURLOPT_HTTPHEADER => $lines,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => (int)($options['timeout'] ?? 30)
        ]);
        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }
        if (($config['auth_type'] ?? '') === 'basic') {
            curl_setopt($ch, CURLOPT_USERPWD, ($credentials['username'] ?? '') . ':' . ($credentials['password'] ?? ''));
        }

        $raw = curl_exec($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($raw === false) {
            error_log('ApiConnector ' . $this->host($config) . ' - ' . $curlError);
            return $this->fail('Unable to reach the service');
        }

        $decoded = json_decode($raw, true);
        $result = [
            'ok' => $status >= 200 && $status < 300,
            'status' => $status,
            'body' => $decoded !== null ? $decoded : $raw,
            'error' => ''
        ];

        if (!$result['ok']) {
            error_log('ApiConnector ' . $this->host($config) . ' - HTTP ' . $status);
            $result['error'] = 'Service returned an error (' . $status . ')';
        }

        return $result;
    }

    private function soap(array $config, array $request): array
    {
        if (!class_exists('SoapClient')) {
            return $this->fail('SOAP extension not enabled');
        }

        $options = $config['options'] ?? [];
        $credentials = $config['credentials'] ?? [];

        $soapOptions = array_merge([
            'exceptions' => true,
            'trace' => 0,
            'connection_timeout' => (int)($options['timeout'] ?? 30),
            'cache_wsdl' => WSDL_CACHE_MEMORY
        ], $options['soap'] ?? []);

        if (($config['auth_type'] ?? '') === 'basic') {
            $soapOptions['login'] = $credentials['username'] ?? '';
            $soapOptions['password'] = $credentials['password'] ?? '';
        }

        $client = new \SoapClient($config['base_url'], $soapOptions);
        $body = $client->__soapCall($request['operation'], [$request['body'] ?? []]);

        return ['ok' => true, 'status' => 200, 'body' => $body, 'error' => ''];
    }

    private function fail(string $error): array
    {
        return ['ok' => false, 'status' => 0, 'body' => null, 'error' => $error];
    }

    /**
     * Host only, so logs never carry keys passed in the URL
     */
    private function host(array $config): string
    {
        return (string)(parse_url($config['base_url'] ?? '', PHP_URL_HOST) ?: '-');
    }
}
