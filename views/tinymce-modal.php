<?php

/**
 * The Insert footnote modal window
 *
 * @package   Sources_Footnotes
 * @author    Steve Taylor
 * @license   GPL-2.0+
 */
global $post;

?>

<div id="sf-insert-footnote-markup" style="display: none;">
<form action="" id="sf-insert-footnote-form">

	<div class="sf-field">
		<label for="sf-source-id" class="label"><?php _e( 'Source', $this->plugin_slug ); ?></label>
		<select name="sf_source_id" id="sf-source-id" class="input">

			<option value="">[<?php _e( 'No source', $this->plugin_slug ); ?>]</option>

			<?php

			// Get source types
			$sf_source_types = get_terms( 'sf_source_type' );

			// Loop through source types
			foreach ( $sf_source_types as $sf_source_type ) {

				// Get sources for this type
				$sf_sources = new WP_Query( array(
					'post_type'			=> 'sf_source',
					'posts_per_page'	=> -1,
					'orderby'			=> 'title',
					'order'				=> 'ASC',
					'tax_query'			=> array(
						array(
							'taxonomy'	=> 'sf_source_type',
							'field'		=> 'term_id',
							'terms'		=> $sf_source_type->term_id
						)
					)
				));

				// Are there any?
				if ( $sf_sources->have_posts() ) {

					?>
					<optgroup label="<?php echo $sf_source_type->name; ?>">
					<?php

					while ( $sf_sources->have_posts() ) {
						$sf_sources->the_post();
						?>
						<option value="<?php the_ID(); ?>"><?php
							the_title();

							// Output some meta
							echo ' (';

							// Get authors
							$sf_authors = get_the_terms( $post, 'sf_author' );
							if ( $sf_authors ) {
								foreach ( $sf_authors as $sf_author ) {
									echo $sf_author->name;
									if ( count( $sf_authors ) > 1 ) {
										echo ' et al.';
									}
									echo ', ';
									break;
								}
							}

							// Date
							if ( function_exists( 'slt_cf_field_value' ) ) {
								echo slt_cf_field_value( 'sf-source-year' );
							}

							echo ')';

						?></option>
						<?php
					}

					?>
					</optgroup>
					<?php

				}

				// Reset query
				wp_reset_postdata();

			}

			?>

		</select>
	</div>

	<div class="sf-field">
		<label for="sf-source-page" class="label"><?php _e( 'Page', $this->plugin_slug ); ?></label>
		<input type="text" name="sf_source_page" id="sf-source-page" class="input" placeholder="e.g. 23-46">
	</div>

	<div class="sf-field">
		<label for="sf-quoted" class="label"><input type="checkbox" name="sf_quoted" id="sf-quoted" value="1"> <?php _e( 'Quoted in source', $this->plugin_slug ); ?></label>
	</div>

	<div class="sf-field">
		<label for="sf-note" class="label"><?php _e( 'Note', $this->plugin_slug ); ?></label>
		<textarea name="sf_note" id="sf-note" class="input" rows="8" cols="50"></textarea>
		<?php /*
		<p class="note"><strong>HTML allowed:</strong> <code>&lt;a href=""&gt;</code>, <code>&lt;i&gt;</code>, <code>&lt;b&gt;</code>, <code>&lt;em&gt;</code>, <code>&lt;strong&gt;</code></p>
		*/ ?>
	</div>

	<div class="sf-buttons">
		<a href="#" class="button button-primary button-large" id="sf-insert-button"><?php _e( 'Insert into text', $this->plugin_slug ); ?></a>
	</div>

</form>
</div>

<script>

	jQuery( document ).ready( function( $ ) {
		$( '#sf-insert-button' ).on( 'click', function( e ) {
			e.preventDefault();
			sf_insert_footnote();
		});
	});

	function sf_insert_footnote() { jQuery( function( $ ) {
		if ( typeof window.send_to_editor == 'function' ) {
			var source_id = $( '#sf-source-id' ).val();
			var page = $.trim( $( '#sf-source-page' ).val() );
			var quoted = $( '#sf-quoted' ).is( ':checked' );
			var note = $.trim( $( '#sf-note' ).val() );
			var footnote = '[sf_footnote';
			if ( source_id ) {
				footnote += ' source="' + source_id + '"';
			}
			if ( page ) {
				footnote += ' page="' + page + '"';
			}
			if ( quoted ) {
				footnote += ' quoted="yes"';
			}
			if ( note ) {
				footnote += ']' + note + '[/sf_footnote]';
			} else {
				footnote += ' /]';
			}
			if ( footnote != '[sf_footnote]' ) {
				$( '.sf-field .input' ).val( '' );
				window.send_to_editor( footnote );
			}
		}
	}); }

</script>
