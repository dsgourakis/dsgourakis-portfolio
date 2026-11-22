/**
 * What the block looks like inside the editor.
 *
 * useInnerBlocksProps turns this block's wrapper into a place where other
 * blocks can be added, with rules about which ones and what to start with.
 */
import { __ } from '@wordpress/i18n';
import { useBlockProps, useInnerBlocksProps } from '@wordpress/block-editor';

// Only Stat blocks can go inside.
const ALLOWED_BLOCKS = [ 'dsg/stat' ];

// What a new Stats block starts with: the big number + three numbered cards.
const TEMPLATE = [
	[
		'dsg/stat',
		{
			title: '4+',
			description: __( 'years of experience', 'dsg-core' ),
		},
	],
	[ 'dsg/stat' ],
	[ 'dsg/stat' ],
	[ 'dsg/stat' ],
];

export default function Edit() {
	const blockProps = useBlockProps();

	// Merges the inner-blocks behaviour into the same <div>, so the Stats are
	// direct children of .wp-block-dsg-stats, exactly like on the frontend.
	const innerBlocksProps = useInnerBlocksProps( blockProps, {
		allowedBlocks: ALLOWED_BLOCKS,
		template: TEMPLATE,
		orientation: 'horizontal',
	} );

	return <div { ...innerBlocksProps } />;
}
