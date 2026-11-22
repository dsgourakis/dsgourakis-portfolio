/**
 * Jest unit tests. Run with: npm run test:js
 */
import {
	sortProjects,
	technologyNames,
	hiddenEdges,
	scrollToReveal,
	arrowStep,
} from '../utils';

describe( 'sortProjects', () => {
	const projects = [
		{ id: 1, menu_order: 0, date: '2025-01-01T00:00:00' },
		{ id: 2, menu_order: 0, date: '2026-01-01T00:00:00' },
		{ id: 3, menu_order: -1, date: '2024-01-01T00:00:00' },
		{ id: 4, menu_order: 5, date: '2026-06-01T00:00:00' },
	];

	it( 'sorts by Order first, then newest first', () => {
		expect(
			sortProjects( projects, 10 ).map( ( project ) => project.id )
		).toEqual( [ 3, 2, 1, 4 ] );
	} );

	it( 'keeps only `limit` projects', () => {
		expect( sortProjects( projects, 2 ) ).toHaveLength( 2 );
	} );

	it( 'does not change the original array', () => {
		const copy = [ ...projects ];
		sortProjects( projects, 10 );

		expect( projects ).toEqual( copy );
	} );
} );

describe( 'technologyNames', () => {
	const terms = [
		{ id: 1, name: 'PHP' },
		{ id: 2, name: 'React' },
	];

	it( 'maps IDs to names and skips unknown ones', () => {
		expect( technologyNames( [ 2, 99, 1 ], terms ) ).toEqual( [
			'React',
			'PHP',
		] );
	} );

	it( 'handles missing data', () => {
		expect( technologyNames( undefined, undefined ) ).toEqual( [] );
	} );
} );

describe( 'hiddenEdges', () => {
	it( 'reports no hidden chips when everything fits', () => {
		expect( hiddenEdges( 0, 500, 500 ) ).toEqual( {
			start: false,
			end: false,
		} );
	} );

	it( 'reports the end at the start, and both in the middle', () => {
		expect( hiddenEdges( 0, 500, 900 ) ).toEqual( {
			start: false,
			end: true,
		} );
		expect( hiddenEdges( 200, 500, 900 ) ).toEqual( {
			start: true,
			end: true,
		} );
	} );

	it( 'reports only the start when scrolled to the end, even when zoomed', () => {
		expect( hiddenEdges( 399.5, 500, 900 ) ).toEqual( {
			start: true,
			end: false,
		} );
	} );
} );

describe( 'scrollToReveal', () => {
	const bar = { scrollLeft: 0, clientWidth: 500 };
	const fade = 48;

	it( 'leaves the bar alone when the chip is already clear of the fades', () => {
		expect( scrollToReveal( bar, { left: 100, width: 80 }, fade ) ).toBe(
			0
		);
	} );

	it( 'scrolls forward so a chip past the end sits clear of the fade', () => {
		expect( scrollToReveal( bar, { left: 700, width: 80 }, fade ) ).toBe(
			700 + 80 + fade - 500
		);
	} );

	it( 'scrolls back, but never past the start', () => {
		const scrolled = { scrollLeft: 600, clientWidth: 500 };
		expect(
			scrollToReveal( scrolled, { left: 300, width: 80 }, fade )
		).toBe( 300 - fade );
		expect(
			scrollToReveal( scrolled, { left: 10, width: 80 }, fade )
		).toBe( 0 );
	} );
} );

describe( 'arrowStep', () => {
	it( 'scrolls the visible width minus both fades', () => {
		expect( arrowStep( 600, 48 ) ).toBe( 504 );
	} );

	it( 'scrolls at least half the bar when it is narrow', () => {
		expect( arrowStep( 150, 48 ) ).toBe( 75 );
	} );
} );
