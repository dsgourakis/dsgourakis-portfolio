/**
 * Jest unit tests. Run with: npm run test:js
 */
import { imageAttributesFromMedia } from '../image';

describe( 'imageAttributesFromMedia', () => {
	it( 'uses the large size when it exists', () => {
		const media = {
			id: 8,
			url: 'https://example.com/full.webp',
			alt: 'Homepage screenshot',
			sizes: { large: { url: 'https://example.com/large.webp' } },
		};

		expect( imageAttributesFromMedia( media ) ).toEqual( {
			imageId: 8,
			imageUrl: 'https://example.com/large.webp',
			imageAlt: 'Homepage screenshot',
		} );
	} );

	it( 'falls back to the full image when there is no large size', () => {
		const media = { id: 9, url: 'https://example.com/small.png' };

		expect( imageAttributesFromMedia( media ) ).toEqual( {
			imageId: 9,
			imageUrl: 'https://example.com/small.png',
			imageAlt: '',
		} );
	} );
} );
