<?php
/**
 * WP MCP Toolkit — Abstract base class for ability groups.
 *
 * Eliminates boilerplate: subclasses only define ability configs and execute callbacks.
 *
 * @package wp-mcp-toolkit
 */

defined( 'ABSPATH' ) || exit();

abstract class WP_MCP_Toolkit_Abstract_Abilities {

	/**
	 * Human-readable labels for ability slugs.
	 *
	 * @var array<string, string>
	 */
	private static array $ability_labels = array(
		// Core — Content Management.
		'wpmcp/get-post'               => 'Get Post',
		'wpmcp/list-posts'             => 'List Posts',
		'wpmcp/create-post'            => 'Create Post',
		'wpmcp/update-post'            => 'Update Post',
		'wpmcp/delete-post'            => 'Delete Post',
		// Core — Block Content.
		'wpmcp/parse-blocks'           => 'Parse Blocks',
		'wpmcp/replace-content'        => 'Find & Replace Content',
		'wpmcp/get-content-guide'      => 'Content Workflow Guide',
		'wpmcp/update-block-content'   => 'Update Block Content',
		// Core — Taxonomy.
		'wpmcp/list-taxonomies'        => 'List Taxonomies',
		'wpmcp/list-terms'             => 'List Terms',
		'wpmcp/create-term'            => 'Create Term',
		// Core — Media.
		'wpmcp/get-media'              => 'Get Media',
		'wpmcp/list-media'             => 'List Media',
		'wpmcp/upload-media'           => 'Upload Media',
		// Core — Schema / Discovery.
		'wpmcp/get-site-structure'     => 'Site Structure',
		'wpmcp/list-post-types'        => 'List Post Types',
		'wpmcp/get-page-tree'          => 'Get Page Tree',
		// ACF.
		'wpmcp-acf/get-post-fields'    => 'Get ACF Post Fields',
		'wpmcp-acf/update-post-fields' => 'Update ACF Post Fields',
		'wpmcp-acf/list-field-groups'  => 'List Field Groups',
		'wpmcp-acf/get-field-group'    => 'Get Field Group',
		'wpmcp-acf/list-acf-blocks'    => 'List ACF Block Types',
		'wpmcp-acf/get-block-fields'   => 'Get ACF Block Fields',
		'wpmcp-acf/update-block-fields' => 'Update ACF Block Fields',
		// Gravity Forms.
		'wpmcp-gf/list-forms'          => 'List Forms',
		'wpmcp-gf/get-form'            => 'Get Form',
		'wpmcp-gf/list-entries'        => 'List Entries',
		'wpmcp-gf/get-entry'           => 'Get Entry',
		'wpmcp-gf/create-entry'        => 'Create Entry',
		// Yoast SEO.
		'wpmcp-yoast/get-post-seo'     => 'Get Post SEO',
		'wpmcp-yoast/update-post-seo'  => 'Update Post SEO',
		'wpmcp-yoast/get-seo-overview' => 'SEO Overview',
		// Templates.
		'wpmcp/list-content-templates' => 'List Templates',
		'wpmcp/get-content-template'   => 'Get Template',
		'wpmcp/create-from-template'   => 'Create from Template',
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
	);

	/**
	 * Get human-readable label for an ability slug.
	 *
	 * Falls back to auto-generating from the slug if not in the map.
	 * Third-party add-ons can filter labels via 'wpmcp_ability_label'.
	 *
	 * @param string $slug Ability slug (e.g. 'wpmcp/get-post').
	 * @return string Human-readable label.
	 */
	public static function get_ability_label( string $slug ): string {
		if ( isset( self::$ability_labels[ $slug ] ) ) {
			$label = self::$ability_labels[ $slug ];
		} else {
			// Auto-generate: 'wpmcp-foo/get-bar-baz' → 'Get Bar Baz'.
			$parts = explode( '/', $slug, 2 );
			$name  = $parts[1] ?? $parts[0];
			$label = ucwords( str_replace( '-', ' ', $name ) );
		}

		return apply_filters( 'wpmcp_ability_label', $label, $slug );
	}

	/**
	 * Return an array of ability definitions.
	 *
	 * Each key is the ability slug (e.g. 'wpmcp/list-posts').
	 * Each value is an array with:
	 *   - label          (string)
	 *   - description    (string)
	 *   - category       (string)
	 *   - input_schema   (array)
	 *   - output_schema  (array)
	 *   - callback       (string) Method name on $this for execution.
	 *   - permission     (string|callable) A capability string, or a callable receiving $input.
	 *   - readonly       (bool, default true)
	 *   - destructive    (bool, default false)
	 *   - idempotent     (bool, default true)
	 *
	 * @return array<string, array>
	 */
	abstract protected function get_abilities(): array;

	/**
	 * Register all abilities that are not in the disabled list.
	 */
	public function register( array $disabled = array() ): void {
		foreach ( $this->get_abilities() as $slug => $def ) {
			if ( in_array( $slug, $disabled, true ) ) {
				continue;
			}

			$readonly    = $def['readonly'] ?? true;
			$destructive = $def['destructive'] ?? false;
			$idempotent  = $def['idempotent'] ?? true;

			$permission = $def['permission'] ?? 'read';
			if ( is_string( $permission ) ) {
				$cap = $permission;
				$permission_callback = static function ( $input = array() ) use ( $cap ): bool {
					return current_user_can( $cap );
				};
			} else {
				$permission_callback = $permission;
			}

			// Ensure non-empty input schemas have a default so that
			// WP_Ability::normalize_input() converts null → array().
			// The upstream MCP adapter converts empty {} params to null,
			// which would otherwise fail schema validation.
			$input_schema = $def['input_schema'];
			if ( ! empty( $input_schema ) && ! array_key_exists( 'default', $input_schema ) ) {
				$input_schema['default'] = array();
			}

			wp_register_ability(
				$slug,
				array(
					'label'               => $def['label'],
					'description'         => $def['description'],
					'category'            => $def['category'],
					'input_schema'        => $input_schema,
					'output_schema'       => $def['output_schema'],
					'execute_callback'    => array( $this, $def['callback'] ),
					'permission_callback' => $permission_callback,
					'meta'                => array(
						'annotations'  => array(
							'readonly'    => $readonly,
							'destructive' => $destructive,
							'idempotent'  => $idempotent,
						),
						'mcp'          => array(
							'public' => true,
						),
						'show_in_rest' => true,
					),
				)
			);
		}
	}

	/**
	 * Permission callback: checks a capability against a post_id from input.
	 *
	 * @since 0.4.1
	 * @param string $cap Capability to check (e.g. 'edit_post', 'read_post', 'delete_post').
	 * @return callable Permission callback.
	 */
	protected static function permission_for_post( string $cap ): callable {
		return static function ( $input = array() ) use ( $cap ): bool {
			$input   = is_array( $input ) ? $input : (array) $input;
			$post_id = absint( $input['post_id'] ?? 0 );
			return current_user_can( $cap, $post_id );
		};
	}

	/**
	 * Permission callback: checks publish capability for a post type from input.
	 *
	 * @since 0.4.1
	 * @param string $input_key Input key containing the post type slug (default 'post_type').
	 * @return callable Permission callback.
	 */
	protected static function permission_for_post_type( string $input_key = 'post_type' ): callable {
		return static function ( $input = array() ) use ( $input_key ): bool {
			$input     = is_array( $input ) ? $input : (array) $input;
			$post_type = sanitize_key( $input[ $input_key ] ?? 'post' );
			$pt_obj    = get_post_type_object( $post_type );
			return $pt_obj && current_user_can( $pt_obj->cap->publish_posts );
		};
	}

	/**
	 * Helper: empty input schema (no parameters).
	 */
	protected static function empty_input_schema(): array {
		return array();
	}

	/**
	 * Helper: normalize input to array.
	 */
	protected static function normalize_input( $input ): array {
		return is_array( $input ) ? $input : (array) $input;
	}

	/**
	 * Decode unicode escape sequences in text content.
	 *
	 * LLMs sometimes emit bare unicode escape sequences (e.g. "u2014" instead
	 * of an actual em dash) in generated content. This method catches both
	 * backslash-prefixed (\u2014) and bare (u2014) patterns between word
	 * characters and decodes them to proper UTF-8.
	 *
	 * Only decodes codepoints in safe ranges (punctuation, symbols, Latin
	 * Extended) to avoid false positives on words like "autumn" or "unusual".
	 *
	 * @param string $content Text to process.
	 * @return string Content with unicode escapes decoded.
	 */
	public static function decode_unicode_escapes( string $content ): string {
		// First: decode explicit \uXXXX sequences (backslash-prefixed).
		$content = preg_replace_callback(
			'/\\\\u([0-9a-fA-F]{4})/',
			static function ( $m ) {
				return mb_chr( hexdec( $m[1] ), 'UTF-8' );
			},
			$content
		);

		// Second: decode bare uXXXX between word characters.
		// Only match codepoints that are clearly unicode symbols, not English words.
		// Safe ranges: U+00A0-00FF (Latin-1 Supplement), U+2000-27FF (punctuation/symbols).
		$content = preg_replace_callback(
			'/(?<=[a-zA-Z0-9])u((?:00[a-fA-F][0-9a-fA-F])|(?:2[0-7][0-9a-fA-F]{2}))(?=[a-zA-Z0-9])/',
			static function ( $m ) {
				return mb_chr( hexdec( $m[1] ), 'UTF-8' );
			},
			$content
		);

		return $content;
	}

	/**
	 * Fix HTML encoding in serialized blocks.
	 *
	 * WordPress's serialize_block_attributes() encodes < > " & as unicode
	 * escapes (\u003c, \u003e, \u0022, \u0026) inside block comment JSON.
	 * This is correct JSON, but Gutenberg's JS serializer preserves literal
	 * HTML characters. When the PHP-serialized content is saved and later
	 * parsed by Gutenberg, the unicode escapes may render as literal text
	 * (e.g. "u003cstrongu003e" instead of "<strong>").
	 *
	 * This method decodes those escapes in block comment attributes to match
	 * the behavior of Gutenberg's JavaScript serializer.
	 */
	protected static function fix_serialized_block_html( string $content ): string {
		// Match block comment JSON attributes: <!-- wp:block-name {"key":"value"} -->
		return preg_replace_callback(
			'/<!-- wp:([a-z][a-z0-9-]*(?:\/[a-z][a-z0-9-]*)?) (\{.*?\}) (\/)?-->/s',
			static function ( $matches ) {
				$block_name = $matches[1];
				$json       = $matches[2];
				$self_close = $matches[3] ?? '';

				// Decode unicode escapes for HTML characters.
				$json = str_replace(
					array( '\\u003c', '\\u003e', '\\u0022', '\\u0026', '\\u0027' ),
					array( '<',       '>',       '\\"',     '&',       "'" ),
					$json
				);

				return '<!-- wp:' . $block_name . ' ' . $json . ' ' . $self_close . '-->';
			},
			$content
		);
	}
}
