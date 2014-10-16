/**
 * Public-facing JavaScript
 */


/* Trigger when DOM has loaded */
jQuery( document ).ready( function( $ ) {
	var sfn = $( 'a', '.sf-number' );

	// Note given on hover
	sfn.tooltip({
		content: function() {
			var el = $( this );
			var im = el.parents( '.sf-post' ).attr( 'class' ).match( /sf\-instance\-([0-9]+)/ );
			var i  = im[1];
			var id = sf_get_string_part( el.parent( '.sf-number' ).attr( 'id' ) );
			var note = '';
			if ( i && id ) {
				note = $( '#sf-instance-' + i + '-note-' + id ).clone().contents();
				note.splice( -1, 1 );
			}
			return note;
		},
		tooltipClass: 'sf-tooltip',
		// Hack to keep visible on mouseover: http://stackoverflow.com/a/15014759/1087660
		show: null, // show immediately
		hide: {
			effect: "" // fadeOut
		},
		close: function( event, ui ) {
			ui.tooltip.hover(
				function () {
					$(this).stop(true).fadeTo(400, 1);
				},
				function () {
					$(this).fadeOut("400", function(){ $(this).remove(); })
				}
			);
		}
	});

});


/**
 * Get a part of a string
 *
 * @since	Source_Footnotes 0.1
 * @param	{string}		s		The string
 * @param	{number|string}	i		The numeric index, or 'first' or 'last' (default 'last')
 * @param	{string}		sep		The character used a separator in the passed string (default '-')
 * @return	{string}
 */
function sf_get_string_part( s, i, sep ) {
	var parts;
	if ( ! sep ) {
		sep = '-';
	}
	parts = s.split( sep );
	if ( ! i || i == 'last' ) {
		i = parts.length - 1;
	} else if ( i == 'first' ) {
		i = 0;
	}
	return parts[ i ];
}
