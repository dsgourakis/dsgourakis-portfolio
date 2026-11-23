<?php
/**
 * Main plugin class: connects every feature to WordPress.
 *
 * @package DSG\Core
 */

namespace DSG\Core;

use DSG\Core\Analytics\CloudflareAnalytics;
use DSG\Core\Blocks\Blocks;
use DSG\Core\Blocks\Copyright;
use DSG\Core\Blocks\PageLabel;
use DSG\Core\Contact\ContactEndpoint;
use DSG\Core\Contact\FallbackHandler;
use DSG\Core\Contact\Mail;
use DSG\Core\Contact\Messages;
use DSG\Core\Media\ImageFormats;
use DSG\Core\Navigation\ClientNavigation;
use DSG\Core\PostTypes\Project;
use DSG\Core\Seo;
use DSG\Core\Settings\ResumeLink;

/**
 * Boots the plugin. Each feature lives in its own class and registers its own hooks.
 */
final class Plugin {

	/**
	 * The single instance of this class.
	 *
	 * @var Plugin|null
	 */
	private static ?Plugin $instance = null;

	/**
	 * Whether init() has already run.
	 *
	 * @var bool
	 */
	private bool $initialized = false;

	/**
	 * Return the single instance.
	 *
	 * @return Plugin
	 */
	public static function instance(): Plugin {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Register all features. Safe to call more than once.
	 *
	 * @return void
	 */
	public function init(): void {
		if ( $this->initialized ) {
			return;
		}
		$this->initialized = true;

		( new Project() )->register_hooks();
		( new Blocks() )->register_hooks();
		( new Copyright() )->register_hooks();
		( new PageLabel() )->register_hooks();
		( new ClientNavigation() )->register_hooks();
		( new ImageFormats() )->register_hooks();
		( new ResumeLink() )->register_hooks();
		( new Messages() )->register_hooks();
		( new Mail() )->register_hooks();
		( new ContactEndpoint() )->register_hooks();
		( new FallbackHandler() )->register_hooks();
		( new CloudflareAnalytics() )->register_hooks();

		// SEO plugins print their own tags; never print them twice.
		if ( ! self::has_seo_plugin() ) {
			( new Seo\Fields() )->register_hooks();
			( new Seo\Settings() )->register_hooks();
			( new Seo\Head() )->register_hooks();
			( new Seo\Robots() )->register_hooks();
			( new Seo\Sitemap() )->register_hooks();
			( new Seo\Schema() )->register_hooks();
		}
	}

	/**
	 * Whether Yoast SEO or Rank Math is active.
	 *
	 * Plugins load in alphabetical order, so this plugin ("dsg-core") boots
	 * before "seo-by-rank-math" and "wordpress-seo" have defined anything.
	 * The check therefore looks at the active plugins list instead.
	 *
	 * @return bool
	 */
	public static function has_seo_plugin(): bool {
		$active = (array) get_option( 'active_plugins', array() );

		foreach ( array( 'wordpress-seo/wp-seo.php', 'wordpress-seo-premium/wp-seo-premium.php', 'seo-by-rank-math/rank-math.php' ) as $plugin ) {
			if ( in_array( $plugin, $active, true ) ) {
				return true;
			}
		}

		return false;
	}
}
