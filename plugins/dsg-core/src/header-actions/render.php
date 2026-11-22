<?php
/**
 * Header Actions: the frontend markup.
 *
 * Wide screens: the menu (inner Navigation block, with the sliding pill),
 * the Resume button and the light/dark toggle in one row.
 *
 * Phones and tablets (below 782px): the Navigation is hidden and a menu
 * button opens a full-screen <dialog> instead: logo and close button on top, big numbered
 * links, then email, Resume and the toggle at the bottom. Its links are
 * read from the same Navigation block, so the two menus can't drift apart.
 *
 * The buttons open and close the dialog with HTML invoker commands
 * (commandfor/command), so it works before any script runs; view.js adds a
 * fallback for older browsers. The client-navigation script closes it after
 * a link is used.
 *
 * The toggle's script and light palette belong to the theme
 * (assets/js/color-scheme.js and functions.php); this only prints the
 * buttons. They ship `hidden` and the script shows them, so visitors
 * without JavaScript never see a button that does nothing.
 *
 * Available variables (WordPress provides them):
 * - $attributes: the block's data ("email")
 * - $content:    the Navigation block's HTML, already rendered
 * - $block:      the WP_Block instance (its inner blocks hold the menu links)
 *
 * @package DSG\Core
 */

use DSG\Core\Navigation\ClientNavigation;
use DSG\Core\Settings\ResumeLink;

$resume_url = ResumeLink::url();
$email      = sanitize_email( $attributes['email'] ?? '' );
$page_path  = ClientNavigation::current_path();

// The links of the Navigation block inside, for the phone menu.
$menu_links = array();
foreach ( $block->inner_blocks as $inner ) {
	if ( 'core/navigation' !== $inner->name ) {
		continue;
	}
	foreach ( $inner->inner_blocks as $item ) {
		$url = $item->attributes['url'] ?? '';
		if ( 'core/navigation-link' !== $item->name || ! $url ) {
			continue;
		}
		$link_path    = ClientNavigation::path_of( $url );
		$menu_links[] = array(
			'label'   => wp_strip_all_tags( $item->attributes['label'] ?? '' ),
			'url'     => $url,
			'current' => null !== $link_path && ClientNavigation::is_current_path( $link_path, $page_path ),
		);
	}
}

$wrapper_attributes = get_block_wrapper_attributes( array( 'class' => 'site-header__actions' ) );

/**
 * The light/dark toggle. Printed twice: in the header and in the phone menu.
 * color-scheme.js keeps every copy in step.
 */
$color_toggle = static function () {
	?>
	<button type="button" class="color-scheme-toggle" aria-label="<?php esc_attr_e( 'Dark mode', 'dsg-core' ); ?>" aria-pressed="true" hidden>
		<svg class="color-scheme-toggle__sun" aria-hidden="true" focusable="false" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41"/></svg>
		<svg class="color-scheme-toggle__moon" aria-hidden="true" focusable="false" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
	</button>
	<?php
};

/**
 * The Resume button, or nothing when no resume is set.
 */
$resume_button = static function () use ( $resume_url ) {
	if ( ! $resume_url ) {
		return;
	}
	?>
	<a class="site-header__resume wp-element-button" href="<?php echo esc_url( $resume_url ); ?>" target="_blank" rel="noopener">
		<?php esc_html_e( 'Resume', 'dsg-core' ); ?> <span aria-hidden="true">↗</span><span class="screen-reader-text"><?php esc_html_e( ' (PDF, opens in a new tab)', 'dsg-core' ); ?></span>
	</a>
	<?php
};
?>
<div <?php echo wp_kses_data( $wrapper_attributes ); ?>>
	<?php if ( $menu_links ) : ?>
		<button type="button" class="site-menu__open" commandfor="site-menu" command="show-modal" aria-haspopup="dialog">
			<svg aria-hidden="true" focusable="false" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M4 9h16M4 15h16"/></svg>
			<span class="screen-reader-text"><?php esc_html_e( 'Menu', 'dsg-core' ); ?></span>
		</button>
	<?php endif; ?>

	<?php
	// The Navigation block escaped its own output.
	echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

	$resume_button();
	$color_toggle();
	?>

	<?php if ( $menu_links ) : ?>
		<dialog id="site-menu" class="site-menu" aria-label="<?php esc_attr_e( 'Menu', 'dsg-core' ); ?>">
			<div class="site-menu__bar">
				<a class="site-logo" href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home" aria-label="<?php esc_attr_e( 'Dimitris Sgourakis, home', 'dsg-core' ); ?>">D<span class="site-logo__dot">.</span>S</a>
				<button type="button" class="site-menu__close" commandfor="site-menu" command="close">
					<svg aria-hidden="true" focusable="false" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M6 6l12 12M18 6L6 18"/></svg>
					<span class="screen-reader-text"><?php esc_html_e( 'Close menu', 'dsg-core' ); ?></span>
				</button>
			</div>

			<nav class="site-menu__nav" aria-label="<?php esc_attr_e( 'Main', 'dsg-core' ); ?>">
				<ol class="site-menu__links">
					<?php foreach ( $menu_links as $index => $menu_link ) : ?>
						<li class="site-menu__item<?php echo $menu_link['current'] ? ' current-menu-item' : ''; ?>" style="--i:<?php echo (int) $index; ?>">
							<a class="site-menu__link" href="<?php echo esc_url( $menu_link['url'] ); ?>"<?php echo $menu_link['current'] ? ' aria-current="page"' : ''; ?>>
								<span class="site-menu__num" aria-hidden="true"><?php echo esc_html( sprintf( '%02d', $index + 1 ) ); ?></span>
								<?php echo esc_html( $menu_link['label'] ); ?>
							</a>
						</li>
					<?php endforeach; ?>
				</ol>
			</nav>

			<div class="site-menu__footer" style="--i:<?php echo count( $menu_links ); ?>">
				<?php if ( $email ) : ?>
					<p class="site-menu__label">// <?php esc_html_e( 'get in touch', 'dsg-core' ); ?></p>
					<a class="site-menu__email" href="<?php echo esc_url( 'mailto:' . $email ); ?>"><?php echo esc_html( $email ); ?></a>
				<?php endif; ?>
				<div class="site-menu__actions">
					<?php
					$resume_button();
					$color_toggle();
					?>
				</div>
			</div>
		</dialog>
	<?php endif; ?>
</div>
