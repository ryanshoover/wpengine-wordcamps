<?php
/**
 * Widget to show events in sidebar
 *
 */

class WPEWordCampWidget extends WP_Widget {

	public function __construct() {
		parent::__construct(
			'wpe_wordcamps', // Base ID
			__( 'WordCamps', 'wpe_wordcamps' ), // Name
			array( 'description' => __( 'Show Events near the visitor', 'wpe-wordcamps' ), ) // Args
		);
	}

	/**
	 * Outputs the content of the widget
	 *
	 * @param array $args
	 * @param array $instance
	 */
	public function widget( $args, $instance ) {

		$events = array();
		$count = $instance['count'] ? $instance['count'] : 3;

		if( class_exists('WPE_WordCampEvents' ) ) {
			$event_class = WPE_WordCampEvents::get_instance();
			$event_args = array(
				'count' => $count,
				);
			$events = $event_class->get_events( $event_args );
		}

		// ******
		// Output all the widget content
		echo $args['before_widget'];

		// Output the widget title
		if ( ! empty( $instance['title'] ) ) {
			echo $args['before_title'] . apply_filters( 'widget_title', $instance['title'] ). $args['after_title'];
		}

		// Output the city name if it is available
		if( class_exists( 'WPEngine\GeoIp' ) ) {
			$geoip = WPEngine\GeoIp::instance();

			if( $geoip->city() ) {
				echo '<p class="event-field home-location">' . ucwords( $geoip->city() ) . '</p>';
			}
		}

		echo '<ul class="events">';

		foreach( $events as $event ) {
			if( $event['website'] ) {
				$a_open = '<a href="' . $event['website'] . '">';
				$a_close = '</a>';
			} else {
				$a_open = $a_close = '';
			}

			echo <<<HTML
	<li>
		<p class="event-field event-date">{$event['date']}</p>
		<p class="event-field event-location">{$event['location']}</p>
		<p class="event-field event-name">{$a_open}{$event['title']}{$a_close}</p>
	</li>
HTML;
		}

		echo '</ul>';

		echo $args['after_widget'];
	}

	/**
	 * Outputs the options form on admin
	 *
	 * @param array $instance The widget options
	 */
	public function form( $instance ) {
		$title = ! empty( $instance['title'] ) ? $instance['title'] : __( 'WordCamps', 'wpengine-wordcamps' );
		$count = ! empty( $instance['count'] ) ? $instance['count'] : 3;

		echo '<p>';
		echo '<label for="' . $this->get_field_id( 'title' ) . '">' . __( 'Title:', 'wpengine-wordcamps' ) . '</label>';
		echo '<input class="widefat" id="' . $this->get_field_id( 'title' ) . '" name="' . $this->get_field_name( 'title' ) . '" type="text" value="' . esc_attr( $title ) . '">';
		echo '</p>';

		echo '<p>';
		echo '<label for="' . $this->get_field_id( 'count' ) . '">' . __( 'Number of Events:', 'wpengine-wordcamps' ) . '</label>';
		echo '<select class="widefat" id="' . $this->get_field_id( 'count' ) . '" name="' . $this->get_field_name( 'count' ) . '">';
		for( $i = 1; $i <= 10; $i++ ) {
			$selected = $count == $i ? 'selected' : '';
			echo "<option value='{$i}' {$selected}>{$i}</option>";
		}
		echo '</select>';
		echo '</p>';
	}
}

// Register the widget in a PHP version - agnostic way
function register_wordcamps_widget() {
	register_widget( 'WPEWordCampWidget' );
}
add_action( 'widgets_init', 'register_wordcamps_widget');
