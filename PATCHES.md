# GutenBricks 1.1.26.1 — Patch Summary for Review

Baseline: stock GutenBricks 1.1.26.1. All changes below are additive patches on top of
that baseline, made to restore compatibility with WordPress 7.1 (which made the post
editor canvas unconditionally iframed) and to fix a couple of unrelated bugs found
along the way. Tested against Bricks Builder 2.3.6 and 2.3.11.

Repo: `mizzinc/gutenbricks` (single-commit snapshot on `main`, no upstream diff history).

---

## 1. WP 7.1 iframe compatibility fix (primary issue)

**File:** `admin/dist/editor.js`

**Root cause:** WP 7.1 makes the post editor canvas unconditionally iframed (previously
it fell back to non-iframe mode for apiVersion 1/2 blocks, which is how GutenBricks was
getting away without this). Per WP's own 7.1 dev notes, code that reaches for the global
`document`/`window` to touch the canvas now targets the wrong document. Three functions
in `editor.js` inject generated CSS via `document.head.appendChild(...)` — the top-level
admin document, not the iframe's document — so block styling never reached the canvas.

**Fix:** added a small resolver to each function that finds
`iframe[name="editor-canvas"]` and targets its `contentDocument` instead, falling back
to `document` when no iframe is present (classic editor, etc.):

- `embedInlineCss()` — primary per-block generated CSS
- `addLinkStyleSheetToDocument()` — dynamically fetched stylesheet links
- `loadDefaultBricksAssets()` — base Bricks framework CSS

No other logic changed in these functions.

**Known related gap, not patched:** the `Hq` script loader (also in `editor.js`) has the
same `document.head` pattern for loading interactive addon scripts (Swiper, Splide,
popups, accordion). Confirmed via console testing that `bricksAccordionForEditor` fails
to register as a result — accordions/tabs don't toggle in the *editor canvas preview*.
Frontend behavior is unaffected (separate code path, not iframed). Decided this is
acceptable to leave as-is rather than risk a bigger patch to stateful listener code
without a live test environment to verify against.

---

## 2. Duplicate CSS class fix

**File:** `includes/core/class-render-context.php`, method `filter__render_attributes()`

**Bug:** dynamic classes (Dynamic Class control / ACF-driven classes) were being merged
into an element's `class` attribute twice — once via `inject_html_attributes()` on
`bricks/element/set_root_attributes`, and again via this method on
`bricks/element/render_attributes`. Result: elements rendered with the same class listed
twice (e.g. `class="bg--dark bg--dark"`).

**Fix:** removed the second, duplicate merge. Only the first (correct) merge remains.

Unrelated to the iframe fix — found while diffing against an earlier local patch dated
29 May 2026.

---

## 3. Supported Bricks versions extended

**File:** `includes/core/class-bricks-bridge.php`

```php
// before
public static $supported_bricks_versions = ['1.10.3', '1.11', '1.11.1.1', '1.12.0', '1.12.1'];

// after
public static $supported_bricks_versions = ['1.10.3', '1.11', '1.11.1.1', '1.12.0', '1.12.1', '2.3.6', '2.3.11'];
```

Cosmetic only (console warning), added after confirming both versions work correctly
with the patched plugin.

---

## 4. Cache-busting fix for `editor.js`

**File:** `includes/core/class-gutenberg-editor.php`, method `enqueue_gutenberg_editor_scripts()`

**Problem:** `editor.js` was enqueued with `'version' => GUTENBRICKS_VERSION` — a static
string. Editing the file's contents doesn't change the URL, so CDN/browser caches can
keep serving stale (pre-patch) copies indefinitely.

**Fix:**

```php
$_gbrx_editor_js_path = GUTENBRICKS_PLUGIN_DIR . 'admin/dist/editor.js';
$_gbrx_asset_version = file_exists($_gbrx_editor_js_path) ? (string) filemtime($_gbrx_editor_js_path) : GUTENBRICKS_VERSION;
```

...passed as the `version` in the `Vite\enqueue_asset()` call instead of the static
constant. The query string now changes automatically whenever the file's mtime changes,
independent of whether the plugin version string gets bumped.

---

## 5. Version string bump

**File:** `gutenbricks.php`

- Plugin header `Version:` → `1.1.26.1-mzn.1`
- `define('GUTENBRICKS_VERSION', ...)` → `'1.1.26.1-mzn.1'`
- The `GUTENBRICKS_MODE = 'dev'` check (originally `if (GUTENBRICKS_VERSION === '1.1.26.1')`)
  was widened to match either the original or the new string, so debug console logging
  (`[GutenBricks] Enqueued Scripts/Styles for Editor:`) still works — this would otherwise
  have silently gone dark on the version bump.

Reasoning: makes the fork visually identifiable in `wp-admin/plugins.php`, and
cache-busts every other asset enqueued with `GUTENBRICKS_VERSION` (not just `editor.js`,
which has its own filemtime-based fix above).

---

## 6. Vendor update-checker disabled

**File:** `includes/sdk/src/Updater.php`, method `run_plugin_hooks()`

```php
public function run_plugin_hooks() {
    // add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'check_plugin_update' ) );
    // add_filter( 'plugins_api', array( $this, 'plugins_api_filter' ), 10, 3 );
}
```

**Reasoning:** the version bump in #5 tripped PHP's `version_compare()` semantics — a
`-suffix` string sorts *below* the plain version it's based on, so WordPress started
reporting "new version 1.1.26.1 available" (i.e. offering to overwrite our patch with
stock code). Rather than chase version-string numbering games, disabled the two hooks
that populate WordPress's update-available transient and `plugins_api` info screen.
License/activation logic (`Client`, `License` classes) is untouched — only the
update-check filters are disabled. This also means "Enable auto-updates" is now inert
for this plugin, preventing an accidental silent overwrite of the patched files.

**Trade-off:** no more automatic notification if the vendor ships a genuine security
fix to the 1.1.26.x line — would need to check their changelog manually going forward.

---

## Testing performed

- WP 7.1 + Bricks 2.3.6 + patched build — editor canvas renders correctly, CSS lands
  in the iframe, no fatal errors.
- WP 7.1 + Bricks 2.3.11 + patched build — same result, console log signature
  consistent with 2.3.6 run (no new errors introduced).
- Bricks 1.12.5 downgrade attempted for isolation testing — caused an unrelated fatal
  in the site's own `bricks-child` theme (`elements.php` referencing `Bricks\Elements`,
  not present at that Bricks version). Not a GutenBricks issue; downgrade path abandoned
  in favor of testing directly on 2.3.x.

## Not fixed / out of scope

- `bricksAccordionForEditor` — editor canvas preview only, frontend unaffected (see §1).
- `bricks.min.js` `Cannot read properties of null (reading 'classList')` console error —
  confirmed to be Bricks Builder's own code, not GutenBricks. Same root cause class
  (top-document vs iframe-document) but not ours to patch.
- Frontend output includes repeated `<style id="gbrx-global-classes-css">` blocks (one
  per rendered block instance rather than once per page) — traced to GutenBricks
  triggering a fresh Bricks Assets render pass per block. Confirmed present in 2.0 RC3
  as well, not something this patch set addresses. 2.0 RC3 includes a new
  `GlobalClassUsageRegistrar.php` class that appears aimed at this but doesn't fully
  resolve it in the RC.
