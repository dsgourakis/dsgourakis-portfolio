/**
 * Pure helpers for the editor preview, kept apart so Jest can test them.
 */

/**
 * Sort Projects the same way render.php does: by the Order field
 * (menu_order, lowest first), then newest first.
 *
 * The REST API can only sort by one field, so the editor fetches the
 * Projects and sorts them here to match the frontend exactly.
 *
 * @param {Object[]} projects Project records from the REST API.
 * @param {number}   limit    How many to keep.
 * @return {Object[]} A new, sorted and trimmed array.
 */
export function sortProjects( projects, limit ) {
	return [ ...projects ]
		.sort(
			( a, b ) =>
				( a.menu_order ?? 0 ) - ( b.menu_order ?? 0 ) ||
				new Date( b.date ) - new Date( a.date )
		)
		.slice( 0, limit );
}

/**
 * Look up technology names for a Project's term IDs.
 *
 * @param {number[]} termIds Term IDs stored on the Project.
 * @param {Object[]} terms   All Technology terms ({ id, name }).
 * @return {string[]} Names, in the order of termIds. Unknown IDs are skipped.
 */
export function technologyNames( termIds = [], terms = [] ) {
	return termIds
		.map( ( id ) => terms.find( ( term ) => term.id === id )?.name )
		.filter( Boolean );
}

/**
 * Which edges of the scrolling filter bar have chips out of view.
 *
 * A 1px tolerance absorbs the fractional scroll positions some browsers
 * report when zoomed.
 *
 * @param {number} scrollLeft  How far the bar is scrolled.
 * @param {number} clientWidth The bar's visible width.
 * @param {number} scrollWidth The width of all its chips together.
 * @return {{start: boolean, end: boolean}} True for each edge with more to see.
 */
export function hiddenEdges( scrollLeft, clientWidth, scrollWidth ) {
	return {
		start: scrollLeft > 1,
		end: scrollLeft + clientWidth < scrollWidth - 1,
	};
}

/**
 * Where to scroll the filter bar so a chip is fully visible, clear of the
 * faded edges. Leaves the bar where it is when the chip already fits.
 *
 * @param {Object} bar             The bar's current scroll state.
 * @param {number} bar.scrollLeft  How far it is scrolled.
 * @param {number} bar.clientWidth Its visible width.
 * @param {Object} chip            The chip's position inside the bar.
 * @param {number} chip.left       Its left edge (offsetLeft).
 * @param {number} chip.width      Its width.
 * @param {number} fade            Width of each faded edge, in px.
 * @return {number} The scrollLeft to use.
 */
export function scrollToReveal( bar, chip, fade ) {
	const start = bar.scrollLeft + fade;
	const end = bar.scrollLeft + bar.clientWidth - fade;

	if ( chip.left < start ) {
		return Math.max( 0, chip.left - fade );
	}
	if ( chip.left + chip.width > end ) {
		return chip.left + chip.width + fade - bar.clientWidth;
	}
	return bar.scrollLeft;
}

/**
 * How far one click on a filter-bar arrow scrolls: most of the visible
 * width, minus both faded edges, so the chip that was half-hidden under
 * the fade ends up fully in view rather than skipped. Never less than half
 * the bar, so it still moves on a narrow bar.
 *
 * @param {number} clientWidth The bar's visible width.
 * @param {number} fade        Width of each faded edge, in px.
 * @return {number} The distance to scroll, in px.
 */
export function arrowStep( clientWidth, fade ) {
	return Math.max( clientWidth - 2 * fade, clientWidth / 2 );
}
