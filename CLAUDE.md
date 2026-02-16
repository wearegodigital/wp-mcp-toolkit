# WP MCP Toolkit

Fork of the official WordPress MCP Adapter with comprehensive content management abilities.

## Architecture

### Core (upstream MCP Adapter — do not modify)
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
- `includes/class-ability-registrar.php` — Central registrar
- `includes/abilities/` (lowercase) — Ability classes
- `includes/modules/acf/` — Optional ACF module
- `admin/` — Settings page

## Abilities API Pattern

Categories on `wp_abilities_api_categories_init`, abilities on `wp_abilities_api_init`.

## Conventions
- PHP 7.4+, WordPress coding standards
- Text domain: `wp-mcp-toolkit`
- Prefix: `wpmcp_` for options, `wpmcp/` for abilities
- Return WP_Error on failure, never throw

## WP-CLI (via Docker)
```bash
docker exec devkinsta_fpm wp [command] --path=/www/kinsta/public/generatormedia/ --allow-root
```
