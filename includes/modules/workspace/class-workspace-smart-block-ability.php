<?php
/**
 * Workspace Smart Block ability — selects scaffold method based on site setting and block complexity.
 *
 * @package WP_MCP_Toolkit
 * @since   2.2.0
 */

defined( 'ABSPATH' ) || exit;

class WP_MCP_Toolkit_Workspace_Smart_Block_Ability extends WP_MCP_Toolkit_Abstract_Abilities {

	use WP_MCP_Toolkit_Workspace_Helpers;

	protected function get_abilities(): array {
		$c = 'wpmcp-workspace-blocks';
		$p = 'manage_options';
		$s = fn( $r, $pr ) => [ 'type' => 'object', 'required' => $r, 'properties' => $pr, 'additionalProperties' => false ];
		$o = fn( $pr ) => [ 'type' => 'object', 'properties' => $pr ];
		$w = fn( $cb, $ro, $dest, $idemp ) => [ 'callback' => $cb, 'permission' => $p, 'readonly' => $ro, 'destructive' => $dest, 'idempotent' => $idemp ];

		return [
			'wpmcp-workspace/scaffold-block-smart' => [
				'label'         => __( 'Smart Block Scaffold', 'wp-mcp-toolkit' ),
				'description'   => __( 'Scaffolds a Gutenberg block using the site\'s configured method (vanilla or ACF), or recommends the best method based on block complexity.', 'wp-mcp-toolkit' ),
				'category'      => $c,
				'input_schema'  => $s( [ 'block_name', 'title' ], [
					'block_name'    => [ 'type' => 'string', 'description' => 'Kebab-case block name.' ],
					'title'         => [ 'type' => 'string' ],
					'description'   => [ 'type' => 'string', 'default' => '' ],
					'category'      => [ 'type' => 'string', 'default' => 'widgets' ],
					'icon'          => [ 'type' => 'string', 'default' => 'block-default' ],
					'method'        => [ 'type' => 'string', 'default' => '', 'description' => 'Override: "vanilla", "acf", or empty to use site setting.' ],
					// Vanilla block params.
					'attributes'    => [ 'type' => 'object', 'default' => [] ],
					'render_php'    => [ 'type' => 'string', 'default' => '' ],
					'css'           => [ 'type' => 'string', 'default' => '' ],
					// ACF block params.
					'fields'        => [ 'type' => 'array', 'default' => [] ],
					'field_storage' => [ 'type' => 'string', 'default' => 'php' ],
				] ),
				'output_schema' => $o( [
					// If scaffolded:
					'block_name'     => [ 'type' => 'string' ],
					'files'          => [ 'type' => 'array' ],
					'registration'   => [ 'type' => 'string' ],
					'method_used'    => [ 'type' => 'string' ],
					// If recommending:
					'recommendation' => [ 'type' => 'string' ],
					'reasoning'      => [ 'type' => 'string' ],
					'confirm_with'   => [ 'type' => 'string' ],
				] ),
			] + $w( 'execute_scaffold_block_smart', false, false, true ),
		];
	}

	// -- Helpers --------------------------------------------------------------

	/**
	 * Check if the fields array contains complex ACF field types or many fields.
	 *
	 * @param array $fields Fields input array.
	 * @return bool True if ACF would be a better fit.
	 */
	private static function fields_suggest_acf( array $fields ): bool {
		$complex_types = [ 'repeater', 'image', 'wysiwyg', 'gallery', 'relationship', 'flexible_content' ];

		foreach ( $fields as $field ) {
			if ( isset( $field['type'] ) && in_array( $field['type'], $complex_types, true ) ) {
				return true;
			}
		}

		return count( $fields ) >= 5;
	}

	// -- Execute methods ------------------------------------------------------

	/**
	 * Dispatch to vanilla or ACF scaffold and tag the result.
	 */
	private function dispatch( string $method, array $input ): array|\WP_Error {
		if ( 'acf' === $method ) {
			$result = ( new WP_MCP_Toolkit_Workspace_ACF_Blocks_Abilities() )->execute_scaffold_acf_block( $input );
		} else {
			$result = ( new WP_MCP_Toolkit_Workspace_Blocks_Abilities() )->execute_scaffold_block( $input );
			$method = 'vanilla';
		}
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		$result['method_used'] = $method;
		return $result;
	}

	/**
	 * Scaffold a block using the site's configured method, or return a recommendation.
	 *
	 * @since 2.2.0
	 * @param mixed $input Ability input.
	 * @return array|\WP_Error
	 */
	public function execute_scaffold_block_smart( $input = [] ): array|\WP_Error {
		$input = self::normalize_input( $input );

		// 1. Determine method.
		$explicit_method = sanitize_key( $input['method'] ?? '' );
		$setting         = sanitize_key( get_option( 'wpmcp_block_method', 'recommended' ) );
		$acf_available   = class_exists( 'ACF' );

		$method = '' !== $explicit_method ? $explicit_method : $setting;

		// If ACF not available, force vanilla.
		if ( ! $acf_available && in_array( $method, [ 'acf', 'agent-decides', 'recommended' ], true ) ) {
			$method = 'vanilla';
		}

		$fields = isset( $input['fields'] ) && is_array( $input['fields'] ) ? $input['fields'] : [];

		// 2. Route based on resolved method.
		if ( 'vanilla' === $method || 'acf' === $method ) {
			return $this->dispatch( $method, $input );
		}

		if ( 'agent-decides' === $method ) {
			return $this->dispatch( self::fields_suggest_acf( $fields ) ? 'acf' : 'vanilla', $input );
		}

		// Default: recommend without building.
		if ( self::fields_suggest_acf( $fields ) ) {
			return [
				'recommendation' => 'acf',
				'reasoning'      => 'This block has complex field types or many fields. ACF blocks provide a better editing experience with native field UI, visual preview, and easier maintenance.',
				'confirm_with'   => 'wpmcp-workspace/scaffold-acf-block',
			];
		}

		return [
			'recommendation' => 'vanilla',
			'reasoning'      => 'This block has simple attributes. A vanilla Gutenberg block is lighter weight and has no ACF dependency.',
			'confirm_with'   => 'wpmcp-workspace/scaffold-block',
		];
	}
}
