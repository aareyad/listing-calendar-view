<?php

use Rtcl\Helpers\Functions;
use Rtcl\Services\FormBuilder\Components\DateTime;

add_action( 'wp_enqueue_scripts', 'classima_child_styles', 18 );
function classima_child_styles() {
	wp_enqueue_style( 'classipost-child-style', get_stylesheet_uri() );
}

add_action( 'wp_enqueue_scripts', 'classima_child_scripts', 18 );
function classima_child_scripts() {
	wp_enqueue_script( "classipost-child-script", get_stylesheet_directory_uri() . "/assets/custom.js", [ "jquery" ], "1.0", true );
	if ( Functions::is_listing() ) {
		wp_enqueue_script( "classipost-listing-calendar", get_stylesheet_directory_uri() . "/assets/listing-calendar.js", [ "jquery", "rtcl-public" ],
			"1.0",
			true );
	}
}

add_action( 'after_setup_theme', 'classima_child_theme_setup' );
function classima_child_theme_setup() {
	load_child_theme_textdomain( 'classima', get_stylesheet_directory() . '/languages' );
}

// Add shortcode to display specific listing calendar data
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

// Get calendar data on ajax call
add_action( 'wp_ajax_get_listing_calendar_meta', 'evenimentul_listing_calendar_meta' );
add_action( 'wp_ajax_nopriv_get_listing_calendar_meta', 'evenimentul_listing_calendar_meta' );

function evenimentul_listing_calendar_meta() {
	if ( ! Functions::verify_nonce() ) {
		wp_send_json_error( 'Unauthorized Access!' );
	}
	$post_id = isset( $_GET['post_id'] ) ? absint( $_GET['post_id'] ) : 0;

	if ( ! $post_id ) {
		wp_send_json_error( 'Invalid post ID' );
	}

	$meta = get_post_meta( $post_id, 'evenimentul_calendar_mbhm20lv', true );

	wp_send_json_success( $meta );
}

// Register new custom field for form builder
add_filter( 'rtcl_fb_fields', function ( $fields ) {
	$fields['evenimentul_calendar'] = [
		'element'         => 'evenimentul_calendar',
		'name'            => 'custom_calendar',
		'container_class' => '',
		'class'           => '',
		'id'              => '',
		'label'           => __( 'Calendar', 'classified-listing' ),
		'label_placement' => '',
		'date_format'     => 'd/m/Y H:i',
		'placeholder'     => '',
		'help_message'    => '',
		'filterable'      => false,
		'single_view'     => true,
		'order'           => 0,
		'validation'      => [
			'required' => [
				'value'   => false,
				'message' => __( 'This field is required', 'classified-listing' ),
			]
		],
		'logics'          => '',
		'editor'          => [
			'title'      => __( 'Evenimentul Calendar', 'classified-listing' ),
			'icon_class' => 'rtcl-icon-calendar-1',
			'template'   => 'evenimentul_calendar'
		]
	];

	return $fields;
} );

// Allow settings to calendar component
add_filter( 'rtcl_fb_editor_settings_placement', function ( $placement ) {

	$placement['evenimentul_calendar'] = [
		'general' => [
			'label',
			'label_placement',
			'icon',
			'evenimentul_calendar_membership',
			'filterable',
			'filterable_date_type',
			'evenimentul_filterable_date_format'
		],
		'advance' => [
			'help_message',
			'container_class'
		]
	];

	return $placement;
} );

// Add new settings for calendar component
add_filter( 'rtcl_fb_editor_settings_fields', function ( $settingsFields ) {

	$dateTime    = new DateTime();
	$dateFormats = $dateTime->getAvailableDateFormats();

	$settingsFields['evenimentul_calendar_membership'] = [
		'template'  => 'inputYesNoCheckBox',
		'label'     => __( 'Enable only for membership user', 'classima-child' ),
		'help_text' => __( 'Only membership user able to add calendar data.', 'classima-child' ),
	];

	$settingsFields['evenimentul_filterable_date_format'] = [
		'template'    => 'select',
		'label'       => __( 'Filter Date Format', 'classima-child' ),
		'filterable'  => true,
		'creatable'   => true,
		'placeholder' => __( 'Select Date Format', 'classima-child' ),
		'options'     => $dateFormats,
		'dependency'  => [
			'depends_on' => 'filterable',
			'value'      => true,
			'operator'   => '==',
		],
	];

	return $settingsFields;
} );

// Allow for ajax filter
add_filter( 'rtcl_ajax_filter_allow_fields', function ( $fields ) {
	$fields[] = 'evenimentul_calendar';

	return $fields;
} );

// Render html for filter field
add_filter( 'rtcl_ajax_filter_cf_field_html', function ( $field_html, $field ) {
	if ( $field && 'evenimentul_calendar' == $field->getElement() ) {
		$metaKey    = $field->getMetaKey();
		$filterName = 'cf_' . $metaKey;

		$params = $_REQUEST;

		$values = ! empty( $params[ $filterName ] ) ? ( is_array( $params[ $filterName ] ) ? array_filter( array_map( function ( $param ) {
			return trim( sanitize_text_field( wp_unslash( $param ) ) );
		}, $params[ $filterName ] ) ) : trim( sanitize_text_field( wp_unslash( $params[ $filterName ] ) ) ) ) : '';

		$field_html .= sprintf( '<div class="rtcl-filter-date-field-wrap">
														<input id="filter-cf-date-%1$s" role="presentation" autocomplete="off" name="filter-cf-date-%1$s" type="text" value="%2$s" data-options="%4$s" class="form-control rtcl-filter-date-field" placeholder="%3$s">									
													</div>',
			esc_attr( $filterName ),
			esc_attr( $values ),
			esc_html__( 'Date', 'classified-listing' ),
			htmlspecialchars(
				wp_json_encode(
					$field->getDateFieldOptions(
						[
							'singleDatePicker' => $field->getData( 'filterable_date_type' ) === 'single',
							'autoUpdateInput'  => false,
						]
					)
				)
			)
		);
	}

	return $field_html;
}, 10, 2 );