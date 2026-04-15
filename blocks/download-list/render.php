<?php
/**
 * Server-side render for i-downloads/download-list block.
 *
 * Available variables (injected by WordPress):
 *   $attributes  array   Block attributes.
 *   $content     string  Inner blocks content (unused — dynamic block).
 *   $block       WP_Block
 */
defined( 'ABSPATH' ) || exit;

$atts = [
	'category'              => absint( $attributes['category'] ?? 0 ) ?: '',
	'include_subcategories' => array_key_exists( 'includeSubcategories', $attributes )
		? ( ! empty( $attributes['includeSubcategories'] ) ? '1' : '0' )
		: '1',
	'tag'                   => absint( $attributes['tag'] ?? 0 ) ?: '',
	'limit'                 => absint( $attributes['limit'] ?? 10 ),
	'orderby'               => sanitize_key( $attributes['orderby'] ?? 'date' ),
	'order'                 => sanitize_key( $attributes['order'] ?? 'DESC' ),
	'layout'                => sanitize_key( $attributes['layout'] ?? '' ),
	'show_search'           => ! empty( $attributes['showSearch'] ) ? '1' : '0',
];

// Reuse shortcode output — single source of truth
echo do_shortcode( '[idl_list' . idl_atts_to_string( $atts ) . ']' ); // phpcs:ignore WordPress.Security.EscapeOutput
