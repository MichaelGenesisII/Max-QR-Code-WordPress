<?php

declare(strict_types=1);

final class Max_QR_Admin_Notices
{
    public static function init(): void
    {
        add_action('admin_notices', [self::class, 'maybe_missing_key']);
    }

    public static function maybe_missing_key(): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }
        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        if ($screen && $screen->id === 'settings_page_max-qr-code') {
            return;
        }
        if (Max_QR_Settings::api_key() !== '') {
            return;
        }
        echo '<div class="notice notice-info"><p>';
        echo esc_html__('Max QR Code is installed. Add your Business API key under Settings → Max QR Code.', 'max-qr-code');
        echo ' <a href="' . esc_url(admin_url('options-general.php?page=max-qr-code')) . '">';
        echo esc_html__('Open settings', 'max-qr-code');
        echo '</a></p></div>';
    }

    public static function message_for_code(string $code, string $fallback): string
    {
        return match ($code) {
            'unauthorized' => __('Unauthorized — check your API key.', 'max-qr-code'),
            'rate_limited' => __('Rate limited — try again shortly.', 'max-qr-code'),
            'daily_quota' => __('Daily quota reached.', 'max-qr-code'),
            'plan_forbidden', 'plan_limit' => __('Plan does not allow this action.', 'max-qr-code'),
            'insufficient_scope' => __('API key is missing a required scope.', 'max-qr-code'),
            'not_found' => __('Resource not found.', 'max-qr-code'),
            default => $fallback,
        };
    }
}
