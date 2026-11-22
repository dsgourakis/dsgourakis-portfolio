/**
 * Section Header block: registers the block with the editor.
 *
 * This is a dynamic block: the frontend HTML comes from render.php,
 * so `save` returns null and the post stores only the attributes.
 * The two layouts ("Meta label" and "Intro paragraph") are block styles
 * defined in block.json, so switching between them only changes a class.
 */
import { registerBlockType } from '@wordpress/blocks';

import './style.scss';

import Edit from './edit';
import metadata from './block.json';

registerBlockType( metadata.name, {
	edit: Edit,
	save: () => null,
} );
