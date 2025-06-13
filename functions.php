<?php

use Rtcl\Helpers\Functions;
use Rtcl\Models\Form\Form;
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
			'filterable_date_type'
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

	return $settingsFields;
} );

// Allow for ajax filter
add_filter( 'rtcl_ajax_filter_allow_fields', function ( $fields ) {
	$fields[] = 'evenimentul_calendar';

	return $fields;
} );

// Render html for filter field
add_filter( 'rtcl_ajax_filter_cf_field_html', function ( $field_html, $field, $params ) {
	if ( $field && 'evenimentul_calendar' == $field->getElement() ) {
		$metaKey    = $field->getMetaKey();
		$filterName = 'cf_' . $metaKey;

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
							'locale'           => [
								'format' => 'MMMM D, YYYY'
							]
						]
					)
				)
			)
		);
	}

	return $field_html;
}, 10, 3 );


// testing
function business_hours_shortcode( $atts ) {
	$atts = shortcode_atts( [
		'view' => 'single' // or 'group'
	], $atts, 'business_hours' );

	$hours = [
		'Monday'    => [ '11:00 AM – 4:00 PM' ],
		'Tuesday'   => [ '11:00 AM – 4:00 PM' ],
		'Wednesday' => [ '11:00 AM – 4:00 PM' ],
		'Thursday'  => [ '10:00 AM – 02:00 PM' ],
		'Friday'    => [ '10:00 AM – 02:00 PM', '04:00 PM – 11:00 PM' ],
		'Saturday'  => [],
		'Sunday'    => [],
	];

	$days          = [ 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday' ];
	$start_of_week = (int) get_option( 'start_of_week' );
	$ordered_days  = array_merge( array_slice( $days, $start_of_week ), array_slice( $days, 0, $start_of_week ) );
	$day_indices   = array_flip( $ordered_days );

	ob_start();
	echo '<div class="business-hours"><ul>';

	if ( $atts['view'] === 'single' ) {
		foreach ( $ordered_days as $day ) {
			$slots = $hours[ $day ] ?? [];
			echo '<li><strong>' . esc_html( $day ) . ':</strong> ' . esc_html( empty( $slots ) ? 'Closed' : implode( ', ', $slots ) ) . '</li>';
		}
	} else {
		// Group days by identical times
		$grouped = [];
		foreach ( $hours as $day => $slots ) {
			$key               = empty( $slots ) ? 'Closed' : implode( '|', $slots );
			$grouped[ $key ][] = $day;
		}

		foreach ( $grouped as $time_key => $days_group ) {
			usort( $days_group, fn( $a, $b ) => $day_indices[ $a ] - $day_indices[ $b ] );
			$label = format_day_ranges( $days_group, $ordered_days );
			$time  = $time_key === 'Closed' ? 'Closed' : str_replace( '|', ', ', $time_key );
			echo '<li><strong>' . esc_html( $label ) . ':</strong> ' . esc_html( $time ) . '</li>';
		}
	}

	echo '</ul></div>';

	return ob_get_clean();
}

add_shortcode( 'business_hours', 'business_hours_shortcode' );

function format_day_ranges( array $days, array $ordered_days ): string {
	$index  = array_flip( $ordered_days );
	$ranges = [];
	$start  = $end = $days[0];

	for ( $i = 1, $len = count( $days ); $i < $len; $i ++ ) {
		if ( $index[ $days[ $i ] ] === $index[ $end ] + 1 ) {
			$end = $days[ $i ];
		} else {
			$ranges[] = $start === $end ? $start : "$start – $end";
			$start    = $end = $days[ $i ];
		}
	}
	$ranges[] = $start === $end ? $start : "$start – $end";

	return implode( ', ', $ranges );
}

// filter localize data
add_filter( 'rtcl_localize_fb_params', function ( $localized_data ) {

	if ( class_exists( 'RtclStore' ) ) {
		$member                               = rtclStore()->factory->get_membership( get_current_user_id() );
		$localized_data['is_membership_user'] = is_user_logged_in() && \RtclStore\Helpers\Functions::is_membership_enabled() && ! $member->is_expired();
	}

	return $localized_data;
} );

// filter listings based on availability

add_filter( 'rtcl_ajax_filter_load_data_query_args', function ( $args, $params ) {
	global $wpdb;

	$meta_key  = 'evenimentul_calendar_mbhm20lv'; // update with field name
	$param_key = 'cf_' . $meta_key;

	if ( ! empty( $params ) && isset( $params[ $param_key ] ) ) {
		$date = sanitize_text_field( $params[ $param_key ] );

		$form  = Form::query()->find( 14 ); // update with form id
		$field = $form->getFieldBy( 'name', $meta_key );

		$date_type = $field['filterable_date_type'] ?? 'single';

		$available_listings = [];

		if ( ! empty( $date ) ) {

			$post_ids = $wpdb->get_col( $wpdb->prepare( "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = %s", $meta_key ) );

			if ( 'range' === $date_type ) {
				$dates      = explode( '-', $date );
				$start_date = date( 'Y-m-d', strtotime( trim( $dates[0] ) ) );
				$end_date   = date( 'Y-m-d', strtotime( trim( $dates[1] ) ) );

				// Convert to DateTime
				$start = new \DateTime( $start_date );
				$end   = new \DateTime( $end_date );
				$end->modify( '+1 day' ); // include end date

				$interval = new \DateInterval( 'P1D' ); // 1 day
				$period   = new \DatePeriod( $start, $interval, $end );

				foreach ( $period as $date ) {
					$date_arr = explode( '-', $date->format( 'Y-m-d' ) );

					if ( ! empty( $post_ids ) ) {
						foreach ( $post_ids as $post_id ) {
							$calendar_data = get_post_meta( $post_id, $meta_key, true );
							$data          = $calendar_data[ $date_arr[0] ][ $date_arr[1] ][ $date_arr[2] ];

							if ( isset( $data['status'] ) && 'available' === $data['status'] ) {
								$available_listings[] = $post_id;
							}
						}
					}
				}
			} else {
				$date_arr = explode( '-', date( 'Y-m-d', strtotime( $date ) ) );

				if ( ! empty( $post_ids ) ) {
					foreach ( $post_ids as $post_id ) {
						$calendar_data = get_post_meta( $post_id, $meta_key, true );
						$data          = $calendar_data[ $date_arr[0] ][ $date_arr[1] ][ $date_arr[2] ];

						if ( isset( $data['status'] ) && 'available' === $data['status'] ) {
							$available_listings[] = $post_id;
						}
					}
				}
			}
		}

		if ( ! empty( $available_listings ) ) {

			$args['post__in'] = array_unique( $available_listings );

			if ( ! empty( $args['meta_query'] ) ) {
				foreach ( $args['meta_query'] as $meta_index => $meta ) {
					if ( isset( $meta['key'] ) && $meta_key === $meta['key'] ) {
						unset( $args['meta_query'][ $meta_index ] );
					}
				}
			}
		}

	}

	return $args;
}, 10, 2 );