<?php

use Rtcl\Helpers\Functions;

add_action( 'wp_enqueue_scripts', 'classima_child_styles', 18 );
function classima_child_styles() {
	wp_enqueue_style( 'classipost-child-style', get_stylesheet_uri() );
}

add_action( 'wp_enqueue_scripts', 'classima_child_scripts', 18 );
function classima_child_scripts() {
	wp_enqueue_script( "classipost-child-script", get_stylesheet_directory_uri() . "/assets/custom.js", [ "jquery" ], "1.0", true );
	wp_enqueue_script( "classipost-listing-calendar", get_stylesheet_directory_uri() . "/assets/listing-calendar.js", [ "jquery", "rtcl-public" ],
		"1.0",
		true );
}

add_action( 'after_setup_theme', 'classima_child_theme_setup' );
function classima_child_theme_setup() {
	load_child_theme_textdomain( 'classima', get_stylesheet_directory() . '/languages' );
}

add_shortcode( 'evenimentul_listing_calendar', function ( $atts ) {
	if ( ! Functions::is_listing() ) {
		return false;
	}

	global $listing;

	if ( ! $listing ) {
		$listing = rtcl()->factory->get_listing( get_the_ID() );
	}

	if ( ! $listing ) {
		return false;
	}

	ob_start();

	echo '<div id="evenimentul-listing-calendar-app"></div>';

	return ob_get_clean();

} );

add_action( 'classima_single_listing_after_product', function () {
	echo do_shortcode( '[evenimentul_listing_calendar]' );
} );

add_action( 'wp_ajax_get_listing_calendar_meta', 'evenimentul_listing_calendar_meta' );
add_action( 'wp_ajax_nopriv_get_listing_calendar_meta', 'evenimentul_listing_calendar_meta' );

function evenimentul_listing_calendar_meta() {
	$post_id = isset( $_GET['post_id'] ) ? absint( $_GET['post_id'] ) : 0;

	if ( ! $post_id ) {
		wp_send_json_error( 'Invalid post ID' );
	}

	$meta = get_post_meta( $post_id, 'calendar_mbeqklic', true );

	wp_send_json_success( $meta );
}