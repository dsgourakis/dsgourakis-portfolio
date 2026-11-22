/**
 * Project Details block + the "Project details" sidebar panel.
 *
 * Dynamic block: the frontend HTML comes from render.php, so `save`
 * returns null. The values are post meta, edited in the sidebar panel
 * (panel.js), not block attributes, so every Project page shows its own.
 */
import { registerBlockType } from '@wordpress/blocks';
import { registerPlugin } from '@wordpress/plugins';

import './style.scss';

import Edit from './edit';
import ProjectDetailsPanel from './panel';
import metadata from './block.json';

registerBlockType( metadata.name, {
	edit: Edit,
	save: () => null,
} );

registerPlugin( 'dsg-project-details', {
	render: ProjectDetailsPanel,
	icon: 'portfolio',
} );
