/**
 * @type {import('lint-staged').Configuration}
 */
export default {
	'*.{js,ts,mjs}': [ 'npx wp-scripts lint-js', () => 'npx tsc' ],
	'*.php': [ 'composer phpcs', () => 'composer phpstan' ],
};
