<?php

declare(strict_types=1);

final class Max_QR_Plugin
{
    private static ?self $instance = null;

    public static function instance(): self
    {
        return self::$instance ??= new self();
    }

    public function boot(): void
    {
        Max_QR_Settings::init();
        Max_QR_Admin_Notices::init();
        Max_QR_Shortcodes::init();

        add_filter(
            'plugin_action_links_' . plugin_basename(MAX_QR_CODE_FILE),
            [$this, 'action_links'],
        );
    }

    /**
     * @param array<string, string> $links
     * @return array<string, string>
     */
    public function action_links(array $links): array
    {
        $settings = '<a href="' . esc_url(admin_url('options-general.php?page=max-qr-code')) . '">'
            . esc_html__('Settings', 'max-qr-code') . '</a>';
        array_unshift($links, $settings);

        return $links;
    }
}
