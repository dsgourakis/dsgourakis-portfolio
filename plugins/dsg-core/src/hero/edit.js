/**
 * What the block looks like inside the editor.
 *
 * - Heading and tagline are edited on the canvas with RichText.
 *   To color a letter (the blue "D"), select it and use Highlight > Text
 *   in the toolbar. Shift+Enter adds a line break.
 * - The buttons are real core Button blocks (inner blocks), so links,
 *   labels and styles are edited the normal way.
 * - The monogram letter and the code tags are set in the sidebar.
 *
 * The markup matches render.php so the same CSS styles both.
 */
import { __ } from '@wordpress/i18n';
import {
	useBlockProps,
	useInnerBlocksProps,
	RichText,
	InspectorControls,
} from '@wordpress/block-editor';
import { PanelBody, TextControl, ToggleControl } from '@wordpress/components';

// Only a Buttons group can go inside.
const ALLOWED_BLOCKS = [ 'core/buttons' ];

// A new Hero starts with an outlined "Contact me" and a plain "See work" link.
const TEMPLATE = [
	[
		'core/buttons',
		{},
		[
			[
				'core/button',
				{
					text: __( 'Contact me', 'dsg-core' ),
					url: '/contact/',
					className: 'is-style-outline',
				},
			],
			[
				'core/button',
				{
					text: __( 'See work ↓', 'dsg-core' ),
					url: '/portfolio/',
					className: 'is-style-text',
				},
			],
		],
	],
];

export default function Edit( { attributes, setAttributes } ) {
	const { heading, tagline, monogram, showCodeTags } = attributes;

	const blockProps = useBlockProps( {
		className: showCodeTags ? 'has-code-tags' : undefined,
	} );

	const innerBlocksProps = useInnerBlocksProps(
		{ className: 'wp-block-dsg-hero__actions' },
		{ allowedBlocks: ALLOWED_BLOCKS, template: TEMPLATE }
	);

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Hero settings', 'dsg-core' ) }>
					<TextControl
						__nextHasNoMarginBottom
						__next40pxDefaultSize
						label={ __( 'Monogram', 'dsg-core' ) }
						help={ __(
							'The big tilted letter on the right. Leave empty to hide it.',
							'dsg-core'
						) }
						value={ monogram }
						maxLength={ 2 }
						onChange={ ( value ) =>
							setAttributes( { monogram: value } )
						}
					/>
					<ToggleControl
						__nextHasNoMarginBottom
						label={ __( 'Show code tags', 'dsg-core' ) }
						help={ __(
							'Decorative <body> and <h1> labels around the heading.',
							'dsg-core'
						) }
						checked={ showCodeTags }
						onChange={ ( value ) =>
							setAttributes( { showCodeTags: value } )
						}
					/>
				</PanelBody>
			</InspectorControls>

			<div { ...blockProps }>
				<div className="wp-block-dsg-hero__content">
					<div className="wp-block-dsg-hero__heading">
						<RichText
							tagName="h1"
							className="wp-block-dsg-hero__title"
							value={ heading }
							onChange={ ( value ) =>
								setAttributes( { heading: value } )
							}
							placeholder={ __( 'Hi, I’m…', 'dsg-core' ) }
							allowedFormats={ [ 'core/text-color' ] }
						/>
					</div>
					<RichText
						tagName="p"
						className="wp-block-dsg-hero__tagline"
						value={ tagline }
						onChange={ ( value ) =>
							setAttributes( { tagline: value } )
						}
						placeholder={ __(
							'WordPress developer / Full-stack / …',
							'dsg-core'
						) }
						allowedFormats={ [] }
					/>
					<div { ...innerBlocksProps } />
				</div>
				{ monogram && (
					<div
						className="wp-block-dsg-hero__monogram"
						aria-hidden="true"
					>
						<span className="wp-block-dsg-hero__letter is-back">
							{ monogram }
						</span>
						<span className="wp-block-dsg-hero__letter is-front">
							{ monogram }
						</span>
					</div>
				) }
			</div>
		</>
	);
}
