( function( blocks, element, serverSideRender, blockEditor, components ) {
	var el = element.createElement;
	var ServerSideRender = serverSideRender;
	var useBlockProps = blockEditor.useBlockProps;
	var InspectorControls = blockEditor.InspectorControls;
	var PanelBody = components.PanelBody;

	blocks.registerBlockType( 'wpmcp-workspace/{{BLOCK_NAME}}', {
		edit: function( props ) {
			var blockProps = useBlockProps();
			var setAttributes = props.setAttributes;

			return el(
				element.Fragment,
				null,
				{{INSPECTOR_CONTROLS}}
				el(
					'div',
					blockProps,
					el( ServerSideRender, {
						block: 'wpmcp-workspace/{{BLOCK_NAME}}',
						attributes: props.attributes,
					} )
				)
			);
		},
		save: function() {
			return null;
		},
	} );
} )( window.wp.blocks, window.wp.element, window.wp.serverSideRender, window.wp.blockEditor, window.wp.components );
