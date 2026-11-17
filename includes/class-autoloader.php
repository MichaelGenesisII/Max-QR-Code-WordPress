<?php

declare(strict_types=1);

final class Max_QR_Autoloader
{
    public static function register(): void
    {
        spl_autoload_register(static function (string $class): void {
            if (!str_starts_with($class, 'Max_QR_')) {
                return;
            }

            $map = [
                'Max_QR_Plugin' => 'class-plugin.php',
                'Max_QR_Settings' => 'class-settings.php',
                'Max_QR_Api_Client' => 'class-api-client.php',
                'Max_QR_Api_Exception' => 'class-api-exception.php',
                'Max_QR_Shortcodes' => 'class-shortcodes.php',
                'Max_QR_Cache' => 'class-cache.php',
                'Max_QR_Admin_Notices' => 'class-admin-notices.php',
            ];

            if (!isset($map[$class])) {
                return;
            }

            $path = MAX_QR_CODE_PATH . 'includes/' . $map[$class];
            if (is_readable($path)) {
                require_once $path;
            }
        });
    }
}
