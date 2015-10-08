<?php

class WPE_Wordcamps {

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
        global $wpe_wordcamps_path;
        require_once( $wpe_wordcamps_path . 'lib/events.php' );
        require_once( $wpe_wordcamps_path . 'lib/widgets/wordcamps.php' );

        add_action( 'init', array( $this, 'create_cpts' ) );
	}

    /**
     * Create the Event CPT
     *
     */
    public function create_cpts() {
        $labels = array(
            'name'               => _x( 'Events', 'post type general name', 'wpengine-wordcamps' ),
            'singular_name'      => _x( 'Event', 'post type singular name', 'wpengine-wordcamps' ),
            'menu_name'          => _x( 'Events', 'admin menu', 'wpengine-wordcamps' ),
            'name_admin_bar'     => _x( 'Event', 'add new on admin bar', 'wpengine-wordcamps' ),
            'add_new'            => _x( 'Add Event', 'book', 'wpengine-wordcamps' ),
            'add_new_item'       => __( 'Add New Event', 'wpengine-wordcamps' ),
            'new_item'           => __( 'New Event', 'wpengine-wordcamps' ),
            'edit_item'          => __( 'Edit Event', 'wpengine-wordcamps' ),
            'view_item'          => __( 'View Event', 'wpengine-wordcamps' ),
            'all_items'          => __( 'All Events', 'wpengine-wordcamps' ),
            'search_items'       => __( 'Search Events', 'wpengine-wordcamps' ),
            'parent_item_colon'  => __( 'Parent Events:', 'wpengine-wordcamps' ),
            'not_found'          => __( 'No events found.', 'wpengine-wordcamps' ),
            'not_found_in_trash' => __( 'No events found in Trash.', 'wpengine-wordcamps' )
        );

        $args = array(
            'labels'             => $labels,
            'description'        => __( 'Events that show with WordCamps', 'wpengine-wordcamps' ),
            'public'             => true,
            'publicly_queryable' => false,
            'show_ui'            => true,
            'show_in_menu'       => true,
            'query_var'          => true,
            'rewrite'            => array( 'slug' => 'event' ),
            'capability_type'    => 'post',
            'has_archive'        => true,
            'hierarchical'       => false,
            'menu_position'      => null,
            'supports'           => array( 'title' ),
        );

        register_post_type( 'wpe-event', $args );
    }
}
