/**
 * Admin JavaScript
 */

/* Trigger when DOM has loaded */
jQuery( document ).ready( function( $ ) {
	var body = $( 'body' );

	// Source edit screen
	if ( body.hasClass( 'post-type-sf_source' ) ) {

		// Move CMB2 box above content
		$( '#sources_footnotes_details_box' ).each( function() {
			$( this )
				.detach()
				.css( 'margin', '20px 0 0' )
				.insertAfter('#titlediv');
		});

		// Checks when form submitted
		$( 'form#post' ).on( 'submit', function( e ) {
			var checked = $( '#taxonomy-sf_source_type' ).find( 'input[name^=tax_input]:checked' ).length;
			var submit = true;

			// Source type required
			if ( ! checked ) {
				alert( 'Please select a source type.' );
				submit = false;
			} else if ( checked > 1 ) {
				alert( 'Please select only one source type.' );
				submit = false;
			}

			if ( ! submit ) {
				e.preventDefault();
			}

		});

		// Hint for author taxonomy
		$( '#sf_author' ).find( '.howto' ).after( '<p class="howto">If no author is set, the source will be classed as anonymous.</p>' );

	}

});

