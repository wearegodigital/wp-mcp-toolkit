# Phase 1: Foundation — Workspace Container and Infrastructure

**Date**: 2026-03-25
**Line count**: 831 lines (6 new files)

## Goal

Build the low-level filesystem helpers, path validation, PHP linting, manifest management, and template rendering that all subsequent workspace phases build on. No abilities yet — just the plumbing.

## What Was Done

- **Task 1.1**: Workspace container class — path resolution with plugin dir → uploads fallback, writable detection, template renderer using `str_replace` with `{{TOKEN}}` placeholders — file: `class-workspace-container.php`
- **Task 1.2**: Path validator — directory traversal prevention, filename pattern enforcement, symlink escape detection, PHP syntax validation via `php -l`, function/class/hook name validation — file: `class-workspace-validator.php`
- **Task 1.3**: File writer — safe write with `LOCK_EX`, atomic bootstrap write (temp file + flock + rename + opcache invalidate), read/delete/checksum helpers — file: `class-workspace-file-writer.php`
- **Task 1.4**: Manifest manager — CRUD for `manifest.json` artifact tracking with file locking, duplicate detection, type filtering — file: `class-workspace-manifest.php`
- **Task 1.5**: Workspace plugin bootstrap template — glob-loads functions/classes/hooks, registers blocks via `register_block_type()`, Bricks elements guarded by `class_exists('\Bricks\Element')` — file: `templates/workspace-plugin.php.tpl`
- **Task 1.6**: MU-plugin loader template — crash recovery with `.loading`/`.crashed` markers, admin notice on crash, `do_action` hook for extensibility — file: `templates/mu-loader.php.tpl`
- **Task 1.7**: Verification — all files pass `php -l`, template placeholders documented

## Key Decisions

- **Static classes over singletons** — Container, Validator, FileWriter, and Manifest are all static utility classes. No instantiation needed, no state to manage across requests (except Container's cached active dir). Matches the simplicity-first principle.
- **Template rendering via str_replace** — Chose simple `{{TOKEN}}` replacement over extract+include or eval. Explicit, no security concerns, easy to debug.
- **Atomic bootstrap write** — Belt-and-suspenders approach (temp file + flock + rename + opcache invalidate) for the workspace bootstrap file. Handles POSIX, NFS, and Windows. Regular artifact files use simpler LOCK_EX.
- **Crash recovery in MU-loader** — `.loading` marker created before require, deleted after. If it persists, next boot renames to `.crashed` and skips loading. Admin notice points to workspace settings tab.

## Simplification Pass

Code simplifier reviewed all 6 files. 6 minor improvements across 3 files:
- Validator: extracted shared `validate_php_identifier()` helper (DRY), removed redundant empty check
- File writer: simplified parent directory calculation, added missing phpcs:ignore
- Manifest: removed redundant `array_values` after splice, removed impossible-case guard

## File Inventory

| File | Lines | Purpose |
| ---- | ----- | ------- |
| `class-workspace-container.php` | 151 | Path resolution, writable detection, template rendering |
| `class-workspace-validator.php` | 171 | Path, syntax, and name validation |
| `class-workspace-file-writer.php` | 170 | Safe file write, atomic bootstrap write, read/delete |
| `class-workspace-manifest.php` | 241 | Artifact tracking CRUD via manifest.json |
| `templates/workspace-plugin.php.tpl` | 47 | Generated workspace plugin bootstrap |
| `templates/mu-loader.php.tpl` | 51 | MU-plugin loader with crash recovery |

## Next Phase Preview

Phase 2: Module Wiring & MU-Plugin Loader — Create the addon classes, wire into the main plugin registry, add ability/category labels, and build the MU-plugin installer. After this phase, workspace abilities can be tested via MCP.
