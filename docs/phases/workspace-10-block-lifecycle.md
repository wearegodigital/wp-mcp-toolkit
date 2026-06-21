# Phase 10: Block Lifecycle — ACF Blocks, Insertion, Smart Scaffolding

**Date**: 2026-06-21
**Commit range**: `3190f2c..da289ff` (10 commits)
**Version**: 1.0.1

## Goal

Grow the Workspace module from "scaffold a single block" into a full block lifecycle: choose a build method, scaffold a complete editor-ready block (vanilla or ACF-powered), insert blocks/elements into real content, and harden generated-code security, Bricks registration, and crash recovery.

## What Was Done

### New abilities (4 net new)

- **`wpmcp-workspace/scaffold-acf-block`** — scaffolds an ACF-powered Gutenberg block (`register.php` via `acf_register_block_type`, `render.php`, field storage as a PHP field group or `acf-json`, `style.css`). Maps simplified field defs to ACF configs; auto-generates render from fields when `render_php` is empty. Permission `manage_options`. **Registered only when `class_exists('ACF')`.** `@since 2.2.0`
- **`wpmcp-workspace/scaffold-block-smart`** — dispatcher that resolves the build method from input or the `wpmcp_block_method` option (`recommended|vanilla|acf|agent-decides`), forces vanilla when ACF is absent, and uses a field-complexity heuristic (`fields_suggest_acf()`) to recommend or build. `@since 2.2.0`
- **`wpmcp-workspace/insert-block`** — inserts a Gutenberg block into a post's content at append/prepend/index; vanilla blocks via raw attributes, `acf/*` blocks via `{name,data,mode}`; parses existing blocks, `serialize_blocks` + `fix_serialized_block_html`, `wp_update_post`. Permission `edit_post`. `@since 2.2.0`
- **`wpmcp-bricks/insert-bricks-element`** — inserts a scaffolded workspace Bricks element into a page's `_bricks_page_content_2` meta, with `parent_id` nesting and `after_element_id` positioning. Permission `edit_post`. `@since 2.0.0`

Ability counts: Workspace addon **10 → 13** (12 without ACF, since `scaffold-acf-block` is ACF-gated); Bricks addon **3 → 4**. The duplicate `wpmcp/get-page-tree` registration was removed from `class-content-abilities.php` (the canonical one survives in `class-schema-abilities.php`).

Always-on toolkit total is now **33** abilities (no optional plugins), **41** with ACF, and up to **53** with ACF + Gravity Forms + Yoast + Bricks all active (excludes the 3 upstream MCP Adapter meta-abilities). Confirm the exact live number with `wp eval "echo count(wp_get_abilities());"`.

### Build-method system

New `wpmcp_block_method` option (`recommended|vanilla|acf|agent-decides`) and a dedicated admin **Blocks** tab (`admin/views/tab-blocks.php`), with ACF options hidden when ACF is inactive. Sanitized via `sanitize_block_method_setting` (default `recommended`); cleaned up in `uninstall.php`. New `wpmcp-workspace-acf-blocks` category; category descriptions added.

### Richer scaffolding

Vanilla `scaffold-block` now emits **5 files**, adding `editor.js` (`ServerSideRender` + auto-built `InspectorControls` from the attribute schema) and `editor.asset.php`. Pure-PHP render is detected by statement pattern (not HTML-tag presence) and wrapped with `get_block_wrapper_attributes()`. Bricks scaffolding emits complete controls (repeater nested fields, min/max/placeholder/units); the `update-bricks-element` render regex was updated to match.

### Generated-code security

New `WP_MCP_Toolkit_Workspace_Validator::scan_code_for_blocked_content()` (blocked function calls, superglobals, backtick operator) and `scan_css_for_blocked_content()` (`<?php` / `<script>`) now run on **every** generated `render_php` / CSS / function body / class method body across workspace, blocks, ACF-blocks, and Bricks abilities. `register-hook` now validates its callback against the blocklist and requires an existing function or a workspace manifest artifact. (Closes the gap tracked as Notion Order 20.)

### Robustness / DRY

- New `trait-workspace-helpers.php` (`tpl()`, `ensure_workspace()`, `save_artifact()`) consumed by all workspace ability classes; `WP_MCP_Toolkit_Abstract_Abilities::sanitize_recursive()` (`@since 2.3.0`) for nested input sanitization.
- `WP_MCP_Toolkit_Workspace_Module::load_classes()` glob-loads the trait + all `class-workspace-*.php` from one place (called by `init`/`activate`/`deactivate` and the Bricks addon), fixing a fatal when the new insertion class was missing.
- `.ready` crash marker written last in workspace init; Bricks registration deferred to `after_setup_theme` and wrapped in try/catch to survive WooCommerce incompatibility; filename validator now allows dots (e.g. `editor.asset.php`).

## Known follow-ups (tracked in Notion)

- `sanitize_recursive()` runs `wp_kses_post()` on every string, which can corrupt typed/scalar block-insertion attributes (Order 45).
- `class-workspace-acf-blocks-abilities.php` is 411 lines (over the 400 cap); the `wpmcp-workspace-acf-blocks` category is registered unconditionally though its ability is ACF-gated (Order 47).
- No toolkit tests cover the new scanner or abilities (Order 50).
- `.mcp.json` hardcodes a customer site path and isn't excluded from VCS/dist (Order 15).

## File Inventory

**New:** `class-workspace-acf-blocks-abilities.php`, `class-workspace-smart-block-ability.php`, `class-workspace-block-insertion-abilities.php`, `class-workspace-bricks-insertion-abilities.php`, `trait-workspace-helpers.php`, templates `acf-block-fields.php.tpl` / `acf-block-render.php.tpl` / `block-editor.js.tpl`, `admin/views/tab-blocks.php`, `.mcp.json`.

**Modified (key):** `wp-mcp-toolkit.php` (v1.0.1), `class-abstract-abilities.php`, `class-workspace-validator.php`, `class-workspace-blocks-abilities.php`, `class-workspace-bricks-abilities.php`, `class-workspace-module.php`, `class-workspace-container.php`, `class-workspace-addon.php`, `class-bricks-workspace-addon.php`, `admin/class-admin-page.php`, `admin/views/settings-page.php`, `Abilities/class-content-abilities.php`, `uninstall.php`.
