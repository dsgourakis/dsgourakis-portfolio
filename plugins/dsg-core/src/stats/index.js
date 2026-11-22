/**
 * Stats block: a container that holds Stat blocks.
 *
 * Dynamic block with inner blocks:
 * - `save` returns only <InnerBlocks.Content />, the Stats inside it.
 *   Those must be stored in the post, so save can't be null here.
 * - The wrapper <div> around them comes from render.php, so its markup
 *   can change at any time without breaking saved content.
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
