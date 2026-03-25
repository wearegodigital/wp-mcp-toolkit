# WP MCP Toolkit — Workspace Module Implementation Plan

## Summary

The Workspace module is a dev-mode extension for WP MCP Toolkit that lets AI agents write structured PHP code inside WordPress via MCP abilities. It creates a self-contained plugin container (`wpmcp-workspace/`) where generated artifacts live, loaded via an MU-plugin with crash recovery. The module provides 13 abilities across three groups: core workspace operations (generate functions, classes, hooks; call WP APIs; manage artifacts), Gutenberg block scaffolding, and Bricks Builder element scaffolding. Two operating modes — staging (open) and production (allowlisted) — control what agents can do. A new admin tab provides mode selection, allowlist editing, artifact browsing, and crash recovery controls.

---

## Requirements

1. Workspace plugin container at `wp-content/plugins/wpmcp-workspace/` with autoloader, `manifest.json`, and uploads fallback
2. MU-plugin loader at `wp-content/mu-plugins/wpmcp-workspace-loader.php` with `.loading`/`.crashed` crash recovery markers
3. Two operating modes: staging (unrestricted) and production (allowlisted), with environment-based defaults
4. 7 core workspace abilities: generate-function, generate-class, register-hook, call-wp-api, list-workspace, read-workspace-file, delete-workspace-artifact
5. 3 Gutenberg block abilities: scaffold-block, update-block, list-workspace-blocks
6. 3 Bricks Builder abilities: scaffold-bricks-element, update-bricks-element, list-bricks-elements
7. Admin UI: "Workspace" tab with mode selector, allowlist editor, artifact browser, crash recovery status
8. PHP syntax validation via `php -l` before writing any generated PHP file
9. Path validation ensuring all writes stay within the workspace directory
10. Manifest tracking of all generated artifacts with name, type, created_at, updated_at, checksum, source_ability
11. File locking for manifest reads/writes and artifact file writes
12. Full integration with existing addon registry, admin tabs, ability labels, category labels, and uninstall cleanup
13. PHP 8.0+ minimum for workspace module code (rest of toolkit remains PHP 7.4+)
14. Template rendering via `str_replace` with `{{VARIABLE_NAME}}` placeholder tokens (uppercase, double-curly-brace)
15. Atomic file writes for bootstrap regeneration using temp file + flock + rename pattern
16. Bricks Builder as a separate conditional addon class (like ACF, GF, Yoast)

---

## Acceptance Criteria

| # | Criterion | Verification |
|---|-----------|-------------|
| AC-1 | Workspace addon appears in Add-ons tab when module is active | Load admin page, check Add-ons tab |
| AC-2 | 13 workspace abilities register and appear in Abilities tab | `wp eval "echo count(wp_get_abilities());"` shows +13 |
| AC-3 | `generate-function` writes a valid PHP file to workspace and updates manifest | Call ability, check file exists + `php -l` passes + manifest updated |
| AC-4 | `generate-class` writes a valid PHP class file with namespace | Call ability, verify class file + autoloader entry |
| AC-5 | `register-hook` adds action/filter to workspace loader | Call ability, verify hook fires on next request |
| AC-6 | `call-wp-api` rejects non-allowlisted functions in production mode | Call with non-allowlisted function, verify WP_Error returned |
| AC-7 | `call-wp-api` runs any function in staging mode | Call with arbitrary function in staging, verify success |
| AC-8 | `call-wp-api` rejects function names containing `::` or `->` in ALL modes | Call with `SomeClass::method`, verify WP_Error returned |
| AC-9 | `scaffold-block` creates block.json + render.php + style.css | Call ability, verify 3 files created + block registers |
| AC-10 | `scaffold-bricks-element` creates element.php extending \Bricks\Element | Call ability, verify file + class structure |
| AC-11 | Bricks abilities only appear when `defined('BRICKS_VERSION')` | Disable Bricks, verify abilities gone |
| AC-12 | MU-plugin loader loads workspace plugin and recovers from crashes | Simulate crash (create .crashed), verify recovery UI shows |
| AC-13 | Admin "Workspace" tab shows mode selector, allowlist, artifact browser | Load tab, verify all 3 sections render |
| AC-14 | `delete-workspace-artifact` removes file and updates manifest | Call ability, verify file gone + manifest entry removed |
| AC-15 | Path traversal attempts return WP_Error | Call generate-function with `../../etc/passwd` path, verify rejection |
| AC-16 | Uninstall cleans up all workspace options | Run uninstall, verify `wpmcp_workspace_%` options deleted |
| AC-17 | PHP syntax errors in generated code are caught before writing | Call generate-function with invalid PHP, verify WP_Error + no file written |
| AC-18 | Workspace abilities do NOT register on PHP < 8.0 | Test on PHP 7.4 environment, verify 0 workspace abilities |
| AC-19 | Workspace abilities DO register on PHP 8.0+ | Test on PHP 8.0+ environment, verify 13 abilities |
| AC-20 | Bricks element loading is guarded by `class_exists('\Bricks\Element')` | Deactivate Bricks theme, verify no fatal errors from workspace bootstrap |

---

## Architecture

### Prefix Conventions

- **`wpmcp-workspace/`** — prefix for ALL core + Gutenberg block abilities (10 abilities)
- **`wpmcp-bricks/`** — prefix for Bricks abilities only (3 abilities, separate conditional addon)
- **`wpmcp-workspace`** — category for core workspace abilities (7 abilities)
- **`wpmcp-workspace-blocks`** — category for Gutenberg block abilities (3 abilities)
- **`wpmcp-bricks`** — category for Bricks abilities (3 abilities, registered by Bricks addon)

### New Files to Create

```
includes/modules/workspace/
  class-workspace-addon.php              # implements WP_MCP_Toolkit_Addon (10 abilities: 7 core + 3 blocks)
  class-workspace-module.php             # static init(), is_active()
  class-workspace-abilities.php          # 7 core abilities, extends Abstract_Abilities
  class-workspace-blocks-abilities.php   # 3 Gutenberg abilities, extends Abstract_Abilities
  class-workspace-container.php          # plugin container management (paths, writable detection, template rendering)
  class-workspace-manifest.php           # manifest.json CRUD with file locking
  class-workspace-validator.php          # PHP linting, path validation, mode enforcement
  class-workspace-file-writer.php        # safe file write helper (mkdir, write, chmod, lock, atomic bootstrap writes)
  class-workspace-mu-loader.php          # MU-plugin installer/updater
  templates/
    workspace-plugin.php.tpl             # template for wpmcp-workspace.php plugin header
    mu-loader.php.tpl                    # template for MU-plugin loader file
    block-json.tpl                       # template for block.json
    block-render.php.tpl                 # template for block render.php
    block-style.css.tpl                  # template for block style.css

includes/modules/bricks-workspace/
  class-bricks-workspace-addon.php       # implements WP_MCP_Toolkit_Addon (3 Bricks abilities)
  class-workspace-bricks-abilities.php   # 3 Bricks abilities, extends Abstract_Abilities
  templates/
    bricks-element.php.tpl              # template for Bricks element class

admin/views/
  tab-workspace.php                      # Workspace admin tab
```

### Files to Modify

```
includes/class-plugin.php               # add workspace + bricks-workspace addons to load_addon_registry()
includes/class-abstract-abilities.php    # add 13 ability labels to $ability_labels
admin/views/settings-page.php           # add 'workspace' to $valid_tabs + nav tab link
admin/views/tab-abilities.php           # add workspace + bricks category labels
admin/class-admin-page.php             # register workspace AJAX handlers + settings
admin/css/admin.css                     # workspace tab styles
admin/js/admin.js                       # workspace AJAX handlers
uninstall.php                           # clean up wpmcp_workspace_% options
```

### Generated at Runtime (NOT in repo)

```
wp-content/plugins/wpmcp-workspace/
  wpmcp-workspace.php                  # generated plugin bootstrap
  manifest.json                        # artifact tracking
  functions/                           # generated functions
  classes/                             # generated classes
  hooks/                               # generated hook registrations
  blocks/{name}/                       # generated Gutenberg blocks
  bricks/{name}/                       # generated Bricks elements
wp-content/mu-plugins/
  wpmcp-workspace-loader.php           # MU-plugin loader
```

---

## Phase 1: Foundation — Workspace Container and Infrastructure

**Goal:** Build the low-level file system helpers, path validation, PHP linting, manifest management, and template rendering. No abilities yet — just the plumbing.

### Task 1.1: Create workspace container class

**File:** `includes/modules/workspace/class-workspace-container.php`

**What to implement:**
- Class `WP_MCP_Toolkit_Workspace_Container` with static methods
- `get_plugin_dir(): string` — returns `WP_CONTENT_DIR . '/plugins/wpmcp-workspace/'`
- `get_uploads_dir(): string` — returns `wp_upload_dir()['basedir'] . '/wpmcp-workspace/'`
- `get_active_dir(): string` — tries plugin dir first, falls back to uploads dir
- `is_writable(): bool` — tests `wp_mkdir_p()` + `wp_is_writable()` on active dir. If `wp_mkdir_p()` returns false, return false (do NOT throw — caller handles error).
- `ensure_dir( string $subdir ): string|WP_Error` — creates subdirectory within workspace, returns absolute path. If `wp_mkdir_p()` returns false, return `new WP_Error('wpmcp_mkdir_failed', 'Could not create directory: ' . $subdir)`.
- `get_relative_path( string $absolute ): string` — strips workspace root for display/manifest
- `render_template( string $template_path, array $vars ): string` — reads template file, applies `str_replace` with `{{VARIABLE_NAME}}` placeholder tokens (uppercase keys, double-curly-brace delimiters). Example: `$vars = ['PLUGIN_DIR' => '/path/to/dir']` replaces `{{PLUGIN_DIR}}` in template. Returns rendered string.

**Verify:** `docker exec devkinsta_fpm php -l /www/kinsta/public/generatormedia/wp-content/plugins/wp-mcp-toolkit/includes/modules/workspace/class-workspace-container.php`

### Task 1.2: Create path validator

**File:** `includes/modules/workspace/class-workspace-validator.php`

**What to implement:**
- Class `WP_MCP_Toolkit_Workspace_Validator`
- `validate_path( string $path ): true|WP_Error` — checks:
  - No `..` components (directory traversal)
  - `realpath()` resolves within workspace dir (after creation)
  - No symlink escape
  - Filename matches `[a-zA-Z0-9_-]+\.(php|css|json|js|txt)` pattern
- `validate_php_syntax( string $code ): true|WP_Error` — writes to temp file, runs `php -l` via a process call, returns error message or true; graceful degradation if process execution is unavailable (return true with warning in manifest)
- `validate_function_name( string $name ): true|WP_Error` — valid PHP function name, not reserved
- `validate_class_name( string $name ): true|WP_Error` — valid PHP class name, not reserved
- `validate_hook_name( string $name ): true|WP_Error` — valid WordPress hook name

**Verify:** PHP syntax check on file

### Task 1.3: Create file writer helper

**File:** `includes/modules/workspace/class-workspace-file-writer.php`

**What to implement:**
- Class `WP_MCP_Toolkit_Workspace_File_Writer`
- `write_file( string $relative_path, string $content ): string|WP_Error` — standard file write pipeline:
  1. Call `Workspace_Validator::validate_path()` on the target
  2. Call `Workspace_Container::ensure_dir()` for parent directory (if returns WP_Error, propagate it)
  3. Write content with `LOCK_EX` flag
  4. Set permissions to `0644`
  5. Return absolute path on success, WP_Error on failure
- `write_bootstrap( string $absolute_path, string $content ): true|WP_Error` — **atomic write for bootstrap file specifically** using belt-and-suspenders pattern:
  1. Write to `$absolute_path . '.tmp.' . getmypid()` using `file_put_contents($tmp, $content, LOCK_EX)`
  2. `fopen($absolute_path, 'c')` to get file handle
  3. `flock($handle, LOCK_EX)` for exclusive lock
  4. `rename($tmp, $absolute_path)` to atomically replace
  5. `flock($handle, LOCK_UN)` to release lock
  6. `fclose($handle)`
  7. `wp_opcache_invalidate($absolute_path)` after rename
  8. Return true on success, WP_Error on any failure (clean up temp file on failure)
- `read_file( string $relative_path ): string|WP_Error` — reads file within workspace with path validation
- `delete_file( string $relative_path ): true|WP_Error` — deletes file within workspace with path validation
- `file_checksum( string $relative_path ): string` — returns `md5_file()` hash

**Verify:** PHP syntax check on file

### Task 1.4: Create manifest manager

**File:** `includes/modules/workspace/class-workspace-manifest.php`

**What to implement:**
- Class `WP_MCP_Toolkit_Workspace_Manifest`
- `read(): array` — reads `manifest.json` from workspace dir, returns empty `['artifacts' => [], 'version' => 1]` if missing
- `write( array $manifest ): true|WP_Error` — writes manifest with `LOCK_EX`, validates JSON before write
- `add_artifact( array $entry ): true|WP_Error` — adds entry with: `name`, `type` (function|class|hook|block|bricks-element), `file` (relative path), `created_at` (ISO 8601), `updated_at`, `checksum` (md5), `source_ability` (ability slug that created it)
- `update_artifact( string $name, array $updates ): true|WP_Error` — updates existing entry
- `remove_artifact( string $name ): true|WP_Error` — removes entry
- `get_artifact( string $name ): array|null` — finds by name
- `list_artifacts( string $type = '' ): array` — returns all or filtered by type

**Verify:** PHP syntax check on file

### Task 1.5: Create workspace plugin bootstrap template

**File:** `includes/modules/workspace/templates/workspace-plugin.php.tpl`

**What to implement:**
- Standard WordPress plugin header (Plugin Name: WP MCP Workspace, Description, Version: 1.0.0, no Author to avoid confusion)
- `defined( 'ABSPATH' ) || exit();`
- Define `WPMCP_WORKSPACE_DIR` constant as `{{WORKSPACE_DIR}}`
- Define `WPMCP_WORKSPACE_VERSION` constant as `{{WORKSPACE_VERSION}}`
- Glob-load all PHP files from `functions/`, `classes/`, `hooks/` subdirectories
- Register all blocks from `blocks/*/block.json` via `register_block_type()`
- Register all Bricks elements from `bricks/*/element.php` — MUST be wrapped in `class_exists( '\Bricks\Element' )` check to prevent fatal errors when Bricks is deactivated
- Keep it minimal — this file is generated and overwritten by the toolkit

**Placeholder tokens:** `{{WORKSPACE_DIR}}`, `{{WORKSPACE_VERSION}}`

**CRITICAL:** The Bricks element loading section MUST use `if ( class_exists( '\Bricks\Element' ) )` guard. Verify this guard is present in the template. If Bricks is deactivated after elements were scaffolded, the workspace must still load without fatal errors.

**Verify:** Manual review of template content; verify `class_exists` guard is present

### Task 1.6: Create MU-plugin loader template

**File:** `includes/modules/workspace/templates/mu-loader.php.tpl`

**What to implement:**
- Standard MU-plugin header comment
- `defined( 'ABSPATH' ) || exit();`
- Check if `wpmcp-workspace.php` exists at `{{WORKSPACE_PLUGIN_PATH}}`
- `.loading` marker: create before require, delete after
- `.crashed` detection: if `.loading` exists on boot, rename to `.crashed`, skip loading, fire `do_action('wpmcp_workspace_crash_detected')`
- Recovery: if `.crashed` exists, do not load workspace plugin (admin notice registered instead)
- Recovery reset: deleting `.crashed` file re-enables loading on next request

**Placeholder tokens:** `{{WORKSPACE_PLUGIN_PATH}}`

**Verify:** Manual review of template content

### Task 1.7: Create template rendering tests

**Goal:** Verify that `Workspace_Container::render_template()` correctly replaces all placeholder tokens.

**Verify for each template:**
- All `{{VARIABLE_NAME}}` tokens are replaced (no leftover `{{` in output)
- Document the exact placeholder tokens each template uses (listed in Task 1.5 and 1.6 above)

---

## Phase 2: Module Wiring, Addon Classes, and MU-Plugin Loader

**Goal:** Wire the workspace into the main plugin so abilities can register and be tested via MCP. Also build the MU-plugin loader management.

### Task 2.1: Create MU-plugin installer class

**File:** `includes/modules/workspace/class-workspace-mu-loader.php`

**What to implement:**
- Class `WP_MCP_Toolkit_Workspace_MU_Loader`
- `get_loader_path(): string` — returns `WPMU_PLUGIN_DIR . '/wpmcp-workspace-loader.php'`
- `is_installed(): bool` — checks if loader file exists
- `needs_update(): bool` — compares checksum of installed loader vs rendered template
- `install(): true|WP_Error` — renders template via `Workspace_Container::render_template()`, writes to MU-plugins dir, verifies with `php -l`
- `uninstall(): true|WP_Error` — removes loader file from MU-plugins dir
- `get_crash_status(): array` — returns `['crashed' => bool, 'loading' => bool, 'crash_file' => string]`
- `recover_from_crash(): true|WP_Error` — deletes `.crashed` marker, allows next boot to retry

**Verify:** PHP syntax check on file

### Task 2.2: Add workspace initialization to container class

**File:** `includes/modules/workspace/class-workspace-container.php` (extend existing from Task 1.1)

**What to implement:**
- `initialize_workspace(): true|WP_Error` — called on first use:
  1. Determine active directory (plugin dir or uploads fallback)
  2. Create directory structure: `functions/`, `classes/`, `hooks/`, `blocks/`, `bricks/`
  3. Render `workspace-plugin.php.tpl` via `render_template()` and write as `wpmcp-workspace.php` using `Workspace_File_Writer::write_bootstrap()` (atomic write)
  4. Write initial empty `manifest.json`
  5. Install MU-plugin loader via `Workspace_MU_Loader::install()`
- `is_initialized(): bool` — checks if `wpmcp-workspace.php` exists in active dir
- `reinitialize(): true|WP_Error` — regenerates `wpmcp-workspace.php` from template using `write_bootstrap()` (preserves artifacts)

**Verify:** PHP syntax check on file

### Task 2.3: Create workspace module bootstrap

**File:** `includes/modules/workspace/class-workspace-module.php`

**What to implement:**
- Class `WP_MCP_Toolkit_Workspace_Module`
- `is_active(): bool` — returns `true` unless mode is `'disabled'`
- `init(): void` — if `! is_active()` return; then:
  1. Load disabled abilities: `get_option( 'wpmcp_disabled_abilities', array() )`
  2. Require and register core abilities: `class-workspace-abilities.php`
  3. Require and register block abilities: `class-workspace-blocks-abilities.php`
- `activate(): void` — called when workspace mode changes from 'disabled' to staging/production:
  1. Call `Workspace_Container::initialize_workspace()`
  2. Update `wpmcp_workspace_mode` option
- `deactivate(): void` — called when mode set to 'disabled':
  1. Call `Workspace_MU_Loader::uninstall()`
  2. Do NOT delete workspace artifacts (user data)

**Note:** Bricks abilities are NOT loaded here. They are loaded by the separate Bricks Workspace addon (see Task 2.6).

**Verify:** PHP syntax check on file

### Task 2.4: Create workspace addon class

**File:** `includes/modules/workspace/class-workspace-addon.php`

**What to implement:**
- Class `WP_MCP_Toolkit_Workspace_Addon implements WP_MCP_Toolkit_Addon`
- `get_slug(): string` — returns `'workspace'`
- `get_name(): string` — returns `'Workspace'`
- `get_description(): string` — returns `'Dev-mode extension that lets AI agents write structured PHP code and Gutenberg blocks inside WordPress.'`
- `get_icon(): string` — returns `'dashicons-editor-code'`
- `is_available(): bool` — returns `version_compare( PHP_VERSION, '8.0', '>=' )` — **CRITICAL: PHP 8.0+ enforcement**
- `is_premium(): bool` — returns `false`
- `is_licensed(): bool` — returns `true`
- `get_version(): string` — returns `WP_MCP_VERSION`
- `get_ability_count(): int` — returns 10 (7 core + 3 blocks)
- `register_categories(): void` — registers categories:
  - `wpmcp-workspace` with label `__( 'Workspace', 'wp-mcp-toolkit' )`
  - `wpmcp-workspace-blocks` with label `__( 'Workspace Blocks', 'wp-mcp-toolkit' )`
- `register_abilities( array $disabled ): void` — calls `Workspace_Module::init()`

**Verify:** PHP syntax check + test on PHP 7.4 (abilities should NOT register) + test on PHP 8.0+ (should register)

### Task 2.5: Register workspace addon in main plugin

**File:** `includes/class-plugin.php`

**What to modify:**
- In `load_addon_registry()`, after the Yoast registration and before the `do_action` hook, add:
  ```php
  require_once __DIR__ . '/modules/workspace/class-workspace-addon.php';
  $registry->register( new WP_MCP_Toolkit_Workspace_Addon() );
  ```

**Verify:** Load plugin, verify workspace addon appears in registry; check ability count increases by 10 on PHP 8.0+; verify 0 increase on PHP 7.4

### Task 2.6: Create Bricks workspace addon class (separate addon)

**File:** `includes/modules/bricks-workspace/class-bricks-workspace-addon.php`

**What to implement:**
- Class `WP_MCP_Toolkit_Bricks_Workspace_Addon implements WP_MCP_Toolkit_Addon`
- `get_slug(): string` — returns `'bricks-workspace'`
- `get_name(): string` — returns `'Bricks Workspace'`
- `get_description(): string` — returns `'Bricks Builder element scaffolding for the Workspace module.'`
- `get_icon(): string` — returns `'dashicons-layout'`
- `is_available(): bool` — returns `version_compare( PHP_VERSION, '8.0', '>=' ) && defined( 'BRICKS_VERSION' ) && class_exists( '\Bricks\Element' )`
- `is_premium(): bool` — returns `false`
- `is_licensed(): bool` — returns `true`
- `get_version(): string` — returns `WP_MCP_VERSION`
- `get_ability_count(): int` — returns 3
- `register_categories(): void` — registers `wpmcp-bricks` with label `__( 'Bricks Workspace', 'wp-mcp-toolkit' )`
- `register_abilities( array $disabled ): void` — requires and registers `class-workspace-bricks-abilities.php`

**Verify:** PHP syntax check + verify abilities only appear when Bricks is active AND PHP 8.0+

### Task 2.7: Register Bricks workspace addon in main plugin

**File:** `includes/class-plugin.php`

**What to modify:**
- In `load_addon_registry()`, after the workspace addon registration, add:
  ```php
  require_once __DIR__ . '/modules/bricks-workspace/class-bricks-workspace-addon.php';
  $registry->register( new WP_MCP_Toolkit_Bricks_Workspace_Addon() );
  ```

**Verify:** Load plugin with Bricks active, verify bricks-workspace addon appears; verify +3 abilities

### Task 2.8: Add ability labels to abstract abilities class

**File:** `includes/class-abstract-abilities.php`

**What to modify:**
- Add to `$ability_labels` array after the Templates entries:
  ```
  // Workspace.
  'wpmcp-workspace/generate-function'          => 'Generate Function',
  'wpmcp-workspace/generate-class'             => 'Generate Class',
  'wpmcp-workspace/register-hook'              => 'Register Hook',
  'wpmcp-workspace/call-wp-api'                => 'Call WP API',
  'wpmcp-workspace/list-workspace'             => 'List Workspace',
  'wpmcp-workspace/read-workspace-file'        => 'Read Workspace File',
  'wpmcp-workspace/delete-workspace-artifact'  => 'Delete Artifact',
  // Workspace — Blocks.
  'wpmcp-workspace/scaffold-block'             => 'Scaffold Block',
  'wpmcp-workspace/update-block'               => 'Update Block',
  'wpmcp-workspace/list-workspace-blocks'      => 'List Workspace Blocks',
  // Bricks Workspace.
  'wpmcp-bricks/scaffold-bricks-element'       => 'Scaffold Bricks Element',
  'wpmcp-bricks/update-bricks-element'         => 'Update Bricks Element',
  'wpmcp-bricks/list-bricks-elements'          => 'List Bricks Elements',
  ```

**Verify:** Load Abilities tab, verify workspace abilities show human-readable labels

### Task 2.9: Add category labels to abilities tab

**File:** `admin/views/tab-abilities.php`

**What to modify:**
- Add to `$category_labels` array after the templates entry:
  ```
  'wpmcp-workspace'        => __( 'Workspace', 'wp-mcp-toolkit' ),
  'wpmcp-workspace-blocks' => __( 'Workspace Blocks', 'wp-mcp-toolkit' ),
  'wpmcp-bricks'           => __( 'Bricks Workspace', 'wp-mcp-toolkit' ),
  ```

**Verify:** Load Abilities tab, verify workspace categories show correct labels

---

## Phase 3: Core Workspace Abilities

**Goal:** Implement the 7 generic workspace abilities. Each ability follows the exact pattern from `class-abstract-abilities.php`.

### Task 3.1: Create workspace abilities class with ability definitions

**File:** `includes/modules/workspace/class-workspace-abilities.php`

**What to implement:**
- Class `WP_MCP_Toolkit_Workspace_Abilities extends WP_MCP_Toolkit_Abstract_Abilities`
- `get_abilities(): array` returning all 7 ability definitions with full input_schema, output_schema, callbacks
- Ability slugs: `wpmcp-workspace/generate-function`, `wpmcp-workspace/generate-class`, `wpmcp-workspace/register-hook`, `wpmcp-workspace/call-wp-api`, `wpmcp-workspace/list-workspace`, `wpmcp-workspace/read-workspace-file`, `wpmcp-workspace/delete-workspace-artifact`
- All write abilities: `readonly => false`, `destructive => false` (except delete: `destructive => true`), `idempotent => true`
- Permission: `manage_options` for all
- Category: `wpmcp-workspace` for all

**Verify:** PHP syntax check on file + call abilities via MCP to confirm registration works

### Task 3.2: Implement generate-function callback

**File:** `includes/modules/workspace/class-workspace-abilities.php` (method)

**What to implement:**
- `execute_generate_function( array $input ): array|WP_Error`
- Input schema: `function_name` (string, required), `parameters` (string, optional — e.g. `string $name, int $count = 10`), `body` (string, required — PHP code without opening tag or function wrapper), `description` (string, optional — PHPDoc summary)
- Steps:
  1. Auto-initialize workspace if needed (`Workspace_Container::initialize_workspace()`)
  2. Validate function name via `Workspace_Validator::validate_function_name()`
  3. Namespace the function: prepend `wpmcp_workspace_` to prevent collisions
  4. Build PHP file: opening PHP tag + PHPDoc + function declaration with parameters and body
  5. Validate PHP syntax via `Workspace_Validator::validate_php_syntax()`
  6. Write via `Workspace_File_Writer::write_file( 'functions/{name}.php', $code )`
  7. Add to manifest via `Workspace_Manifest::add_artifact()`
  8. Regenerate workspace plugin bootstrap via `Workspace_Container::reinitialize()` (uses atomic `write_bootstrap()`)
  9. Return `['name' => ..., 'file' => ..., 'checksum' => ...]`

**Verify:** PHP syntax check + call ability via MCP, check file created

### Task 3.3: Implement generate-class callback

**File:** `includes/modules/workspace/class-workspace-abilities.php` (method)

**What to implement:**
- `execute_generate_class( array $input ): array|WP_Error`
- Input schema: `class_name` (string, required), `extends` (string, optional), `implements` (string, optional — comma-separated), `methods` (array of objects with `name`, `visibility`, `params`, `body`, `return_type`, `is_static`), `properties` (array of objects with `name`, `visibility`, `type`, `default`, `is_static`), `description` (string, optional)
- Steps: similar to generate-function but builds a full class file
  1. Validate class name, prefix with `WPMCP_Workspace_`
  2. Build class PHP code with proper structure
  3. Validate syntax, write, manifest, regenerate bootstrap (atomic)
- Return: `['class_name' => ..., 'file' => ..., 'checksum' => ...]`

**Verify:** PHP syntax check + call ability via MCP

### Task 3.4: Implement register-hook callback

**File:** `includes/modules/workspace/class-workspace-abilities.php` (method)

**What to implement:**
- `execute_register_hook( array $input ): array|WP_Error`
- Input schema: `hook_type` (enum: 'action'|'filter', required), `hook_name` (string, required), `callback_function` (string, required — must be a function already in workspace or a built-in), `priority` (integer, default 10), `accepted_args` (integer, default 1)
- Steps:
  1. Validate hook name
  2. Verify callback function exists in workspace manifest or is a known WordPress function
  3. Generate PHP file with the appropriate `add_action` or `add_filter` call
  4. Validate, write to `hooks/{hook_name}--{callback}.php`, manifest, regenerate bootstrap (atomic)
- Return: `['hook_type' => ..., 'hook_name' => ..., 'callback' => ..., 'file' => ...]`

**Verify:** PHP syntax check + call ability via MCP

### Task 3.5: Implement call-wp-api callback

**File:** `includes/modules/workspace/class-workspace-abilities.php` (method)

**What to implement:**
- `execute_call_wp_api( array $input ): array|WP_Error`
- Input schema: `function_name` (string, required), `arguments` (array, optional — positional args)
- Steps:
  1. **REJECT if function name contains `::` or `->`** — global functions only. Return `WP_Error('wpmcp_invalid_function', 'Only global functions accepted. For class methods, use generate-function to write a wrapper.')`
  2. Check hardcoded blocklist in ALL modes (staging AND production) — NEVER allow:
     - **Process/exec:** `eval`, `assert`, `create_function`, `exec`, `system`, `passthru`, `popen`, `proc_open`, `pcntl_exec`, `shell_exec`, `dl`
     - **Environment:** `putenv`, `ini_set`, `extract`, `parse_str`
     - **Include/require:** `include`, `require`, `include_once`, `require_once`
     - **Callback wrappers:** `call_user_func`, `call_user_func_array`, `preg_replace` (with e modifier)
     - **Filesystem:** `unlink`, `rmdir`, `rename`, `copy`, `mkdir`, `chmod`, `chown`, `chgrp`, `file_put_contents`, `file_get_contents`, `fopen`, `fwrite`, `fputs`, `fclose`, `readfile`, `move_uploaded_file`
     - **Network:** `curl_exec`, `curl_multi_exec`, `mail`, `header`, `setcookie`
     - **WordPress dangerous:** `wp_delete_user`, `wp_set_password`, `wp_insert_user`, `update_option`
  3. Check mode: if production, validate function against allowlist (`get_option('wpmcp_workspace_allowlist', [])` merged with default allowlist)
  4. If staging, allow any callable that `function_exists()` and passes blocklist
  5. Verify function exists via `function_exists()`
  6. Call via `call_user_func_array()` with sanitized arguments
  7. Catch any thrown exceptions, convert to WP_Error
  8. Return result (serialize complex objects to arrays)
- Default allowlist for production mode: `register_post_type`, `register_taxonomy`, `register_meta`, `add_action`, `add_filter`, `remove_action`, `remove_filter`, `register_block_type`, `register_block_style`, `add_theme_support`, `register_nav_menus`, `register_sidebar`, `register_widget`, `wp_register_style`, `wp_register_script`, `wp_enqueue_style`, `wp_enqueue_script`, `add_shortcode`, `add_rewrite_rule`, `add_rewrite_tag`, `flush_rewrite_rules`, `register_rest_route`
- LLM description MUST note: "Only global functions accepted. For class methods, use generate-function to write a wrapper."

**Verify:** PHP syntax check + test with:
1. Allowlisted function in production mode (should succeed)
2. Non-allowlisted function in production mode (should fail)
3. Any function in staging mode (should succeed)
4. Blocklisted function in staging mode (should fail)
5. Function with `::` in name (should fail with clear error)
6. Function with `->` in name (should fail with clear error)

### Task 3.6: Implement list, read, and delete callbacks

**File:** `includes/modules/workspace/class-workspace-abilities.php` (methods)

**What to implement:**

`execute_list_workspace( array $input ): array`
- Input schema: `type` (string, optional — filter by artifact type)
- Returns manifest artifacts list with file sizes added

`execute_read_workspace_file( array $input ): array|WP_Error`
- Input schema: `name` (string, required — artifact name from manifest)
- Looks up in manifest, reads file via `Workspace_File_Writer::read_file()`
- Returns `['name' => ..., 'type' => ..., 'content' => ..., 'checksum' => ...]`

`execute_delete_workspace_artifact( array $input ): array|WP_Error`
- Input schema: `name` (string, required), `confirm` (boolean, required — must be true)
- Deletes file, removes from manifest, regenerates workspace bootstrap (atomic)
- Returns `['deleted' => name, 'remaining_count' => ...]`

**Verify:** PHP syntax check + call each ability via MCP

---

## Phase 4: Gutenberg Block Module

**Goal:** Implement the 3 Gutenberg block scaffolding abilities under the `wpmcp-workspace/` prefix.

### Task 4.1: Create block templates

**Files:**
- `includes/modules/workspace/templates/block-json.tpl`
- `includes/modules/workspace/templates/block-render.php.tpl`
- `includes/modules/workspace/templates/block-style.css.tpl`

**What to implement:**

`block-json.tpl`:
- Standard `block.json` with `apiVersion: 3`, `name: wpmcp-workspace/{{BLOCK_NAME}}`, `title: {{BLOCK_TITLE}}`, `description: {{BLOCK_DESCRIPTION}}`, `category: {{BLOCK_CATEGORY}}` (default 'widgets'), `icon`, `supports`, `attributes` (from input), `textdomain: wpmcp-workspace`, `editorStyle`, `style`, `render` pointing to `render.php`
- Placeholder tokens: `{{BLOCK_NAME}}`, `{{BLOCK_TITLE}}`, `{{BLOCK_DESCRIPTION}}`, `{{BLOCK_CATEGORY}}`, `{{BLOCK_ATTRIBUTES}}`, `{{BLOCK_ICON}}`

`block-render.php.tpl`:
- Opening PHP tag with `defined('ABSPATH') || exit();`
- PHPDoc with block name
- Default render using `$attributes` and `$content` variables
- Wrapper div with `get_block_wrapper_attributes()` pattern
- Placeholder tokens: `{{BLOCK_NAME}}`, `{{RENDER_BODY}}`

`block-style.css.tpl`:
- `.wp-block-wpmcp-workspace-{{BLOCK_NAME}}` scoped styles
- Minimal default styles (padding, margin)
- Placeholder tokens: `{{BLOCK_NAME}}`, `{{CUSTOM_CSS}}`

**Verify:** Review templates for correctness; verify all placeholder tokens are documented

### Task 4.2: Create block abilities class

**File:** `includes/modules/workspace/class-workspace-blocks-abilities.php`

**What to implement:**
- Class `WP_MCP_Toolkit_Workspace_Blocks_Abilities extends WP_MCP_Toolkit_Abstract_Abilities`
- 3 ability definitions:

`wpmcp-workspace/scaffold-block`:
- Input: `block_name` (string, required — kebab-case), `title` (string, required), `description` (string, optional), `category` (string, default 'widgets'), `attributes` (object, optional — block.json attributes schema), `render_php` (string, optional — custom render.php body), `css` (string, optional — custom styles)
- Steps: validate name, create `blocks/{name}/` dir, render 3 templates via `Workspace_Container::render_template()`, write files, register in manifest, regenerate workspace bootstrap (atomic)
- Output: `['block_name' => ..., 'files' => [...], 'registration' => 'wpmcp-workspace/{name}']`

`wpmcp-workspace/update-block`:
- Input: `block_name` (string, required), `block_json` (object, optional — partial merge), `render_php` (string, optional), `css` (string, optional)
- Steps: verify block exists in manifest, update only provided files, re-validate syntax, update manifest checksums
- Output: `['block_name' => ..., 'updated_files' => [...]]`

`wpmcp-workspace/list-workspace-blocks`:
- Input: none
- Steps: filter manifest for type='block', enrich with block.json metadata
- Output: array of block summaries

- All abilities: category `wpmcp-workspace-blocks`, permission `manage_options`
- scaffold: `readonly => false`, `destructive => false`, `idempotent => true`
- update: `readonly => false`, `destructive => false`, `idempotent => true`
- list: `readonly => true`, `destructive => false`, `idempotent => true`

**Verify:** PHP syntax check on file

### Task 4.3: Implement block ability callbacks

**File:** `includes/modules/workspace/class-workspace-blocks-abilities.php` (methods)

**What to implement:**
- `execute_scaffold_block( array $input ): array|WP_Error`
- `execute_update_block( array $input ): array|WP_Error`
- `execute_list_workspace_blocks( array $input ): array`
- Each follows the steps outlined in Task 4.2
- Block name validation: must be kebab-case, alphanumeric + hyphens only
- The scaffold callback must check for existing block with same name (return WP_Error if exists — use update-block instead)

**Verify:** PHP syntax check + scaffold a test block via MCP, verify `block.json` is valid JSON, `render.php` passes `php -l`

---

## Phase 5: Bricks Builder Addon

**Goal:** Implement the 3 Bricks Builder element scaffolding abilities as a separate addon under the `wpmcp-bricks/` prefix.

### Task 5.1: Create Bricks element template

**File:** `includes/modules/bricks-workspace/templates/bricks-element.php.tpl`

**What to implement:**
- Opening PHP tag with `defined('ABSPATH') || exit();`
- Class `{{CLASS_NAME}}` extending `\Bricks\Element`
- Required properties: `$category = '{{ELEMENT_CATEGORY}}'`, `$name = '{{ELEMENT_NAME}}'`, `$icon = '{{ELEMENT_ICON}}'`, `$css_selector = '{{CSS_SELECTOR}}'`
- Required methods:
  - `get_label(): string` — returns `'{{ELEMENT_LABEL}}'`
  - `set_controls(): void` — with control definitions from `{{CONTROLS_BODY}}`
  - `render(): void` — with `$this->set_attribute('_root', ...)` pattern and `{{RENDER_BODY}}`
- Companion `style.css` template with `.brxe-{{ELEMENT_NAME}}` scoping

**Placeholder tokens:** `{{CLASS_NAME}}`, `{{ELEMENT_CATEGORY}}`, `{{ELEMENT_NAME}}`, `{{ELEMENT_ICON}}`, `{{CSS_SELECTOR}}`, `{{ELEMENT_LABEL}}`, `{{CONTROLS_BODY}}`, `{{RENDER_BODY}}`

**Verify:** Review template for Bricks API correctness

### Task 5.2: Create Bricks abilities class

**File:** `includes/modules/bricks-workspace/class-workspace-bricks-abilities.php`

**What to implement:**
- Class `WP_MCP_Toolkit_Workspace_Bricks_Abilities extends WP_MCP_Toolkit_Abstract_Abilities`
- 3 ability definitions:

`wpmcp-bricks/scaffold-bricks-element`:
- Input: `element_name` (string, required — kebab-case), `label` (string, required), `description` (string, optional), `category` (string, default 'wpmcp-workspace'), `icon` (string, default 'ti-layout-cta-right'), `controls` (array of objects with `name`, `type`, `label`, `default`, optional `options`), `render_php` (string, optional — custom render body), `css` (string, optional)
- Steps: validate name, create `bricks/{name}/` dir in workspace, render element.php from template via `Workspace_Container::render_template()`, write style.css, register in manifest, regenerate workspace bootstrap (atomic)
- Output: `['element_name' => ..., 'files' => [...], 'class_name' => ...]`

`wpmcp-bricks/update-bricks-element`:
- Input: `element_name` (string, required), `controls` (array, optional), `render_php` (string, optional), `css` (string, optional)
- Steps: verify element exists, update files, validate syntax, update manifest
- Output: `['element_name' => ..., 'updated_files' => [...]]`

`wpmcp-bricks/list-bricks-elements`:
- Input: none
- Steps: filter manifest for type='bricks-element', return summaries
- Output: array of element summaries

- All abilities: category `wpmcp-bricks`, permission `manage_options`
- scaffold: `readonly => false`, `destructive => false`, `idempotent => true`
- update: `readonly => false`, `destructive => false`, `idempotent => true`
- list: `readonly => true`, `destructive => false`, `idempotent => true`

**Verify:** PHP syntax check on file

### Task 5.3: Implement Bricks ability callbacks

**File:** `includes/modules/bricks-workspace/class-workspace-bricks-abilities.php` (methods)

**What to implement:**
- `execute_scaffold_bricks_element( array $input ): array|WP_Error`
- `execute_update_bricks_element( array $input ): array|WP_Error`
- `execute_list_bricks_elements( array $input ): array`
- Element name validation: kebab-case, maps to PascalCase class name `WPMCP_Workspace_Bricks_{Name}`
- Controls array generates `set_controls()` method body with `$this->controls['{name}'] = [...]` entries
- Render PHP is injected into the `render()` method body
- The scaffold callback checks for existing element (return WP_Error if exists)

**Verify:** PHP syntax check + scaffold a test element via MCP, verify PHP class is valid

---

## Phase 6: Mode System

**Goal:** Implement the staging/production mode system with allowlist enforcement.

### Task 6.1: Add mode detection and storage

**File:** `includes/modules/workspace/class-workspace-validator.php` (extend from Task 1.2)

**What to implement:**
- `get_mode(): string` — returns `get_option('wpmcp_workspace_mode', 'auto')`:
  - `'auto'`: staging if `wp_get_environment_type() !== 'production'` OR `defined('WPMCP_DEV_MODE') && WPMCP_DEV_MODE`, else production
  - `'staging'`: always staging
  - `'production'`: always production
  - `'disabled'`: workspace abilities do not register
- `is_staging(): bool` — resolves 'auto' and returns true/false
- `is_production(): bool` — inverse of staging
- `set_mode( string $mode ): true|WP_Error` — validates mode value, updates option

**Verify:** PHP syntax check + test mode resolution with different environment types

### Task 6.2: Add allowlist management

**File:** `includes/modules/workspace/class-workspace-validator.php` (extend)

**What to implement:**
- `get_default_allowlist(): array` — hardcoded safe WordPress functions (see Task 3.5 list)
- `get_blocklist(): array` — hardcoded dangerous functions (see Task 3.5 expanded list), checked in ALL modes (staging AND production)
- `get_allowlist(): array` — merges default allowlist with `get_option('wpmcp_workspace_allowlist', [])` custom entries
- `set_custom_allowlist( array $functions ): true|WP_Error` — validates each function name, saves to option
- `is_function_allowed( string $function_name ): true|WP_Error` — checks:
  1. Reject if contains `::` or `->` (global functions only)
  2. Check blocklist first (always denied, both staging and production)
  3. If staging, allow any remaining function
  4. If production, check allowlist

**Verify:** PHP syntax check + test with known allowed/denied functions

### Task 6.3: Wire mode into module initialization

**File:** `includes/modules/workspace/class-workspace-module.php` (modify from Task 2.3)

**What to implement:**
- Update `is_active()` to return `false` when mode is `'disabled'`
- Update `init()` to only register abilities when mode is not 'disabled'
- Ensure `call-wp-api` callback reads mode at execution time (not registration time) so mode changes take effect immediately

**Verify:** Set mode to 'disabled', verify no workspace abilities register; set to 'staging', verify all register

---

## Phase 7: Admin UI

**Goal:** Add the Workspace tab to the existing admin settings page with mode selector, allowlist editor, and artifact browser.

### Task 7.1: Add workspace tab to settings page navigation

**File:** `admin/views/settings-page.php`

**What to modify:**
- Add `'workspace'` to `$valid_tabs` array
- Add nav tab link after the Templates tab link

**Verify:** Load admin page, verify Workspace tab link appears and navigates without error

### Task 7.2: Create workspace tab view — mode selector section

**File:** `admin/views/tab-workspace.php`

**What to implement:**
- Form with `settings_fields('wpmcp_settings')` — uses the EXISTING `wpmcp_settings` group (not a new group), matching the pattern used for `wpmcp_disabled_abilities`
- Mode selector: radio buttons for Auto (default), Staging, Production, Disabled
- Each radio shows explanation text:
  - Auto: "Staging on development/staging environments, production on production"
  - Staging: "All workspace abilities available, call-wp-api unrestricted"
  - Production: "Only allowlisted functions in call-wp-api"
  - Disabled: "Workspace abilities are not registered"
- Current resolved mode shown as badge: "Currently: Staging" or "Currently: Production"
- Save button via `submit_button()`

**Verify:** Load tab, verify form renders correctly, save mode, verify option persisted

### Task 7.3: Create workspace tab view — allowlist editor section

**File:** `admin/views/tab-workspace.php` (extend)

**What to implement:**
- Section header: "Production Allowlist"
- Display default allowlist as read-only chips/tags (not editable, always included)
- Textarea for custom additions (one function name per line)
- Help text: "These functions are allowed in production mode. The default list is always included."
- Only visible/active when mode is Production or Auto

**Verify:** Load tab, verify allowlist renders, add custom function, save, verify option updated

### Task 7.4: Create workspace tab view — artifact browser section

**File:** `admin/views/tab-workspace.php` (extend)

**What to implement:**
- Section header: "Workspace Artifacts"
- Reads manifest via `Workspace_Manifest::list_artifacts()`
- Table with columns: Name, Type, File, Created, Checksum (truncated)
- Type filter tabs: All, Functions, Classes, Hooks, Blocks, Bricks Elements
- Empty state: "No artifacts yet. AI agents can create workspace artifacts using MCP abilities."
- Shows workspace directory path and writable status

**Verify:** Load tab, verify table renders (empty state when no artifacts, populated when artifacts exist)

### Task 7.5: Create workspace tab view — crash recovery section

**File:** `admin/views/tab-workspace.php` (extend)

**What to implement:**
- Only shown when `.crashed` marker exists (via `Workspace_MU_Loader::get_crash_status()`)
- Warning notice box: "Workspace plugin crashed on last load. The MU-plugin loader has disabled it to prevent further issues."
- "Recover" button — AJAX call to delete `.crashed` marker and retry loading
- "Disable Workspace" button — sets mode to 'disabled' and removes `.crashed`

**Verify:** Manually create `.crashed` file, load tab, verify warning appears

### Task 7.6: Register workspace settings and AJAX handlers

**File:** `admin/class-admin-page.php`

**What to modify:**
- In `register_settings()`: add `register_setting('wpmcp_settings', 'wpmcp_workspace_mode', ...)` and `register_setting('wpmcp_settings', 'wpmcp_workspace_allowlist', ...)` — uses the EXISTING `wpmcp_settings` group
- In `register_ajax_handlers()`: add `wp_ajax_wpmcp_workspace_recover` handler
- Add `ajax_workspace_recover()` method:
  - Call `check_ajax_referer( 'wpmcp_admin', 'nonce' )` — matching existing pattern
  - Calls `Workspace_MU_Loader::recover_from_crash()`, returns JSON success/error
- Sanitize callbacks: mode validates against enum, allowlist validates each entry as valid function name
- JavaScript sends nonce via `wmcpAdmin.nonce` (existing admin JS global)

**Verify:** Save settings via form, verify options persisted correctly; test AJAX handler

---

## Phase 8: Polish

**Goal:** Uninstall cleanup, admin styling, admin JavaScript, and LLM-optimized descriptions.

### Task 8.1: Update uninstall cleanup

**File:** `uninstall.php`

**What to modify:**
- Add after existing template cleanup:
  ```php
  // Clean up workspace options.
  delete_option( 'wpmcp_workspace_mode' );
  delete_option( 'wpmcp_workspace_allowlist' );
  $wpdb->query(
      $wpdb->prepare(
          "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
          'wpmcp_workspace_%'
      )
  );

  // Remove MU-plugin loader (but leave workspace artifacts for safety).
  $mu_loader = defined( 'WPMU_PLUGIN_DIR' ) ? WPMU_PLUGIN_DIR . '/wpmcp-workspace-loader.php' : '';
  if ( $mu_loader && file_exists( $mu_loader ) ) {
      unlink( $mu_loader );
  }
  ```

**Note on LIKE pattern:** Uses `wpmcp_workspace_%` with unescaped underscores, matching the existing `wpmcp_template_%` convention in this file. The underscore wildcard match is acceptable here since no other options would collide with this prefix.

**Verify:** Review code for correctness; note: workspace plugin directory is intentionally NOT deleted (user data)

### Task 8.2: Add workspace CSS styles

**File:** `admin/css/admin.css`

**What to modify:**
- Add styles for:
  - `.wpmcp-workspace-mode-selector` — radio group styling
  - `.wpmcp-workspace-mode-badge` — current mode indicator badge
  - `.wpmcp-workspace-allowlist` — allowlist editor layout
  - `.wpmcp-workspace-allowlist-defaults` — read-only default function chips
  - `.wpmcp-workspace-artifacts-table` — artifact browser table
  - `.wpmcp-workspace-crash-warning` — crash recovery warning notice box
  - `.wpmcp-workspace-empty-state` — empty artifact browser state
- Follow existing admin.css patterns for consistency (match existing card, toggle, and table styles)

**Verify:** Load Workspace tab, verify styles render correctly

### Task 8.3: Add workspace AJAX JavaScript

**File:** `admin/js/admin.js`

**What to modify:**
- Add workspace tab handlers (guarded with tab existence check):
  - Crash recovery button click handler (AJAX POST to `wpmcp_workspace_recover` action with `nonce: wmcpAdmin.nonce`)
  - Allowlist form validation (check function name format before submit)
  - Artifact type filter tab switching (show/hide table rows by data attribute)

**Verify:** Load Workspace tab, verify JavaScript initializes without console errors

### Task 8.4: Add LLM-optimized ability descriptions

**File:** `includes/modules/workspace/class-workspace-abilities.php` (modify description strings)

**What to implement:**
- Ensure each ability's `description` field contains LLM-optimized instructions explaining:
  - What the ability does
  - What inputs are expected and their formats
  - What the output means
  - How it relates to other workspace abilities
  - Current mode constraints (for call-wp-api)
- The `list-workspace` description should explain the discovery workflow: "Call this first to see what artifacts exist in the workspace before creating or modifying them."
- The `generate-function` description should note: "Functions are automatically prefixed with wpmcp_workspace_ to avoid collisions. Provide the function body without opening PHP tag or function declaration wrapper."
- The `call-wp-api` description MUST note: "Only global functions accepted. For class methods, use generate-function to write a wrapper. In staging mode, any WordPress function can be called. In production mode, only allowlisted functions are permitted. Dangerous functions (filesystem, process, network) are blocked in all modes."

**Verify:** Read ability descriptions, verify they are clear and actionable for an LLM

---

## Risk Assessment

| # | Risk | Likelihood | Impact | Mitigation |
|---|------|-----------|--------|------------|
| R-1 | Process execution disabled on host — PHP linting unavailable | Medium | Low | Graceful degradation: skip linting, add warning to manifest entry, log admin notice |
| R-2 | Plugin directory not writable (managed hosting) | Medium | Medium | Uploads directory fallback with clear admin notice explaining limitation |
| R-3 | MU-plugins directory not writable | Low | High | Admin notice explaining manual MU-plugin installation; provide copy-paste code |
| R-4 | Generated PHP crashes WordPress | Medium | High | MU-plugin loader crash recovery with `.loading`/`.crashed` markers; workspace plugin loads after WordPress core |
| R-5 | Path traversal via crafted input | Low | Critical | Strict path validation, `realpath()` check, filename regex whitelist, no `..` allowed |
| R-6 | `call-wp-api` used to call dangerous functions | Low | Critical | Hardcoded blocklist checked in ALL modes; `::` and `->` rejected; production restricts to allowlist |
| R-7 | Manifest corruption from concurrent writes | Low | Medium | `LOCK_EX` on all manifest writes; `json_decode` validation on reads |
| R-8 | Workspace bootstrap file becomes stale after artifact changes | Medium | Low | Regenerate bootstrap on every add/update/delete operation using atomic write (temp file + flock + rename) |
| R-9 | Bricks Builder API changes between versions | Medium | Medium | Version-gate Bricks element template; test against BRICKS_VERSION; `class_exists('\Bricks\Element')` guard in bootstrap |
| R-10 | Large number of workspace artifacts degrades WordPress load time | Low | Medium | Workspace plugin uses glob loading — measurable but unlikely to be problematic under 100 files; document performance expectations |
| R-11 | Bootstrap regeneration during concurrent requests | Low | Medium | Atomic write pattern: temp file + `LOCK_EX` + `flock` + `rename` + `wp_opcache_invalidate`; see Task 1.3 `write_bootstrap()` |
| R-12 | PHP 7.4 host tries to load workspace module | Low | Medium | `is_available()` returns false on PHP < 8.0; abilities never register; addon hidden from registry |

---

## Commit Strategy

| Phase | Commit Message |
|-------|---------------|
| Phase 1 | `feat(workspace): add foundation classes — container, validator, file writer, manifest, templates, template renderer` |
| Phase 2 | `feat(workspace): add module wiring — addon classes, MU-plugin loader, main plugin registration, labels` |
| Phase 3 | `feat(workspace): add 7 core workspace abilities` |
| Phase 4 | `feat(workspace): add Gutenberg block scaffolding abilities (wpmcp-workspace/ prefix)` |
| Phase 5 | `feat(workspace): add Bricks Builder addon with element scaffolding abilities (wpmcp-bricks/ prefix)` |
| Phase 6 | `feat(workspace): add staging/production mode system with allowlist` |
| Phase 7 | `feat(workspace): add admin UI — Workspace tab with mode selector, allowlist editor, artifact browser` |
| Phase 8 | `feat(workspace): polish — uninstall cleanup, admin CSS/JS, LLM descriptions` |

---

## Success Criteria

1. All 13 workspace abilities register and are visible in the admin Abilities tab (10 from workspace addon + 3 from bricks addon when Bricks is active)
2. `generate-function` produces syntactically valid PHP that loads on next request
3. `scaffold-block` produces a working Gutenberg block visible in the block editor
4. `scaffold-bricks-element` produces a working Bricks element (when Bricks is active)
5. `call-wp-api` respects mode: unrestricted in staging, allowlisted in production, blocklist always enforced
6. `call-wp-api` rejects any function name containing `::` or `->`
7. Crash recovery works: simulated crash triggers `.crashed` marker, admin shows recovery UI, recovery button re-enables workspace
8. Path traversal attempts are rejected with clear WP_Error messages
9. Invalid PHP is caught by linting and never written to workspace
10. Uninstall removes all options and MU-plugin loader (but preserves workspace artifacts)
11. Zero PHP warnings/notices/errors on plugin load with workspace active
12. Workspace abilities do NOT register on PHP < 8.0
13. Bricks element loading in bootstrap is guarded by `class_exists('\Bricks\Element')`
14. Bootstrap regeneration uses atomic write pattern (no corruption under concurrent requests)

---

## Audit-Driven Fix Phases

The following tasks were identified during the pre-workspace codebase audit (3 reviewers + critic calibration). They are mapped to the phase where we'll naturally touch the relevant files, or grouped into dedicated fix phases.

### Phase 0: Pre-Workspace Fixes (COMPLETED)

These were fixed before the workspace build began:

- [x] WP_Error code prefix violations — added `wpmcp_` prefix to all bare codes in content-abilities, block-abilities, yoast-abilities
- [x] Template engine regex bug — fixed `/>(.*?)</s` literal char match in class-template-engine.php
- [x] Module `$disabled` parameter — wired through from Addon to Module::init() in all 3 modules
- [x] uninstall.php — added cleanup for `wpmcp_licenses` and `wpmcp_disabled_addons`

### Fix-During-Build Tasks (mapped to workspace phases)

These fixes will be addressed when we touch the relevant code areas during workspace development.

#### During Phase 7 (Admin UI):

- **Task 7.0a: Bundle Select2 locally** — Download Select2 CSS/JS to `admin/vendor/select2/` and update `class-admin-page.php` to enqueue locally instead of from CDN. Eliminates SRI/supply-chain concern.
  - **File:** `admin/class-admin-page.php` lines 47-58
  - **Verify:** Page loads with no CDN requests for Select2

#### During Phase 8 (Polish):

- **Task 8.0a: Fix GF get-entry description** — Remove references to `ip`, `user_agent`, `source_url` from the ability description since the code strips these PII fields. Update output schema to document dynamic `field_N` keys with `additionalProperties: true`.
  - **File:** `includes/modules/gravity-forms/class-gf-abilities.php` line 152
  - **Verify:** Description matches actual response shape

- **Task 8.0b: Fix Yoast URL field sanitizer** — Change `sanitize_text_field()` to `esc_url_raw()` for `canonical_url` and `og_image` fields in update-post-seo.
  - **File:** `includes/modules/yoast/class-yoast-abilities.php` line 174
  - **Verify:** URLs with protocols preserved correctly

- **Task 8.0c: Fix nested meta sanitization** — Add recursive sanitization for nested array values in `set_post_meta()`. Arrays of arrays should sanitize all levels.
  - **File:** `includes/abilities/class-content-abilities.php` lines 537-546
  - **Verify:** Nested array meta values are sanitized at all levels

- **Task 8.0d: Fix GF create-entry non-string sanitization** — Document or sanitize non-string field values passed to `GFAPI::add_entry()`.
  - **File:** `includes/modules/gravity-forms/class-gf-abilities.php` line 385
  - **Verify:** Non-string values handled explicitly

### Phase 9: Backlog Cleanup

These are lower-priority improvements to address after the workspace module ships. Each is independent and can be done in any order.

#### Task 9.1: Split content-abilities.php (561 lines)

**File:** `includes/abilities/class-content-abilities.php`
**What:** Split into two files — read abilities (list, get, get-page-tree) and write abilities (create, update, delete, replace-content). Move write abilities to `class-content-write-abilities.php`.
**Why:** Exceeds 400-line limit. Natural split point between read and write operations.

#### Task 9.2: License validation stubs

**Files:** `includes/modules/gravity-forms/class-gf-addon.php`, `includes/modules/yoast/class-yoast-addon.php`
**What:** Replace the TODO stubs that accept any non-empty string. Implement actual LemonSqueezy API validation or at minimum validate key format.
**Why:** Currently any string grants premium module access. Pre-launch blocker.

#### Task 9.3: Hardcoded ability counts in Addon classes

**Files:** All addon `class-*-addon.php` files
**What:** Replace hardcoded `get_ability_count()` return values with dynamic counts. Could use `count( (new Abilities())->get_abilities() )` or a class constant kept next to the ability definitions.
**Why:** Drift risk — count must be manually updated when abilities are added/removed.

#### Task 9.4: Duplicate ACF inline permission callbacks

**Files:** `includes/modules/acf/class-acf-field-abilities.php`, `includes/modules/acf/class-acf-block-abilities.php`
**What:** Replace 4 inline permission closures with `self::permission_for_post('read_post')` and `self::permission_for_post('edit_post')` from the base class.
**Why:** DRY — base class already provides this exact helper.

#### Task 9.5: Move POST handler out of tab-templates.php

**File:** `admin/views/tab-templates.php`
**What:** Move the form submission handler (lines 11-32) into `class-admin-page.php` as an `admin_init` hook. Keep view files purely presentational.
**Why:** Defense-in-depth — separates form processing from rendering.

#### Task 9.6: Document apply_filters('the_content') risk

**File:** `includes/abilities/class-content-abilities.php`
**What:** Add a comment documenting that `content_rendered` runs all content filters including shortcodes, oEmbed, and third-party filters. Consider wrapping output in `wp_kses_post()`.
**Why:** Side effects and potentially large output in API context.

#### Task 9.7: Sanitize media sideload title

**File:** `includes/abilities/class-media-abilities.php`
**What:** Add `sanitize_text_field()` to `$input['title']` on line 225 before passing to `media_sideload_image()`. WP core sanitizes internally, but this adds defense-in-depth and matches the pattern used on line 238.
**Why:** Consistency with project sanitization standards.

#### Task 9.8: Internationalize admin.js strings

**File:** `admin/js/admin.js`, `admin/class-admin-page.php`
**What:** Pass translatable strings via `wp_localize_script` in the `wmcpAdmin` object. Replace hardcoded English strings ('Copied!', 'Deleting...', 'Enabled', etc.) with `wmcpAdmin.strings.*` references.
**Why:** PHP side uses `__()` for i18n but JS side has hardcoded English.
