<?php

/**
 * Represents the view for the administration dashboard.
 *
 * This includes the header, options, and other information that should provide
 * The User Interface to the end user.
 *
 * @package   Sources_Footnotes
 * @author    Steve Taylor
 * @license   GPL-2.0+
 */

?>

<div class="wrap">

	<h2><?php echo esc_html( get_admin_page_title() ); ?></h2>

	<?php if ( isset( $_GET['done'] ) ) { ?>
		<div class="updated"><p><strong><?php _e( 'Settings updated successfully.' ); ?></strong></p></div>
	<?php } ?>

	<form method="post" action="">

		<?php wp_nonce_field( $this->plugin_slug . '_settings', $this->plugin_slug . '_settings_admin_nonce' ); ?>

		<table class="form-table">
			<tbody>
				<tr valign="top">
					<th scope="row"><label for="<?php echo $this->plugin_slug . '-footnotes_post_types'; ?>"><?php _e( 'Post types to make footnotes available for' ); ?></label></th>
					<td>
						<select name="footnotes_post_types[]" id="<?php echo $this->plugin_slug . '-footnotes_post_types'; ?>" class="regular-text" multiple="multiple">
							<?php foreach ( $this->get_eligible_post_types() as $pt_name => $pt_label ) { ?>
								<option value="<?php echo $pt_name; ?>"<?php if ( in_array( $pt_name, $this->settings['footnotes_post_types'] ) ) echo ' selected="selected"'; ?>><?php echo $pt_label; ?></option>
							<?php } ?>
						</select>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row"><label for="<?php echo $this->plugin_slug . '-auto_list_footnotes'; ?>"><?php _e( 'Automatically list footnotes at end of content' ); ?></label></th>
					<td>
						<input type="checkbox" name="auto_list_footnotes" id="<?php echo $this->plugin_slug . '-auto_list_footnotes'; ?>" value="1"<?php checked( $this->settings['auto_list_footnotes'] ); ?>>
						<p class="description"><?php _e( 'If you don\'t want footnotes listed automatically, use the <code>[list_footnotes]</code> shortcode or the <code>sf_list_footnotes()</code> function.', $this->plugin_slug ); ?></p>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row"><label for="<?php echo $this->plugin_slug . '-footnotes_wrapper_tag'; ?>"><?php _e( 'Footnotes wrapper tag' ); ?></label></th>
					<td>
						<select name="footnotes_wrapper_tag" id="<?php echo $this->plugin_slug . '-footnotes_wrapper_tag'; ?>" class="regular-text">
							<?php foreach ( array( 'aside', 'footer', 'div' ) as $wrapper_tag ) { ?>
								<option value="<?php echo $wrapper_tag; ?>"<?php selected( $wrapper_tag, $this->settings['footnotes_wrapper_tag'] ); ?>><?php echo $wrapper_tag; ?></option>
							<?php } ?>
						</select>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row"><label for="<?php echo $this->plugin_slug . '-list_footnotes_heading'; ?>"><?php _e( 'Heading for footnotes list' ); ?></label></th>
					<td><input type="text" name="list_footnotes_heading" id="<?php echo $this->plugin_slug . '-list_footnotes_heading'; ?>" value="<?php echo esc_attr( $this->settings['list_footnotes_heading'] ); ?>"></td>
				</tr>
				<tr valign="top">
					<th scope="row"><label for="<?php echo $this->plugin_slug . '-before_number'; ?>"><?php _e( 'Before numbers' ); ?></label></th>
					<td><input type="text" name="before_number" id="<?php echo $this->plugin_slug . '-before_number'; ?>" value="<?php echo esc_attr( $this->settings['before_number'] ); ?>" placeholder="e.g. ("></td>
				</tr>
				<tr valign="top">
					<th scope="row"><label for="<?php echo $this->plugin_slug . '-after_number'; ?>"><?php _e( 'After numbers' ); ?></label></th>
					<td><input type="text" name="after_number" id="<?php echo $this->plugin_slug . '-after_number'; ?>" value="<?php echo esc_attr( $this->settings['before_number'] ); ?>" placeholder="e.g. )"></td>
				</tr>
				<tr valign="top">
					<th scope="row"><label for="<?php echo $this->plugin_slug . '-ibid'; ?>"><?php _e( 'Use Ibid. and op. cit.?' ); ?></label></th>
					<td>
						<input type="checkbox" name="ibid" id="<?php echo $this->plugin_slug . '-ibid'; ?>" value="1"<?php checked( $this->settings['ibid'] ); ?>>
						<p class="description"><?php _e( 'If Ibid. isn\'t used, repeated source references will be abbreviated.', $this->plugin_slug ); ?></p>
					</td>
				</tr>
			</tbody>
		</table>

		<p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary" value="Save settings"></p>

	</form>

</div>
