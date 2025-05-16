<?php
/**
 * Plugin Name: Featured Image Block with Caption
 * Plugin URI: https://github.com/westonruter/featured-image-block-with-caption
 * Description: Adds the ability to show a caption in the Featured Image block via the same "Add caption" block toolbar button available on the Image block. Image captions must be edited in the Media Library. This is a prototype to implement <a href="https://github.com/WordPress/gutenberg/issues/40946">gutenberg#40946</a>.
 * Requires at least: 6.5
 * Requires PHP: 8.1
 * Version: 0.1.0
 * Author: Weston Ruter
 * Author URI: https://weston.ruter.net/
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * Update URI: https://github.com/westonruter/featured-image-block-with-caption
 * GitHub Plugin URI: https://github.com/westonruter/featured-image-block-with-caption
 *
 * @package FeaturedImageBlockWithCaption
 */

namespace FeaturedImageBlockWithCaption;

const VERSION = '0.1.0';

const STYLE_HANDLE = 'featured-image-block-with-caption';

const BLOCK_NAME = 'core/post-featured-image';

/**
 * Adds the 'showCaption' attribute to the server-side registration of the
 * 'core/post-featured-image' block.
 *
 * This ensures WordPress is aware of our custom attribute and handles its saving
 * and availability in server-side rendering contexts.
 *
 * @param array{ attributes?: array<string, array{ type: string, default: bool }> } $settings Existing block type settings.
 * @param array{ name: non-empty-string }       $metadata Block metadata.
 * @return array{ attributes?: array<string, array{ type: string, default: bool }> } Modified block type settings.
 */
function add_show_caption_attribute_to_block_schema( mixed $settings, array $metadata ): array {
	if ( ! is_array( $settings) ) { // @phpstan-ignore function.alreadyNarrowedType (Because another plugin could do a bad thing.)
		$settings = array();
	}
	if ( 'core/post-featured-image' === $metadata['name'] ) {
		$settings['attributes']['showCaption'] = array(
			'type'    => 'boolean',
			'default' => false,
		);
	}
	return $settings;
}
add_filter( 'block_type_metadata_settings', add_show_caption_attribute_to_block_schema(...), 10, 2 );

/**
 * Enqueues block editor assets.
 *
 * These assets will be enqueued only in the editor.
 */
function enqueue_block_editor_assets(): void {
	wp_enqueue_script(
		'featured-image-block-with-caption-edit',
		plugins_url( 'edit.js', __FILE__ ),
		array(
			'wp-block-editor',
			'wp-blocks',
			'wp-components',
			'wp-compose',
			'wp-element',
			'wp-hooks',
			'wp-i18n',
		),
		VERSION,
		array( 'in_footer' => true )
	);
}
add_action( 'enqueue_block_editor_assets', enqueue_block_editor_assets(...) );

/**
 * Registers the stylesheet for the Featured Image block to support a caption.
 *
 * The stylesheet will be enqueued if a caption is rendered in the block.
 */
function register_block_style(): void {
	/*
	 * The first rule in the following CSS comes from the caption-style() SASS mix-in:
	 * https://github.com/WordPress/gutenberg/blob/5c7c4e7751df5e05fc70a354cd0d81414ac9c7e7/packages/base-styles/_mixins.scss#L220-L224
	 * And this emulates how it is applied to the Image block's caption:
	 * https://github.com/WordPress/gutenberg/blob/5c7c4e7751df5e05fc70a354cd0d81414ac9c7e7/packages/block-library/src/image/style.scss#L99-L104
	 *
	 * The second rule prevents links in the caption from being displayed as block:
	 * https://github.com/WordPress/gutenberg/blob/5c7c4e7751df5e05fc70a354cd0d81414ac9c7e7/packages/block-library/src/post-featured-image/style.scss#L4-L7
	 */
	wp_register_style(
		STYLE_HANDLE,
		plugins_url( 'block.css', __FILE__ ),
		array(),
		VERSION
	);
	wp_style_add_data( STYLE_HANDLE, 'path', plugin_dir_path( __FILE__ ) . '/block.css' );
}
add_action( 'init', register_block_style(...) );

/**
 * Filters the Featured Image block to add a caption on the singular template.
 *
 * @param string $block_content The block content.
 * @param array{ attrs: array{ showCaption?: boolean } } $attributes The block attributes.
 * @return string The filtered block content.
 */
function filter_featured_image_block( mixed $block_content, array $attributes ): string {
	if ( ! is_string( $block_content ) ) { // @phpstan-ignore function.alreadyNarrowedType (Because another plugin could do a bad thing.)
		$block_content = '';
	}

	// Bail if showing the caption is not requested.
	if ( ! isset( $attributes['attrs']['showCaption'] ) || ! $attributes['attrs']['showCaption'] ) {
		return $block_content;
	}

	$caption = get_the_post_thumbnail_caption();
	if ( $caption ) {

		// Allow all markup that the block editor allows in the caption context. This may be overkill.
		$caption = wp_kses(
			$caption,
			array(
				'a' => array(
					'href' => true,
					'rel' => true,
					'target' => true,
				),
				'bdo' => array(
					'code' => array(),
					'lang' => true,
					'dir' => true,
				),
				'br' => array(),
				'em' => array(),
				'kbd' => array(),
				'mark' => array(
					'style' => true,
					'class' => true,
				),
				's' => array(),
				'strong' => array(),
				'sub' => array(),
				'sup' => array(),
			)
		);

		$block_content = (string) preg_replace(
			'#(?=</figure>)#',
			'<figcaption class="wp-element-caption">' . $caption . '</figcaption>',
			$block_content,
			1
		);

		// Enqueue the stylesheet on demand.
		wp_enqueue_style( STYLE_HANDLE );
	}

	return $block_content;
}
add_filter(
	'render_block_' . BLOCK_NAME,
	filter_featured_image_block(...),
	10,
	2
);
