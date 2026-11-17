<?php

declare(strict_types=1);

final class Max_QR_Settings
{
    public const OPTION = 'max_qr_code_settings';

    public static function init(): void
    {
        add_action('admin_menu', [self::class, 'register_menu']);
        add_action('admin_init', [self::class, 'register_settings']);
        add_action('admin_enqueue_scripts', [self::class, 'enqueue_assets']);
        add_action('wp_ajax_max_qr_code_test_connection', [self::class, 'ajax_test_connection']);
    }

    /** @return array<string, mixed> */
    public static function get(): array
    {
        $defaults = [
            'api_key' => '',
            'workspace_id' => '',
            'base_url' => Max_QR_Api_Client::DEFAULT_BASE,
            'default_format' => 'png',
            'default_size' => 512,
            'default_fg' => '#111111',
            'default_bg' => '#ffffff',
            'cache_enabled' => 1,
        ];
        $stored = get_option(self::OPTION, []);
        if (!is_array($stored)) {
            $stored = [];
        }

        return array_merge($defaults, $stored);
    }

    public static function api_key(): string
    {
        $key = (string) (self::get()['api_key'] ?? '');
        if ($key !== '') {
            return $key;
        }
        $env = getenv('MAXQR_API_KEY');

        return is_string($env) ? $env : '';
    }

    public static function workspace_id(): string
    {
        $id = (string) (self::get()['workspace_id'] ?? '');
        if ($id !== '') {
            return $id;
        }
        $env = getenv('MAXQR_WORKSPACE_ID');

        return is_string($env) ? $env : '';
    }

    public static function register_menu(): void
    {
        add_options_page(
            __('Max QR Code', 'max-qr-code'),
            __('Max QR Code', 'max-qr-code'),
            'manage_options',
            'max-qr-code',
            [self::class, 'render_page'],
        );
    }

    public static function register_settings(): void
    {
        register_setting('max_qr_code', self::OPTION, [
            'type' => 'array',
            'sanitize_callback' => [self::class, 'sanitize'],
            'default' => [],
        ]);
    }

    public static function enqueue_assets(string $hook): void
    {
        if ($hook !== 'settings_page_max-qr-code') {
            return;
        }

        wp_enqueue_style(
            'max-qr-code-admin',
            MAX_QR_CODE_URL . 'assets/admin.css',
            [],
            MAX_QR_CODE_VERSION,
        );
        wp_enqueue_script(
            'max-qr-code-admin',
            MAX_QR_CODE_URL . 'assets/admin.js',
            [],
            MAX_QR_CODE_VERSION,
            true,
        );
        wp_localize_script('max-qr-code-admin', 'maxQrCodeAdmin', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('max_qr_code_test'),
            'i18n' => [
                'testing' => __('Testing…', 'max-qr-code'),
                'failed' => __('Connection failed', 'max-qr-code'),
            ],
        ]);
    }

    /** @param mixed $input */
    public static function sanitize($input): array
    {
        $input = is_array($input) ? $input : [];
        $current = self::get();

        $api_key = isset($input['api_key']) ? trim((string) $input['api_key']) : '';
        if ($api_key === '' && !empty($input['api_key_keep'])) {
            $api_key = (string) $current['api_key'];
        }

        $format = sanitize_key((string) ($input['default_format'] ?? 'png'));
        if (!in_array($format, ['png', 'svg', 'pdf'], true)) {
            $format = 'png';
        }

        $base = esc_url_raw((string) ($input['base_url'] ?? Max_QR_Api_Client::DEFAULT_BASE));
        if ($base === '') {
            $base = Max_QR_Api_Client::DEFAULT_BASE;
        }

        return [
            'api_key' => $api_key,
            'workspace_id' => sanitize_text_field((string) ($input['workspace_id'] ?? '')),
            'base_url' => untrailingslashit($base),
            'default_format' => $format,
            'default_size' => max(64, min(4096, (int) ($input['default_size'] ?? 512))),
            'default_fg' => sanitize_hex_color((string) ($input['default_fg'] ?? '#111111')) ?: '#111111',
            'default_bg' => sanitize_hex_color((string) ($input['default_bg'] ?? '#ffffff')) ?: '#ffffff',
            'cache_enabled' => empty($input['cache_enabled']) ? 0 : 1,
        ];
    }

    public static function ajax_test_connection(): void
    {
        if (!current_user_can('manage_options')) {
            wp_send_json(['ok' => false, 'message' => __('Forbidden', 'max-qr-code')], 403);
        }
        check_ajax_referer('max_qr_code_test', 'nonce');

        $client = Max_QR_Api_Client::from_settings();
        if ($client === null) {
            wp_send_json(['ok' => false, 'message' => __('API key not configured', 'max-qr-code')]);
        }

        try {
            $options = $client->generate_options();
            $types = $options['content_types'] ?? [];
            $count = is_array($types) ? count($types) : 0;
            wp_send_json([
                'ok' => true,
                'message' => sprintf(
                    /* translators: %d: number of content types */
                    __('Connected. API reports %d content types.', 'max-qr-code'),
                    $count,
                ),
            ]);
        } catch (Max_QR_Api_Exception $e) {
            wp_send_json([
                'ok' => false,
                'message' => Max_QR_Admin_Notices::message_for_code($e->error_code, $e->getMessage()),
            ]);
        } catch (Throwable $e) {
            wp_send_json(['ok' => false, 'message' => $e->getMessage()]);
        }
    }

    public static function render_page(): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        $settings = self::get();
        $has_key = self::api_key() !== '';
        ?>
        <div class="wrap max-qr-code-settings">
            <h1><?php echo esc_html__('Max QR Code', 'max-qr-code'); ?></h1>
            <p class="max-qr-lead">
                <?php echo esc_html__('Embed QR codes with shortcodes. API keys stay on your WordPress server.', 'max-qr-code'); ?>
            </p>
            <p class="max-qr-links">
                <a class="button button-secondary" href="https://www.getmaxqr.com/dashboard" target="_blank" rel="noopener noreferrer">
                    <?php echo esc_html__('Open dashboard', 'max-qr-code'); ?>
                </a>
                <a href="https://www.getmaxqr.com/docs" target="_blank" rel="noopener noreferrer">
                    <?php echo esc_html__('API docs', 'max-qr-code'); ?>
                </a>
                <span class="max-qr-version"><?php echo esc_html(sprintf('v%s', MAX_QR_CODE_VERSION)); ?></span>
            </p>

            <?php if (!$has_key) : ?>
                <div class="notice notice-warning">
                    <p><?php echo esc_html__('Add a Business API key (mq_live_…) to generate QR codes.', 'max-qr-code'); ?></p>
                </div>
            <?php endif; ?>

            <div class="max-qr-card">
                <h2><?php echo esc_html__('Connection', 'max-qr-code'); ?></h2>
                <p id="max-qr-usage" class="max-qr-usage">
                    <?php echo $has_key
                        ? esc_html__('Click “Test connection” to verify your key.', 'max-qr-code')
                        : esc_html__('Save an API key first.', 'max-qr-code'); ?>
                </p>
                <p>
                    <button type="button" class="button" id="max-qr-test-btn" <?php disabled(!$has_key); ?>>
                        <?php echo esc_html__('Test connection', 'max-qr-code'); ?>
                    </button>
                    <span id="max-qr-test-status" class="max-qr-status" aria-live="polite"></span>
                </p>
            </div>

            <form method="post" action="options.php" class="max-qr-card">
                <?php settings_fields('max_qr_code'); ?>
                <h2><?php echo esc_html__('Settings', 'max-qr-code'); ?></h2>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="max_qr_api_key"><?php echo esc_html__('API key', 'max-qr-code'); ?></label></th>
                        <td>
                            <input type="password" class="regular-text" id="max_qr_api_key" name="<?php echo esc_attr(self::OPTION); ?>[api_key]" value="" autocomplete="off" placeholder="<?php echo $has_key ? esc_attr__('•••••••• (saved — leave blank to keep)', 'max-qr-code') : 'mq_live_…'; ?>" />
                            <?php if ($has_key) : ?>
                                <input type="hidden" name="<?php echo esc_attr(self::OPTION); ?>[api_key_keep]" value="1" />
                            <?php endif; ?>
                            <p class="description"><?php echo esc_html__('Business plan key from the Max QR Code dashboard. Or set MAXQR_API_KEY in the environment.', 'max-qr-code'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="max_qr_workspace"><?php echo esc_html__('Workspace ID', 'max-qr-code'); ?></label></th>
                        <td>
                            <input type="text" class="regular-text" id="max_qr_workspace" name="<?php echo esc_attr(self::OPTION); ?>[workspace_id]" value="<?php echo esc_attr((string) $settings['workspace_id']); ?>" />
                            <p class="description"><?php echo esc_html__('Optional. Required for dynamic-code shortcodes. Or set MAXQR_WORKSPACE_ID.', 'max-qr-code'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="max_qr_base"><?php echo esc_html__('API base URL', 'max-qr-code'); ?></label></th>
                        <td>
                            <input type="url" class="regular-text" id="max_qr_base" name="<?php echo esc_attr(self::OPTION); ?>[base_url]" value="<?php echo esc_attr((string) $settings['base_url']); ?>" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="max_qr_format"><?php echo esc_html__('Default format', 'max-qr-code'); ?></label></th>
                        <td>
                            <select id="max_qr_format" name="<?php echo esc_attr(self::OPTION); ?>[default_format]">
                                <?php foreach (['png', 'svg', 'pdf'] as $fmt) : ?>
                                    <option value="<?php echo esc_attr($fmt); ?>" <?php selected($settings['default_format'], $fmt); ?>><?php echo esc_html(strtoupper($fmt)); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="max_qr_size"><?php echo esc_html__('Default size', 'max-qr-code'); ?></label></th>
                        <td>
                            <input type="number" min="64" max="4096" id="max_qr_size" name="<?php echo esc_attr(self::OPTION); ?>[default_size]" value="<?php echo esc_attr((string) (int) $settings['default_size']); ?>" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="max_qr_fg"><?php echo esc_html__('Foreground', 'max-qr-code'); ?></label></th>
                        <td><input type="text" id="max_qr_fg" name="<?php echo esc_attr(self::OPTION); ?>[default_fg]" value="<?php echo esc_attr((string) $settings['default_fg']); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="max_qr_bg"><?php echo esc_html__('Background', 'max-qr-code'); ?></label></th>
                        <td><input type="text" id="max_qr_bg" name="<?php echo esc_attr(self::OPTION); ?>[default_bg]" value="<?php echo esc_attr((string) $settings['default_bg']); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th scope="row"><?php echo esc_html__('Cache', 'max-qr-code'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="<?php echo esc_attr(self::OPTION); ?>[cache_enabled]" value="1" <?php checked(!empty($settings['cache_enabled'])); ?> />
                                <?php echo esc_html__('Cache generated images under uploads/max-qr-code/', 'max-qr-code'); ?>
                            </label>
                        </td>
                    </tr>
                </table>
                <?php submit_button(__('Save settings', 'max-qr-code')); ?>
            </form>

            <div class="max-qr-card">
                <h2><?php echo esc_html__('Shortcodes', 'max-qr-code'); ?></h2>
                <p><code>[max_qr url="https://example.com"]</code></p>
                <p><code>[max_qr type="tel" number="+15550100" size="256"]</code></p>
                <p><code>[max_qr_dynamic code_id="…"]</code> — <?php echo esc_html__('QR for an existing dynamic code redirect URL', 'max-qr-code'); ?></p>
            </div>
        </div>
        <?php
    }
}
