# Security Policy

## Supported Versions

We recommend running the latest stable release of this plugin.

Security fixes will be included in new plugin releases. Older versions may not receive backported fixes.

## Reporting a Vulnerability

If you believe you have found a security vulnerability, please report it responsibly:

1. **Do not** open a public GitHub issue or disclose the vulnerability publicly.
2. Email the maintainer with:
   - A clear description of the issue
   - Steps to reproduce (proof of concept if possible)
   - Impact assessment (what can an attacker do?)
   - Your environment (WordPress version, PHP version, other relevant plugins/themes)
3. If you have a suggested fix, include it.

## Disclosure Process

After receiving a report, we will:

- Confirm receipt wihtin 72 hours
- Investigate and validate the issue
- Prepare a fix and release a new version
- Credit the reporter if desired

## Security Notes (Plugin Behavior)

- The plugin renders clocks client-side for real-time updates.
- User-provided admin settings are sanitized on save.
- Shortcode attributes are treated as untrusted input and validated/sanitized.
- Timezone handling relies on browser `Intl` support; invalid timezones are handled gracefully.

Thank you for helping keep users safe.