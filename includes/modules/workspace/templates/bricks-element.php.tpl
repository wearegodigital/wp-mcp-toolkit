<?php
/**
 * Bricks element: {{ELEMENT_LABEL}}
 *
 * @package WP_MCP_Workspace
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( '\Bricks\Element' ) ) {
	return;
}

class {{ELEMENT_CLASS}} extends \Bricks\Element {

	public $category     = '{{ELEMENT_CATEGORY}}';
	public $name         = '{{ELEMENT_NAME}}';
	public $icon         = '{{ELEMENT_ICON}}';
	public $css_selector = '.{{ELEMENT_CSS_SELECTOR}}';

	public function get_label(): string {
		return '{{ELEMENT_LABEL}}';
	}

	public function set_controls(): void {
		{{ELEMENT_CONTROLS}}
	}

	public function render(): void {
		$root_attributes = $this->set_root_attributes();
		echo "<div {$root_attributes}>";
		{{ELEMENT_RENDER}}
		echo '</div>';
	}
}
