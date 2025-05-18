/**
 * @type {import('lint-staged').Configuration}
 */
export default {
	'*.{js,ts,mjs}': [ 'npx wp-scripts lint-js', () => 'npx tsc' ],
	'*.css': [ 'npx wp-scripts lint-css' ],
	'*.php': [ 'composer phpcs', () => 'composer phpstan' ],
};
