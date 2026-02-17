=== WP MCP Toolkit ===
Contributors: seanwilkinson
Tags: mcp, ai, content-management, blocks, acf
Requires at least: 6.9
Tested up to: 6.9
Stable tag: 0.4.0
Requires PHP: 8.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Comprehensive WordPress MCP server for AI agents. Single plugin install — no separate MCP adapter needed. 35 abilities for content, blocks, media, ACF, Gravity Forms, Yoast SEO, and content templates.

== Description ==

**WP MCP Toolkit** is a fork of the official WordPress MCP Adapter that adds comprehensive content management abilities for AI agents like Claude. It's a single plugin install that replaces both the official MCP Adapter and any custom ability code.

= What is MCP? =

MCP (Model Context Protocol) is Anthropic's standard for connecting AI agents to external tools and data sources. WP MCP Toolkit makes your WordPress site accessible to AI agents through this protocol.

= Key Features =

* **One Plugin Install** — Replaces both MCP Adapter and custom ability code
* **35 Content Abilities** — CRUD operations for posts, pages, custom post types, taxonomies, media, and more
* **Block Editing** — Parse and edit WordPress blocks with AI-friendly tools
* **ACF Module** — Auto-detects Advanced Custom Fields and adds 7 abilities for field groups, field values, and ACF blocks
* **Gravity Forms Module** — Auto-detects Gravity Forms and adds 5 abilities for forms, entries, and form data
* **Yoast SEO Module** — Auto-detects Yoast SEO and adds 3 abilities for reading and writing SEO metadata
* **Content Templates** — Extract block structure templates from reference posts and create new content from templates
* **Agent-Friendly** — Tool descriptions optimized for AI consumption
* **Content Workflow Guide** — Built-in `wpmcp/get-content-guide` ability teaches AI agents WordPress content patterns
* **Admin Settings Page** — Connection info, ability toggles, and status at a glance
* **HTML Encoding Fix** — Preserves HTML in ACF block fields during serialization

= Included Abilities =

**Content Management (8 abilities)**
* `wpmcp/list-post-types` — Discover available post types with features and REST config
* `wpmcp/list-posts` — Query posts of any type with pagination, filtering, search
* `wpmcp/get-post` — Get full post: title, content (raw + rendered), meta, taxonomy terms, featured image
* `wpmcp/create-post` — Create a new post, page, or custom post type
* `wpmcp/update-post` — Update any field on an existing post
* `wpmcp/delete-post` — Trash or force-delete a post
* `wpmcp/replace-content` — Find-and-replace text in post content at any nesting depth
* `wpmcp/get-content-guide` — AI agent workflow guide for WordPress content patterns

**Block Management (2 abilities)**
* `wpmcp/parse-blocks` — Parse a post's content into structured block tree
* `wpmcp/update-block-content` — Update text within a specific block by index or search

**Taxonomy Management (3 abilities)**
* `wpmcp/list-taxonomies` — List all registered taxonomies
* `wpmcp/list-terms` — Get terms in a taxonomy with count, parent, description
* `wpmcp/create-term` — Create a new term in a taxonomy

**Media Library (2 abilities)**
* `wpmcp/list-media` — Query media items with filtering by type
* `wpmcp/get-media` — Get media item details (URL, dimensions, alt text)

**Site Discovery (2 abilities)**
* `wpmcp/get-site-structure` — Full site overview: post types, taxonomies, theme, plugins, counts
* `wpmcp/get-page-tree` — Hierarchical page tree with IDs, titles, URLs, parents

**ACF Module (7 abilities, auto-loaded when ACF is active)**
* `wpmcp-acf/list-field-groups` — List all ACF field groups with location rules
* `wpmcp-acf/get-field-group` — Get full field group config (fields, types, choices)
* `wpmcp-acf/get-post-fields` — Get all ACF field values for a post
* `wpmcp-acf/update-post-fields` — Update ACF field values on a post
* `wpmcp-acf/list-acf-blocks` — List registered ACF blocks with field configurations
* `wpmcp-acf/get-block-fields` — Get ACF field values from a block in post content
* `wpmcp-acf/update-block-fields` — Update ACF field values within a block

**Core MCP Adapter (6 abilities)**
* `core/get-site-info` — Site URL, name, description
* `core/get-environment-info` — WordPress version, PHP version, environment details
* `core/get-user-info` — Current authenticated user info
* `mcp-adapter/discover-abilities` — List all available abilities
* `mcp-adapter/execute-ability` — Execute an ability by name
* `mcp-adapter/get-ability-info` — Get details about a specific ability

= Why WP MCP Toolkit? =

The official WordPress MCP Adapter provides only basic site info and healthcheck. WP MCP Toolkit adds 35 abilities for real content management work:

* AI agents can create, read, update, and delete WordPress content
* Block-level editing without manual JSON manipulation
* Taxonomy and term management
* Media library access
* ACF integration for custom fields
* Built-in workflow guide teaches agents WordPress patterns

= Use Cases =

* Content creation and editing via AI tools like Claude Code
* Automated content workflows
* AI-assisted site management
* Bulk content operations
* Custom field management via ACF

== Installation ==

1. Upload the `wp-mcp-toolkit` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to Settings > WP MCP Toolkit to view connection info
4. Add the MCP server configuration to your AI client (e.g., Claude Desktop)

= MCP Server Configuration =

Add this to your MCP client configuration (e.g., `~/Library/Application Support/Claude/claude_desktop_config.json`):

```json
{
  "mcpServers": {
    "wordpress": {
      "command": "php",
      "args": [
        "/path/to/wp-content/plugins/wp-mcp-toolkit/mcp-server.php"
      ]
    }
  }
}
```

Replace `/path/to/` with the actual path to your WordPress installation.

== Frequently Asked Questions ==

= Does this replace the official WordPress MCP Adapter? =

Yes. WP MCP Toolkit is a fork of the official adapter that includes all core adapter functionality plus 29 additional abilities. You only need one or the other, not both.

= Do I need Advanced Custom Fields (ACF)? =

No. ACF is optional. The ACF module auto-loads when ACF is detected, adding 7 abilities for field group and field value management. Without ACF, you still get 29 abilities.

= What AI clients work with this plugin? =

Any MCP-compatible client, including:
* Claude Desktop (Anthropic)
* Claude Code (VS Code / CLI)
* Other MCP-compatible AI tools

= Can I disable specific abilities? =

Yes. Go to Settings > WP MCP Toolkit and use the ability toggles to enable/disable individual abilities. Changes take effect immediately.

= Does this work with custom post types? =

Yes. All content abilities work with custom post types. Use `wpmcp/get-post-types` to discover available types and `wpmcp/get-post-type-schema` to see their fields.

= What's the `wpmcp/get-content-guide` ability? =

It returns a workflow guide that teaches AI agents WordPress content patterns: how to create posts, edit blocks, assign terms, upload media, etc. AI agents can read this guide to understand how to use the other abilities effectively.

= Does this modify WordPress core files? =

No. WP MCP Toolkit is a standard WordPress plugin that uses public WordPress APIs. It does not modify core files.

= Is this compatible with block themes? =

Yes. The block parsing and serialization abilities work with all WordPress blocks, including block theme templates.

== Screenshots ==

1. Settings page showing connection info and ability toggles
2. AI agent creating content via MCP abilities
3. Block editing with `wpmcp/parse-blocks` and `wpmcp/serialize-blocks`
4. ACF field management via MCP

== Changelog ==

= 0.4.0 =
* Added: Gravity Forms module — 5 abilities for listing forms, viewing entries, and creating entries (auto-loads when Gravity Forms is active)
* Added: Yoast SEO module — 3 abilities for reading and writing SEO metadata (auto-loads when Yoast SEO is active)
* Added: Content Templates — 3 abilities for extracting block structure templates from reference posts and creating new content from templates
* Added: Admin settings Templates tab for template management
* Updated: 35 total abilities (was 24)

= 0.3.0 =
* Added: `wpmcp/replace-content` ability for find-and-replace in post content at any nesting depth
* Added: `wpmcp/get-content-guide` ability — teaches AI agents WordPress content patterns
* Fixed: `serialize_blocks()` HTML encoding — preserves `<`, `>`, `&` in block attribute JSON
* Improved: All 24 tool descriptions rewritten for AI agent consumption (when to use, gotchas, input/output tips)
* Added: WordPress.org readme.txt

= 0.2.0 =
* Refactored: DRY ability registration via abstract base class
* Added: `uninstall.php` for clean plugin removal

= 0.1.0 =
* Initial release — fork of WordPress MCP Adapter v0.4.1
* Added: 17 content abilities (CRUD, blocks, taxonomy, media, site discovery)
* Added: 7 ACF abilities (field groups, post fields, ACF blocks) — auto-loads when ACF detected
* Added: Admin settings page with connection info and ability toggles

== Upgrade Notice ==

= 0.4.0 =
Adds Gravity Forms, Yoast SEO, and Content Templates modules. 35 total abilities.

= 0.3.0 =
Adds replace-content ability, content guide for AI agents, fixes HTML encoding in blocks, rewrites all tool descriptions.

= 0.2.0 =
DRY refactor with abstract base class. Clean uninstall support.

= 0.1.0 =
Initial release: 24 content abilities, ACF module, admin settings page.
