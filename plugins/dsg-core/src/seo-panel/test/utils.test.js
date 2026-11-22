/**
 * Unit tests for the "Search & sharing" panel helpers.
 */
import { fallback, lengthStatus, plainText } from '../utils';

describe( 'plainText', () => {
	it( 'removes tags and collapses spaces', () => {
		expect( plainText( '<p>Hello\n  <b>world</b></p>' ) ).toBe(
			'Hello world'
		);
	} );

	it( 'handles missing text', () => {
		expect( plainText( undefined ) ).toBe( '' );
	} );
} );

describe( 'fallback', () => {
	it( 'returns the first candidate with text', () => {
		expect( fallback( '', '  ', 'Excerpt', 'Tagline' ) ).toBe( 'Excerpt' );
	} );

	it( 'returns an empty string when nothing has text', () => {
		expect( fallback( '', undefined ) ).toBe( '' );
	} );
} );

describe( 'lengthStatus', () => {
	it( 'counts characters, not bytes', () => {
		expect( lengthStatus( 'Καλημέρα', 60 ) ).toEqual( {
			count: 8,
			tooLong: false,
		} );
	} );

	it( 'flags text over the limit', () => {
		expect( lengthStatus( 'a'.repeat( 61 ), 60 ).tooLong ).toBe( true );
	} );
} );
