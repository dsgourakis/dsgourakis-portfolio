/**
 * Header Actions block: the menu, the Resume button and the light/dark toggle.
 *
 * Dynamic block with one inner block (the Navigation):
 * - `save` returns only <InnerBlocks.Content />, so the menu stays a normal
 *   Navigation block that can be edited in the Site Editor.
 * - Everything else (wrapper, Resume button, toggle) comes from render.php,
 *   so that markup lives in one place and never needs updating in the
 *   template file.
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
