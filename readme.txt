=== Max QR Code ===
Contributors: michaelgenesisii
Tags: qr, qr code, shortcode, dynamic qr, getmaxqr
Requires at least: 6.0
Tested up to: 6.7
Requires PHP: 8.1
Stable tag: 0.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Generate and embed Max QR Code images with shortcodes via the Max QR Code API.

== Description ==

Max QR Code connects your WordPress site to the Max QR Code API so you can embed polished QR images with shortcodes.

= Features =

* Shortcode `[max_qr]` for URL, text, tel, SMS, Wi‑Fi, email, payment, and geo
* Shortcode `[max_qr_dynamic]` for an existing dynamic code’s redirect URL
* Settings for API key, workspace, defaults, and image cache
* Test connection against the live API
* Optional disk cache under `uploads/max-qr-code/`

= External service =

This plugin sends QR generation requests to `https://api.getmaxqr.com`. You need a Max QR Code Business API key (`mq_live_…`).

* Dashboard: https://www.getmaxqr.com/dashboard
* Documentation: https://www.getmaxqr.com/docs
* Privacy: https://www.getmaxqr.com/privacy

API keys stay on your WordPress server (settings or `MAXQR_API_KEY` environment variable). Do not put them in theme JavaScript.

== Installation ==

1. Upload the `max-qr-code` folder to `/wp-content/plugins/`, or install the ZIP via Plugins → Add New → Upload
2. Activate **Max QR Code**
3. Open **Settings → Max QR Code** and add your API key
4. Click **Test connection**
5. Add `[max_qr url="https://example.com"]` to a post or page

== Frequently Asked Questions ==

= Does this work without an API key? =

Guest generate may work for limited traffic, but the plugin expects a Business API key for reliable production use.

= Are API keys safe? =

Keys are stored in WordPress options (or `MAXQR_API_KEY` in the environment) and used only in server-side requests.

= What happens on uninstall? =

Plugin settings are removed. Cached images under `uploads/max-qr-code/` are kept so published pages keep working.

== Changelog ==

= 0.1.0 =
* Initial release: settings, test connection, `[max_qr]` and `[max_qr_dynamic]`, disk cache

== Upgrade Notice ==

= 0.1.0 =
Initial public release.
