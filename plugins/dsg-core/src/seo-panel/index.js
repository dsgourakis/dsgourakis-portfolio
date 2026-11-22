/**
 * "Search & sharing" panel in the Page and Project editor's sidebar.
 *
 * Not a block: it has its own build entry (webpack.config.js in the repo
 * root) and is loaded by includes/Seo/Fields.php. The values are post
 * meta registered there. Every field is optional; the placeholders show
 * what will be used when one is left empty.
 */
import { __, sprintf } from '@wordpress/i18n';
import { useSelect } from '@wordpress/data';
import { useEntityProp } from '@wordpress/core-data';
import { PluginDocumentSettingPanel } from '@wordpress/editor';
import { registerPlugin } from '@wordpress/plugins';
import {
	BaseControl,
	Button,
	CheckboxControl,
	TextControl,
	TextareaControl,
} from '@wordpress/components';
import { MediaUpload, MediaUploadCheck } from '@wordpress/block-editor';

import { KEYS, LIMITS, POST_TYPES, fallback, lengthStatus } from './utils';

/**
 * "42 / 60 characters", with a warning when it's too long.
 *
 * @param {string} text  The text being counted.
 * @param {number} limit About where search results cut it off.
 * @return {string} Help text.
 */
function lengthHelp( text, limit ) {
	const { count, tooLong } = lengthStatus( text, limit );
	const counter = sprintf(
		/* translators: 1: number of characters, 2: recommended maximum. */
		__( '%1$d / %2$d characters', 'dsg-core' ),
		count,
		limit
	);

	return tooLong
		? `${ counter }. ${ __(
				'Search results may cut it short.',
				'dsg-core'
		  ) }`
		: counter;
}

/**
 * Picks the share image (an attachment ID in the post's meta).
 *
 * @param {Object}   props
 * @param {number}   props.imageId    Current image, 0 for none.
 * @param {number}   props.fallbackId Image used when none is picked (featured image).
 * @param {Function} props.onChange   Called with the new ID.
 */
function ImageField( { imageId, fallbackId, onChange } ) {
	const shownId = imageId || fallbackId;
	const imageUrl = useSelect(
		( select ) => {
			if ( ! shownId ) {
				return '';
			}
			const media = select( 'core' ).getMedia( shownId, {
				context: 'view',
			} );
			return (
				media?.media_details?.sizes?.medium?.source_url ??
				media?.source_url ??
				''
			);
		},
		[ shownId ]
	);

	let help = __(
		'Shown when the page is shared. Best at 1200 × 630 pixels.',
		'dsg-core'
	);
	if ( ! imageId && fallbackId ) {
		help = __(
			'None picked: the featured image is used. Best at 1200 × 630 pixels.',
			'dsg-core'
		);
	}

	return (
		<BaseControl
			__nextHasNoMarginBottom
			id="dsg-seo-image"
			label={ __( 'Share image', 'dsg-core' ) }
			help={ help }
		>
			<MediaUploadCheck>
				<MediaUpload
					onSelect={ ( media ) => onChange( media.id ) }
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
										aspectRatio: '1.91 / 1',
										objectFit: 'cover',
										opacity: imageId ? 1 : 0.6,
									} }
								/>
							) }
							<div style={ { display: 'flex', gap: '8px' } }>
								<Button variant="secondary" onClick={ open }>
									{ imageId
										? __( 'Replace', 'dsg-core' )
										: __( 'Pick image', 'dsg-core' ) }
								</Button>
								{ !! imageId && (
									<Button
										variant="tertiary"
										isDestructive
										onClick={ () => onChange( 0 ) }
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

function SeoFields( { postType } ) {
	const [ meta, setMeta ] = useEntityProp( 'postType', postType, 'meta' );
	const { title, excerpt, featuredImage, tagline } = useSelect(
		( select ) => {
			const editor = select( 'core/editor' );
			return {
				title: editor.getEditedPostAttribute( 'title' ),
				excerpt: editor.getEditedPostAttribute( 'excerpt' ),
				featuredImage:
					editor.getEditedPostAttribute( 'featured_media' ),
				// Only readable by admins; the placeholder is just a hint.
				tagline:
					select( 'core' ).getEntityRecord( 'root', 'site' )
						?.description ?? '',
			};
		},
		[]
	);

	const set = ( key, value ) => setMeta( { ...meta, [ key ]: value } );
	const seoTitle = meta?.[ KEYS.title ] ?? '';
	const seoDescription = meta?.[ KEYS.description ] ?? '';

	return (
		<div style={ { display: 'grid', gap: '16px' } }>
			<TextControl
				__next40pxDefaultSize
				__nextHasNoMarginBottom
				label={ __( 'Search title', 'dsg-core' ) }
				value={ seoTitle }
				placeholder={ fallback( title ) }
				help={ lengthHelp( seoTitle || title, LIMITS.title ) }
				onChange={ ( value ) => set( KEYS.title, value ) }
			/>
			<TextareaControl
				__nextHasNoMarginBottom
				label={ __( 'Search description', 'dsg-core' ) }
				value={ seoDescription }
				placeholder={ fallback( excerpt, tagline ) }
				help={ lengthHelp(
					seoDescription || fallback( excerpt, tagline ),
					LIMITS.description
				) }
				onChange={ ( value ) => set( KEYS.description, value ) }
			/>
			<ImageField
				imageId={ meta?.[ KEYS.image ] || 0 }
				fallbackId={ featuredImage || 0 }
				onChange={ ( id ) => set( KEYS.image, id ) }
			/>
			<CheckboxControl
				__nextHasNoMarginBottom
				label={ __( 'Hide from search engines', 'dsg-core' ) }
				help={ __(
					'Adds "noindex" and leaves the page out of the sitemap.',
					'dsg-core'
				) }
				checked={ !! meta?.[ KEYS.noindex ] }
				onChange={ ( value ) => set( KEYS.noindex, value ) }
			/>
		</div>
	);
}

function SeoPanel() {
	const postType = useSelect(
		( select ) => select( 'core/editor' )?.getCurrentPostType(),
		[]
	);

	if ( ! POST_TYPES.includes( postType ) ) {
		return null;
	}

	return (
		<PluginDocumentSettingPanel
			name="dsg-seo"
			title={ __( 'Search & sharing', 'dsg-core' ) }
			initialOpen={ false }
		>
			<SeoFields postType={ postType } />
		</PluginDocumentSettingPanel>
	);
}

registerPlugin( 'dsg-seo', {
	render: SeoPanel,
	icon: 'search',
} );
