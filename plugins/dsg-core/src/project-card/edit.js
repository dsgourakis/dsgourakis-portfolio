/**
 * What the block looks like inside the editor.
 *
 * React concepts used here:
 * - props: WordPress passes `attributes` (the saved data) and `setAttributes` (to change it).
 * - state: `technologiesText` keeps what the user is typing until they leave the field.
 * - components: RichText, InspectorControls and our own ImagePicker are React components.
 */
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import {
	useBlockProps,
	RichText,
	InspectorControls,
} from '@wordpress/block-editor';
import {
	PanelBody,
	TextControl,
	ToggleControl,
	Flex,
} from '@wordpress/components';

import ImagePicker from '../shared/image-picker';
import { parseTechnologies, formatTechnologies } from './utils';

export default function Edit( { attributes, setAttributes } ) {
	const { title, summary, url, imageAlt, technologies, featured } =
		attributes;

	// Local state: the raw text in the "Technologies" field while typing.
	const [ technologiesText, setTechnologiesText ] = useState(
		formatTechnologies( technologies )
	);

	const blockProps = useBlockProps( {
		className: featured
			? 'wp-block-dsg-project-card is-featured'
			: 'wp-block-dsg-project-card',
	} );

	return (
		<>
			{ /* Sidebar settings, shown when the block is selected. */ }
			<InspectorControls>
				<PanelBody title={ __( 'Project settings', 'dsg-core' ) }>
					<Flex direction="column" gap={ 4 }>
						<ToggleControl
							__nextHasNoMarginBottom
							label={ __( 'Featured', 'dsg-core' ) }
							help={ __(
								'Large, full-row layout when this is the first card in its group.',
								'dsg-core'
							) }
							checked={ featured }
							onChange={ ( value ) =>
								setAttributes( { featured: value } )
							}
						/>
						<TextControl
							__nextHasNoMarginBottom
							__next40pxDefaultSize
							label={ __( 'Project link', 'dsg-core' ) }
							help={ __(
								'Page or external site the card links to.',
								'dsg-core'
							) }
							type="url"
							value={ url }
							onChange={ ( value ) =>
								setAttributes( { url: value } )
							}
						/>
						<TextControl
							__nextHasNoMarginBottom
							__next40pxDefaultSize
							label={ __( 'Technologies', 'dsg-core' ) }
							help={ __(
								'Comma separated, e.g. PHP, React, WooCommerce',
								'dsg-core'
							) }
							value={ technologiesText }
							onChange={ setTechnologiesText }
							onBlur={ () =>
								setAttributes( {
									technologies:
										parseTechnologies( technologiesText ),
								} )
							}
						/>
						<TextControl
							__nextHasNoMarginBottom
							__next40pxDefaultSize
							label={ __( 'Image alt text', 'dsg-core' ) }
							help={ __(
								'Describe the image for screen reader users. Leave empty if it is decorative.',
								'dsg-core'
							) }
							value={ imageAlt }
							onChange={ ( value ) =>
								setAttributes( { imageAlt: value } )
							}
						/>
					</Flex>
				</PanelBody>
			</InspectorControls>

			<article { ...blockProps }>
				<ImagePicker
					className="wp-block-dsg-project-card__media"
					attributes={ attributes }
					setAttributes={ setAttributes }
				/>

				<div className="wp-block-dsg-project-card__body">
					<h3 className="wp-block-dsg-project-card__title">
						<RichText
							tagName="span"
							className="wp-block-dsg-project-card__title-text"
							value={ title }
							onChange={ ( value ) =>
								setAttributes( { title: value } )
							}
							placeholder={ __( 'Project name…', 'dsg-core' ) }
							allowedFormats={ [] }
						/>
					</h3>

					<RichText
						tagName="p"
						className="wp-block-dsg-project-card__summary"
						value={ summary }
						onChange={ ( value ) =>
							setAttributes( { summary: value } )
						}
						placeholder={ __(
							'What you built and the result…',
							'dsg-core'
						) }
					/>

					{ technologies.length > 0 && (
						<ul className="wp-block-dsg-project-card__tags">
							{ technologies.map( ( name ) => (
								<li key={ name }>{ name }</li>
							) ) }
						</ul>
					) }

					{ url && (
						<span
							className="wp-block-dsg-project-card__cta"
							aria-hidden="true"
						>
							{ __( 'View the project', 'dsg-core' ) } →
						</span>
					) }
				</div>
			</article>
		</>
	);
}
