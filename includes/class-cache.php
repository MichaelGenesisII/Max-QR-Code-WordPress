<?php

declare(strict_types=1);

final class Max_QR_Cache
{
    public static function dir(): string
    {
        $upload = wp_upload_dir();
        $dir = trailingslashit($upload['basedir']) . 'max-qr-code';
        if (!is_dir($dir)) {
            wp_mkdir_p($dir);
        }

        return $dir;
    }

    public static function url_for(string $filename): string
    {
        $upload = wp_upload_dir();

        return trailingslashit($upload['baseurl']) . 'max-qr-code/' . ltrim($filename, '/');
    }

    /**
     * @param array<string, mixed> $fingerprint
     */
    public static function key(array $fingerprint): string
    {
        return 'mq_' . md5((string) wp_json_encode($fingerprint));
    }

    /**
     * @return array{path: string, url: string}|null
     */
    public static function get(string $key, string $ext): ?array
    {
        $filename = $key . '.' . $ext;
        $path = trailingslashit(self::dir()) . $filename;
        if (!is_readable($path)) {
            return null;
        }

        return [
            'path' => $path,
            'url' => self::url_for($filename),
        ];
    }

    /**
     * @return array{path: string, url: string}
     */
    public static function put(string $key, string $ext, string $bytes): array
    {
        $filename = $key . '.' . $ext;
        $path = trailingslashit(self::dir()) . $filename;
        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
        file_put_contents($path, $bytes);

        return [
            'path' => $path,
            'url' => self::url_for($filename),
        ];
    }
}
