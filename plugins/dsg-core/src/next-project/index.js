/**
 * Next Project block: registers the block with the editor.
 *
 * Dynamic block: the card is picked and printed by render.php, so `save`
 * returns null. In the editor a placeholder stands in for it; the real
 * card is easier to check on the frontend.
 */
import { __ } from '@wordpress/i18n';
import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps } from '@wordpress/block-editor';

import './style.scss';

import metadata from './block.json';

function Edit() {
	return (
		<div { ...useBlockProps() }>
			<p className="wp-block-dsg-next-project__placeholder">
				{ __(
					'// next project — the card for the following Project appears here on the site.',
					'dsg-core'
				) }
			</p>
		</div>
	);
}

registerBlockType( metadata.name, {
	edit: Edit,
	save: () => null,
} );
