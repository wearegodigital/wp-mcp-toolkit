# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

WP MCP Toolkit is a WordPress plugin providing 35+ MCP (Model Context Protocol) abilities for AI agents. Fork of the official WordPress MCP Adapter — keeps the transport layer, adds content management abilities.

## Architecture

### Core (upstream MCP Adapter — do NOT modify)
- `includes/Plugin.php` — Singleton, dependency check, loads McpAdapter
- `includes/Autoloader.php` — PSR-4 autoloader for WP\MCP namespace
- `includes/Core/` — MCP server, adapter, transport factory
- `includes/Transport/` — STDIO + HTTP transports
- `includes/Handlers/` — JSON-RPC handlers
- `includes/Domain/` — MCP tool/resource/prompt domain objects
- `includes/Abilities/` (uppercase) — 3 meta-abilities (discover, get-info, execute)
- `includes/Cli/` — WP-CLI mcp-adapter serve command

### Toolkit Extensions (our code)
- `includes/class-plugin.php` — Toolkit main class, hooks, module loader
- `includes/class-ability-registrar.php` — Central registrar for core abilities
- `includes/class-abstract-abilities.php` — DRY base class for all ability groups
- `includes/class-template-engine.php` — Template extraction and rendering
- `includes/abilities/` (lowercase) — Content, Block, Schema, Taxonomy, Media abilities
- `includes/Abilities/class-template-abilities.php` — Content Template abilities
- `includes/modules/acf/` — ACF module (auto-loads when `class_exists('ACF')`)
- `includes/modules/gravity-forms/` — GF module (auto-loads when `class_exists('GFAPI')`)
- `includes/modules/yoast/` — Yoast module (auto-loads when `defined('WPSEO_VERSION')`)
- `admin/` — Settings page (3 tabs: Connection, Abilities, Templates)

### Registration Flow
`wp-mcp-toolkit.php` → `class-plugin.php::register_abilities()` → `class-ability-registrar.php` → individual ability classes

### Abilities API Pattern
Categories on `wp_abilities_api_categories_init`, abilities on `wp_abilities_api_init`.

## Development Environment

WordPress runs in DevKinsta Docker. WP-CLI commands:
```bash
docker exec devkinsta_fpm wp <command> --path=/www/kinsta/public/generatormedia/ --allow-root --user=1
```

PHP syntax check:
```bash
docker exec devkinsta_fpm php -l /www/kinsta/public/generatormedia/wp-content/plugins/wp-mcp-toolkit/<file>
```

Verify ability count:
```bash
docker exec devkinsta_fpm wp eval "echo count(wp_get_abilities());" --path=/www/kinsta/public/generatormedia/ --allow-root --user=1
```

## Conventions

- PHP 7.4+, WordPress coding standards
- Text domain: `wp-mcp-toolkit`
- Prefix: `wpmcp_` for options/error codes, `wpmcp/` for core abilities, `wpmcp-acf/` `wpmcp-gf/` `wpmcp-yoast/` for modules
- Return `WP_Error` on failure, never throw
- Error codes: `wpmcp_` prefix (e.g., `wpmcp_not_found`, `wpmcp_missing_fields`)
- Sanitize all inputs: `sanitize_key()` for slugs, `absint()` for IDs, `sanitize_text_field()` for strings, `wp_kses_post()` for HTML
- All `$wpdb` queries must use `$wpdb->prepare()`
- All HTML output escaped with `esc_html()`, `esc_attr()`, `esc_url()`

## Known Gotchas

- `serialize_block_attributes()` encodes `<` as `\u003c` — use `fix_serialized_block_html()` post-processor in base class
- Smart quotes (UTF-8 chars like `'`) require exact byte sequence matching
- Template options stored as `wpmcp_template_{post_type}` — must be cleaned up in `uninstall.php`
- Upstream `Plugin.php` should NOT be modified to avoid merge conflicts with the official MCP Adapter
- PII fields (`ip`, `user_agent`, `source_url`) must be filtered from Gravity Forms entry responses

## Development Process

Full dev process: `docs/plans/dev-process.md`. Summary below.

### Phase Lifecycle

Every development phase follows this sequence:

```
1. PLAN         Read phase objectives. Break into atomic tasks.
2. EXECUTE      Implement tasks. Per-task: implement → php -l → verify ability count.
3. VERIFY       Mandatory before "done": php -l on all changed files + WP-CLI ability count.
4. SIMPLIFY     Run code-simplifier on all changed files. Re-verify after changes.
5. DOCUMENT     Generate phase summary to docs/phases/{phase-name}.md.
6. AUDIT        Log completed task to .omc/audit.db via audit script.
7. REVIEW       Sean reads phase documentation. Feedback before next phase.
8. COMMIT       Atomic commit per phase. Descriptive conventional commit message.
9. NEXT PHASE   Begin from step 1.
```

### Hard Rules — Simplicity First

- Write the simplest code that solves the current problem. Not the hypothetical future problem.
- Prefer 2 lines over 40. No defensive code for cases that don't exist yet.
- No abstraction before the third use. Write the specific thing, refactor when pattern emerges.
- Comments explain WHY, not WHAT. The code explains what.
- No barrel files. No "framework" code. Use WordPress APIs directly.

### File Size Limits

- Ability files: 400 lines max. If approaching, split by domain.
- Infrastructure files: 300 lines max. Split into modules.
- Admin view files: 300 lines max. Extract sections.
- These are hard limits. Split before continuing if exceeded.

### DRY

- One function, one place. If used in multiple files, extract to shared module.
- No copy-paste across files. Extract and import.
- If a pattern appears 3+ times, extract to the abstract base class.

### Before Claiming "Done" (MANDATORY)

Before marking ANY task complete, you MUST:

1. PHP syntax check on all modified files: `docker exec devkinsta_fpm php -l <file>`
2. Verify ability count: `docker exec devkinsta_fpm wp eval "echo count(wp_get_abilities());" --path=/www/kinsta/public/generatormedia/ --allow-root --user=1`
3. If either fails, fix before claiming completion. No exceptions.

### Audit Log

After completing any task, log to `.omc/audit.db` (SQLite):

```bash
docker exec devkinsta_fpm php /www/kinsta/public/generatormedia/wp-content/plugins/wp-mcp-toolkit/.omc/audit.php log \
  --phase "workspace-1" \
  --task "Create workspace container class" \
  --action "implemented" \
  --files "includes/modules/workspace/class-workspace-container.php" \
  --status "completed" \
  --description "Container class with path resolution and writable detection"
```

If a task fails, log with `--status failed` and `--retry-context "what went wrong"`.

## Plans

- Workspace module: `docs/plans/workspace-module.md`
- Dev process: `docs/plans/dev-process.md`

## Release Checklist

Run this checklist before every version bump:

### 1. Version Strings
- [ ] `wp-mcp-toolkit.php` header: `Version: X.Y.Z`
- [ ] `wp-mcp-toolkit.php` constant: `WP_MCP_VERSION`
- [ ] `readme.txt`: `Stable tag: X.Y.Z`

### 2. Changelog & Readme
- [ ] `readme.txt` has a `= X.Y.Z =` changelog entry describing changes
- [ ] `readme.txt` has a matching `= X.Y.Z =` upgrade notice
- [ ] `readme.txt` ability counts match actual registered count (verify with WP-CLI)
- [ ] `readme.txt` tags are 5 or fewer
- [ ] `readme.txt` `Tested up to` uses major version only (e.g., `6.9` not `6.9.1`)

### 3. Security
- [ ] All new write paths sanitize input (`wp_kses_post`, `sanitize_text_field`, `sanitize_key`, `absint`)
- [ ] All new `$wpdb` queries use `$wpdb->prepare()`
- [ ] No PII exposed in API responses (filter `ip`, `user_agent`, `source_url`)
- [ ] New abilities have appropriate permission callbacks

### 4. WP.org Compliance
- [ ] `load_plugin_textdomain()` is called (already in init hook)
- [ ] No admin notices with `dismiss: false`
- [ ] All output escaped with `esc_html()`, `esc_attr()`, `esc_url()`
- [ ] `uninstall.php` cleans up any new options/transients
- [ ] `.distignore` updated if new dev-only files added

### 5. Code Quality
- [ ] Error codes use `wpmcp_` prefix
- [ ] New public methods have `@since X.Y.Z` tags
- [ ] PHP syntax check passes on all modified files
- [ ] Plugin loads without errors (verify ability count with WP-CLI)

### 6. Distribution Build
- [ ] Run `composer install --no-dev --optimize-autoloader`
- [ ] Build zip with `wp dist-archive .` (uses `.distignore`)
- [ ] Verify zip is under 10 MB
- [ ] Test install from zip on clean WordPress

### 7. Git
- [ ] Commit with descriptive message
- [ ] Tag: `git tag -a vX.Y.Z -m "vX.Y.Z"`
- [ ] Push: `git push origin main --tags`
