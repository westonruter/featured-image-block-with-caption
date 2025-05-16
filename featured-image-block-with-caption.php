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
 * Adds the 'showCaption' attribute to the server-side registration of the Featured Image block.
 *
 * @param array{ attributes?: array<string, array{ type: string, default: bool }> }|mixed $settings Existing block type settings.
 * @param array{ name: non-empty-string }                                                 $metadata Block metadata.
 * @return array{ attributes?: array<string, array{ type: string, default: bool }> } Modified block type settings.
 */
function add_show_caption_attribute_to_block_schema( mixed $settings, array $metadata ): array {
	// Because other plugins can do bad things.
	if ( ! is_array( $settings ) ) {
		$settings = array( 'attributes' => array() );
	}
	/**
	 * Settings.
	 *
	 * @var array{ attributes: array<string, array{ type: string, default: bool }> } $settings
	 */

	if ( BLOCK_NAME === $metadata['name'] ) {
		$settings['attributes']['showCaption'] = array(
			'type'    => 'boolean',
			'default' => false,
		);
	}
	return $settings;
}
add_filter( 'block_type_metadata_settings', add_show_caption_attribute_to_block_schema( ... ), 10, 2 );

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
add_action( 'enqueue_block_editor_assets', enqueue_block_editor_assets( ... ) );

/**
 * Adds inline style for the Featured Image block to support a caption.
 *
 * This is only called when a caption is to be shown in block.
 */
function add_featured_image_block_inline_style(): void {
	$css = (string) file_get_contents( __DIR__ . '/block.css' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents

	// Ad hoc minification.
	$css = trim( $css );
	$css = (string) preg_replace( '/\/\*.*?\*\//s', '', $css ); // Remove all comments.
	$css = (string) preg_replace( '/[\t\n]/', '', $css ); // Remove all tabs and newlines.
	$css = (string) preg_replace( '/;(?=})/', '', $css ); // Remove the last property's semicolon.
	$css = (string) preg_replace( '/ +(?={)/', '', $css ); // Remove spaces before the opening brace.
	$css = (string) preg_replace( '/(?<=:) +/', '', $css ); // Remove spaces after a property's colon.

	$handle = wp_should_load_separate_core_block_assets() ? 'wp-block-post-featured-image' : 'wp-block-library';
	wp_add_inline_style( $handle, $css );
}

/**
 * Filters the Featured Image block to add a caption on the singular template.
 *
 * @param string|mixed                                   $block_content The block content.
 * @param array{ attrs: array{ showCaption?: boolean } } $attributes The block attributes.
 * @return string The filtered block content.
 */
function filter_featured_image_block( mixed $block_content, array $attributes ): string {
	static $added_inline_style = false;

	// Because other plugins can do bad things.
	if ( ! is_string( $block_content ) ) {
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
				'a'      => array(
					'href'   => true,
					'rel'    => true,
					'target' => true,
				),
				'bdo'    => array(
					'code' => array(),
					'lang' => true,
					'dir'  => true,
				),
				'br'     => array(),
				'em'     => array(),
				'kbd'    => array(),
				'mark'   => array(
					'style' => true,
					'class' => true,
				),
				's'      => array(),
				'strong' => array(),
				'sub'    => array(),
				'sup'    => array(),
			)
		);

		$block_content = (string) preg_replace(
			'#(?=</figure>)#',
			'<figcaption class="wp-element-caption">' . $caption . '</figcaption>',
			$block_content,
			1
		);

		// Enqueue the stylesheet on demand.
		if ( ! $added_inline_style ) {
			add_action( 'enqueue_block_assets', add_featured_image_block_inline_style( ... ) );
			$added_inline_style = true;
		}
	}

	return $block_content;
}
add_filter(
	'render_block_' . BLOCK_NAME,
	filter_featured_image_block( ... ),
	10,
	2
);

// Unconditionally enqueue the block style in the editor, since we cannot conditionally enqueue during block rendering.
if ( is_admin() ) {
	add_action(
		'enqueue_block_assets',
		add_featured_image_block_inline_style( ... )
	);
}
