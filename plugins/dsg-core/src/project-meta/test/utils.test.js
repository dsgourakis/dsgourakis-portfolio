/**
 * Unit tests for the Project Details helpers.
 */
import { filledFields, displayUrl } from '../fields';

describe( 'filledFields', () => {
	it( 'keeps only details with a value, in display order', () => {
		const fields = filledFields( {
			dsg_role: 'Lead developer',
			dsg_agency: 'Acme Digital',
			dsg_year: '  ',
		} );

		expect( fields.map( ( field ) => field.key ) ).toEqual( [
			'dsg_agency',
			'dsg_role',
		] );
		expect( fields[ 0 ].value ).toBe( 'Acme Digital' );
	} );

	it( 'handles missing meta', () => {
		expect( filledFields( undefined ) ).toEqual( [] );
	} );
} );

describe( 'displayUrl', () => {
	it( 'strips the scheme, www. and trailing slash', () => {
		expect( displayUrl( 'https://www.redcross.gr/' ) ).toBe(
			'redcross.gr'
		);
		expect( displayUrl( 'http://example.com/shop' ) ).toBe(
			'example.com/shop'
		);
	} );
} );
