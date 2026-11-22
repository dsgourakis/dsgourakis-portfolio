/**
 * Contact Form block.
 *
 * Dynamic: render.php prints the form, view.js makes it send without a
 * reload. Nothing is saved in the post except the two attributes.
 */
import { registerBlockType } from '@wordpress/blocks';

import './style.scss';

import Edit from './edit';
import metadata from './block.json';

registerBlockType( metadata.name, {
	edit: Edit,
} );
