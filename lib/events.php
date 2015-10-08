<?php
/**
 * Events management class
 *
 * Handles all functionality tied to showing events
 * @todo need full docblock
 */

class WPE_WordcampEvents {

	public static function get_instance() {

        static $instance = null;

        if ( null === $instance )
			$instance = new static();

        return $instance;
    }

	private function __clone(){
    }

    private function __wakeup(){
    }

	protected function __construct() {
        date_default_timezone_set ( get_option('timezone_string') );
	}

    /**
     * Get Events from multiple sources
     *
     * @todo Need full docblock
     */

    public function get_events( $user_args = array() ) {

        $default_args = array(
            'count' => 3,
            );

        $args = apply_filters( 'wpe_get_events_args', wp_parse_args( $user_args, $default_args ) );

        // Get any cached events from the database
        $events = get_transient( 'wpengine-wordcamps-events' );

        if ( ! is_array( $events ) ) {
            $events = $this->get_new_events();
        }

        // Calculate the distance to the events and sort by closest ones.
        $events = $this->sort_events_by_distance( $events );

        // Show only needed number of events
        $events = array_slice( $events, 0, $args['count'] );

        return apply_filters( 'wpe_events_post_fetch', $events, $args );
    }

    /**
     * Fetch new events from all sources
     *
     * @return array $events The events from RSS feeds and the CPT
     */
    public function get_new_events() {

        $events = array();

        $feeds = apply_filters( 'wpe_get_events_feeds', array( 'https://central.wordcamp.org/wordcamps/feed/' ) );

        foreach( $feeds as $feed ) {
            $events = array_merge( $events, $this->get_events_from_feed( $feed ) );
        }

        $events = array_merge( $events, $this->get_events_from_cpt() );

        // Remove any previous events and sort by time
        $events = $this->sort_events_by_timestamp( $events );

        // Get the location of the events if we haven't already
        foreach( $events as $key => $event ) {
            if( ! isset( $event['latlng'] ) || ! $event['latlng'] ) {
                $events[ $key ]['latlng'] = $this->get_location_data( $event['location'] );
            }
        }

        // Store the events for 6 hours so we don't exhaust our API
        set_transient( 'wpengine-wordcamps-events', $events, HOUR_IN_SECONDS * 6 );

        return $events;
    }

    /**
     * Get events from an RSS feed
     *
     * Gets all events from a provided RSS URI
     *
     * @todo currently hard coded to the wordpress.org WordCamp content structure. Needs to be flexible
     * @param string $uri The URI of the feed to fetch
     * @return array $events The events from the provided URI
     */
    public function get_events_from_feed( $uri ) {
        include_once(ABSPATH . WPINC . '/feed.php');

        $feed = fetch_feed( $uri );

        $events = array();

        foreach( $feed->get_items() as $item ) {
            // Get the title of the event
            $title = $item->get_title();

            // Parse the content for the information we need
            $content = wp_strip_all_tags( $item->get_content() );
            preg_match( '/^([^\n\r]*)/', $content, $desc_arr );
            preg_match( '/(\w+\s\d{1,2},\s\d{4})/', $content, $date_arr );
            preg_match( '/Location:[\s\t\n\r]+([^\t\n\r]+)/', $content, $location_arr );
            preg_match( '/(https?:\/\/?[\da-z\.-]+\.[a-z\.]{2,6}[\/\w \.-]*\/?)/', $content, $website_arr );

            // Create event array
            $event = array(
                'title'    => $title,
                'desc'     => trim( array_shift( $desc_arr ) ),
                'date'     => trim( array_shift( $date_arr ) ),
                'location' => trim( array_pop( $location_arr ) ),
                'website'  => trim( array_shift( $website_arr ) ),
                );

            // Null the description if it has one of the other fields in it
            if( false !== stripos( $event['desc'], 'Date:' ) ||
                false !== stripos( $event['desc'], 'Location:' ) ||
                false !== stripos( $event['desc'], 'Website:' ) ) {
                $event['desc'] = '';
            }

            // Create a timestamp from the date
            $event['timestamp'] = strtotime( $event['date'] );

            // Add the event to the events array
            $events[] = $event;
        }

        return $events;
    }

    /**
     * Get events from CPT
     *
     * Gets all events from the Events custom post type
     *
     * @return array $events The events from the provided URI
     */
    public function get_events_from_cpt() {
        $events = array();

        $args = array(
            'post_type' => 'wpe-event',
            'posts_per_page' => -1,
            );

        $posts = get_posts( $args );

        foreach( $posts as $post ) {
            $meta = get_post_meta( $post->ID );

            $event_timestamp = strtotime( $meta['wpe_events_date'][0] );

            if( time() > $event_timestamp ) {
                // continue;
            }

            $events[] = array(
                'title'    => $meta['wpe_events_title'][0],
                'desc'     => $meta['wpe_events_desc'][0],
                'date'     => date( 'F j, Y', $event_timestamp),
                'location' => $meta['wpe_events_location'][0],
                'website'  => $meta['wpe_events_link'][0],
                'timestamp' => $event_timestamp,
                );
        }

        return $events;
    }

    /**
     * Get the lat/lng of an address
     *
     * Uses the Google Maps API to determine location data
     *
     * @param string $address Location string ready for submission to Google Maps
     * @return array $location Google processed location data
     */
     private function get_location_data( $address ) {

        if( empty( $address ) || !is_string( $address) ) {
            return false;
        }

        $key = 'AIzaSyB6r0AwZFb7SooTK9Brgcfth363_nz9I5c';

        $api_url = 'https://maps.googleapis.com/maps/api/geocode/json?key=' . $key . '&address=' . urlencode( $address );

        $response_raw = wp_remote_get( $api_url );

        if( is_wp_error( $response_raw ) ) {
            return false;
        }

        $response = json_decode( $response_raw['body'] );

        if( 'OK' != $response->status ) {
            return false;
        }

        $location = array(
            'lat' => $response->results[0]->geometry->location->lat,
            'lng' => $response->results[0]->geometry->location->lng,
            // 'address' => $response['results']['address_components'],
            // 'formatted_address' => $response['results']['formatted_address'],
            );

        return $location;
     }

    /**
     * Sorts events by a timestamp field
     *
     * @param array $events The events before sorting
     * @return array $events The events after sorting
     */
    private function sort_events_by_timestamp( $events ) {
        $now = time();
        $future = $now + 60*60*24*30; // 1 month in the future

        foreach ( $events as $key => $event ) {

            // Unset any events in the past or more than 1 month out
            if( $event['timestamp'] < $now || $event['timestamp'] > $future ) {
                unset( $events[ $key ] );
                continue;
            }

            $times[$key] = $event['timestamp'];
        }

        // Sort the events by their timestamp
        array_multisort($times, SORT_ASC, $events);

        return $events;
    }

    /**
     * Sorts events by distance from user
     *
     * Uses GeoIP to determine user's location
     *
     * @link http://andrew.hedges.name/experiments/haversine/
     * @param array $events The events before sorting
     * @return array $events The events after sorting
     */
    private function sort_events_by_distance( $events ) {
        // If we can't get the user's location, abort
        if( ! class_exists( 'WPEngine\GeoIp' ) ) {
            return $events;
        }

        $geo    = WPEngine\GeoIp::instance();
        $lat1   = deg2rad( $geo->latitude() );
        $lng1   = deg2rad( $geo->longitude() );
        $radius = 3961; // Radius of the Earth in miles
        $dist   = array();

        foreach( $events as $key => $event ) {
            // If there's no latlng, let's set it to a ridiculously huge distance
            if( ! $event['latlng'] ) {
                $events[ $key ]['distance'] = 1000000;
                $dist[ $key ] = 1000000;
                continue;
            }

            $lng2 = deg2rad( $event['latlng']['lng'] );
            $lat2 = deg2rad( $event['latlng']['lat'] );

            $dlng = $lng2 - $lng1;
            $dlat = $lat2 - $lat1;

            $a = ( sin( $dlat / 2 ) * sin( $dlat / 2 ) ) + ( cos( $lat1 ) * cos( $lat2 ) * sin( $dlng / 2 ) * sin( $dlng / 2 ) );
            $c = 2 * atan2( sqrt( $a ), sqrt( 1 - $a ) );
            $d = $radius * $c;

            $events[ $key ]['distance'] = $d;
            $dist[ $key ] = $d;
        }

        // Sort the events by their timestamp
        array_multisort($dist, SORT_ASC, $events);

        return $events;
    }
}
