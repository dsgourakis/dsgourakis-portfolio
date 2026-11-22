/**
 * Tech Stack block: registers the block with the editor.
 *
 * This is a dynamic block: the frontend HTML comes from render.php,
 * so `save` returns null and the post stores only the attributes.
 */
import { registerBlockType } from '@wordpress/blocks';

import './style.scss';

import Edit from './edit';
import metadata from './block.json';

registerBlockType( metadata.name, {
	edit: Edit,
	save: () => null,
} );
