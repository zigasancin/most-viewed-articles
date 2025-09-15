<?php
namespace Most_Viewed_Articles\Rest_Logger;

/*
 * Rest_logger class.
 */
class Rest_Logger {
	/*
	 * Class constructor.
	 *
	 * @since 1.0
	 *
	 * @return void
	 */
	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'register_rest' ) );
	}

	/*
	 * Registers the rest route.
	 *
	 * @since 1.0
	 *
	 * @return void
	 */
	public function register_rest() {
		register_rest_route(
			'mva/v1',
			'/log',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'rest_log_view' ),
				'permission_callback' => '__return_true'
			)
		);
	}

	/*
	 * Logs the post view.
	 *
	 * @since 1.0
	 *
	 * @param  array          $request WP_REST_Request array.
	 * @return WP_Error|array          Returns a WP_Error if invalid nonce or invalid post ID is provided, else a success array.
	 */
	public function rest_log_view( \WP_REST_Request $request ) {
		global $wpdb;

		$nonce = $request->get_header( 'X-MVA-Nonce' );
		if ( wp_hash( 'mva-nonce' . NONCE_KEY ) !== $nonce ) {
			return new \WP_Error( 'invalid_nonce', 'Invalid security token', array( 'status' => 403 ) );
		}

		$post_id = intval( $request->get_param( 'post_id' ) );
		if ( ! $post_id ) {
			return new \WP_Error( 'invalid_post', 'Invalid post ID', array( 'status' => 400 ) );
		}

		// Tries to use the Cloudflare IP header, else goes with the server default
		$ip = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['REMOTE_ADDR'];

		$recent = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->prefix}mva_views WHERE post_id = %d AND ip_address = %s AND viewed_at >= (NOW() - INTERVAL 1 HOUR)",
				$post_id, $ip
			)
		);

		if ( 0 == $recent ) {
			$wpdb->insert(
				$wpdb->prefix . 'mva_views',
				array(
					'post_id'    => $post_id,
					'ip_address' => $ip,
					'viewed_at'  => current_time( 'mysql' )
				),
				array(
					'%d',
					'%s',
					'%s'
				)
			);

			delete_transient( 'mva_top_posts_7' );
			delete_transient( 'mva_top_posts_30' );

			delete_transient( 'mva_widget_output' );
		}

		return array( 'success' => true );
	}
}

new Rest_Logger();