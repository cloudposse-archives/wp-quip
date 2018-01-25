# wp-quip

Quip integration for Wordpress


## Introduction

`WP Qiup` plugin uses WordPress shortcodes to embed Quip documents into WordPress pages and blog posts.

https://codex.wordpress.org/Shortcode

https://codex.wordpress.org/Shortcode_API

https://en.support.wordpress.com/shortcodes/


## Installation

1. Upload `wp-quip` folder to the `/wp-content/plugins/` directory

2. Activate the `WP Quip` plugin through the `Plugins` menu in WordPress

3. On `Quip Settings` page (menu `Settings/WP Quip`), enter and save `Quip Access Token`


> To generate a personal access token, visit this page: https://quip.com/dev/token . Whenever you generate a new token, all previous tokens are automatically invalidated.
          
          

## Usage

To embed the content of a Quip document into a WordPress page or blog post, use `quip` shortcode.

`quip` shortcode accepts two attributes and has the following format:

```
[quip id="mWnnAszre3MW" ttl=7200]
```

where

* `id` (Required) - The ID of the Quip document (_e.g._ https://quip.com/mWnnAszre3MW)

* `ttl` (Optional) - Time-To-Live in seconds. 
After the first request to the Quip API, the plugin caches the content of the document (HTML and images) for the specified amount of time (seconds).
All consecutive requests to the same page or blog post will not call the Quip API again but will retrieve the document from the internal cache, making the pages faster.
After the `ttl` expires, the plugin will call the API again and cache the result. 
If the `ttl` attribute is not provided, the default value of 3600 seconds (1 hour) is used.


## References

For more information on Quip API, visit https://quip.com/dev/automation/documentation
