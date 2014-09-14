<?php

/**
 * Sources and Footnotes
 *
 * @package   Sources_Footnotes
 * @author    Steve Taylor
 * @license   GPL-2.0+
 *
 * @wordpress-plugin
 * Plugin Name:			Sources and Footnotes
 * Description:			A WordPress plugin for managing sources and footnotes.
 * Version:				0.2
 * Author:				Steve Taylor
 * Text Domain:			sources-footnotes-locale
 * License:				GPL-2.0+
 * License URI:			http://www.gnu.org/licenses/gpl-2.0.txt
 * Domain Path:			/lang
 * GitHub Plugin URI:	https://github.com/gyrus/sources-footnotes
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

require_once( plugin_dir_path( __FILE__ ) . 'class-sources-footnotes.php' );

// Register hooks that are fired when the plugin is activated, deactivated, and uninstalled, respectively.
register_activation_hook( __FILE__, array( 'Sources_Footnotes', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'Sources_Footnotes', 'deactivate' ) );

Sources_Footnotes::get_instance();


/* Easy-access functions for theme templates
*******************************************************************************/

/**
 * List footnotes
 *
 * @since	0.1
 * @param	bool	$echo
 * @return	mixed
 */
function sf_list_footnotes( $echo = true ) {
	$SF = Sources_Footnotes::get_instance();

	$output = $SF->list_footnotes( false );

	if ( $echo ) {
		echo $output;
	} else {
		return $output;
	}

}