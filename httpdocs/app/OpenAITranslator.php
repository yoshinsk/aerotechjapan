<?php
/**
 * httpdocs/app/OpenAITranslator.php
 * OpenAI Responses APIを使い、CMS入力欄の日本語テキストを英語へ翻訳します。
 */

declare(strict_types=1);

final class OpenAITranslator
{
    public function __construct(private array $config)
    {
    }

    /**
     * 管理画面から受け取った複数入力欄を一括英訳し、英語欄名ごとの翻訳結果とslug候補を返します。
     *
     * @param array<int, array{label:string, source:string, target:string}> $fields
     * @return array{translations: array<string, string>, slug: string}
     */
    public function translateFields(array $fields, string $context = ''): array
    {
        $apiKey = trim((string)($this->config['api_key'] ?? ''));
        if ($apiKey === '') {
            throw new RuntimeException('OpenAI APIキーが未設定です。管理画面の「設定」から登録してください。');
        }

        $allowedFields = $this->normalizeFields($fields);
        if (!$allowedFields) {
            throw new InvalidArgumentException('翻訳対象の日本語テキストがありません。');
        }

        $schema = [
            'type' => 'object',
            'additionalProperties' => false,
            'properties' => [
                'translations' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'additionalProperties' => false,
                        'properties' => [
                            'target' => ['type' => 'string'],
                            'text' => ['type' => 'string'],
                        ],
                        'required' => ['target', 'text'],
                    ],
                ],
                'slug' => [
                    'type' => 'string',
                    'description' => 'ASCII lowercase URL slug generated from the main English title/name.',
                ],
            ],
            'required' => ['translations', 'slug'],
        ];

        $payload = [
            'model' => $this->config['model'] ?? 'gpt-5.4-mini',
            'reasoning' => ['effort' => $this->config['reasoning_effort'] ?? 'low'],
            'instructions' => implode("\n", [
                'You translate Japanese automotive aftermarket CMS content into natural English.',
                'Preserve brand names, chassis codes, product names, measurements, part numbers, and HTML tags.',
                'Do not add facts that are not present in the source.',
                'Return concise commercial English suitable for an official product website.',
                'For empty or unclear source values, return an empty string for that target.',
                'Generate slug from the main translated title or name using lowercase ASCII letters, numbers, and hyphens only.',
            ]),
            'input' => json_encode([
                'context' => mb_substr($context, 0, 200),
                'fields' => $allowedFields,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'text' => [
                'format' => [
                    'type' => 'json_schema',
                    'name' => 'cms_translation',
                    'strict' => true,
                    'schema' => $schema,
                ],
            ],
        ];

        $response = $this->request($payload, $apiKey);
        $text = $this->extractText($response);
        $decoded = json_decode($text, true);
        if (!is_array($decoded)) {
            throw new RuntimeException('OpenAI APIの応答をJSONとして解析できませんでした。');
        }

        return $this->normalizeResponse($decoded, array_column($allowedFields, 'target'));
    }

    /**
     * 入力欄ごとの翻訳対象を検証し、過大な文字列を切り詰めます。
     *
     * @param array<int, mixed> $fields
     * @return array<int, array{label:string, source:string, target:string}>
     */
    private function normalizeFields(array $fields): array
    {
        $normalized = [];
        foreach ($fields as $field) {
            if (!is_array($field)) {
                continue;
            }
            $target = trim((string)($field['target'] ?? ''));
            $source = trim((string)($field['source'] ?? ''));
            if ($target === '' || $source === '') {
                continue;
            }
            if (!preg_match('/^[a-z0-9_]+$/', $target)) {
                continue;
            }
            $normalized[] = [
                'label' => mb_substr(trim((string)($field['label'] ?? $target)), 0, 80),
                'target' => $target,
                'source' => mb_substr($source, 0, 8000),
            ];
        }
        return array_slice($normalized, 0, 12);
    }

    /**
     * OpenAI Responses APIへJSON POSTし、HTTPエラーやAPIエラーを例外に変換します。
     *
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function request(array $payload, string $apiKey): array
    {
        $baseUrl = rtrim((string)($this->config['base_url'] ?? 'https://api.openai.com/v1'), '/');
        $timeout = max(10, (int)($this->config['timeout'] ?? 30));
        $body = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($body === false) {
            throw new RuntimeException('OpenAI APIへの送信JSONを生成できませんでした。');
        }

        if (function_exists('curl_init')) {
            $result = $this->requestWithCurl($baseUrl . '/responses', $apiKey, $body, $timeout);
        } else {
            $result = $this->requestWithStream($baseUrl . '/responses', $apiKey, $body, $timeout);
        }

        $decoded = json_decode($result['body'], true);
        if (!is_array($decoded)) {
            throw new RuntimeException('OpenAI APIのHTTP応答をJSONとして解析できませんでした。');
        }
        if ($result['status'] >= 400) {
            $message = $decoded['error']['message'] ?? ('OpenAI API HTTP ' . $result['status']);
            throw new RuntimeException((string)$message);
        }
        return $decoded;
    }

    /**
     * cURL拡張でOpenAI APIへ接続します。
     *
     * @return array{status:int, body:string}
     */
    private function requestWithCurl(string $url, string $apiKey, string $body, int $timeout): array
    {
        $curl = curl_init($url);
        if ($curl === false) {
            throw new RuntimeException('cURLを初期化できませんでした。');
        }
        curl_setopt_array($curl, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $apiKey,
            ],
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_TIMEOUT => $timeout,
        ]);
        $response = curl_exec($curl);
        $status = (int)curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        $error = curl_error($curl);
        curl_close($curl);
        if ($response === false) {
            throw new RuntimeException('OpenAI APIへの接続に失敗しました: ' . $error);
        }
        return ['status' => $status, 'body' => (string)$response];
    }

    /**
     * cURLがない環境向けに、PHPストリームでOpenAI APIへ接続します。
     *
     * @return array{status:int, body:string}
     */
    private function requestWithStream(string $url, string $apiKey, string $body, int $timeout): array
    {
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => implode("\r\n", [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $apiKey,
                ]),
                'content' => $body,
                'timeout' => $timeout,
                'ignore_errors' => true,
            ],
        ]);
        $response = file_get_contents($url, false, $context);
        if ($response === false) {
            throw new RuntimeException('OpenAI APIへの接続に失敗しました。');
        }
        $status = 0;
        foreach (($http_response_header ?? []) as $header) {
            if (preg_match('/^HTTP\/\S+\s+(\d+)/', $header, $matches)) {
                $status = (int)$matches[1];
                break;
            }
        }
        return ['status' => $status, 'body' => (string)$response];
    }

    /**
     * Responses APIのoutput配列から生成テキストを安全に抽出します。
     *
     * @param array<string, mixed> $response
     */
    private function extractText(array $response): string
    {
        if (isset($response['output_text']) && is_string($response['output_text'])) {
            return trim($response['output_text']);
        }

        $parts = [];
        foreach (($response['output'] ?? []) as $item) {
            if (!is_array($item)) {
                continue;
            }
            foreach (($item['content'] ?? []) as $content) {
                if (is_array($content) && isset($content['text']) && is_string($content['text'])) {
                    $parts[] = $content['text'];
                }
            }
        }
        $text = trim(implode("\n", $parts));
        if ($text === '') {
            throw new RuntimeException('OpenAI APIの応答にテキスト出力がありません。');
        }
        return $text;
    }

    /**
     * API応答を管理画面が扱いやすい連想配列へ正規化します。
     *
     * @param array<string, mixed> $decoded
     * @param array<int, string> $allowedTargets
     * @return array{translations: array<string, string>, slug: string}
     */
    private function normalizeResponse(array $decoded, array $allowedTargets): array
    {
        $allowed = array_flip($allowedTargets);
        $translations = [];
        foreach (($decoded['translations'] ?? []) as $item) {
            if (!is_array($item)) {
                continue;
            }
            $target = (string)($item['target'] ?? '');
            if (!isset($allowed[$target])) {
                continue;
            }
            $translations[$target] = trim((string)($item['text'] ?? ''));
        }

        return [
            'translations' => $translations,
            'slug' => slugify_ascii((string)($decoded['slug'] ?? '')),
        ];
    }
}
