/**
 * What the block looks like inside the editor.
 *
 * Shows the same strip as the frontend, read from the Project in context.
 * In the Site Editor's template (no Project yet) it shows sample values,
 * so the layout can still be seen.
 */
import { __ } from '@wordpress/i18n';
import { useBlockProps } from '@wordpress/block-editor';
import { useEntityProp } from '@wordpress/core-data';

import { filledFields, displayUrl } from './fields';

const SAMPLE = {
	dsg_agency: __( 'Agency name', 'dsg-core' ),
	dsg_year: '2025',
	dsg_role: __( 'Lead developer', 'dsg-core' ),
	dsg_live_url: 'https://example.com',
};

export default function Edit( { context: { postId, postType } } ) {
	const [ meta ] = useEntityProp(
		'postType',
		postType || 'dsg_project',
		'meta',
		postId
	);

	const blockProps = useBlockProps();
	const fields = filledFields( postId ? meta : SAMPLE );

	if ( ! fields.length ) {
		return (
			<p { ...blockProps }>
				{ __(
					'Fill in "Project details" in the sidebar to show them here.',
					'dsg-core'
				) }
			</p>
		);
	}

	return (
		<dl { ...blockProps }>
			{ fields.map( ( { key, label, value, type } ) => (
				<div key={ key } className="wp-block-dsg-project-meta__item">
					<dt>{ label }</dt>
					<dd>
						{ type === 'url' ? (
							<span className="wp-block-dsg-project-meta__link">
								{ displayUrl( value ) } ↗
							</span>
						) : (
							value
						) }
					</dd>
				</div>
			) ) }
		</dl>
	);
}
