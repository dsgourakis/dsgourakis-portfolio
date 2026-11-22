/**
 * What the block looks like inside the editor.
 *
 * A preview of the form (the fields can't be typed in here), with the
 * button label and the success message in the sidebar. The markup
 * matches render.php so the same CSS styles both.
 */
import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import {
	PanelBody,
	TextControl,
	TextareaControl,
	ExternalLink,
} from '@wordpress/components';

const DEFAULT_BUTTON = __( 'Send message', 'dsg-core' );

const FIELDS = [
	{ key: 'name', label: __( 'Name', 'dsg-core' ) },
	{ key: 'email', label: __( 'Email address', 'dsg-core' ) },
	{ key: 'message', label: __( 'Message', 'dsg-core' ), multiline: true },
];

export default function Edit( { attributes, setAttributes } ) {
	const { buttonLabel, successMessage } = attributes;

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Form settings', 'dsg-core' ) }>
					<TextControl
						__nextHasNoMarginBottom
						__next40pxDefaultSize
						label={ __( 'Button label', 'dsg-core' ) }
						placeholder={ DEFAULT_BUTTON }
						value={ buttonLabel }
						onChange={ ( value ) =>
							setAttributes( { buttonLabel: value } )
						}
					/>
					<TextareaControl
						__nextHasNoMarginBottom
						label={ __( 'Success message', 'dsg-core' ) }
						help={ __(
							'Shown after a message is sent. Leave empty for the default.',
							'dsg-core'
						) }
						value={ successMessage }
						onChange={ ( value ) =>
							setAttributes( { successMessage: value } )
						}
					/>
					<p>
						<ExternalLink href="edit.php?post_type=dsg_message">
							{ __( 'Read received messages', 'dsg-core' ) }
						</ExternalLink>
					</p>
				</PanelBody>
			</InspectorControls>

			<div { ...useBlockProps() }>
				<div className="wp-block-dsg-contact-form__form">
					{ FIELDS.map( ( { key, label, multiline } ) => (
						<div
							key={ key }
							className="wp-block-dsg-contact-form__field"
						>
							<label htmlFor={ `dsg-contact-preview-${ key }` }>
								{ label }
							</label>
							{ multiline ? (
								<textarea
									id={ `dsg-contact-preview-${ key }` }
									rows={ 4 }
									disabled
								/>
							) : (
								<input
									id={ `dsg-contact-preview-${ key }` }
									type="text"
									disabled
								/>
							) }
						</div>
					) ) }
					<div className="wp-block-dsg-contact-form__footer">
						<span className="wp-block-dsg-contact-form__submit wp-element-button">
							{ buttonLabel || DEFAULT_BUTTON }
						</span>
					</div>
				</div>
			</div>
		</>
	);
}
