/**
 * Hero block: the homepage intro.
 *
 * Dynamic block with inner blocks, like Stats:
 * - `save` returns only <InnerBlocks.Content />, the buttons inside it.
 * - Everything else (heading, tagline, monogram) comes from render.php.
 */
import { registerBlockType } from '@wordpress/blocks';
import { InnerBlocks } from '@wordpress/block-editor';

import './style.scss';

import Edit from './edit';
import metadata from './block.json';

registerBlockType( metadata.name, {
	edit: Edit,
	save: () => <InnerBlocks.Content />,
} );
