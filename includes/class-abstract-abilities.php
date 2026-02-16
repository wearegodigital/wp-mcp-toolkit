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

			wp_register_ability(
				$slug,
				array(
					'label'               => $def['label'],
					'description'         => $def['description'],
					'category'            => $def['category'],
					'input_schema'        => $def['input_schema'],
					'output_schema'       => $def['output_schema'],
					'execute_callback'    => array( $this, $def['callback'] ),
					'permission_callback' => $permission_callback,
					'meta'                => array(
						'annotations'  => array(
							'readonly'    => $readonly,
							'destructive' => $destructive,
							'idempotent'  => $idempotent,
						),
						'show_in_rest' => true,
					),
				)
			);
		}
	}

	/**
	 * Helper: empty input schema (no parameters).
	 */
	protected static function empty_input_schema(): array {
		return array(
			'type'                 => 'object',
			'properties'           => new \stdClass(),
			'additionalProperties' => false,
		);
	}

	/**
	 * Helper: normalize input to array.
	 */
	protected static function normalize_input( $input ): array {
		return is_array( $input ) ? $input : (array) $input;
	}
}
