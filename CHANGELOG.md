# Changelog

All notable changes to **i-Downloads**. Format loosely based on [Keep a Changelog](https://keepachangelog.com/). Versions follow [Semantic Versioning](https://semver.org/) once we hit 1.0.0; pre-1.0 bumps are incremental and freely breaking.

## [0.4.6] — 2026-04-14

### Changed
- **Full WPCS 3.0 pass.** Reduced phpcs violations from 1,575 errors + 399 warnings down to **0 errors + 18 intentional warnings**. Every remaining warning has a rationale-bearing `phpcs:ignore` suppression (direct filesystem ops where `WP_Filesystem` doesn't apply, external `wp_redirect()` for off-site download targets, read-only `$_GET` display filters in admin list tables, reserved-keyword parameter names in autoloaders and filter callbacks, Ghostscript `exec`/`shell_exec` for PDF thumbnails).
- Custom phpcs ruleset (`phpcs.xml.dist`) pinned to WPCS 3.0, PHP 8.4, WP 6.6+. Excludes long-array syntax, docblock sniffs, and template-scope variable globals — keeps short `[]` syntax and the class-file autoloader naming convention while enforcing the rest of WordPress core style.
- `composer.json` now requires PHP `>=8.4` to match the plugin header (was `>=8.1`).

### Added
- **PHPUnit test suite** under `tests/` — 16 tests, 45 assertions, all green on wp-phpunit 6.9 + PHPUnit 9.6.
  - `ActivationTest` — custom tables exist, CPT and taxonomies register, `idl_files_dir()` is under uploads.
  - `FileManagerTest` — add/get/get_files/update_meta/increment_count/delete for external links.
  - `HelpersTest` — `idl_create_draft_download()` title requirement, CPT/status/category/meta wiring; `idl_cyrillic_to_latin()` transliteration; `idl_category_folder_path()` ancestor walking; end-to-end Cyrillic-name → ASCII-folder-path invariant.
- `phpunit.xml.dist` + `tests/bootstrap.php` + `tests/wp-tests-config-sample.php` + `tests/php.ini` + `tests/php-wrapper.bat` (Windows/Local-by-Flywheel harness).

## [0.4.5] — 2026-04-11

### Changed
- Marked Sentinel and Orbit extensions as **Coming soon** in the Extensions tab with an amber badge. Learn More buttons marked `aria-disabled`.

## [0.4.4] — 2026-04-11

### Added
- **Frontend unpublished-download visibility filter.** Drafts and private downloads are now hidden from frontend queries unless the current user's allowed-category set covers them. Implemented via `posts_clauses` for SQL-level OR between `post_status = 'publish'` and "user is in scope" — `WP_Query` can't express this natively.
- **Classic editor category metabox filter.** `get_terms_args` filter scoped to `post.php` / `post-new.php` on `idl` posts injects an `include` list of the user's effective term IDs, so editors only see categories they can actually pick.

## [0.4.3] — 2026-04-11

### Fixed
- External-link downloads silently redirecting to `/wp-admin/`. `wp_safe_redirect()` rejects any URL whose host isn't in WordPress's allowlist and falls back to admin — the opposite of what you want for an intentional off-site link. Switched to `wp_redirect()` with explicit URL validation.

## [0.4.2] — 2026-04-11

### Fixed
- Multi-file grid cards bursting out of their fixed-aspect tile because each file rendered as its own flex row. Cards with a `__title` header (multi-file indicator) now release the 1.5:2.7 aspect lock and stack files compactly, using CSS `:has()` to detect the case.

## [0.4.1] — 2026-04-11

### Fixed
- File-type badge CSS specificity bugs: grid badges rendered grey instead of colored, and list-mode showed duplicate badges next to the big icon tile. `.idl-file-item__meta .idl-meta--type` (2-class) now matches `.idl-file-item__meta .idl-meta` for the hide rule, and type colors use a 3-class selector to beat the fallback.

## [0.4.0] — 2026-04-11

### Changed
- File-type badges on the public download card are now **grid-only**. Single-download views and the Download Button block were showing a duplicate small badge next to the already-colored big icon tile.

## [0.3.9] — 2026-04-11

### Changed
- **Container queries instead of media queries.** The public download list now adapts to whatever container it's dropped into — narrow sidebar, full-width FSE template, two-column layout — independent of viewport. `.idl-list-wrap`, `.idl-grid`, `.idl-category-grid` are query containers; breakpoints moved from `@media` to `@container` with rem-based thresholds so zoom scales them naturally.
- **px → em/rem where lossless.** Grid track minmax (`180px` → `11rem`), list-mode file icon tile (`42px × 52px` → `2.6em × 3.25em`), HOT badge sizing, modal dimensions. Kept px for 1-pixel hairlines, tap-target minimums (WCAG 2.5.5 is in physical px), and dashicon glyph sizing.

## [0.3.8] — 2026-04-11

### Fixed
- Mobile rendering of grid cards — the portrait aspect-ratio was leaving massive whitespace when only one card fit per row. Released the aspect lock on narrow viewports so cards size to their content.

## [0.3.7] — 2026-04-11

### Fixed
- Grid-card title clipping to a single line instead of wrapping. `white-space: nowrap` was cascading from the list-mode title rule; the grid override now explicitly resets `white-space`, `text-overflow`, and puts `-webkit-line-clamp: 3` directly on the title element.

## [0.3.6] — 2026-04-11

### Changed
- Grid-card layout rewritten with three fixed bands: **30% title strip**, **55% meta block**, **15% full-width download button**. Title band gets a distinct light-grey background and bottom divider. Button `padding: 0; line-height: 1` so text actually centers vertically (`.wp-element-button` ships with padding that was pushing text off-center).
- Meta row reflows column-wise in grid mode, `font-size: .95em` (up from `.75em`), dashicons bumped to `18px`.

## [0.3.5] — 2026-04-11

### Changed
- Grid card: title on top with 3-line clamp, file type as a colored inline badge in the meta row, full-width bottom button. Date formatting now falls back to `get_option('date_format')` when the plugin's date format setting is empty.

## [0.3.4] — 2026-04-11

### Changed
- Grid tracks use `repeat(auto-fill, minmax(180px, 1fr))` instead of `repeat(3, 1fr)` so cells never stretch wider than a card when the row is half-empty.

## [0.3.3] — 2026-04-11

### Added
- **"Include subcategories" toggle** on the Download List block (Filter panel) and `[idl_list]` shortcode. Default `true`, matching previous implicit behaviour; can be turned off for "this category exactly" listings.

### Fixed
- Rewrite flush triggered on upgrade fixes `idl_category` taxonomy archive 404s when the slug option is freshly set.

## [0.3.2] — 2026-04-11

### Fixed
- Non-admin users getting locked out of draft save with "you don't have permission to edit this post". `can_edit_download()` returned `false` for posts with no assigned category (fresh auto-drafts), which `map_meta_cap` translated into a `do_not_allow` on `edit_post`. Brand-new drafts now pass through; save-time `tax_input` enforcement still blocks forbidden categories.
- Dropped the save-time "source category" check. By the time `save_post_idl` fires, `wp_insert_post` has already written the new `tax_input` terms, so the "source" read back was actually the target. Source is already enforced by `map_meta_cap` at screen/REST entry, so the save-side check was both broken and redundant.

## [0.3.1] — 2026-04-11

### Added
- `wp_insert_post_data` filter transliterates Cyrillic characters in `idl` post slugs to Latin. Same urldecode-first pattern as the category fix.

### Fixed
- Dropzone click not opening the file picker. `jQuery.trigger('click')` fires a synthetic event — browsers only open the file-picker dialog in response to a real user gesture, which means the native DOM `element.click()` method. Called that directly instead.

## [0.3.0] — 2026-04-11

### Added
- **User-category ACL (write-side permissions).** New `IDL_Category_ACL` class:
  - `_idl_allowed_categories` user meta — array of explicit term IDs.
  - `get_effective()` expands each explicit ID with `get_term_children()` so inheritance works down the subtree.
  - `map_meta_cap` filter on `edit_post` / `delete_post` / `publish_post` denies via `do_not_allow` when the user can't write the download's category. Covers admin UI, REST, inline row actions, quick edit, bulk edit, and all AJAX handlers gated on `current_user_can('edit_post', $id)`.
  - `save_post_idl` priority 1 rejects the save with `wp_die(403)` if posted `tax_input[idl_category]` contains a forbidden term.
  - `pre_get_posts` admin list filter injects a `tax_query` restricting to effective categories. Empty set → shows nothing.
  - Profile UI with collapsible `<details>` category tree (zero JS), admin-only. Branches auto-open when a descendant is selected.
  - Admins (`manage_options`) always unrestricted.

## [0.2.9] — 2026-04-10

### Added
- **Per-file inline metadata editing.** Edit button on each file row opens an inline editor for title + description. `IDL_File_Manager::update_meta()` writes only those two fields; file path / name / size / hash / mime are never touched. Optimistic row update on save.

## [0.2.8] — 2026-04-10

### Removed (Dead-weight purge)
- `wp_idl_files.attachment_id` column dropped via activator migration (`drop_legacy_columns()`, idempotent via `INFORMATION_SCHEMA` check).
- `IDL_File_Manager::attach_media()` method.
- `wp_ajax_idl_attach_media` handler.
- Media-library fallback in `IDL_Download_Handler::resolve_path()`.
- `get_attached_file($file->attachment_id)` in `IDL_Pdf_Thumbnail` — now reads from `file_path`.
- `idl_storage_mode` and `idl_custom_folder` options.
- `_idl_storage_mode` post meta (cleaned up across all posts).
- `idl_attach_file()` and `idl_file_exists_by_hash()` wrapper functions from `functions.php`.
- Storage-mode dropdowns from `settings-page.php` and `meta-box-settings.php`.
- `wp_enqueue_media()` call.

### Changed
- Settings → General now shows a read-only "Storage Location" display with the absolute `idl_files_dir()` path.

## [0.2.7] — 2026-04-10

### Fixed
- Cyrillic category slug transliteration not triggering. `sanitize_title()` URL-encodes non-ASCII characters, so by the time `force_latin_slug()` checked `preg_match('/\p{Cyrillic}/u', …)`, the string was already `%d0%bf…` — pure ASCII, no Cyrillic to match. Fix: `urldecode()` the stored slug before testing and transliterating.

## [0.2.6] — 2026-04-10

### Changed
- `created_idl_category` / `edited_idl_category` hooks register at `PHP_INT_MAX` priority so no later callback can overwrite the slug after we rewrite it.
- Added `error_log()` diagnostics inside `force_latin_slug()` for debugging.

## [0.2.5] — 2026-04-10

### Added
- `pre_term_slug` filter — intercepts and transliterates inside `wp_insert_term()` before the slug is stored. Runs alongside the `created_idl_category` DB safety net.

## [0.2.4] — 2026-04-10

### Added
- `IDL_Category_Folders::force_latin_slug()` — bulletproof DB-level slug rewrite. Reads via `$wpdb`, transliterates, updates via `$wpdb->update()`, invalidates term cache. Handles uniqueness by appending `-2`, `-3`, etc. on collision. Unconditional (removes dependency on filter chains being intact).

### Removed
- `wp_insert_term_data` / `wp_update_term_data` filters — unreliable, replaced by the DB-level approach above.

## [0.2.3] — 2026-04-10

### Added
- **Upload popup rewrite.** Files meta box replaced with tabbed UI:
  - **Upload** — drag-and-drop dropzone with per-file XHR upload and progress bars. Native `click()` on the hidden file input. Files land directly in the target category folder via `idl_sanitize_filename()` + collision check.
  - **From Folder** — lists physical files currently in the category folder, flags tracked vs untracked, one-click link import.
  - **External URL** — the pre-existing URL form, preserved.
- `IDL_File_Manager::add_local_file()` — inserts a DB row for a file already on disk.
- Three new AJAX handlers: `ajax_upload_file`, `ajax_browse_category`, `ajax_import_file`.
- Target-category bar at the top of the meta box; warning state when no category is assigned or the post is unsaved.

### Changed
- Category filter on the admin Downloads list now uses `include_children` explicitly (true by default).

### Removed
- Dependency on `wp_enqueue_media()` and the old Media Library picker flow.

## [0.2.2] — 2026-04-10

### Changed
- PHP requirement bumped to **8.4**. Adopted new-without-parens syntax (`new IDL_X()->register_hooks()` instead of `(new IDL_X())->register_hooks()`) throughout the bootstrap.
- `define('IDL_VERSION', …)` replaced with `const IDL_VERSION` where the IDE suggested it.
- Various string interpolation and arrow-function hygiene fixes from IDE hints.

## [0.2.1] — 2026-04-10

### Added
- **Custom folder + category filesystem architecture.** First cut. Every `idl_category` term maps to a physical folder under `wp-content/uploads/idl-files/`, nested by slug chain.
- `idl_files_dir()` helper with static cache.
- `idl_category_folder_path(int)` and `idl_category_fs_path(int)` helpers that walk the ancestor chain.
- **`IDL_Category_Folders` class**:
  - Folder creation on `created_idl_category`.
  - Folder rename on `edited_idl_category` (with prefix-replace SQL updating all affected `idl_files.file_path` rows in one query).
  - Blocking error on category delete if any downloads are still assigned.
  - Auto-move files on disk when a download's category assignment changes (`set_object_terms` hook).
- **Filename sanitization pipeline** (`idl_sanitize_filename()`):
  - Strip duplicate extension (`file.pdf.pdf` → `file.pdf`).
  - Lowercase extension against allow-list from settings.
  - Serbian Cyrillic → Latin transliteration.
  - `remove_accents()` for Latin diacritics.
  - Slugify to `[a-z0-9-]`.
  - 80-char stem length cap, blocking error above.
- `idl_filename_collision()` blocking-collision check.
- `idl_cyrillic_to_latin()` and `idl_latin_to_cyrillic()` transliteration maps (digraph-aware).
- `idl_autofill_title()` — Latin → Cyrillic conversion for the title field when the "Cyrillic titles" setting is on.
- `.htaccess` deny-all written to `idl-files/` on activation (Apache; Nginx handled via settings hint).
- Auto-upgrade on version mismatch via `plugins_loaded` priority-1 version check that calls `IDL_Activator::activate()`.
- Settings → General: **Allowed File Extensions** textarea, **Cyrillic Titles** switch.
- Single-download page template now reads `idl_files_dir()` with `realpath` path-traversal guard.

### Changed
- `IDL_Download_Handler::resolve_path()` now uses `idl_files_dir()` as the single source of truth.
- `plugins_loaded` bootstrap registers `IDL_Category_Folders`.

### Fixed
- Single download page 404 from the activation-hook `flush_rewrite_rules()` firing before `plugins_loaded` (CPT not yet registered). Deferred flush flag pattern: `idl_flush_rewrite_rules` option set on activation, consumed at `init` priority 999.
- `idl_mime_icon_class()` fatal redeclaration — moved from the inline `download-card.php` template (which was included once per download in a list) into `functions.php`.
- Download Button block rendered as a bare button instead of a full card — now loads `get_post()` and renders `download-card.php`.
- Live-search insert UI on the Download Entry block, with debounced `apiFetch`.
- Global **Default Button Text** setting in Settings → Display.
- Classic editor TinyMCE insert button with modal search.

[0.4.5]: https://github.com/isoft-mionica/i-downloads/releases/tag/v0.4.5
[0.4.4]: https://github.com/isoft-mionica/i-downloads/releases/tag/v0.4.4
[0.4.3]: https://github.com/isoft-mionica/i-downloads/releases/tag/v0.4.3
[0.4.2]: https://github.com/isoft-mionica/i-downloads/releases/tag/v0.4.2
[0.4.1]: https://github.com/isoft-mionica/i-downloads/releases/tag/v0.4.1
[0.4.0]: https://github.com/isoft-mionica/i-downloads/releases/tag/v0.4.0
[0.3.9]: https://github.com/isoft-mionica/i-downloads/releases/tag/v0.3.9
[0.3.8]: https://github.com/isoft-mionica/i-downloads/releases/tag/v0.3.8
[0.3.7]: https://github.com/isoft-mionica/i-downloads/releases/tag/v0.3.7
[0.3.6]: https://github.com/isoft-mionica/i-downloads/releases/tag/v0.3.6
[0.3.5]: https://github.com/isoft-mionica/i-downloads/releases/tag/v0.3.5
[0.3.4]: https://github.com/isoft-mionica/i-downloads/releases/tag/v0.3.4
[0.3.3]: https://github.com/isoft-mionica/i-downloads/releases/tag/v0.3.3
[0.3.2]: https://github.com/isoft-mionica/i-downloads/releases/tag/v0.3.2
[0.3.1]: https://github.com/isoft-mionica/i-downloads/releases/tag/v0.3.1
[0.3.0]: https://github.com/isoft-mionica/i-downloads/releases/tag/v0.3.0
[0.2.9]: https://github.com/isoft-mionica/i-downloads/releases/tag/v0.2.9
[0.2.8]: https://github.com/isoft-mionica/i-downloads/releases/tag/v0.2.8
[0.2.7]: https://github.com/isoft-mionica/i-downloads/releases/tag/v0.2.7
[0.2.6]: https://github.com/isoft-mionica/i-downloads/releases/tag/v0.2.6
[0.2.5]: https://github.com/isoft-mionica/i-downloads/releases/tag/v0.2.5
[0.2.4]: https://github.com/isoft-mionica/i-downloads/releases/tag/v0.2.4
[0.2.3]: https://github.com/isoft-mionica/i-downloads/releases/tag/v0.2.3
[0.2.2]: https://github.com/isoft-mionica/i-downloads/releases/tag/v0.2.2
[0.2.1]: https://github.com/isoft-mionica/i-downloads/releases/tag/v0.2.1
