<?php
/**
 * Plugin Name: Most Viewed Articles
 * Description: A widget that displays top 10 viewed articles in the last 7 and 10 days.
 * Author: Žiga
 * Version: 1.0
 */

namespace Most_Viewed_Articles;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

define( 'MVA_VERSION', '1.0' );
define( 'MVA_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'MVA_GET_TOP_POSTS_CACHE', HOUR_IN_SECONDS );
define( 'MVA_GET_TOP_POSTS_TRANSIENT', HOUR_IN_SECONDS );
define( 'MVA_WIDGET_TRANSIENT', HOUR_IN_SECONDS );

require __DIR__ . '/includes/rest-logger.php';
require __DIR__ . '/includes/widget.php';

/*
 * Most_Viewed_Articles class.
 */
class Most_Viewed_Articles {
	/*
	 * Class constructor.
	 *
	 * @since 1.0
	 *
	 * @return void
	 */
	public function __construct() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue' ) );
	}

	/*
	 * Enqueues scripts and styles.
	 *
	 * @since 1.0
	 *
	 * @return void
	 */
	public function enqueue() {
		global $post;

		wp_enqueue_style( 'mva-tabs', MVA_PLUGIN_URL . 'assets/css/mva-tabs.css', array(), MVA_VERSION );
		wp_enqueue_script( 'mva-tabs', MVA_PLUGIN_URL . 'assets/js/mva-tabs.js', array(), MVA_VERSION, true );

		if ( is_singular( 'post' ) ) {
			wp_enqueue_script( 'mva-logger', MVA_PLUGIN_URL . 'assets/js/mva-logger.js', array(), MVA_VERSION, true );
			wp_localize_script( 
				'mva-logger',
				'mvaLogger',
				array(
					'postId'  => $post->ID,
					'restUrl' => esc_url_raw( rest_url( 'mva/v1/log' ) ),
					'nonce'   => wp_hash( 'mva-nonce' . NONCE_KEY )
				)
			);
		}
	}

	/*
	 * Gets top posts for a given number of days.
	 *
	 * @since 1.0
	 *
	 * @param  int   $days  Number of days to fetch.
	 * @return array $posts Fetched posts array.
	 */
	public static function get_top_posts( $days = 7 ) {
		global $wpdb;

		$cache_key = 'mva_top_posts_' . $days;
		$cache_group = 'mva';

		$posts = wp_cache_get( $cache_key, $cache_group );
		if ( false === $posts ) {
			$posts = get_transient( $cache_key );
		}

		if ( false === $posts ) {
			$date_limit = gmdate( 'Y-m-d H:i:s', strtotime( '-' . $days . ' days' ) );

			$results = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT post_id, COUNT(*) AS views FROM {$wpdb->prefix}mva_views
					WHERE viewed_at >= %s GROUP BY post_id ORDER BY views DESC LIMIT 10",
					$date_limit
				)
			);

			$posts = array();
			foreach ( $results as $row ) {
				$posts[] = array(
					'post'  => get_post( $row->post_id ),
					'views' => (int) $row->views,
				);
			}

			wp_cache_set( $cache_key, $posts, $cache_group, MVA_GET_TOP_POSTS_CACHE );
			set_transient( $cache_key, $posts, MVA_GET_TOP_POSTS_TRANSIENT );
		}

		return $posts;
	}
}

new Most_Viewed_Articles();

/*
 * Creates the database table wp_mva_views (where 'wp_' is the currently set prefix).
 *
 * @since 1.0
 *
 * @return void
 */
register_activation_hook( __FILE__, function () {
	global $wpdb;

	$table = $wpdb->prefix . 'mva_views';
	$charset = $wpdb->get_charset_collate();

	$sql = "CREATE TABLE $table (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		post_id BIGINT UNSIGNED NOT NULL,
		ip_address VARCHAR(45) NOT NULL,
		viewed_at DATETIME NOT NULL,
		PRIMARY KEY (id),
		INDEX (post_id),
		INDEX (viewed_at)
	) $charset;";

	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta( $sql );
} );