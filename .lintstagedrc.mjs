/**
 * Join and escape filenames for shell.
 *
 * @param {string[]} files Files to join.
 *
 * @return {string} Joined files.
 */
const joinFiles = ( files ) => {
	return files.map( ( file ) => `'${ file }'` ).join( ' ' );
};

export default {
	'*.{js,ts}': ( files ) => {
		return [ `npm run lint-js -- ${ joinFiles( files ) }`, `npm run tsc` ];
	},
	'*.php': ( files ) => {
		return [ `composer phpcs -- ${ joinFiles( files ) }`, 'composer phpstan' ];
	},
};
