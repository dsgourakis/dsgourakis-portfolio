/**
 * What the block looks like inside the editor.
 *
 * Every field is edited directly on the canvas with RichText,
 * so this block needs no sidebar settings.
 */
import { __ } from '@wordpress/i18n';
import { useBlockProps, RichText } from '@wordpress/block-editor';

export default function Edit( { attributes, setAttributes } ) {
	const { pretitle, title, description } = attributes;

	// Adds the block's class, plus any color/padding the user picks.
	const blockProps = useBlockProps();

	return (
		<div { ...blockProps }>
			<RichText
				tagName="p"
				className="wp-block-dsg-stat__pretitle"
				value={ pretitle }
				onChange={ ( value ) => setAttributes( { pretitle: value } ) }
				placeholder={ __( 'Pre-title, e.g. build', 'dsg-core' ) }
				allowedFormats={ [] }
			/>
			<RichText
				tagName="h3"
				className="wp-block-dsg-stat__title"
				value={ title }
				onChange={ ( value ) => setAttributes( { title: value } ) }
				placeholder={ __( '4+ or a title…', 'dsg-core' ) }
				allowedFormats={ [] }
			/>
			<RichText
				tagName="p"
				className="wp-block-dsg-stat__description"
				value={ description }
				onChange={ ( value ) =>
					setAttributes( { description: value } )
				}
				placeholder={ __( 'A short description…', 'dsg-core' ) }
			/>
		</div>
	);
}
