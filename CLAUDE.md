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
- Notion project (live roadmap mirror): see `.notion-sync.json` and the Notion Sync section below

## Notion Sync

This project mirrors its roadmap and status to a Notion **Project** page and **Tasks** database. The repo is canonical; Notion is the mirror. If they disagree on scope/roadmap, the repo wins and Notion is re-synced. Status and human feedback that originate in Notion are the exception — pull them in at session start.

### Canonical IDs

Non-secret IDs live in `.notion-sync.json` at the repo root (committed — IDs only, never tokens). Notion auth and wheelhouse config stay out of the repo (`~/.wheelhouse-local/config.json`, macOS Keychain, env). Read IDs from that file; never hardcode them or match Notion pages by title.

### Session startup (pull Notion → local)

At the start of any planning, build, or wheelhouse session, before doing work:

1. Read `.notion-sync.json` for `projectPageId` and `tasksDataSourceId`.
2. `notion-fetch` the project page — read Overview, Notes, Status.
3. `notion-query-data-sources` on the Tasks data source for this Project where Status ∈ {To Do, Ready, In Progress} — those are the active tasks.
4. If Notion contradicts `docs/plans/` or `docs/phases/`, treat the repo as canonical and run the Plan-change mirror.

Skip the pull only for trivial one-file edits unrelated to the roadmap.

### Plan-change mirror (push local → Notion)

Whenever the roadmap changes — edit `docs/plans/*.md`, add a `docs/phases/*.md`, finish a phase, or change the Release Checklist — mirror it in the same session:

1. `notion-fetch` the project page (get current), then `notion-update-page` to refresh Overview / Key Files / Notes and set Status (`In Progress` while building, `Complete` at release).
2. For each new actionable item, create/update a Tasks-DB row (`notion-create-pages` / `notion-update-page`): Name `(X Hrs) Title`, Project relation = this project, `Repo URL` + `Target Branch` from `.notion-sync.json`. Mark a task `Completed` when its phase summary lands in `docs/phases/`.
3. Bump `lastSyncedCommit` in `.notion-sync.json` to HEAD and commit it with the plan change.

### Wheelhouse handoff (delegating a task to run autonomously)

The wheelhouse automation reads these Task fields — get them right or the task silently won't run:

- **Delegation Status** = `local` or `both` (the live trigger; `web` / `not delegated` are ignored).
- **Repo URL** set, or no job is ever created.
- **Status** ∈ {To Do, Ready, Backlog, Awaiting Feedback, Awaiting Info} (not In Progress / To Review / On Hold / Completed / Cancelled).
- **Due Date** ≤ today, or `/wh:work` never auto-claims it (run on demand with `/wh:run <pageId>`).
- **Auto-plan** checked = hands-off; unchecked = posts a plan to the page and waits for approval.
- Everything else (Estimated Time, Task Type, Engagement Tier, Granularity, Sprint/Project relations) is organizational — the wheelhouse ignores it for control.
- The runner clones into an isolated worktree and **cannot use DevKinsta/WP-CLI** — write acceptance criteria as static checks (`php -l`, `grep`, file contents), not live ability-count runs.

Task page bodies should follow: Objective / Context / Scope (in + out) / Action Points (atomic checklist) / Acceptance Criteria (binary, statically checkable) / Constraints / Verification. The planner also reads this CLAUDE.md, so convention detail can be deferred to "follow CLAUDE.md".

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
