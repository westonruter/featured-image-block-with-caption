<?php
/**
 * Plugin Name: Featured Image Block with Caption
 * Plugin URI:
 * Description: Adds captions to the Featured Image block on the singular template.
 * Requires at least: 6.5
 * Requires PHP: 8.1
 * Version: 0.1.0
 * Author: Weston Ruter
 * Author URI: https://weston.ruter.net/
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * Update URI: ...
 * Gist Plugin URI: ...
 *
 * @package FeaturedImageBlockWithCaption
 */

namespace FeaturedImageBlockWithCaption;

const VERSION = '0.1.0';

/**
 * Adds the 'showCaption' attribute to the server-side registration of the
 * 'core/post-featured-image' block.
 *
 * This ensures WordPress is aware of our custom attribute and handles its saving
 * and availability in server-side rendering contexts.
 *
 * @param array|mixed $settings Existing block type settings.
 * @param array       $metadata Block metadata.
 * @return array Modified block type settings.
 */
function add_show_caption_attribute_to_block_schema( mixed $settings, array $metadata ): array {
	if ( ! is_array( $settings) ) {
		$settings = array();
	}
	if ( 'core/post-featured-image' === $metadata['name'] ) {
		if ( ! isset( $settings['attributes'] ) ) {
			$settings['attributes'] = [];
		}
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
 * Filters the Featured Image block to add a caption on the singular template.
 *
 * @param string|mixed $block_content The block content.
 * @param array{ attrs: array{ showCaption?: boolean } } $attributes The block attributes.
 * @return string The filtered block content.
 */
function filter_featured_image_block( mixed $block_content, array $attributes ): string {
	if ( ! is_string( $block_content ) ) {
		$block_content = '';
	}

	// Bail if
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

		$block_content = preg_replace(
			'#(?=</figure>)#',
			'<figcaption class="wp-element-caption">' . $caption . '</figcaption>',
			$block_content,
			1
		);
	}

	return $block_content;
}

add_filter(
	'render_block_core/post-featured-image',
	filter_featured_image_block(...),
	10,
	2
);

/**
 * Adds custom inline styles to the Post Featured Image block.
 *
 * This function injects CSS rules for styling the Post Featured Image block's caption and its links.
 * The CSS is minified before being added to optimize performance.
 *
 * TODO: Only do this if present on the page.
 */
function add_post_featured_image_style(): void {
	/*
	 * The first rule in the following CSS comes from the caption-style() SASS mix-in:
	 * https://github.com/WordPress/gutenberg/blob/5c7c4e7751df5e05fc70a354cd0d81414ac9c7e7/packages/base-styles/_mixins.scss#L220-L224
	 * And this emulates how it is applied to the Image block's caption:
	 * https://github.com/WordPress/gutenberg/blob/5c7c4e7751df5e05fc70a354cd0d81414ac9c7e7/packages/block-library/src/image/style.scss#L99-L104
	 *
	 * The second rule prevents links in the caption from being displayed as block:
	 * https://github.com/WordPress/gutenberg/blob/5c7c4e7751df5e05fc70a354cd0d81414ac9c7e7/packages/block-library/src/post-featured-image/style.scss#L4-L7
	 */
	$css = <<<CSS
	.wp-block-post-featured-image :where(figcaption) {
		margin-bottom: 1em;
		margin-top: .5em;
	}

	.wp-block-post-featured-image figcaption a {
		display: inline;
		height: auto;
	}
	CSS;

	// Ad hoc minification.
	$css = preg_replace( '/[\t\n]/', '', $css ); // Remove all tabs and newlines.
	$css = preg_replace( '/;(?=})/', '', $css ); // Remove the last property's semicolon.
	$css = preg_replace( '/ +(?={)/', '', $css ); // Remove spaces before the opening brace.
	$css = preg_replace( '/(?<=:) +/', '', $css ); // Remove spaces after a property's colon.

	wp_add_inline_style( 'wp-block-post-featured-image', $css );
}

add_action( 'enqueue_block_assets', add_post_featured_image_style(...) );
