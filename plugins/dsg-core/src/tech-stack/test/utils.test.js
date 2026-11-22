/**
 * Jest unit tests. Run with: npm run test:js
 */
import {
	addItem,
	updateItem,
	removeItem,
	moveItem,
	cleanItems,
} from '../utils';

describe( 'addItem', () => {
	it( 'adds a trimmed item to the end', () => {
		expect( addItem( [ 'PHP' ], '  React ' ) ).toEqual( [
			'PHP',
			'React',
		] );
	} );

	it( 'ignores empty text and duplicates', () => {
		const items = [ 'PHP' ];

		expect( addItem( items, '   ' ) ).toBe( items );
		expect( addItem( items, 'PHP' ) ).toBe( items );
	} );
} );

describe( 'updateItem', () => {
	it( 'replaces only the given position', () => {
		expect( updateItem( [ 'a', 'b', 'c' ], 1, 'B' ) ).toEqual( [
			'a',
			'B',
			'c',
		] );
	} );
} );

describe( 'removeItem', () => {
	it( 'removes the given position', () => {
		expect( removeItem( [ 'a', 'b', 'c' ], 0 ) ).toEqual( [ 'b', 'c' ] );
	} );
} );

describe( 'moveItem', () => {
	it( 'moves an item up and down', () => {
		expect( moveItem( [ 'a', 'b', 'c' ], 2, 0 ) ).toEqual( [
			'c',
			'a',
			'b',
		] );
		expect( moveItem( [ 'a', 'b', 'c' ], 0, 1 ) ).toEqual( [
			'b',
			'a',
			'c',
		] );
	} );

	it( 'returns the same list when a position is out of range', () => {
		const items = [ 'a', 'b' ];

		expect( moveItem( items, 0, -1 ) ).toBe( items );
		expect( moveItem( items, 1, 2 ) ).toBe( items );
	} );

	it( 'does not change the original array', () => {
		const items = [ 'a', 'b' ];
		moveItem( items, 0, 1 );

		expect( items ).toEqual( [ 'a', 'b' ] );
	} );
} );

describe( 'cleanItems', () => {
	it( 'trims and drops empty items', () => {
		expect( cleanItems( [ ' PHP ', '', '  ', 'React' ] ) ).toEqual( [
			'PHP',
			'React',
		] );
	} );
} );
