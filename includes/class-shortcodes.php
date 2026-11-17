<?php

declare(strict_types=1);

final class Max_QR_Shortcodes
{
    public static function init(): void
    {
        add_shortcode('max_qr', [self::class, 'render_static']);
        add_shortcode('max_qr_dynamic', [self::class, 'render_dynamic']);
    }

    /**
     * [max_qr url="…" type="url|text|tel|sms|wifi|email|payment|geo" …]
     *
     * @param array<string, string>|string $atts
     */
    public static function render_static($atts): string
    {
        $settings = Max_QR_Settings::get();
        $atts = shortcode_atts(
            [
                'type' => 'url',
                'url' => '',
                'text' => '',
                'number' => '',
                'phone' => '',
                'body' => '',
                'ssid' => '',
                'password' => '',
                'security' => 'WPA',
                'to' => '',
                'subject' => '',
                'link' => '',
                'lat' => '',
                'lng' => '',
                'format' => (string) $settings['default_format'],
                'size' => (string) (int) $settings['default_size'],
                'fg' => (string) $settings['default_fg'],
                'bg' => (string) $settings['default_bg'],
                'class' => 'max-qr-code',
                'alt' => 'QR code',
            ],
            is_array($atts) ? $atts : [],
            'max_qr',
        );

        try {
            $content = self::build_content($atts);
            $image = self::generate_image($content, $atts);
        } catch (Max_QR_Api_Exception $e) {
            if (current_user_can('manage_options')) {
                return '<p class="max-qr-error">' . esc_html(Max_QR_Admin_Notices::message_for_code($e->error_code, $e->getMessage())) . '</p>';
            }

            return '';
        } catch (Throwable $e) {
            if (current_user_can('manage_options')) {
                return '<p class="max-qr-error">' . esc_html($e->getMessage()) . '</p>';
            }

            return '';
        }

        return sprintf(
            '<img src="%s" alt="%s" class="%s" width="%d" height="%d" loading="lazy" decoding="async" />',
            esc_url($image['url']),
            esc_attr($atts['alt']),
            esc_attr($atts['class']),
            (int) $atts['size'],
            (int) $atts['size'],
        );
    }

    /**
     * [max_qr_dynamic code_id="…" workspace="" size="…" …]
     *
     * @param array<string, string>|string $atts
     */
    public static function render_dynamic($atts): string
    {
        $settings = Max_QR_Settings::get();
        $atts = shortcode_atts(
            [
                'code_id' => '',
                'workspace' => Max_QR_Settings::workspace_id(),
                'format' => (string) $settings['default_format'],
                'size' => (string) (int) $settings['default_size'],
                'fg' => (string) $settings['default_fg'],
                'bg' => (string) $settings['default_bg'],
                'class' => 'max-qr-code max-qr-dynamic',
                'alt' => 'Dynamic QR code',
            ],
            is_array($atts) ? $atts : [],
            'max_qr_dynamic',
        );

        if ($atts['code_id'] === '' || $atts['workspace'] === '') {
            return current_user_can('manage_options')
                ? '<p class="max-qr-error">' . esc_html__('max_qr_dynamic requires code_id and workspace.', 'max-qr-code') . '</p>'
                : '';
        }

        $client = Max_QR_Api_Client::from_settings();
        if ($client === null) {
            return current_user_can('manage_options')
                ? '<p class="max-qr-error">' . esc_html__('API key not configured.', 'max-qr-code') . '</p>'
                : '';
        }

        try {
            $result = $client->dynamic_get($atts['workspace'], $atts['code_id']);
            $code = $result['code'] ?? null;
            if (!is_array($code) || empty($code['redirect_url'])) {
                throw new Max_QR_Api_Exception('Dynamic code missing redirect_url', 404, 'not_found');
            }
            $content = [
                'type' => 'url',
                'data' => ['url' => (string) $code['redirect_url']],
            ];
            $image = self::generate_image($content, $atts);
        } catch (Max_QR_Api_Exception $e) {
            if (current_user_can('manage_options')) {
                return '<p class="max-qr-error">' . esc_html(Max_QR_Admin_Notices::message_for_code($e->error_code, $e->getMessage())) . '</p>';
            }

            return '';
        } catch (Throwable $e) {
            if (current_user_can('manage_options')) {
                return '<p class="max-qr-error">' . esc_html($e->getMessage()) . '</p>';
            }

            return '';
        }

        return sprintf(
            '<img src="%s" alt="%s" class="%s" width="%d" height="%d" loading="lazy" decoding="async" />',
            esc_url($image['url']),
            esc_attr($atts['alt']),
            esc_attr($atts['class']),
            (int) $atts['size'],
            (int) $atts['size'],
        );
    }

    /**
     * @param array<string, string> $atts
     * @return array{type: string, data: array<string, mixed>}
     */
    private static function build_content(array $atts): array
    {
        $type = sanitize_key($atts['type']);
        switch ($type) {
            case 'text':
                if ($atts['text'] === '') {
                    throw new InvalidArgumentException(__('text is required for type=text', 'max-qr-code'));
                }
                return ['type' => 'text', 'data' => ['text' => $atts['text']]];
            case 'tel':
                $number = $atts['number'] !== '' ? $atts['number'] : $atts['phone'];
                if ($number === '') {
                    throw new InvalidArgumentException(__('number is required for type=tel', 'max-qr-code'));
                }
                return ['type' => 'tel', 'data' => ['number' => $number]];
            case 'sms':
                $number = $atts['number'] !== '' ? $atts['number'] : $atts['phone'];
                if ($number === '') {
                    throw new InvalidArgumentException(__('number is required for type=sms', 'max-qr-code'));
                }
                $data = ['number' => $number];
                if ($atts['body'] !== '') {
                    $data['body'] = $atts['body'];
                }
                return ['type' => 'sms', 'data' => $data];
            case 'wifi':
                if ($atts['ssid'] === '') {
                    throw new InvalidArgumentException(__('ssid is required for type=wifi', 'max-qr-code'));
                }
                return [
                    'type' => 'wifi',
                    'data' => array_filter([
                        'ssid' => $atts['ssid'],
                        'password' => $atts['password'] !== '' ? $atts['password'] : null,
                        'security' => $atts['security'] !== '' ? $atts['security'] : null,
                    ], static fn ($v) => $v !== null),
                ];
            case 'email':
                return [
                    'type' => 'email',
                    'data' => array_filter([
                        'to' => $atts['to'] !== '' ? $atts['to'] : null,
                        'subject' => $atts['subject'] !== '' ? $atts['subject'] : null,
                        'body' => $atts['body'] !== '' ? $atts['body'] : null,
                    ], static fn ($v) => $v !== null),
                ];
            case 'payment':
                $link = $atts['link'] !== '' ? $atts['link'] : $atts['url'];
                if ($link === '') {
                    throw new InvalidArgumentException(__('link is required for type=payment', 'max-qr-code'));
                }
                return ['type' => 'payment', 'data' => ['link' => $link]];
            case 'geo':
                if ($atts['lat'] === '' || $atts['lng'] === '') {
                    throw new InvalidArgumentException(__('lat and lng are required for type=geo', 'max-qr-code'));
                }
                return [
                    'type' => 'geo',
                    'data' => [
                        'lat' => (float) $atts['lat'],
                        'lng' => (float) $atts['lng'],
                    ],
                ];
            case 'url':
            default:
                $url = $atts['url'] !== '' ? $atts['url'] : '';
                if ($url === '' && is_singular()) {
                    $url = get_permalink() ?: '';
                }
                if ($url === '') {
                    throw new InvalidArgumentException(__('url is required for type=url', 'max-qr-code'));
                }
                return ['type' => 'url', 'data' => ['url' => esc_url_raw($url)]];
        }
    }

    /**
     * @param array{type: string, data: array<string, mixed>} $content
     * @param array<string, string> $atts
     * @return array{url: string, path?: string}
     */
    private static function generate_image(array $content, array $atts): array
    {
        $client = Max_QR_Api_Client::from_settings();
        if ($client === null) {
            throw new Max_QR_Api_Exception(__('API key not configured', 'max-qr-code'), 401, 'unauthorized');
        }

        $format = in_array($atts['format'], ['png', 'svg', 'pdf'], true) ? $atts['format'] : 'png';
        $size = max(64, min(4096, (int) $atts['size']));
        $style = [
            'foreground' => $atts['fg'],
            'background' => $atts['bg'],
            'size' => $size,
        ];

        $fingerprint = [
            'content' => $content,
            'format' => $format,
            'style' => $style,
            'v' => MAX_QR_CODE_VERSION,
        ];
        $key = Max_QR_Cache::key($fingerprint);
        $ext = $format === 'svg' ? 'svg' : ($format === 'pdf' ? 'pdf' : 'png');

        $settings = Max_QR_Settings::get();
        if (!empty($settings['cache_enabled'])) {
            $cached = Max_QR_Cache::get($key, $ext);
            if ($cached !== null) {
                return $cached;
            }
        }

        $result = $client->generate_binary($content, [
            'format' => $format,
            'style' => $style,
        ]);

        if (!empty($settings['cache_enabled'])) {
            return Max_QR_Cache::put($key, $ext, $result['bytes']);
        }

        $mime = $result['mime'] !== '' ? $result['mime'] : 'image/png';

        return [
            'url' => 'data:' . $mime . ';base64,' . base64_encode($result['bytes']),
        ];
    }
}
