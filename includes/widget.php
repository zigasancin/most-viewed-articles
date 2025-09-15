<?php
namespace Most_Viewed_Articles\Widget;

use Most_Viewed_Articles\Most_Viewed_Articles;

/*
 * Registers the widget.
 *
 * @since 1.0
 *
 * @return void
 */
add_action( 'widgets_init', function () {
	register_widget( __NAMESPACE__ . '\MVA_Widget' );
} );

/*
 * MVA_Widget class.
 */
class MVA_Widget extends \WP_Widget {
	/*
	 * Class constructor.
	 *
	 * @since 1.0
	 *
	 * @return void
	 */
	public function __construct() {
		parent::__construct( 'mva_widget', 'Most Viewed Articles' );
	}

	/*
	 * Displays the widget.
	 *
	 * @since 1.0
	 *
	 * @param  array  $args     Widget display arguments.
	 * @param  array  $instance Widget instance.
	 * @return string $output   Widget content.
	 */
	public function widget( $args, $instance ) {
		$cache_key = 'mva_widget_output';
		$output = get_transient( $cache_key );

		if ( $output === false ) {
			ob_start();

			echo $args['before_widget'];

			echo $args['before_title'] . 'Most Viewed Articles' . $args['after_title'];
			?>
			<div class="mva-tabs">
				<div class="mva-tab-nav">
					<button class="mva-tab-button active" data-target="mva-7days">Last 7 Days</button>
					<button class="mva-tab-button" data-target="mva-30days">Last 30 Days</button>
				</div>

				<div class="mva-tab-content">
					<div id="mva-7days" class="mva-tab-panel active">
						<ol>
							<?php
							$posts7 = Most_Viewed_Articles::get_top_posts( 7 );
							foreach ( $posts7 as $p ) {
								echo '<li><a href="' . esc_url( get_permalink( $p['post']->ID ) ) . '">' 
									. esc_html( $p['post']->post_title ) 
									. '</a> (' . intval( $p['views'] ) . ')</li>';
							}
							?>
						</ol>
					</div>
					<div id="mva-30days" class="mva-tab-panel">
						<ol>
							<?php
							$posts30 = Most_Viewed_Articles::get_top_posts( 30 );
							foreach ( $posts30 as $p ) {
								echo '<li><a href="' . esc_url( get_permalink( $p['post']->ID ) ) . '">' 
									. esc_html( $p['post']->post_title ) 
									. '</a> (' . intval( $p['views'] ) . ')</li>';
							}
							?>
						</ol>
					</div>
				</div>
			</div>
			<?php
			echo $args['after_widget'];

			$output = ob_get_clean();

			set_transient( $cache_key, $output, MVA_WIDGET_TRANSIENT );
		}

		echo $output;
	}
}

new MVA_Widget();