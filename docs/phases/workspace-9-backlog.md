# Phase 9: Backlog Cleanup

**Date**: 2026-03-25
**Files modified**: 16

## Goal

Address all audit findings from the pre-workspace codebase review: fix-during-build items that weren't reached during workspace phases, plus backlog cleanup tasks for code quality, security, and DRY improvements.

## What Was Done

### Security Fixes

- **8.0a: GF description + schema** — Removed PII field references (ip, user_agent, source_url) from get-entry description. Added `additionalProperties: true` to output schemas documenting dynamic field_N keys.
- **8.0b: Yoast URL sanitizer** — Changed `sanitize_text_field()` to `esc_url_raw()` for `canonical_url` and `og_image` fields in update-post-seo.
- **8.0c: Nested meta sanitization** — Added recursive `sanitize_meta_value()` method to content write abilities. Arrays of arrays now sanitized at all levels.
- **8.0d: GF create-entry sanitization** — Added recursive sanitization for non-string field values in GFAPI::add_entry() calls.
- **9.7: Media sideload title** — Added `sanitize_text_field()` to title before passing to `media_sideload_image()`.
- **7.0a: Bundle Select2 locally** — Downloaded Select2 CSS/JS to `admin/vendor/select2/`, eliminated CDN dependency and SRI concern.

### Code Quality

- **9.1: Split content-abilities.php** — Split 561-line file into read (333 lines) and write (342 lines) abilities. Updated registrar to load both.
- **9.4: ACF permission callbacks** — Replaced 4 inline closures with `self::permission_for_post()` from base class.
- **9.5: POST handler moved** — Extracted template extraction form processing from tab-templates.php view into class-admin-page.php controller.
- **9.6: Document the_content risk** — Added comment explaining that `apply_filters('the_content')` runs all content filters including shortcodes and third-party plugins.

### DRY / Consistency

- **9.2: License validation stubs** — Replaced accept-any stubs with basic format validation (8+ chars, alphanumeric + hyphens). TODO for LemonSqueezy API remains.
- **9.3: Ability count comments** — Added breakdown comments to all 5 addon `get_ability_count()` methods documenting where the number comes from.
- **9.8: Internationalize admin.js** — Passed 20 translatable strings via `wp_localize_script` in `wmcpAdmin.strings`. All hardcoded English removed from JS.

## File Inventory

| File | Change |
| ---- | ------ |
| `abilities/class-content-abilities.php` | Split to read-only (333 lines), added the_content comment |
| `abilities/class-content-write-abilities.php` | NEW — write abilities (342 lines), recursive meta sanitization |
| `class-ability-registrar.php` | Added write-abilities registration |
| `modules/gravity-forms/class-gf-abilities.php` | PII description fix, output schema fix, entry sanitization |
| `modules/gravity-forms/class-gf-addon.php` | License format validation, ability count comment |
| `modules/yoast/class-yoast-abilities.php` | URL field sanitizer fix |
| `modules/yoast/class-yoast-addon.php` | License format validation, ability count comment |
| `modules/acf/class-acf-field-abilities.php` | permission_for_post() DRY fix |
| `modules/acf/class-acf-block-abilities.php` | permission_for_post() DRY fix |
| `modules/acf/class-acf-addon.php` | Ability count comment |
| `modules/workspace/class-workspace-addon.php` | Ability count comment |
| `modules/bricks-workspace/class-bricks-workspace-addon.php` | Ability count comment |
| `abilities/class-media-abilities.php` | Title sanitization added |
| `admin/class-admin-page.php` | POST handler moved in, i18n strings added, Select2 bundled |
| `admin/views/tab-templates.php` | POST handler removed |
| `admin/js/admin.js` | All strings via wmcpAdmin.strings |
| `admin/vendor/select2/` | NEW — local Select2 CSS/JS |
