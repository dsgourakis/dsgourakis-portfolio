/**
 * "Project details" panel in the Project editor's sidebar.
 *
 * Saves straight to the post's meta (registered with show_in_rest in
 * Project.php), so the values are stored when the Project is saved.
 * Only shown when editing a Project.
 */
import { __ } from '@wordpress/i18n';
import { useSelect } from '@wordpress/data';
import { useEntityProp } from '@wordpress/core-data';
import { PluginDocumentSettingPanel } from '@wordpress/editor';
import {
	BaseControl,
	Button,
	CheckboxControl,
	TextControl,
} from '@wordpress/components';
import { MediaUpload, MediaUploadCheck } from '@wordpress/block-editor';

import { FIELDS } from './fields';

const POST_TYPE = 'dsg_project';
const SCREENSHOT_KEY = 'dsg_screenshot';
const FEATURED_KEY = 'dsg_featured';

/**
 * Picks the homepage screenshot (an attachment ID in the post's meta).
 */
function ScreenshotField() {
	const [ meta, setMeta ] = useEntityProp( 'postType', POST_TYPE, 'meta' );
	const imageId = meta?.[ SCREENSHOT_KEY ] || 0;
	const imageUrl = useSelect(
		( select ) => {
			if ( ! imageId ) {
				return '';
			}
			const media = select( 'core' ).getMedia( imageId, {
				context: 'view',
			} );
			return (
				media?.media_details?.sizes?.medium?.source_url ??
				media?.source_url ??
				''
			);
		},
		[ imageId ]
	);
	const setImageId = ( id ) => setMeta( { ...meta, [ SCREENSHOT_KEY ]: id } );

	return (
		<BaseControl
			__nextHasNoMarginBottom
			id="dsg-project-screenshot"
			label={ __( 'Homepage screenshot', 'dsg-core' ) }
		>
			<MediaUploadCheck>
				<MediaUpload
					onSelect={ ( media ) => setImageId( media.id ) }
					allowedTypes={ [ 'image' ] }
					value={ imageId }
					render={ ( { open } ) => (
						<div style={ { display: 'grid', gap: '8px' } }>
							{ imageUrl && (
								<img
									src={ imageUrl }
									alt=""
									style={ {
										width: '100%',
										maxHeight: '200px',
										objectFit: 'cover',
										objectPosition: 'top',
									} }
								/>
							) }
							<div style={ { display: 'flex', gap: '8px' } }>
								<Button variant="secondary" onClick={ open }>
									{ imageId
										? __( 'Replace', 'dsg-core' )
										: __( 'Add screenshot', 'dsg-core' ) }
								</Button>
								{ !! imageId && (
									<Button
										variant="tertiary"
										isDestructive
										onClick={ () => setImageId( 0 ) }
									>
										{ __( 'Remove', 'dsg-core' ) }
									</Button>
								) }
							</div>
						</div>
					) }
				/>
			</MediaUploadCheck>
		</BaseControl>
	);
}

/**
 * "Featured project": the large, full-row card when it is listed first.
 */
function FeaturedField() {
	const [ meta, setMeta ] = useEntityProp( 'postType', POST_TYPE, 'meta' );

	return (
		<CheckboxControl
			__nextHasNoMarginBottom
			label={ __( 'Featured project', 'dsg-core' ) }
			help={ __(
				'Shown as the large card when it is the first Project in a list.',
				'dsg-core'
			) }
			checked={ !! meta?.[ FEATURED_KEY ] }
			onChange={ ( value ) =>
				setMeta( { ...meta, [ FEATURED_KEY ]: value } )
			}
		/>
	);
}

function ProjectDetailsFields() {
	const [ meta, setMeta ] = useEntityProp( 'postType', POST_TYPE, 'meta' );

	return FIELDS.map( ( { key, label, type } ) => (
		<TextControl
			key={ key }
			__next40pxDefaultSize
			__nextHasNoMarginBottom
			label={ label }
			type={ type ?? 'text' }
			value={ meta?.[ key ] ?? '' }
			placeholder={ type === 'url' ? 'https://' : '' }
			onChange={ ( value ) => setMeta( { ...meta, [ key ]: value } ) }
		/>
	) );
}

export default function ProjectDetailsPanel() {
	const postType = useSelect(
		( select ) => select( 'core/editor' )?.getCurrentPostType(),
		[]
	);

	if ( postType !== POST_TYPE ) {
		return null;
	}

	return (
		<PluginDocumentSettingPanel
			name="dsg-project-details"
			title={ __( 'Project details', 'dsg-core' ) }
		>
			<div style={ { display: 'grid', gap: '16px' } }>
				<FeaturedField />
				<ProjectDetailsFields />
				<ScreenshotField />
			</div>
		</PluginDocumentSettingPanel>
	);
}
