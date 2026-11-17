<?php

declare(strict_types=1);

final class Max_QR_Api_Client
{
    public const DEFAULT_BASE = 'https://api.getmaxqr.com/api/v1';

    private string $api_key;

    private string $base_url;

    private int $timeout;

    public function __construct(string $api_key, string $base_url = self::DEFAULT_BASE, int $timeout = 60)
    {
        $this->api_key = trim($api_key);
        $this->base_url = rtrim($base_url !== '' ? $base_url : self::DEFAULT_BASE, '/');
        $this->timeout = max(5, $timeout);
    }

    public static function from_settings(): ?self
    {
        $key = Max_QR_Settings::api_key();
        if ($key === '') {
            return null;
        }

        $settings = Max_QR_Settings::get();
        $base = (string) ($settings['base_url'] ?? self::DEFAULT_BASE);

        return new self($key, $base);
    }

    /** @return array<string, mixed> */
    public function generate_options(): array
    {
        return $this->request_json('GET', '/generate/options');
    }

    /**
     * @param array{type: string, data: array<string, mixed>} $content
     * @param array<string, mixed> $options
     * @return array{format: string, mime: string, bytes: string}
     */
    public function generate_binary(array $content, array $options = []): array
    {
        $format = (string) ($options['format'] ?? 'png');
        $body = [
            'content' => $content,
            'format' => $format,
        ];
        if (!empty($options['style']) && is_array($options['style'])) {
            $body['style'] = $options['style'];
        }
        if (!empty($options['preset'])) {
            $body['preset'] = (string) $options['preset'];
        }

        $response = $this->wp_request(
            'POST',
            $this->base_url . '/generate',
            wp_json_encode($body),
            ['Content-Type' => 'application/json'],
        );
        $code = (int) wp_remote_retrieve_response_code($response);
        $raw = (string) wp_remote_retrieve_body($response);
        if ($code < 200 || $code >= 300) {
            throw $this->exception_from_response($code, $response, $raw);
        }

        $mime = (string) wp_remote_retrieve_header($response, 'content-type');
        if ($mime === '') {
            $mime = 'application/octet-stream';
        }

        return [
            'format' => $this->guess_format($mime, $format),
            'mime' => $mime,
            'bytes' => $raw,
        ];
    }

    /**
     * @param array{type: string, data: array<string, mixed>} $content
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    public function dynamic_create(string $workspace_id, array $input): array
    {
        return $this->request_json(
            'POST',
            '/workspaces/' . rawurlencode($workspace_id) . '/dynamic-codes',
            $input,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function dynamic_get(string $workspace_id, string $code_id): array
    {
        return $this->request_json(
            'GET',
            '/workspaces/' . rawurlencode($workspace_id) . '/dynamic-codes/' . rawurlencode($code_id),
        );
    }

    /**
     * @param array<string, mixed>|null $json_body
     * @return array<string, mixed>
     */
    private function request_json(string $method, string $path, ?array $json_body = null): array
    {
        $headers = ['Accept' => 'application/json'];
        $body = null;
        if ($json_body !== null) {
            $headers['Content-Type'] = 'application/json';
            $body = wp_json_encode($json_body);
        }

        $response = $this->wp_request($method, $this->base_url . $path, $body, $headers);
        $code = (int) wp_remote_retrieve_response_code($response);
        $raw = (string) wp_remote_retrieve_body($response);
        if ($code < 200 || $code >= 300) {
            throw $this->exception_from_response($code, $response, $raw);
        }
        if ($raw === '') {
            return [];
        }
        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            throw new Max_QR_Api_Exception('Invalid JSON response', 502, 'invalid_json');
        }

        return $decoded;
    }

    /**
     * @param array<string, string> $extra_headers
     * @return array<string, mixed>
     */
    private function wp_request(string $method, string $url, ?string $body, array $extra_headers = []): array
    {
        $headers = array_merge(
            [
                'X-Api-Key' => $this->api_key,
                'Accept' => 'application/json',
            ],
            $extra_headers,
        );

        $args = [
            'method' => $method,
            'timeout' => $this->timeout,
            'headers' => $headers,
            'redirection' => 3,
        ];
        if ($body !== null) {
            $args['body'] = $body;
        }

        $response = wp_remote_request($url, $args);
        if (is_wp_error($response)) {
            throw new Max_QR_Api_Exception(
                sanitize_text_field($response->get_error_message()),
                0,
                'http_error',
            );
        }

        /** @var array<string, mixed> $response */
        return $response;
    }

    /**
     * @param array<string, mixed> $response
     */
    private function exception_from_response(int $status, array $response, string $raw): Max_QR_Api_Exception
    {
        $retry_after = null;
        $header = wp_remote_retrieve_header($response, 'retry-after');
        if (is_numeric($header)) {
            $retry_after = (float) $header;
        }
        $request_id = (string) wp_remote_retrieve_header($response, 'x-request-id');
        $message = sprintf('Max QR API error (%d)', $status);
        $code = 'api_error';
        $details = null;

        if ($raw !== '') {
            $parsed = json_decode($raw, true);
            if (is_array($parsed)) {
                $err = $parsed['error'] ?? null;
                if (is_array($err)) {
                    if (!empty($err['message'])) {
                        $message = sanitize_text_field((string) $err['message']);
                    }
                    if (!empty($err['code'])) {
                        $code = sanitize_key((string) $err['code']);
                    }
                    $details = $err['details'] ?? $parsed;
                } elseif (!empty($parsed['message'])) {
                    $message = sanitize_text_field((string) $parsed['message']);
                    $details = $parsed;
                }
            } else {
                $message = sanitize_text_field(substr($raw, 0, 500));
            }
        }

        return new Max_QR_Api_Exception(
            $message,
            $status,
            $code !== '' ? $code : 'api_error',
            $details,
            $retry_after,
            $request_id !== '' ? $request_id : null,
        );
    }

    private function guess_format(string $mime, string $fallback): string
    {
        if (str_contains($mime, 'svg')) {
            return 'svg';
        }
        if (str_contains($mime, 'pdf')) {
            return 'pdf';
        }

        return $fallback;
    }
}
