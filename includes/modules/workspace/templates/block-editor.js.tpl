( function( blocks, element, serverSideRender, blockEditor ) {
	var el = element.createElement;
	var ServerSideRender = serverSideRender;
	var useBlockProps = blockEditor.useBlockProps;

	blocks.registerBlockType( 'wpmcp-workspace/{{BLOCK_NAME}}', {
		edit: function( props ) {
			var blockProps = useBlockProps();
			return el(
				'div',
				blockProps,
				el( ServerSideRender, {
					block: 'wpmcp-workspace/{{BLOCK_NAME}}',
					attributes: props.attributes,
				} )
			);
		},
		save: function() {
			return null; // Server-side rendered.
		},
	} );
} )( window.wp.blocks, window.wp.element, window.wp.serverSideRender, window.wp.blockEditor );
