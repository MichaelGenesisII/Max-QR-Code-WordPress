<?php

declare(strict_types=1);

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

delete_option('max_qr_code_settings');

// Cached QR files under uploads/max-qr-code/ are left in place so sites can keep published images.
