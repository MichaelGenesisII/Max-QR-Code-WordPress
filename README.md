# Max QR Code — WordPress plugin

Official WordPress plugin for [Max QR Code](https://www.getmaxqr.com).

Embed QR images with shortcodes using the Max QR Code API (Business `mq_live_…` keys).

## Install

1. Download / clone this repo into `wp-content/plugins/max-qr-code`
2. Activate **Max QR Code**
3. **Settings → Max QR Code** → paste API key → **Test connection**

Or upload a ZIP of this folder via **Plugins → Add New → Upload**.

## Shortcodes

```
[max_qr url="https://example.com"]
[max_qr type="tel" number="+15550100" size="256" fg="#111111" bg="#ffffff"]
[max_qr type="wifi" ssid="Cafe" password="secret" security="WPA"]
[max_qr_dynamic code_id="YOUR_CODE_ID" workspace="YOUR_WORKSPACE_ID"]
```

If `url` is omitted on a singular post/page, the current permalink is used.

## Environment

Optional server env vars (override empty settings):

- `MAXQR_API_KEY`
- `MAXQR_WORKSPACE_ID`

## Development

PHP 8.1+, WordPress 6.0+. No Composer required — uses the WordPress HTTP API.

## License

GPLv2 or later
