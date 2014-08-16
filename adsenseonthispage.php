<?php
/*
Plugin Name: AdSense on this page
Plugin URI: https://productforums.google.com/d/topic/adsense/ysD9etg7P1A/discussion
Description: With this plugin you can disable AdSense ads on individual pages and posts. This is NOT stand-alone plugin, it can be used only in addition to your existing solution for showing AdSense ads. It should work with plugins, and if ad code is pasted in template or text widget, both synchronous and asynchronous, both content and link units. HOW IT WORKS: If you disable ads on page, this plugin will search page source and it will replace all occurances of adsbygoogle.js and show_ads.js with empty.js. For more info, please see plugin source. TERMS OF USE: [1] YOU UNDERSTAND THERE ARE NO GUARANTEES THIS PLUGIN WILL WORK AS EXPECTED, AND YOU WILL USE IT ON YOUR OWN RISK. I (DEVELOPER) WILL NOT ACCEPT ANY RESPOSIBLITY FOR ANY KIND OF DAMAGE. [2] YOU UNDERSTAND THAT IF YOU HAVE PROHIBITED CONTENT ON SITE, YOU MUST REMOVE THAT CONTENT, NOT ADS. AND YOU WILL USE THIS PLUGIN TO RESOLVE PAGE-SPECIFIC ISSUES WITH QUALITY, FOR EXAMPLE CONTACT PAGE, ABOUT PAGE, ETC. [3] YOU WILL NOT USE PLUGIN IF YOU DON'T UNDERSTAND OR DISAGREE WITH TERMS.
Version: 0.1
Author: galeksic
Author URI: https://productforums.google.com/forum/#!profile/adsense/APn2wQdYA5g69lgel_b3Jm72EpWgrIkFvqpazW9w2VhzqkYSZhsN0DRKW5dkPSrko18CUVwbv6sg
*/

function adsenseonthispage_replace($html_source) {
	/* If you know how to do this better, please let me know. */
	if( strpos($_SERVER["REQUEST_URI"], 'wp-admin') === false &&  get_post_meta( get_the_ID(), '_adsenseonthispage', true ) == 'false' ){
		$html_source = str_replace('http://pagead2.', '//pagead2.', $html_source);
		$html_source = str_replace('//pagead2.googlesyndication.com/pagead/js/adsbygoogle.js', plugins_url( 'empty.js' , __FILE__ ), $html_source);
		$html_source = str_replace('//pagead2.googlesyndication.com/pagead/show_ads.js', plugins_url( 'empty.js' , __FILE__ ), $html_source);
	}
	return $html_source;
}

function adsenseonthispage_start() {
	ob_start("adsenseonthispage_replace");
}

function adsenseonthispage_end() {
	ob_end_flush();
}

add_action('init', 'adsenseonthispage_start', 100);

function adsenseonthispage_add_meta_box() {
	$screens = array( 'post', 'page' );
	foreach ( $screens as $screen ) {
		add_meta_box(
			'adsenseonthispage_id',
			'AdSense ads on this ' . $screen,
			'adsenseonthispage_meta_box_callback',
			$screen,
			'side',
			'high'
		);
	}
}
add_action( 'add_meta_boxes', 'adsenseonthispage_add_meta_box' );

function adsenseonthispage_meta_box_callback( $post ) {
	wp_nonce_field( 'adsenseonthispage_meta_box', 'adsenseonthispage_meta_box_nonce' );
	$value = get_post_meta( $post->ID, '_adsenseonthispage', true );
	echo '<label for="adsenseonthispage_new_field">';
	echo 'Status&nbsp;';
	echo ( ( $value == 'false' ) ? '<span style="color:yellow; background:red; ">&nbsp;DISABLED!&nbsp;</span>&nbsp;' : 'OK&nbsp;' );
	echo '</label> ';
	echo '<input type="checkbox" id="adsenseonthispage_new_field" name="adsenseonthispage_new_field" ' . ( ( $value == 'false' ) ? '' : 'checked="checked"' ) . ' />';
}

function adsenseonthispage_save_meta_box_data( $post_id ) {

	if ( ! isset( $_POST['adsenseonthispage_meta_box_nonce'] ) ) {
		return;
	}

	if ( ! wp_verify_nonce( $_POST['adsenseonthispage_meta_box_nonce'], 'adsenseonthispage_meta_box' ) ) {
		return;
	}

	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	if ( isset( $_POST['post_type'] ) && 'page' == $_POST['post_type'] ) {

		if ( ! current_user_can( 'edit_page', $post_id ) ) {
			return;
		}

	} else {

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
	}

	if ( ! isset( $_POST['adsenseonthispage_new_field'] ) ) {
		update_post_meta( $post_id, '_adsenseonthispage', 'false' );
		return;
	}

	update_post_meta( $post_id, '_adsenseonthispage', 'yes' );
}
add_action( 'save_post', 'adsenseonthispage_save_meta_box_data' );

function adsenseonthispage_admin_column_value( $column, $post_id ) {
    echo ( ( get_post_meta( $post_id, '_adsenseonthispage', true ) == 'false' ) ? '<a href="' . get_edit_post_link( ) . '" style="color:yellow; background:red; "> DISABLED! </a>' : 'OK' );
}
add_action( 'manage_posts_custom_column' , 'adsenseonthispage_admin_column_value', 10, 2 );
add_action( 'manage_pages_custom_column' , 'adsenseonthispage_admin_column_value', 10, 2 );

function adsenseonthispage_admin_column($columns) {
    return array_merge( $columns, 
              array('adsenseonthispage' => 'AdSense' ) );
}
add_filter('manage_pages_columns' , 'adsenseonthispage_admin_column');
add_filter('manage_posts_columns' , 'adsenseonthispage_admin_column');

function adsenseonthispage_style() {
  echo '<style> .column-adsenseonthispage{width: 120px} </style>';
}
add_action('admin_head', 'adsenseonthispage_style');
add_action( 'admin_bar_menu', 'adsenseonthispage_admin_bar', 999 );
function adsenseonthispage_admin_bar( $wp_admin_bar ) {
	if ( ! is_single() && ! is_page() ) {
		return;
	}
	$status = ( ( get_post_meta( get_the_ID(), '_adsenseonthispage', true ) == 'false' ) ? '<span style="color:yellow; background:red; ">&nbsp;AdSense ads are disabled on this page&nbsp;</span>' : '&#10003;' );
	$args = array(
		'id'    => 'adsenseonthispage_status',
		'title' => $status,
		'href'  => get_edit_post_link( ),
		'meta'  => array( 'title' => 'AdSense on this page configuration' )
	);
	$wp_admin_bar->add_node( $args );
}
