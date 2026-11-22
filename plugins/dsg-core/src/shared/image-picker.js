/**
 * Editor-only component: shows the block's image with Replace/Remove buttons,
 * or an "Add image" button when there is none.
 *
 * Shared by several blocks. Any folder in src/ without a block.json
 * (like this one) isn't a block. Blocks import from it, and the build
 * bundles the code into each block that uses it.
 */
import { __ } from '@wordpress/i18n';
import { MediaUpload, MediaUploadCheck } from '@wordpress/block-editor';
import { Button } from '@wordpress/components';

import { EMPTY_IMAGE, imageAttributesFromMedia } from './image';

/**
 * @param {Object}   props
 * @param {string}   props.className     Class for the wrapper, e.g. "wp-block-dsg-stat__media".
 * @param {Object}   props.attributes    The block's attributes (imageId, imageUrl, imageAlt).
 * @param {Function} props.setAttributes The block's setAttributes.
 */
export default function ImagePicker( {
	className,
	attributes,
	setAttributes,
} ) {
	const { imageId, imageUrl, imageAlt } = attributes;

	return (
		<MediaUploadCheck>
			<MediaUpload
				onSelect={ ( media ) =>
					setAttributes( imageAttributesFromMedia( media ) )
				}
				allowedTypes={ [ 'image' ] }
				value={ imageId }
				render={ ( { open } ) => (
					<div className={ className }>
						{ imageUrl ? (
							<>
								<img src={ imageUrl } alt={ imageAlt } />
								<div className={ `${ className }-actions` }>
									<Button
										variant="secondary"
										onClick={ open }
									>
										{ __( 'Replace image', 'dsg-core' ) }
									</Button>
									<Button
										variant="tertiary"
										isDestructive
										onClick={ () =>
											setAttributes( EMPTY_IMAGE )
										}
									>
										{ __( 'Remove', 'dsg-core' ) }
									</Button>
								</div>
							</>
						) : (
							<Button variant="secondary" onClick={ open }>
								{ __( 'Add image', 'dsg-core' ) }
							</Button>
						) }
					</div>
				) }
			/>
		</MediaUploadCheck>
	);
}
