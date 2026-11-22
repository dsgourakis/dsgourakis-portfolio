/**
 * What the block looks like inside the editor.
 *
 * The menu is a real, editable Navigation block. The Resume button and the
 * toggle are previews only: their frontend markup, and the phone menu, come
 * from render.php. The email shown in the phone menu is set in the sidebar.
 */
import { __ } from '@wordpress/i18n';
import {
	InspectorControls,
	useBlockProps,
	useInnerBlocksProps,
} from '@wordpress/block-editor';
import { PanelBody, TextControl } from '@wordpress/components';

// Exactly one Navigation block, which can't be moved or removed.
const TEMPLATE = [
	[
		'core/navigation',
		{
			className: 'dsg-pill-nav',
			// The phone menu is this block's own (see render.php).
			overlayMenu: 'never',
			layout: { type: 'flex', justifyContent: 'right' },
		},
	],
];

export default function Edit( { attributes, setAttributes } ) {
	const blockProps = useBlockProps( { className: 'site-header__actions' } );

	const innerBlocksProps = useInnerBlocksProps(
		{},
		{
			allowedBlocks: [ 'core/navigation' ],
			template: TEMPLATE,
			templateLock: 'all',
		}
	);

	return (
		<div { ...blockProps }>
			<InspectorControls>
				<PanelBody title={ __( 'Phone menu', 'dsg-core' ) }>
					<TextControl
						__next40pxDefaultSize
						__nextHasNoMarginBottom
						type="email"
						label={ __( 'Email address', 'dsg-core' ) }
						help={ __(
							'Shown at the bottom of the phone menu. Leave empty to hide it.',
							'dsg-core'
						) }
						value={ attributes.email }
						onChange={ ( email ) => setAttributes( { email } ) }
					/>
				</PanelBody>
			</InspectorControls>
			<div { ...innerBlocksProps } />
			<span className="site-header__resume wp-element-button">
				{ __( 'Resume', 'dsg-core' ) }{ ' ' }
				<span aria-hidden="true">↗</span>
			</span>
			<span
				className="color-scheme-toggle"
				aria-hidden="true"
				title={ __(
					'Light/dark toggle (shown on the site)',
					'dsg-core'
				) }
			>
				☀
			</span>
		</div>
	);
}
