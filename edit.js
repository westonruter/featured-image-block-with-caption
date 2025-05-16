// This JavaScript requires no build step!
( function ( wp ) {
	'use strict';

	const { __ } = wp.i18n;
	const { addFilter } = wp.hooks;
	const { createHigherOrderComponent } = wp.compose;
	const { Fragment, createElement } = wp.element;
	const { BlockControls } = wp.blockEditor;
	const { ToolbarGroup, ToolbarButton } = wp.components;

	// Source: <https://github.com/WordPress/gutenberg/blob/5c7c4e7751df5e05fc70a354cd0d81414ac9c7e7/packages/icons/src/library/caption.js>.
	const captionIcon = createElement(
		'svg',
		{ viewBox: '0 0 24 24', xmlns: 'http://www.w3.org/2000/svg' },
		createElement( 'path', {
			fillRule: 'evenodd',
			clipRule: 'evenodd',
			d: 'M6 5.5h12a.5.5 0 0 1 .5.5v12a.5.5 0 0 1-.5.5H6a.5.5 0 0 1-.5-.5V6a.5.5 0 0 1 .5-.5ZM4 6a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6Zm4 10h2v-1.5H8V16Zm5 0h-2v-1.5h2V16Zm1 0h2v-1.5h-2V16Z',
		} )
	);

	const withFeaturedImageCaptionControl = createHigherOrderComponent(
		( BlockEdit ) => {
			return (
				/**
				 * @param {Object}   props
				 * @param {string}   props.name
				 * @param {Object}   props.attributes
				 * @param {boolean}  props.attributes.showCaption
				 * @param {Function} props.setAttributes
				 * @return {import('@wordpress/element').Element} BlockEdit component.
				 */
				( props ) => {
					if ( props.name !== 'core/post-featured-image' ) {
						return createElement( BlockEdit, props );
					}

					const { attributes, setAttributes } = props;
					const { showCaption } = attributes;

					const toolbarButton = createElement( ToolbarButton, {
						icon: captionIcon,
						label: showCaption
							? __( 'Remove caption' ) // Text domain omitted to re-use strings in WP core.
							: __( 'Add caption' ),
						onClick: () =>
							setAttributes( { showCaption: ! showCaption } ),
						isActive: !! showCaption,
						showTooltip: true,
					} );

					const blockControls = createElement(
						BlockControls,
						{ group: 'block' },
						createElement( ToolbarGroup, null, toolbarButton )
					);

					const figure = createElement(
						'figure',
						{
							className: 'wp-block-post-featured-image',
						},
						createElement( BlockEdit, props ),
						showCaption
							? createElement(
									'figcaption',
									{
										className: 'wp-element-caption',
									},
									'(Any caption provided for the current featured image in the Media Library will go here.)' // TODO: Translate.
							  )
							: null
					);

					return createElement(
						Fragment,
						null,
						blockControls,
						figure
					);
				}
			);
		},
		'withFeaturedImageCaptionControl'
	);

	addFilter(
		'editor.BlockEdit',
		'featured-image-with-caption/add-toolbar-control',
		withFeaturedImageCaptionControl
	);
} )( window.wp );
