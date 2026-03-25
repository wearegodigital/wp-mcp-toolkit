# Phases 3-5: Core Abilities, Gutenberg Blocks, and Bricks Elements

**Date**: 2026-03-25
**Line count**: 2,248 lines cumulative (3 ability classes + 5 templates added)

## Goal

Implement all 13 workspace abilities: 7 core (generate-function, generate-class, register-hook, call-wp-api, list, read, delete), 3 Gutenberg block scaffolding (scaffold-block, update-block, list-blocks), and 3 Bricks element scaffolding (scaffold-bricks-element, update-bricks-element, list-bricks-elements).

## What Was Done

### Phase 3: Core Workspace Abilities (372 lines)
- **Task 3.1-3.6**: Full workspace abilities class with 7 ability definitions and 7 execute methods
- Key features: auto-init workspace on first write, `wpmcp_workspace_` function prefix, `WPMCP_Workspace_` class prefix
- `call-wp-api` security: rejects `::` and `->`, comprehensive blocklist in all modes, production allowlist
- Helper extraction: `ensure_workspace()`, `save_artifact()`, `resolve_mode()` avoid repetition
- File: `class-workspace-abilities.php`

### Phase 4: Gutenberg Block Module (351 lines + 3 templates)
- **Task 4.1**: Block templates — `block-json.tpl`, `block-render.php.tpl`, `block-style.css.tpl`
- **Task 4.2-4.3**: Block abilities class with scaffold, update, and list callbacks
- Scaffold creates proper `blocks/{name}/` structure with block.json, render.php, style.css
- Update supports partial file updates (render, css, or metadata independently)
- File: `class-workspace-blocks-abilities.php`

### Phase 5: Bricks Builder Module (359 lines + 2 templates)
- **Task 5.1**: Bricks templates — `bricks-element.php.tpl`, `bricks-element-style.css.tpl`
- **Task 5.2-5.3**: Bricks abilities class with scaffold, update, and list callbacks
- Scaffold generates PHP class extending `\Bricks\Element` with controls and render method
- Template has `class_exists('\Bricks\Element')` guard at top
- Kebab-to-PascalCase conversion for class names (`hero-section` → `WPMCP_Workspace_Bricks_Hero_Section`)
- File: `class-workspace-bricks-abilities.php`

## Key Decisions

- **Single file per ability group** — each group (core, blocks, bricks) is one class under 400 lines. No splitting needed.
- **Helper closures in get_abilities()** — `$s()` and `$o()` closures build input/output schema boilerplate to keep definitions compact.
- **Blocklist over allowlist in staging** — staging mode blocks known-dangerous functions but allows everything else. Production uses allowlist.
- **Atomic bootstrap regeneration** — every write ability calls `reinitialize()` which uses atomic write (temp + flock + rename).

## Simplification Pass

### Phase 3 (core abilities):
- Simplifier reviewed, made improvements to mode resolution and error handling patterns

### Phase 4+5 (blocks + bricks):
- **Bug fixed**: Bricks `$s()` closure called with wrong argument count — spurious 'object' string as first arg caused required fields to be silently discarded
- **Bug fixed**: Bricks `save_artifact()` swallowed `update_artifact()` errors
- Bricks file trimmed from 403 → 359 lines (was over limit)
- Blocks gained `save_artifact()` helper for consistency
- Removed redundant `ensure_workspace()` from update paths and unused normalize in list methods

## File Inventory

| File | Lines | Purpose |
| ---- | ----- | ------- |
| `class-workspace-abilities.php` | 372 | 7 core abilities: generate-function/class, register-hook, call-wp-api, list/read/delete |
| `class-workspace-blocks-abilities.php` | 351 | 3 block abilities: scaffold, update, list |
| `class-workspace-bricks-abilities.php` | 359 | 3 Bricks abilities: scaffold, update, list |
| `templates/block-json.tpl` | 16 | block.json template |
| `templates/block-render.php.tpl` | 13 | Block render.php template |
| `templates/block-style.css.tpl` | 7 | Block style.css template |
| `templates/bricks-element.php.tpl` | 40 | Bricks element PHP class template |
| `templates/bricks-element-style.css.tpl` | 7 | Bricks element CSS template |

## Next Phase Preview

Phase 6: Mode System — formalize staging/production mode detection and allowlist management in the validator class. Phase 7: Admin UI — workspace tab with mode selector, allowlist editor, artifact browser, crash recovery.
