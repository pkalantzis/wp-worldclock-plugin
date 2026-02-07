# Multi Timezone Clocks

Multi Timezone Clocks is a WordPress plugin that lets you configure multiple clocks in the admin area and display them on the frontend using shortcodes. Each clock can be **Analog or Digital**, assigned to a specific **IANA timezone**, or optionally show the **visitor’s local (browser) time**.

Each shortcode renders **one clock**, making it easy to place clocks anywhere in pages, posts, or widgets.

## Features

- Admin settings page (Settings → Timezone Clocks)
- Configure multiple clocks with:
  - Label
  - IANA timezone (e.g. `Europe/Athens`)
  - Clock type: **Analog** or **Digital**
- One clock per shortcode: `[mtc_clock id="1"]`
- Per-shortcode overrides:
  - Override clock type (`analog`, `digital`, or `config`)
  - Show visitor’s local time (browser timezone)
- Digital clock options:
  - 12h / 24h format
  - Show or hide seconds
  - Optional date display
- Analog clock options:
  - Adjustable size
  - Optional smooth second-hand animation

## Requirements

- WordPress 5.8+
- PHP 7.4+
- Modern browser with `Intl.DateTimeFormat` support

## Installation

1. Copy the plugin directory to:
   `wp-content/plugins/wp-clocks-plugin/`
2. Activate the plugin in **WordPress Admin → Plugins**
3. Configure clocks in **Settings → Timezone Clocks**

## Usage

Basic usage (uses configured type and timezone): [mtc_clock id="1"]
Force digital clock: [mtc_clock id="1" type="digital"]
Force analog clock with options: [mtc_clock id="2" type="analog" size="220" smooth="1"]
Show visitor’s local (browser) time: [mtc_clock id="1" user_time="1"]
Digital clock options: [mtc_clock id="3" format="12" seconds="0" show_date="1"]

## Label position

Show label above the clock (default): [mtc_clock id="1" label_position="above"]
Show label below the clock: [mtc_clock id="1" label_position="below"]

## Shortcode Attributes

| Attribute        | Description                            |
| ---------------- | -------------------------------------- |
| `id`             | Clock ID from admin configuration      |
| `type`           | `config`, `analog`, or `digital`       |
| `user_time`      | Use visitor’s local timezone (`1`/`0`) |
| `label_position` | `above` or `below`                     |
| `show_tz`        | Show timezone label (`1`/`0`)          |
| `size`           | Analog clock size (80–600 px)          |
| `smooth`         | Smooth second hand (analog)            |
| `format`         | `24` or `12` (digital)                 |
| `seconds`        | Show seconds (digital)                 |
| `show_date`      | Show date (digital)                    |

## Notes

Only IANA timezone names are supported (e.g. America/New_York). Visitor local time uses the browser timezone (privacy-safe). Invalid timezones are handled gracefully on the frontend

## License

GPL-2.0-or-later