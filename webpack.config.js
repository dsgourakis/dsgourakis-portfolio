/**
 * Build config: the @wordpress/scripts default, plus entries that aren't blocks.
 *
 * The default finds blocks by their block.json. The "Search & sharing"
 * editor panel (plugins/dsg-core/src/seo-panel) is not a block, so it is
 * added here and built to build/seo-panel/index.js (+ index.asset.php).
 */
const path = require( 'path' );
const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );

// With --experimental-modules the default is [ scripts, modules ].
const configs = Array.isArray( defaultConfig )
	? defaultConfig
	: [ defaultConfig ];
const [ scriptConfig, ...rest ] = configs;

const extraEntries = {
	'seo-panel/index': path.resolve(
		__dirname,
		'plugins/dsg-core/src/seo-panel/index.js'
	),
};

module.exports = [
	{
		...scriptConfig,
		entry: () => {
			const entry =
				typeof scriptConfig.entry === 'function'
					? scriptConfig.entry()
					: scriptConfig.entry;
			return { ...entry, ...extraEntries };
		},
	},
	...rest,
];
