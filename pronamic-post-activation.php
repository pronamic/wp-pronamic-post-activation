<?php
/*
Plugin Name: Pronamic Post Activation
Plugin URI: http://pronamic.eu/wp-plugins/pronamic-post-activation/
Description: The Pronamic Post Activation plugin allows you to activate and deactivate posts through an URL with an key.
 
Version: 0.1
Requires at least: 3.0

Author: Pronamic
Author URI: http://pronamic.eu/

Text Domain: pronamic_post_activation
Domain Path: /languages/

License: GPL
*/

/**
 * Get post activation meta key
 */
function pronamic_post_activation_meta_key() {
	return '_pronamic_post_activation_key';
}


/**
 * Generate secret key
 * 
 * @param mixed $data
 * @return string
 */
function pronamic_post_activation_generate_key( $data ) {
	$string = serialize( $data );
	$key = md5( $string );

	return $key;
}

/**
 * Gravity Forms - Post data
 * 
 * @param array $post_data
 * @param array $form
 * @param array $lead
 */
function pronamic_post_activation_gform_post_data( $post_data, $form, $lead ) {
	$meta_key = pronamic_post_activation_meta_key();
	$activation_key = pronamic_post_activation_generate_key( $lead );

	$post_data['post_custom_fields'][$meta_key] = $activation_key;

	return $post_data;
}

add_action( 'gform_post_data', 'pronamic_post_activation_gform_post_data', 10, 3 );

/**
 * Link
 * 
 * @param string $post_id
 * @param string $action
 * @param string $content
 */
function pronamic_post_activation_link( $post_id, $action, $content ) {
	$result = null;

	$meta_key = pronamic_post_activation_meta_key();
	$activation_key = get_post_meta( $post_id, $meta_key, true );

	if ( ! empty( $meta_value ) ) {
		$url = add_query_arg( array(
			'id' => $post_id , 
			'key' => $activation_key , 
			'action' => $action
		), home_url() );

		$result = sprintf( '<a href="%s">%s</a>', esc_attr( $url ), $content );
	} 

	return $result;
}

/**
 * Activate link
 * 
 * @param array $atts
 * @param string $content
 */
function pronamic_post_activation_activate_link( $atts, $content ) {
	extract( shortcode_atts( array(
		'post_id' => null
	), $atts ) );

	return pronamic_post_activation_link( $post_id, 'activate', $content );
}

add_shortcode( 'pronamic_post_activate_link', 'pronamic_post_activation_activate_link' );

/**
 * Deactivate link
 * 
 * @param array $atts
 * @param string $content
 */
function pronamic_post_activation_deactivate_link( $atts, $content ) {
	extract( shortcode_atts( array(
		'post_id' => null
	), $atts ) );

	return pronamic_post_activation_link( $post_id, 'deactivate', $content );
}

add_shortcode( 'pronamic_post_deactivate_link', 'pronamic_post_activation_deactivate_link' );

/**
 * Maybe activate post
 */
function pronamic_post_activation_maybe_activate() {
	$id = filter_input( INPUT_GET, 'id', FILTER_SANITIZE_STRING );
	$key = filter_input( INPUT_GET, 'key', FILTER_SANITIZE_STRING );
	$action = filter_input( INPUT_GET, 'action', FILTER_SANITIZE_STRING );
	
	if ( ! empty( $id ) && ! empty( $key ) ) {
		$meta_key = pronamic_post_activation_meta_key();
		$activation_key = get_post_meta( $id, $meta_key, true );

		if( $key == $activation_key ) {
			switch ( $action ) {
				case 'activate':
					$activate_post = array();
					$activate_post['ID'] = $id;
					$activate_post['post_status'] = 'publish';

					wp_update_post( $activate_post );

					$redirect_url = get_permalink( $id );
					
					wp_redirect( $redirect_url );
					
					exit;

					break;
				case 'deactivate':
					$deactivate_post = array();
					$deactivate_post['ID'] = $id;
					$deactivate_post['post_status'] = 'trash';

					wp_update_post( $deactivate_post );

					$redirect_url = home_url();
					
					wp_redirect( $redirect_url );
					
					exit;

					break;
			}
		}
	}
}

add_action( 'init', 'pronamic_post_activation_maybe_activate' );
