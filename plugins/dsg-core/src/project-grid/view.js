/**
 * Project Grid: the technology filter's scrolling bar.
 *
 * The chips sit on one line and scroll sideways when they don't fit
 * (see style.scss). This script acts only on the filter's <nav> (its
 * data-wp-* directives in render.php), so grids without a filter skip it:
 * - fades the edges that have more chips out of view;
 * - scrolls the current chip into view when the page loads, so a shared
 *   ?tech= link shows which filter is on;
 * - keeps the bar where it was when a chip is clicked. Client-side
 *   navigation replaces the whole bar with the new page's, which would
 *   otherwise jump back to the start;
 * - scrolls the bar when an arrow is clicked. The arrows show over an edge
 *   with more chips, for mouse users (a mouse wheel can't scroll sideways).
 *
 * Without it, the bar still scrolls; it just has no fades or arrows.
 */
import { store, getElement } from '@wordpress/interactivity';

import { hiddenEdges, scrollToReveal, arrowStep } from './utils.js';

// Where the bar was scrolled to, carried over to the next page's bar.
let lastScrollLeft = 0;

/**
 * Show or hide the fade on each edge.
 *
 * @param {HTMLElement} list The scrolling <ul>.
 */
function updateFades( list ) {
	const edges = hiddenEdges(
		list.scrollLeft,
		list.clientWidth,
		list.scrollWidth
	);
	list.classList.toggle( 'has-more-start', edges.start );
	list.classList.toggle( 'has-more-end', edges.end );
}

/**
 * Width of each faded edge, in px: the bar's scroll-padding (style.scss).
 *
 * @param {HTMLElement} list The scrolling <ul>.
 * @return {number} The fade's width.
 */
function fadeWidth( list ) {
	return parseFloat( window.getComputedStyle( list ).scrollPaddingLeft ) || 0;
}

/**
 * Scroll the bar one step, for an arrow click.
 *
 * @param {number} direction -1 for back, 1 for forward.
 */
function scrollByArrow( direction ) {
	const { ref: arrow } = getElement();
	const list = arrow.parentElement.querySelector( 'ul' );
	const reduceMotion = window.matchMedia(
		'(prefers-reduced-motion: reduce)'
	).matches;

	list.scrollBy( {
		left: direction * arrowStep( list.clientWidth, fadeWidth( list ) ),
		behavior: reduceMotion ? 'auto' : 'smooth',
	} );
}

store( 'dsg/project-grid', {
	actions: {
		/**
		 * Back arrow: scroll towards the first chip.
		 */
		scrollPrev() {
			scrollByArrow( -1 );
		},

		/**
		 * Forward arrow: scroll towards the last chip.
		 */
		scrollNext() {
			scrollByArrow( 1 );
		},
	},

	callbacks: {
		/**
		 * When the bar appears (page load or after filtering): restore the
		 * scroll position, bring the current chip into view, then keep the
		 * fades in step with the bar's size.
		 *
		 * @return {Function} Cleanup, run when the bar is replaced.
		 */
		initFilter() {
			const { ref: list } = getElement();
			const fade = fadeWidth( list );
			const current = list.querySelector( '[aria-current="true"]' );

			list.scrollLeft = lastScrollLeft;
			if ( current ) {
				list.scrollLeft = scrollToReveal(
					list,
					{ left: current.offsetLeft, width: current.offsetWidth },
					fade
				);
			}
			lastScrollLeft = list.scrollLeft;
			updateFades( list );

			// Window resizes and web fonts loading both change what fits.
			const observer = new window.ResizeObserver( () =>
				updateFades( list )
			);
			observer.observe( list );
			[ ...list.children ].forEach( ( chip ) =>
				observer.observe( chip )
			);

			return () => observer.disconnect();
		},

		/**
		 * On scroll: update the fades and remember the position.
		 */
		onScroll() {
			const { ref: list } = getElement();
			lastScrollLeft = list.scrollLeft;
			updateFades( list );
		},
	},
} );
