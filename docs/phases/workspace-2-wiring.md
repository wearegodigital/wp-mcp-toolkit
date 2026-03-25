# Phase 2: Module Wiring, Addon Classes, and MU-Plugin Loader

**Date**: 2026-03-25
**Line count**: 1,166 lines (4 new files + 3 modified files, cumulative with Phase 1)

## Goal

Wire the workspace into the main plugin so abilities can register and be tested via MCP. Build the MU-plugin loader management. Create both addon classes (workspace + bricks-workspace). Add ability and category labels.

## What Was Done

- **Task 2.1**: MU-plugin installer class — install/update/uninstall loader, crash status detection, crash recovery — file: `class-workspace-mu-loader.php`
- **Task 2.2**: Extended container with `initialize_workspace()`, `is_initialized()`, `reinitialize()` — creates full directory structure, renders bootstrap template, installs MU-plugin — file: `class-workspace-container.php`
- **Task 2.3**: Module bootstrap — `is_active()` checks mode option, `init()` loads workspace + block abilities, `activate()`/`deactivate()` manage workspace lifecycle — file: `class-workspace-module.php`
- **Task 2.4**: Workspace addon — implements all 11 interface methods, PHP 8.0+ gating via `is_available()`, registers 2 categories — file: `class-workspace-addon.php`
- **Task 2.5**: Registered workspace addon in `class-plugin.php::load_addon_registry()`
- **Task 2.6**: Bricks workspace addon — separate addon with `defined('BRICKS_VERSION')` + PHP 8.0+ gating, registers 1 category — file: `class-bricks-workspace-addon.php`
- **Task 2.7**: Registered bricks-workspace addon in `class-plugin.php::load_addon_registry()`
- **Task 2.8**: Added 13 ability labels to `class-abstract-abilities.php`
- **Task 2.9**: Added 3 category labels to `admin/views/tab-abilities.php`

## Key Decisions

- **Bricks as separate addon** — follows one-prefix-per-addon convention. Availability gated by both PHP 8.0+ and `defined('BRICKS_VERSION')`. Loads abilities from workspace module dir via cross-reference.
- **Module bootstrap accepts $disabled** — follows the corrected pattern from pre-workspace fixes. No re-reading from DB.
- **Container initialize_workspace uses atomic write** — bootstrap file written via `write_bootstrap()` (temp file + flock + rename) to handle concurrent MCP requests safely.

## Simplification Pass

2 improvements:
- Module `activate()` now propagates WP_Error from `initialize_workspace()` instead of silently continuing
- Container extracted duplicate bootstrap-write logic into private `write_bootstrap_file()` helper (DRY)

## File Inventory

| File | Lines | Purpose |
| ---- | ----- | ------- |
| `class-workspace-mu-loader.php` | 177 | MU-plugin install/update/uninstall, crash recovery |
| `class-workspace-module.php` | 42 | Module bootstrap, loads ability classes |
| `class-workspace-addon.php` | 62 | Addon interface for workspace (10 abilities) |
| `class-bricks-workspace-addon.php` | 59 | Addon interface for Bricks workspace (3 abilities) |
| `class-workspace-container.php` | 247 | Extended with initialize/reinitialize (was 151) |
| `class-plugin.php` | +6 lines | Addon registrations added |
| `class-abstract-abilities.php` | +13 lines | Ability labels added |
| `tab-abilities.php` | +3 lines | Category labels added |

## Next Phase Preview

Phase 3: Core Workspace Abilities — implement the 7 generic abilities (generate-function, generate-class, register-hook, call-wp-api, list-workspace, read-workspace-file, delete-workspace-artifact). After this phase, workspace will be functional end-to-end.
