<?php
/**
 * Server-side render for i-downloads/category-grid block.
 */
defined( 'ABSPATH' ) || exit;

$atts = [
	'parent'           => absint( $attributes['parent'] ?? 0 ),
	'columns'          => absint( $attributes['columns'] ?? 3 ),
	'show_count'       => ! empty( $attributes['showCount'] ) ? '1' : '0',
	'show_description' => ! empty( $attributes['showDescription'] ) ? '1' : '0',
];

echo do_shortcode( '[idl_categories' . idl_atts_to_string( $atts ) . ']' ); // phpcs:ignore WordPress.Security.EscapeOutput
