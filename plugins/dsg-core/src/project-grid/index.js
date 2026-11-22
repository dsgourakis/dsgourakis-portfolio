/**
 * Project Grid block: registers the block with the editor.
 *
 * Dynamic block: render.php queries the Projects every time the page is
 * shown, so a new Project appears on the site without editing the page.
 * The post only stores the settings: <!-- wp:dsg/project-grid {"numberOfItems":3} /-->
 */
import { registerBlockType } from '@wordpress/blocks';

import './style.scss';

import Edit from './edit';
import metadata from './block.json';

registerBlockType( metadata.name, {
	edit: Edit,
	save: () => null,
} );
