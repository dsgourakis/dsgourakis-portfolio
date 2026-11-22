<?php
/**
 * Plugin Name:       DSG Core
 * Plugin URI:        https://www.dsgourakis.com
 * Description:       Custom post types, blocks and site functionality for dsgourakis.com.
 * Version:           0.9.1
 * Requires at least: 6.8
 * Requires PHP:      8.2
 * Author:            Dimitris Sgourakis
 * Author URI:        https://www.dsgourakis.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       dsg-core
 *
 * @package DSG\Core
 */

defined( 'ABSPATH' ) || exit;

define( 'DSG_CORE_VERSION', '0.9.1' );
define( 'DSG_CORE_FILE', __FILE__ );
define( 'DSG_CORE_DIR', plugin_dir_path( __FILE__ ) );

$dsg_core_autoloader = DSG_CORE_DIR . 'vendor/autoload.php';

// Composer creates vendor/autoload.php. Without it, show a notice instead of a fatal error.
if ( ! file_exists( $dsg_core_autoloader ) ) {
	add_action(
		'admin_notices',
		static function () {
			printf(
				'<div class="notice notice-error"><p>%s</p></div>',
				esc_html__( 'DSG Core: run "composer install" to generate the autoloader.', 'dsg-core' )
			);
		}
	);
	return;
}

require_once $dsg_core_autoloader;

\DSG\Core\Plugin::instance()->init();
