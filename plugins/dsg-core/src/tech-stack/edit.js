/**
 * What the block looks like inside the editor.
 *
 * - The label is edited on the canvas with RichText.
 * - The chips are an array attribute, edited in the sidebar: each chip is a
 *   text field with move up / move down / remove buttons, plus a field to
 *   add a new one. The canvas shows the chips as they will look on the site.
 *
 * - "Use the Project's technologies" (sidebar toggle) replaces the chips with
 *   the current Project's Technology terms, for the Project page template.
 *
 * React concepts used here: arrays in attributes (always replaced with a new
 * array, never changed in place), local state for the "new chip" field, and
 * lists rendered with map() and a `key`.
 */
import { __, sprintf } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import { useSelect } from '@wordpress/data';
import { store as coreStore } from '@wordpress/core-data';
import {
	useBlockProps,
	RichText,
	InspectorControls,
} from '@wordpress/block-editor';
import {
	PanelBody,
	TextControl,
	Button,
	Flex,
	FlexBlock,
	ToggleControl,
} from '@wordpress/components';

import { addItem, updateItem, removeItem, moveItem, cleanItems } from './utils';

// Shown in the Site Editor's template, where there's no Project yet.
const SAMPLE_TERMS = [ 'WordPress', 'PHP', 'JavaScript' ];

/**
 * The Technology term names of a Project, for the editor preview.
 *
 * @param {number|undefined} postId  Project ID from the block context.
 * @param {boolean}          enabled Whether to look them up at all.
 * @return {string[]} Term names.
 */
function useProjectTerms( postId, enabled ) {
	return useSelect(
		( select ) => {
			if ( ! enabled ) {
				return [];
			}
			if ( ! postId ) {
				return SAMPLE_TERMS;
			}
			const terms = select( coreStore ).getEntityRecords(
				'taxonomy',
				'dsg_technology',
				{ post: postId, per_page: -1, _fields: 'id,name' }
			);
			return ( terms ?? [] ).map( ( term ) => term.name );
		},
		[ postId, enabled ]
	);
}

export default function Edit( { attributes, setAttributes, context } ) {
	const { label, items, fromProject } = attributes;
	const projectTerms = useProjectTerms( context?.postId, fromProject );
	const shownItems = fromProject ? projectTerms : items;

	// What the user is typing in the "Add a technology" field.
	const [ newItem, setNewItem ] = useState( '' );

	const blockProps = useBlockProps();

	const setItems = ( next ) => setAttributes( { items: next } );

	const add = () => {
		setItems( addItem( items, newItem ) );
		setNewItem( '' );
	};

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Technologies', 'dsg-core' ) }>
					<ToggleControl
						__nextHasNoMarginBottom
						label={ __(
							"Use the Project's technologies",
							'dsg-core'
						) }
						help={ __(
							'Lists the Technologies ticked on the Project being shown. For the Project page template.',
							'dsg-core'
						) }
						checked={ fromProject }
						onChange={ ( value ) =>
							setAttributes( { fromProject: value } )
						}
					/>
				</PanelBody>
				{ ! fromProject && (
					<PanelBody title={ __( 'Chips', 'dsg-core' ) }>
						<Flex direction="column" gap={ 2 }>
							{ items.map( ( item, index ) => (
								// Index as key is fine here: the fields hold no
								// state of their own, their value comes from `items`.
								<Flex key={ index } gap={ 1 } align="center">
									<FlexBlock>
										<TextControl
											__nextHasNoMarginBottom
											__next40pxDefaultSize
											hideLabelFromVision
											label={ sprintf(
												/* translators: %d: position of the chip in the list. */
												__(
													'Technology %d',
													'dsg-core'
												),
												index + 1
											) }
											value={ item }
											onChange={ ( value ) =>
												setItems(
													updateItem(
														items,
														index,
														value
													)
												)
											}
											// A chip emptied out is removed when the field loses focus.
											onBlur={ () =>
												setItems( cleanItems( items ) )
											}
										/>
									</FlexBlock>
									<Button
										icon="arrow-up-alt2"
										label={ __( 'Move up', 'dsg-core' ) }
										size="small"
										disabled={ index === 0 }
										accessibleWhenDisabled
										onClick={ () =>
											setItems(
												moveItem(
													items,
													index,
													index - 1
												)
											)
										}
									/>
									<Button
										icon="arrow-down-alt2"
										label={ __( 'Move down', 'dsg-core' ) }
										size="small"
										disabled={ index === items.length - 1 }
										accessibleWhenDisabled
										onClick={ () =>
											setItems(
												moveItem(
													items,
													index,
													index + 1
												)
											)
										}
									/>
									<Button
										icon="no-alt"
										label={ __( 'Remove', 'dsg-core' ) }
										size="small"
										isDestructive
										onClick={ () =>
											setItems(
												removeItem( items, index )
											)
										}
									/>
								</Flex>
							) ) }

							<Flex gap={ 2 } align="flex-end">
								<FlexBlock>
									<TextControl
										__nextHasNoMarginBottom
										__next40pxDefaultSize
										label={ __(
											'Add a technology',
											'dsg-core'
										) }
										value={ newItem }
										onChange={ setNewItem }
										onKeyDown={ ( event ) => {
											if ( event.key === 'Enter' ) {
												event.preventDefault();
												add();
											}
										} }
									/>
								</FlexBlock>
								<Button
									variant="secondary"
									__next40pxDefaultSize
									onClick={ add }
									disabled={ ! newItem.trim() }
									accessibleWhenDisabled
								>
									{ __( 'Add', 'dsg-core' ) }
								</Button>
							</Flex>
						</Flex>
					</PanelBody>
				) }
			</InspectorControls>

			<div { ...blockProps }>
				<RichText
					tagName="p"
					className="wp-block-dsg-tech-stack__label"
					value={ label }
					onChange={ ( value ) => setAttributes( { label: value } ) }
					placeholder={ __( 'Label, e.g. Stack', 'dsg-core' ) }
					allowedFormats={ [] }
				/>
				{ shownItems.length > 0 ? (
					<ul className="wp-block-dsg-tech-stack__list">
						{ shownItems.map( ( item, index ) => (
							<li key={ index }>{ item }</li>
						) ) }
					</ul>
				) : (
					<p className="wp-block-dsg-tech-stack__empty">
						{ fromProject
							? __(
									'Tick Technologies on this Project to show them here.',
									'dsg-core'
							  )
							: __(
									'Add technologies in the block settings sidebar.',
									'dsg-core'
							  ) }
					</p>
				) }
			</div>
		</>
	);
}
