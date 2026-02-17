<?php
defined( 'ABSPATH' ) || exit();

class WP_MCP_Toolkit_Template_Engine {

	/**
	 * Extract a template from a reference post.
	 *
	 * Parses the post's block content, identifies sections (by background color CSS classes
	 * or sequential order), and replaces text content with {{placeholder}} markers.
	 *
	 * @param int $post_id The reference post ID.
	 * @return array|WP_Error Template data with keys: post_type, reference_post_id, sections, placeholders, raw_template, acf_fields
	 */
	public static function extract_template( int $post_id ) {
		$post = get_post( $post_id );
		if ( ! $post ) {
			return new \WP_Error( 'not_found', 'Reference post not found.' );
		}

		$blocks = parse_blocks( $post->post_content );
		$blocks = array_values( array_filter( $blocks, fn( $b ) => ! empty( $b['blockName'] ) ) );

		$sections = array();
		$placeholders = array();
		$placeholder_index = 0;

		foreach ( $blocks as $section_index => $block ) {
			$section_name = self::detect_section_name( $block, $section_index );
			$section_placeholders = array();

			// Walk the block tree and replace text content with placeholders
			$template_block = self::templatize_block( $block, $section_name, $section_placeholders, $placeholder_index );

			$sections[] = array(
				'index' => $section_index,
				'name' => $section_name,
				'block_name' => $block['blockName'],
				'placeholders' => array_keys( $section_placeholders ),
			);

			$placeholders = array_merge( $placeholders, $section_placeholders );
			$blocks[ $section_index ] = $template_block;
		}

		// Serialize blocks back to HTML with placeholders
		$raw_template = '';
		foreach ( $blocks as $block ) {
			$raw_template .= serialize_block( $block );
		}

		// Get ACF fields if available
		$acf_fields = array();
		if ( class_exists( 'ACF' ) ) {
			$field_groups = acf_get_field_groups( array( 'post_type' => $post->post_type ) );
			foreach ( $field_groups as $group ) {
				$fields = acf_get_fields( $group['key'] );
				if ( $fields ) {
					foreach ( $fields as $field ) {
						$acf_fields[ $field['name'] ] = array(
							'type' => $field['type'],
							'label' => $field['label'],
							'required' => ! empty( $field['required'] ),
						);
						if ( ! empty( $field['choices'] ) ) {
							$acf_fields[ $field['name'] ]['choices'] = $field['choices'];
						}
					}
				}
			}
		}

		return array(
			'post_type' => $post->post_type,
			'reference_post_id' => $post_id,
			'sections' => $sections,
			'placeholders' => $placeholders,
			'raw_template' => $raw_template,
			'acf_fields' => $acf_fields,
		);
	}

	/**
	 * Detect section name from block attributes or position.
	 */
	private static function detect_section_name( array $block, int $index ): string {
		// Try to detect from CSS class names
		$class = $block['attrs']['className'] ?? '';
		$bg = $block['attrs']['backgroundColor'] ?? '';
		$custom_bg = $block['attrs']['style']['color']['background'] ?? '';

		// Common section name patterns
		$section_names = array( 'challenge', 'insight', 'idea', 'result', 'hero', 'cta', 'features', 'metrics' );
		foreach ( $section_names as $name ) {
			if ( stripos( $class, $name ) !== false ) {
				return $name;
			}
		}

		// Fallback: use sequential names based on index
		$default_names = array( 'section_1', 'section_2', 'section_3', 'section_4', 'section_5', 'section_6' );
		return $default_names[ $index ] ?? 'section_' . ( $index + 1 );
	}

	/**
	 * Recursively walk a block tree and replace text content with placeholders.
	 * Modifies inner content and innerHTML with {{placeholder}} markers.
	 */
	private static function templatize_block( array $block, string $section_name, array &$placeholders, int &$index ): array {
		// Process inner blocks recursively
		if ( ! empty( $block['innerBlocks'] ) ) {
			foreach ( $block['innerBlocks'] as $i => $inner ) {
				$block['innerBlocks'][ $i ] = self::templatize_block( $inner, $section_name, $placeholders, $index );
			}
		}

		// Replace content in leaf blocks (blocks that contain actual text)
		$text_blocks = array( 'core/paragraph', 'core/heading', 'core/list-item', 'core/button' );

		if ( in_array( $block['blockName'], $text_blocks, true ) ) {
			$placeholder_name = $section_name . '_' . self::block_type_suffix( $block['blockName'] ) . '_' . $index;

			// Get the inner HTML content
			$content = $block['innerHTML'] ?? '';
			$stripped = strip_tags( $content );

			if ( trim( $stripped ) !== '' ) {
				// Determine placeholder type
				$type = 'string';
				if ( $block['blockName'] === 'core/list-item' ) {
					$type = 'string';
				}

				$placeholders[ $placeholder_name ] = array(
					'type' => $type,
					'section' => $section_name,
					'block_type' => $block['blockName'],
					'sample_value' => $stripped,
				);

				// Replace content in innerHTML and innerContent
				$placeholder_marker = '{{' . $placeholder_name . '}}';

				// Replace just the text content, preserving HTML tags
				$block['innerHTML'] = preg_replace(
					'/>(.*?)</s',
					'>' . $placeholder_marker . '<',
					$block['innerHTML'],
					1
				);

				if ( ! empty( $block['innerContent'] ) ) {
					foreach ( $block['innerContent'] as $ci => $c ) {
						if ( is_string( $c ) && trim( $c ) !== '' ) {
							$block['innerContent'][ $ci ] = preg_replace(
								'/>(.*?)</s',
								'>' . $placeholder_marker . '<',
								$c,
								1
							);
						}
					}
				}

				$index++;
			}
		}

		// Handle coblocks/feature blocks (icon + text)
		if ( $block['blockName'] === 'coblocks/feature' || $block['blockName'] === 'coblocks/features' ) {
			// These are container blocks - their content is in innerBlocks
			// The placeholder replacement happens in the recursive walk above
		}

		return $block;
	}

	/**
	 * Get a short suffix for a block type.
	 */
	private static function block_type_suffix( string $block_name ): string {
		$map = array(
			'core/paragraph' => 'text',
			'core/heading' => 'heading',
			'core/list-item' => 'item',
			'core/list' => 'list',
			'core/button' => 'button',
			'core/image' => 'image',
		);
		return $map[ $block_name ] ?? str_replace( array('core/', 'coblocks/'), '', $block_name );
	}

	/**
	 * Create a new post from a template by filling in placeholders.
	 *
	 * @param string $post_type Post type slug.
	 * @param string $title Post title.
	 * @param array $template_data Placeholder name => value pairs.
	 * @param array $acf_fields ACF field name => value pairs.
	 * @param string $status Post status (default 'draft').
	 * @return array|WP_Error Created post data with post_id and url.
	 */
	public static function create_from_template( string $post_type, string $title, array $template_data, array $acf_fields = array(), string $status = 'draft' ) {
		$option_key = 'wpmcp_template_' . sanitize_key( $post_type );
		$template = get_option( $option_key );

		if ( ! $template || empty( $template['raw_template'] ) ) {
			return new \WP_Error( 'no_template', 'No template found for post type: ' . $post_type );
		}

		$content = $template['raw_template'];

		// Replace all placeholders
		foreach ( $template_data as $placeholder => $value ) {
			if ( is_array( $value ) ) {
				// Array values are for repeated elements (list items, feature cards, metrics)
				// For now, join with newline for simple cases
				$value = implode( "\n", array_map( function( $v ) {
					return is_array( $v ) ? wp_json_encode( $v ) : (string) $v;
				}, $value ) );
			}
			$content = str_replace( '{{' . $placeholder . '}}', wp_kses_post( (string) $value ), $content );
		}

		// Remove any unfilled placeholders (replace with empty string)
		$content = preg_replace( '/\{\{[a-z0-9_]+\}\}/', '', $content );

		// Create the post
		$post_id = wp_insert_post( array(
			'post_type'    => sanitize_key( $post_type ),
			'post_title'   => sanitize_text_field( $title ),
			'post_content' => wp_kses_post( $content ),
			'post_status'  => sanitize_key( $status ),
		), true );

		if ( is_wp_error( $post_id ) ) {
			return $post_id;
		}

		// Set ACF fields if provided and ACF is active
		if ( ! empty( $acf_fields ) && function_exists( 'update_field' ) ) {
			foreach ( $acf_fields as $field_name => $value ) {
				update_field( $field_name, $value, $post_id );
			}
		}

		return array(
			'post_id' => $post_id,
			'url' => get_permalink( $post_id ),
		);
	}

	/**
	 * Save an extracted template to options.
	 */
	public static function save_template( string $post_type, array $template ): bool {
		$option_key = 'wpmcp_template_' . sanitize_key( $post_type );
		return update_option( $option_key, $template, false );
	}

	/**
	 * Get a saved template.
	 */
	public static function get_template( string $post_type ) {
		$option_key = 'wpmcp_template_' . sanitize_key( $post_type );
		return get_option( $option_key, false );
	}

	/**
	 * List all saved templates.
	 */
	public static function list_templates(): array {
		global $wpdb;
		$results = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s",
				'wpmcp_template_%'
			)
		);

		$templates = array();
		foreach ( $results as $option_name ) {
			$post_type = str_replace( 'wpmcp_template_', '', $option_name );
			$template = get_option( $option_name );
			if ( $template ) {
				$templates[] = array(
					'post_type' => $post_type,
					'reference_post_id' => $template['reference_post_id'] ?? 0,
					'placeholder_count' => count( $template['placeholders'] ?? array() ),
					'section_count' => count( $template['sections'] ?? array() ),
					'has_acf_fields' => ! empty( $template['acf_fields'] ),
				);
			}
		}

		return $templates;
	}
}
