=== GutenBricks (mizzinc patched fork) ===
Contributors: Ryan Lee (original), patched by mizzinc
Donate link: www.wiredwp.com
Tags: bricks builder, gutenbricks, gutenberg
Requires at least: 6.2.1
Tested up to: 7.1
Stable tag: 1.1.26.1-mzn.1
Requires PHP: 7.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

== Description ==

www.gutenbricks.com

This is a patched fork of the vendor's 1.1.26.1 release, maintained at
mizzinc/gutenbricks to restore compatibility with WordPress 7.1's always-iframed
post editor. See CHANGELOG.md in this repo for the full list of edits and reasoning.
The vendor's own update-checker is disabled in this fork — do not re-enable
auto-updates, or WordPress will silently overwrite these patches with stock 1.1.26.1.

== Installation ==

- The plugin requires Bricks Builder installed. Tested against 1.10.3–1.12.1 (vendor
  supported range) and 2.3.6 / 2.3.11 (patched for WP 7.1 compatibility).
- You can upload the plugin via WordPress UI or upload the plugin to /wp-content/plugins
- Then you can activate the plugin

== Changelog ==

= 1.1.26.1-mzn.1 =
* Fix: block styling not rendering in the editor canvas under WP 7.1 (always-iframed
  post editor) — see CHANGELOG.md
* Fix: duplicate CSS classes on elements using Dynamic Class / ACF-driven classes
* Fix: editor.js cache-busting now uses file mtime instead of a static version string
* Chore: extended supported Bricks Builder versions to include 2.3.6 and 2.3.11
* Chore: disabled vendor auto-update checker to prevent this fork being silently
  overwritten by a stock release
