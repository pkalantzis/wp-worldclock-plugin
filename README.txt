=== Multi Timezone Clocks ===
Contributors: pkalantzis
Tags: clock, timezone, analog, digital, shortcode
Requires at least: 5.8
Tested up to: 6.5
Requires PHP: 7.4
Stable tag: 1.3.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Configure multiple clocks (Analog or Digital) with different timezones, then display one clock per shortcode. Supports type overrides and showing the visitor’s local time.

== Description ==

Multi Timezone Clocks lets you configure clocks from an admin page:

* Set the number of clocks (1–50)
* For each clock: label, IANA timezone, and type (Analog/Digital)

Then display individual clocks with a shortcode like:

[mtc_clock id="1"]

Per-shortcode overrides include:

* Override type: type="config|analog|digital"
* Show visitor’s local time: user_time="1"

Digital shortcodes can also control 12/24-hour format, seconds, and optional date. Analog shortcodes can control canvas size and smooth second-hand animation.

== Installation ==

1. Upload the plugin folder to /wp-content/plugins/wp-clocks-plugin/
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to Settings → Timezone Clocks to configure your clocks
4. Insert shortcodes into your pages/posts

== Frequently Asked Questions ==

= What timezone formats are supported? =

Use IANA timezones, e.g. Europe/Athens, America/New_York, Asia/Tokyo. Avoid "GMT+2" style strings.

= What does user_time="1" do? =

It uses the visitor’s browser timezone (local timezone). This is privacy-safe and does not require geolocation permission.

= Can I override the clock type per shortcode? =

Yes. Use type="digital" or type="analog". The default is type="config" which uses the admin setting for that clock.

= Does it update in real time? =

Yes. Updates occur client-side. Digital clocks align to the second boundary; analog clocks can tick each second or animate smoothly depending on smooth="1".

== Changelog ==

= 0.1.0=
* Initial version

== Shortcodes ==

= mtc_clock =

Basic:
[mtc_clock id="1"]

Override type:
[mtc_clock id="1" type="digital"]
[mtc_clock id="2" type="analog" size="220" smooth="1"]

Use visitor local time:
[mtc_clock id="1" user_time="1"]

Digital options:
format="24|12" seconds="1|0" show_date="1|0"

Analog options:
size="80..600" smooth="1|0"

Both:
show_tz="1|0"