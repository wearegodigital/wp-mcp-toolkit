# Phases 6-8: Mode System, Admin UI, and Polish

**Date**: 2026-03-25
**Total workspace line count**: ~2,700 lines across 18 files (11 new PHP, 7 templates, 7 modified existing)

## Phase 6: Mode System

### Goal
Centralize mode detection and allowlist/blocklist management in the validator class, removing duplication from the abilities class.

### What Was Done
- **Task 6.1-6.2**: Added mode methods (`get_mode`, `resolve_mode`, `is_staging`, `is_production`, `set_mode`) and allowlist methods (`get_default_allowlist`, `get_blocklist`, `get_allowlist`, `set_custom_allowlist`, `is_function_allowed`) to `class-workspace-validator.php`
- **Task 6.3**: Refactored `execute_call_wp_api()` in abilities class to delegate to `Validator::is_function_allowed()` — removed 64 lines of inline blocklist/allowlist/mode logic

### Key Decisions
- Validator is the single source of truth for security enforcement — both the abilities class and admin UI read from it
- `resolve_mode('auto')` checks `WPMCP_DEV_MODE` constant BEFORE `wp_get_environment_type()` — the constant's purpose is to override the environment type
- Validator at 354 lines is over the 300-line guideline, but it's the central security hub — all content is substantive (blocklist arrays, validation methods, mode logic)

## Phase 7: Admin UI

### Goal
Add a Workspace tab to the existing settings page with mode selector, allowlist editor, artifact browser, and crash recovery.

### What Was Done
- **Task 7.1**: Added `'workspace'` to `$valid_tabs` in `settings-page.php`, added nav tab link
- **Task 7.2-7.5**: Created `tab-workspace.php` with 4 sections:
  - Mode selector (radio buttons: Auto/Staging/Production/Disabled with resolved mode badge)
  - Production allowlist (read-only defaults + custom textarea)
  - Artifact browser (table with name/type/file/created, empty state)
  - Crash recovery (conditional warning + recover button)
- **Task 7.6**: Registered workspace settings (`wpmcp_workspace_mode`, `wpmcp_workspace_allowlist`) with sanitize callbacks, added `ajax_workspace_recover` AJAX handler

### Key Decisions
- Used existing `wpmcp_settings` settings group — no separate settings group needed
- AJAX nonce uses existing `wpmcp_admin` pattern for consistency
- Workspace classes required at top of tab view using `dirname( __DIR__, 2 )` for plugin root resolution

## Phase 8: Polish

### What Was Done
- **Task 8.1**: Verified uninstall cleanup — `wpmcp_workspace_mode`, `wpmcp_workspace_allowlist` options cleaned, MU-plugin loader removed
- **Task 8.4**: LLM-optimized ability descriptions reviewed and updated across all 3 ability classes

## Simplification Passes

### Phase 6+7:
- Validator trimmed from 371 → 354 lines
- Abilities class clean at 307 lines (down from 372 before mode extraction)
- Admin tab clean at 193 lines

## File Inventory (Full Workspace Module)

### New Files Created (11 PHP + 7 templates)

| File | Lines | Purpose |
| ---- | ----- | ------- |
| `workspace/class-workspace-container.php` | 247 | Path resolution, writable detection, template rendering, workspace init |
| `workspace/class-workspace-validator.php` | 354 | Path/syntax/name validation, mode system, blocklist/allowlist |
| `workspace/class-workspace-file-writer.php` | 170 | Safe file write, atomic bootstrap write |
| `workspace/class-workspace-manifest.php` | 241 | Artifact tracking CRUD |
| `workspace/class-workspace-mu-loader.php` | 177 | MU-plugin install/update/uninstall, crash recovery |
| `workspace/class-workspace-module.php` | 41 | Module bootstrap |
| `workspace/class-workspace-addon.php` | 62 | Workspace addon interface (10 abilities) |
| `workspace/class-workspace-abilities.php` | 307 | 7 core abilities |
| `workspace/class-workspace-blocks-abilities.php` | 351 | 3 Gutenberg block abilities |
| `workspace/class-workspace-bricks-abilities.php` | 359 | 3 Bricks element abilities |
| `bricks-workspace/class-bricks-workspace-addon.php` | 59 | Bricks workspace addon interface (3 abilities) |
| `workspace/templates/*.tpl` | 7 files | Bootstrap, MU-loader, block, Bricks templates |

### Modified Existing Files (7)

| File | Change |
| ---- | ------ |
| `class-plugin.php` | +6 lines (addon registrations) |
| `class-abstract-abilities.php` | +13 lines (ability labels) |
| `admin/views/settings-page.php` | +2 lines (workspace tab) |
| `admin/views/tab-abilities.php` | +3 lines (category labels) |
| `admin/views/tab-workspace.php` | 193 lines (new tab view) |
| `admin/class-admin-page.php` | +40 lines (settings + AJAX) |
| `uninstall.php` | +8 lines (workspace cleanup) |
