
interface Window {
	wp: {
		blockEditor: typeof import( '@wordpress/block-editor' ), // TODO: TS7016: Could not find a declaration file for module @wordpress/block-editor.
		components: typeof import( '@wordpress/components' ),
		compose: typeof import( '@wordpress/compose' ),
		element: typeof import( '@wordpress/element' ),
		hooks: typeof import( '@wordpress/hooks' ),
		i18n: typeof import( '@wordpress/i18n' ),
	}
}
