<?php
/**
 * Theme setup. A block theme needs very little PHP:
 * design lives in theme.json, layout in templates/ and parts/.
 *
 * @package DSG\Theme
 */

defined( 'ABSPATH' ) || exit;

/**
 * Load style.css on the frontend and in the editor.
 *
 * @return void
 */
function dsg_theme_enqueue_styles() {
	wp_enqueue_style(
		'dsg-theme',
		get_stylesheet_uri(),
		array(),
		wp_get_theme()->get( 'Version' )
	);
}
add_action( 'wp_enqueue_scripts', 'dsg_theme_enqueue_styles' );

/**
 * Theme supports.
 *
 * @return void
 */
function dsg_theme_setup() {
	add_editor_style( 'style.css' );

	// Pages use their excerpt as the intro text under the title (see templates/page.html).
	add_post_type_support( 'page', 'excerpt' );
}
add_action( 'after_setup_theme', 'dsg_theme_setup' );

/**
 * Names and descriptions for the theme's own templates in the Site Editor.
 *
 * WordPress names the standard templates (front-page, page, 404…) itself;
 * any other file would show up as its bare file name. Templates picked from
 * a page's Template panel (e.g. "Reading") are named in theme.json instead.
 *
 * @param array $types Template slug => title and description.
 * @return array
 */
function dsg_theme_template_types( $types ) {
	$types['single-dsg_project'] = array(
		'title'       => __( 'Single Project', 'dsg-theme' ),
		'description' => __( 'Case-study layout for one project: title, project details, cover image, "Built with" card and the next project.', 'dsg-theme' ),
	);

	$types['archive-dsg_project'] = array(
		'title'       => __( 'Projects Archive', 'dsg-theme' ),
		'description' => __( 'The Work page: every project as a card, with the technology filter.', 'dsg-theme' ),
	);

	$types['page-about'] = array(
		'title'       => __( 'About Page', 'dsg-theme' ),
		'description' => __( 'Used automatically for the page with the address "about". Big heading and intro above the page content.', 'dsg-theme' ),
	);

	$types['page-contact'] = array(
		'title'       => __( 'Contact Page', 'dsg-theme' ),
		'description' => __( 'Used automatically for the page with the address "contact". Big heading and intro above the page content; the footer\'s contact card is hidden here.', 'dsg-theme' ),
	);

	return $types;
}
add_filter( 'default_template_types', 'dsg_theme_template_types' );

/**
 * On pages, show the Excerpt block only when an excerpt was written.
 *
 * Without this, WordPress makes one up from the first words of the page
 * content, and the intro would repeat the text right below it.
 *
 * @param string   $block_content The block's HTML.
 * @param array    $block         The block's data.
 * @param WP_Block $instance      The block instance, which knows which post it's showing.
 * @return string
 */
function dsg_theme_page_excerpt_only_if_written( $block_content, $block, $instance ) {
	$post_id = $instance->context['postId'] ?? 0;

	if ( $post_id && 'page' === get_post_type( $post_id ) && ! has_excerpt( $post_id ) ) {
		return '';
	}

	return $block_content;
}
add_filter( 'render_block_core/post-excerpt', 'dsg_theme_page_excerpt_only_if_written', 10, 3 );

/**
 * Preload the main font, so the hero heading doesn't flash in the fallback.
 *
 * Only Schibsted Grotesk: it's the big text above the fold. JetBrains Mono
 * is used for small labels, and preloading it too would compete for bandwidth.
 * The @font-face rules themselves come from theme.json.
 *
 * @param array $resources Resources WordPress will preload.
 * @return array
 */
function dsg_theme_preload_fonts( $resources ) {
	$resources[] = array(
		'href'        => get_theme_file_uri( 'assets/fonts/schibsted-grotesk-latin-wght-normal.woff2' ),
		'as'          => 'font',
		'type'        => 'font/woff2',
		'crossorigin' => 'anonymous', // Fonts are always fetched in CORS mode; without this the preload is wasted.
	);

	return $resources;
}
add_filter( 'wp_preload_resources', 'dsg_theme_preload_fonts' );

/**
 * Extra block styles. Their CSS lives in style.css.
 *
 * @return void
 */
function dsg_theme_register_block_styles() {
	// A button that looks like a plain link, e.g. "See work ↓" next to a real button.
	register_block_style(
		'core/button',
		array(
			'name'  => 'text',
			'label' => __( 'Text link', 'dsg-theme' ),
		)
	);
}
add_action( 'init', 'dsg_theme_register_block_styles' );

/*
 * Light/dark toggle.
 *
 * Visitors get their system setting (prefers-color-scheme) until they pick
 * one with the toggle; that choice is saved in localStorage and wins from
 * then on. Dark is the fallback: theme.json holds the dark palette. In light,
 * <html> gets data-theme="light" and the light palette from
 * styles/light.json is swapped in as CSS variables. The button is printed by dsg-core's dsg/header-actions block and its script is in
 * assets/js/color-scheme.js.
 */

/**
 * Key the choice is saved under. The script module uses the same one.
 */
const DSG_THEME_SCHEME_KEY = 'dsg-color-scheme';

/**
 * Pick the scheme before anything is painted: the saved choice if there is
 * one, otherwise the system setting.
 *
 * Inline and first in <head>, so a visitor in light mode doesn't see a
 * flash of dark on every page load.
 *
 * @return void
 */
function dsg_theme_print_color_scheme_script() {
	wp_print_inline_script_tag(
		sprintf(
			'try{var s=localStorage.getItem(%s)}catch(e){}if(s==="light"||(s!=="dark"&&matchMedia("(prefers-color-scheme: light)").matches))document.documentElement.dataset.theme="light"',
			wp_json_encode( DSG_THEME_SCHEME_KEY )
		)
	);
}
add_action( 'wp_head', 'dsg_theme_print_color_scheme_script', 1 );

/**
 * CSS that swaps in the light palette when data-theme="light" is set.
 *
 * Built from styles/light.json, so the light colors are defined in one
 * place (the same file the Site Editor offers as the "Light" style).
 * `:root[data-theme]` is more specific than the `:root` WordPress prints
 * the dark palette on, so it wins regardless of load order.
 *
 * @return string
 */
function dsg_theme_light_scheme_css() {
	$light   = wp_json_file_decode( get_theme_file_path( 'styles/light.json' ), array( 'associative' => true ) );
	$palette = $light['settings']['color']['palette'] ?? array();

	$vars = '';
	foreach ( $palette as $color ) {
		$vars .= sprintf( '--wp--preset--color--%s:%s;', sanitize_key( $color['slug'] ), $color['color'] );
	}

	return ':root[data-theme="light"]{' . $vars . '}';
}

/**
 * Load the light palette and the toggle's script on the frontend.
 *
 * The script is a module so it keeps working with client-side navigation
 * (see docs/header-and-navigation.md).
 *
 * @return void
 */
function dsg_theme_enqueue_color_scheme() {
	wp_add_inline_style( 'dsg-theme', dsg_theme_light_scheme_css() );

	wp_enqueue_script_module(
		'dsg-theme/color-scheme',
		get_theme_file_uri( 'assets/js/color-scheme.js' ),
		array(),
		wp_get_theme()->get( 'Version' )
	);
}
add_action( 'wp_enqueue_scripts', 'dsg_theme_enqueue_color_scheme' );
