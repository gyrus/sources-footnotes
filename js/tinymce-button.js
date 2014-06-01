(function() {
	tinymce.PluginManager.add(
		'sf_tinymce_button',
		function( editor, url ) {
			editor.addButton(
				'sf_tinymce_button',
				{
					text: 		'',
					icon: 		'sf-tinymce-button',
					tooltip:	'Insert footnote',
					onclick:	function() {
						tb_show( 'Insert footnote', '#TB_inline?height=640&inlineId=sf-insert-footnote-markup' );
						//tinymce.DOM.setStyle( ["TB_overlay", "TB_window", "TB_load"], "z-index", "999999" );
					}
				}
			);
		}
	);
})();