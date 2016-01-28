<?php
/**
 * Plugin Name: Creative Church Credit System
 * Plugin URI:  http://wordpress.org/extend/plugins
 * Description: Integrate credit system into membership roles to be used for downloads.
 * Version:     0.1.0
 * Author:      Tanner Moushey
 * Author URI:  http://tannermoushey.com
 * License:     GPLv2+
 * Text Domain: cccs
 * Domain Path: /languages
 */

/**
 * Copyright (c) 2015 Tanner Moushey (email : tanner@iwitnessdesign.com)
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2 or, at
 * your discretion, any later version, as published by the Free
 * Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

/**
 * Built using grunt-wp-plugin
 * Copyright (c) 2013 10up, LLC
 * https://github.com/10up/grunt-wp-plugin
 */

// Useful global constants
define( 'CCCS_VERSION', '0.1.0' );
define( 'CCCS_URL',     plugin_dir_url( __FILE__ ) );
define( 'CCCS_PATH',    dirname( __FILE__ ) . '/' );

require_once( CCCS_PATH . 'includes/settings.php'     );
require_once( CCCS_PATH . 'includes/user-credits.php' );
require_once( CCCS_PATH . 'includes/shortcodes.php'   );
require_once( CCCS_PATH . 'includes/downloads.php'     );

/**
 * Default initialization for the plugin:
 * - Registers the default textdomain.
 */
function cccs_init() {
	$locale = apply_filters( 'plugin_locale', get_locale(), 'cccs' );
	load_textdomain( 'cccs', WP_LANG_DIR . '/cccs/cccs-' . $locale . '.mo' );
	load_plugin_textdomain( 'cccs', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
}
add_action( 'init', 'cccs_init' );

/**
 * Activate the plugin
 */
function cccs_activate() {
	// First load the init scripts in case any rewrite functionality is being loaded
	cccs_init();

	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'cccs_activate' );

/**
 * Deactivate the plugin
 * Uninstall routines should be in uninstall.php
 */
function cccs_deactivate() {

}
register_deactivation_hook( __FILE__, 'cccs_deactivate' );