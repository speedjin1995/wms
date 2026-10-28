<?php
namespace App\Integrations;

/**
 * Reads a document (image / PDF) with the configured LLM provider and returns its JSON answer.
 * Each provider is one request mapping plus one path to the reply text.
 */
class LlmExtractor
{
    /**
     * Connection defaults per provider; a config row only has to hold the provider, model and key
     */
    public const PROVIDERS = [
        'anthropic' => [
            'base_url' => 'https://api.anthropic.com',
            'key_header' => 'x-api-key',
            'headers' => ['anthropic-version' => '2023-06-01'],
            'model' => 'claude-sonnet-5-5'
        ],
        'openai' => [
            'base_url' => 'https://api.openai.com',
            'key_header' => null,
            'headers' => [],
            'model' => 'gpt-4.1'
        ],
        'gemini' => [
            'base_url' => 'https://generativelanguage.googleapis.com',
            'key_header' => 'x-goog-api-key',
            'headers' => [],
            'model' => 'gemini-2.5-flash'
        ]
    ];

    private ApiConnector $connector;

    public function __construct(?ApiConnector $connector = null)
    {
        $this->connector = $connector ?? new ApiConnector();
    }

    /**
     * Decoded JSON from the model, or null when the call or the parsing fails
     */
    public function extract(array $config, string $mime, string $base64, string $prompt): ?array
    {
        $text = $this->ask($config, $prompt, $mime, $base64);

        if ($text === null) {
            return null;
        }

        // Models sometimes wrap JSON in ```json fences
        $text = trim(preg_replace('/^```(?:json)?\s*|\s*```$/', '', trim($text)));
        $json = json_decode($text, true);

        return is_array($json) ? $json : null;
    }

    /**
     * Tiny call to check the key and model work
     */
    public function ping(array $config): bool
    {
        return $this->ask($config, 'Reply with the single word OK.', null, null) !== null;
    }

    private function ask(array $config, string $prompt, ?string $mime, ?string $base64): ?string
    {
        $provider = $config['options']['provider'] ?? '';

        if (!isset(self::PROVIDERS[$provider])) {
            error_log('LlmExtractor - unknown provider ' . $provider);
            return null;
        }

        $config = $this->withDefaults($config, $provider);
        $model = $config['options']['model'];

        switch ($provider) {
            case 'anthropic':
                $content = [];
                if ($base64 !== null) {
                    $content[] = [
                        'type' => $mime === 'application/pdf' ? 'document' : 'image',
                        'source' => ['type' => 'base64', 'media_type' => $mime, 'data' => $base64]
                    ];
                }
                $content[] = ['type' => 'text', 'text' => $prompt];
                $request = [
                    'path' => 'v1/messages',
                    'json' => ['model' => $model, 'max_tokens' => 4096, 'messages' => [['role' => 'user', 'content' => $content]]]
                ];
                break;

            case 'openai':
                $content = [['type' => 'text', 'text' => $prompt]];
                if ($base64 !== null) {
                    $dataUrl = 'data:' . $mime . ';base64,' . $base64;
                    $content[] = $mime === 'application/pdf'
                        ? ['type' => 'file', 'file' => ['filename' => 'document.pdf', 'file_data' => $dataUrl]]
                        : ['type' => 'image_url', 'image_url' => ['url' => $dataUrl]];
                }
                $request = [
                    'path' => 'v1/chat/completions',
                    'json' => ['model' => $model, 'messages' => [['role' => 'user', 'content' => $content]]]
                ];
                break;

            case 'gemini':
                $parts = [];
                if ($base64 !== null) {
                    $parts[] = ['inline_data' => ['mime_type' => $mime, 'data' => $base64]];
                }
                $parts[] = ['text' => $prompt];
                $request = [
                    'path' => 'v1beta/models/' . rawurlencode($model) . ':generateContent',
                    'json' => ['contents' => [['parts' => $parts]]]
                ];
                break;
        }

        $request['http_method'] = 'POST';
        $response = $this->connector->call($config, $request);

        if (!$response['ok'] || !is_array($response['body'])) {
            return null;
        }

        $body = $response['body'];
        switch ($provider) {
            case 'anthropic':
                $text = $body['content'][0]['text'] ?? null;
                break;

            case 'openai':
                $text = $body['choices'][0]['message']['content'] ?? null;
                break;

            case 'gemini':
                $text = $body['candidates'][0]['content']['parts'][0]['text'] ?? null;
                break;
        }

        return is_string($text) ? $text : null;
    }

    /**
     * Fill URL, auth and model from the provider when the row leaves them empty
     */
    private function withDefaults(array $config, string $provider): array
    {
        $defaults = self::PROVIDERS[$provider];
        $key = $config['credentials']['key'] ?? ($config['credentials']['token'] ?? '');

        $config['method'] = 'rest';
        $config['base_url'] = $config['base_url'] ?: $defaults['base_url'];
        $config['headers'] = array_merge($defaults['headers'], $config['headers'] ?? []);
        $config['options']['model'] = ($config['options']['model'] ?? '') ?: $defaults['model'];
        $config['options']['timeout'] = $config['options']['timeout'] ?? 90;

        if ($defaults['key_header'] === null) {
            $config['auth_type'] = 'bearer';
            $config['credentials'] = ['token' => $key];
        } else {
            $config['auth_type'] = 'api_key';
            $config['credentials'] = ['header' => $defaults['key_header'], 'key' => $key];
        }

        return $config;
    }
}
