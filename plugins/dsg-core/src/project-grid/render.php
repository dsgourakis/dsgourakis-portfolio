<?php
/**
 * Project Grid: the frontend markup.
 *
 * Queries published Projects and prints each one as a Project Card block,
 * so the grid reuses the card's markup, styles, numbering and featured
 * layout instead of duplicating them. A Project ticked as "Featured" gets
 * the large layout when it comes first; otherwise every card is the same size.
 *
 * With "showFilter" on (the Portfolio page), it shows every Project with
 * a row of technology chips above. The chips are plain links back to the
 * same page with ?tech=<slug>, so filtering works without JavaScript and
 * a filtered view can be shared. With JavaScript, dsg-core's client-side
 * navigation swaps the results in place without a reload, keeping the
 * scroll position and focus on the chip (assets/client-navigation/).
 * The chips stay on one line and scroll sideways when there are more than
 * fit; view.js fades the edges and keeps the chosen chip in view.
 *
 * Available variables (WordPress provides them):
 * - $attributes: the block's data, as defined in block.json
 * - $content:    inner blocks' HTML (unused here)
 * - $block:      the WP_Block instance
 *
 * @package DSG\Core
 */

use DSG\Core\PostTypes\Project;

$show_filter     = ! empty( $attributes['showFilter'] );
$number_of_items = max( 1, min( 12, (int) ( $attributes['numberOfItems'] ?? 3 ) ) );
$technology      = (int) ( $attributes['technology'] ?? 0 );

// The filter's chips: technologies that have at least one published Project.
$filter_terms = array();
$active_term  = null;

if ( $show_filter ) {
	$filter_terms = get_terms(
		array(
			'taxonomy'   => Project::TAXONOMY,
			'hide_empty' => true,
			'orderby'    => 'name',
		)
	);
	$filter_terms = is_wp_error( $filter_terms ) ? array() : $filter_terms;

	// Read-only filter from the URL: no nonce needed. Unknown slugs are ignored.
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$requested = isset( $_GET['tech'] ) ? sanitize_title( wp_unslash( $_GET['tech'] ) ) : '';
	foreach ( $filter_terms as $tech_term ) {
		if ( $tech_term->slug === $requested ) {
			$active_term = $tech_term;
			$technology  = $tech_term->term_id;
		}
	}
}

$query_args = array(
	'post_type'           => Project::POST_TYPE,
	'post_status'         => 'publish',
	// The Portfolio page shows them all; 100 keeps the query bounded.
	'posts_per_page'      => $show_filter ? 100 : $number_of_items,
	'orderby'             => Project::ORDERBY,
	'ignore_sticky_posts' => true,
	'no_found_rows'       => true, // No pagination, so skip counting all rows.
);

if ( $technology ) {
	// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- Small, filtered list of Projects.
	$query_args['tax_query'] = array(
		array(
			'taxonomy' => Project::TAXONOMY,
			'field'    => 'term_id',
			'terms'    => $technology,
		),
	);
}

$projects = get_posts( $query_args );

// No Projects: print nothing rather than an empty grid.
if ( ! $projects ) {
	return;
}

$cards = '';

foreach ( $projects as $index => $project ) {
	// render_block() runs the Project Card's render.php, which escapes everything.
	// The first card's image is usually above the fold (the Portfolio page's
	// Largest Contentful Paint), so it loads right away at high priority.
	$cards .= Project::render_card( $project, null, 0 === $index );
}

// Without the filter, the wrapper is the grid itself (as before).
if ( ! $show_filter ) {
	$wrapper_attributes = get_block_wrapper_attributes();
	?>
	<div <?php echo wp_kses_data( $wrapper_attributes ); ?>>
		<?php
		// Each card's render.php already escaped its own output.
		echo $cards; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		?>
	</div>
	<?php
	return;
}

// The page's own address without ?tech=, for the chip links.
$base_url = remove_query_arg( 'tech' );

$chips = array(
	array(
		'label'   => __( 'All', 'dsg-core' ),
		'url'     => $base_url,
		'current' => null === $active_term,
	),
);

foreach ( $filter_terms as $tech_term ) {
	$chips[] = array(
		'label'   => $tech_term->name,
		'url'     => add_query_arg( 'tech', $tech_term->slug, $base_url ),
		'current' => $active_term && $active_term->term_id === $tech_term->term_id,
	);
}

$count       = count( $projects );
$status_text = $active_term
	? sprintf(
		/* translators: 1: number of projects, 2: technology name. */
		_n( '%1$d project built with %2$s', '%1$d projects built with %2$s', $count, 'dsg-core' ),
		$count,
		$active_term->name
	)
	: sprintf(
		/* translators: %d: number of projects. */
		_n( '%d project', '%d projects', $count, 'dsg-core' ),
		$count
	);

$wrapper_attributes = get_block_wrapper_attributes( array( 'class' => 'has-filter' ) );
?>
<div <?php echo wp_kses_data( $wrapper_attributes ); ?>>
	<div class="wp-block-dsg-project-grid__toolbar">
		<nav class="wp-block-dsg-project-grid__filter" aria-label="<?php esc_attr_e( 'Filter projects by technology', 'dsg-core' ); ?>" data-wp-interactive="dsg/project-grid">
			<?php // One line of chips that scrolls sideways; view.js adds the edge fades. ?>
			<ul data-wp-init="callbacks.initFilter" data-wp-on--scroll="callbacks.onScroll">
				<?php foreach ( $chips as $chip ) : ?>
					<li>
						<a href="<?php echo esc_url( $chip['url'] ); ?>" data-dsg-filter<?php echo $chip['current'] ? ' aria-current="true"' : ''; ?>><?php echo esc_html( $chip['label'] ); ?></a>
					</li>
				<?php endforeach; ?>
			</ul>
			<?php
			/*
			 * Arrows for mouse users, shown over an edge with more chips
			 * (style.scss). They come after the <ul> so CSS can show each one
			 * from the <ul>'s has-more-* class. They're hidden from screen
			 * readers and keyboards: Tab already scrolls each chip into view,
			 * so the arrows would only repeat that. They're <div>s, not
			 * buttons, because a click focuses a button even with
			 * tabindex="-1", and focus inside aria-hidden is an error (Chrome
			 * warns about it). A <div> never takes focus.
			 */
			$arrows = array(
				'prev' => 'm15 18-6-6 6-6',
				'next' => 'm9 18 6-6-6-6',
			);
			foreach ( $arrows as $direction => $chevron ) :
				?>
				<div class="wp-block-dsg-project-grid__arrow is-<?php echo esc_attr( $direction ); ?>" aria-hidden="true" data-wp-on--click="actions.scroll<?php echo esc_attr( ucfirst( $direction ) ); ?>">
					<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" focusable="false"><path d="<?php echo esc_attr( $chevron ); ?>"/></svg>
				</div>
			<?php endforeach; ?>
		</nav>
		<p class="wp-block-dsg-project-grid__status" role="status">// <?php echo esc_html( $status_text ); ?></p>
	</div>

	<div class="wp-block-dsg-project-grid__items">
		<?php
		// Each card's render.php already escaped its own output.
		echo $cards; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		?>
	</div>
</div>
