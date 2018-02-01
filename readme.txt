=== WP Quip ===
Contributors: cloudposse
Tags: quip, doc
Donate link:
Requires at least: 4.2
Tested up to: 4.9.2
Requires PHP: 5.6
Stable tag: 1.0.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Quip integration for WordPress


== Description ==

## Introduction

WP Quip plugin uses WordPress shortcodes to embed Quip documents into WordPress pages and blog posts.

https://codex.wordpress.org/Shortcode
https://codex.wordpress.org/Shortcode_API


## Usage

To embed the content of a Quip document into a WordPress page or blog post, use the 'quip' shortcode.

'quip' shortcode accepts two attributes and has the following format:

[quip id="mWnnAszre3MW" ttl=7200]


where

* 'id' (Required) - The ID of the Quip document (e.g. https://quip.com/mWnnAszre3MW)

* 'ttl' (Optional) - Time-To-Live in seconds.
After the first request to the Quip API, the plugin caches the content of the document (HTML and images) for the specified amount of time (seconds).
All consecutive requests to the same page or blog post will not call the Quip API again but will retrieve the document from the internal cache, making the pages faster.
After the 'ttl' expires, the plugin will call the Quip API and cache the result again.
If the 'ttl' attribute is not provided, the default value of 7200 seconds (2 hours) is used.
You can change the default value in 'Quip Settings' (menu 'Settings/WP Quip').
If 'ttl' is set to '0', the plugin will not cache the responses, and every request to the WordPress page or blog post will call the Quip API.

__NOTE__: Setting 'ttl' to '0' also invalidates the document cache.
This could be used if you change the Quip document and want the changes to be reflected on the website immediately.
In this case, update the document in Quip, set 'ttl' to '0' in the 'quip' shortcode,
refresh the WordPress page or blog post in the browser to invalidate the cache,
and then set 'ttl' back to its original value.


== Installation ==
1. Install the 'WP Quip' plugin

2. Activate the plugin through the 'Plugins' menu in WordPress

3. On 'Quip Settings' page (menu 'Settings/WP Quip'), update the default value for 'Time-to-Live' if needed

4. On 'Quip Settings' page, enter and save 'Quip API Access Token'

NOTE: To generate a Quip API Access Token, visit this page: https://quip.com/dev/token .
Whenever you generate a new token, all previous tokens are automatically invalidated.


== Frequently Asked Questions ==


== Screenshots ==
1. WP Quip plugin Settings page
2. WordPress post editor with a 'quip' shortcode
3. Quip document embedded into a WordPress blog post
4. Quip document embedded into a WordPress blog post


== Changelog ==
= 1.0.0 =
* Initial release


== Upgrade Notice ==
= 1.0.0 =
Initial release
