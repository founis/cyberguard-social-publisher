=== CyberGuard Social Publisher ===
Contributors: cyberguard
Tags: facebook, instagram, meta, social media, scheduling
Requires at least: 6.2
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 0.3.1
License: GPLv2 or later

Publish and schedule CyberGuard content to a Facebook Page and Instagram Business account from WordPress.

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/` and activate it.
2. Open Social Publisher > Settings.
3. Enter a Facebook Page ID, Instagram Business Account ID and Page Access Token.
4. Test the connection, then create a post.

The Meta app/token must have the permissions required by Meta for Page and Instagram publishing. Images used for Instagram must be available through a public HTTPS URL.

== Customer status page ==

1. Create a WordPress page titled `בדיקת סטטוס טיפול`.
2. Add the shortcode `[cg_case_status]` to the page.
3. Manage customer cases under Social Publisher > פניות לקוחות.
4. Give each customer a case reference. The public lookup also requires the last four phone digits.

== Changelog ==

= 0.3.1 =
* Added a ready-to-publish Meta age-restriction guide based on the August 2026 official update.
* Added private age-verification diagnostic fields to customer cases.
* Fixed the front-end publisher dashboard so it loads every JSON content collection.

= 0.3.0 =
* Secure customer case-status lookup with a mobile RTL timeline.
* Case management inside WordPress admin.
* Weekly CyberGuard security content collection.
* Load multiple JSON content collections.

= 0.1.0 =
* Initial MVP with Facebook and Instagram publishing.
* WordPress cron scheduling.
* Media library integration and publication logs.
