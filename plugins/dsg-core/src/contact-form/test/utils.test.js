/**
 * Jest unit tests. Run with: npm run test:js
 */
import { validate, allFields, firstInvalid } from '../utils';

const MESSAGES = {
	nameRequired: 'name required',
	emailRequired: 'email required',
	emailInvalid: 'email invalid',
	messageRequired: 'message required',
};

const VALID = {
	name: 'Ada',
	email: 'ada@example.com',
	message: 'Hello',
};

describe( 'validate', () => {
	it( 'returns no errors for valid fields', () => {
		expect( validate( VALID, MESSAGES ) ).toEqual( {} );
	} );

	it( 'reports every empty field at once, ignoring spaces', () => {
		expect(
			validate( { name: '  ', email: '', message: '\n' }, MESSAGES )
		).toEqual( {
			name: 'name required',
			email: 'email required',
			message: 'message required',
		} );
	} );

	it( 'rejects malformed email addresses', () => {
		[ 'ada', 'ada@', 'ada@example', 'a da@example.com' ].forEach(
			( email ) => {
				expect( validate( { ...VALID, email }, MESSAGES ) ).toEqual( {
					email: 'email invalid',
				} );
			}
		);
	} );

	it( 'treats missing fields as empty', () => {
		expect( Object.keys( validate( {}, MESSAGES ) ) ).toEqual( [
			'name',
			'email',
			'message',
		] );
	} );
} );

describe( 'allFields', () => {
	it( 'fills in an empty error for every other field', () => {
		expect( allFields( { email: 'bad' } ) ).toEqual( {
			name: '',
			email: 'bad',
			message: '',
		} );
	} );

	it( 'ignores keys that are not fields', () => {
		expect( allFields( { website: 'x' } ) ).toEqual( {
			name: '',
			email: '',
			message: '',
		} );
	} );
} );

describe( 'firstInvalid', () => {
	it( 'returns the first field in form order, not object order', () => {
		expect( firstInvalid( { message: 'x', name: 'y' } ) ).toBe( 'name' );
	} );

	it( 'returns undefined when nothing is invalid', () => {
		expect( firstInvalid( allFields() ) ).toBeUndefined();
	} );
} );
