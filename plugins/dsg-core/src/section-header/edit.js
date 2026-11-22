/**
 * What the block looks like inside the editor.
 *
 * Both fields are edited directly on the canvas with RichText.
 * The layout (meta label vs intro paragraph) is picked in the sidebar's
 * Styles panel, which WordPress adds from the "styles" list in block.json.
 */
import { __ } from '@wordpress/i18n';
import { useBlockProps, RichText } from '@wordpress/block-editor';

export default function Edit( { attributes, setAttributes } ) {
	const { heading, aside } = attributes;

	// Adds the block's class, alignment and the chosen is-style-* class.
	const blockProps = useBlockProps();

	return (
		<div { ...blockProps }>
			<RichText
				tagName="h2"
				className="wp-block-dsg-section-header__heading"
				value={ heading }
				onChange={ ( value ) => setAttributes( { heading: value } ) }
				placeholder={ __( 'Section heading…', 'dsg-core' ) }
				allowedFormats={ [] }
			/>
			<RichText
				tagName="p"
				className="wp-block-dsg-section-header__aside"
				value={ aside }
				onChange={ ( value ) => setAttributes( { aside: value } ) }
				placeholder={ __(
					'// meta label, or a short intro paragraph…',
					'dsg-core'
				) }
				allowedFormats={ [ 'core/bold', 'core/italic', 'core/link' ] }
			/>
		</div>
	);
}
