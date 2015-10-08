<?php

class WPE_WordcampsAdmin extends WPE_Wordcamps {

    private $options_slug = 'wpe_wordcamps_';
    private $slug = 'wpe-event';

	public static function get_instance()
    {
        static $instance = null;

        if ( null === $instance ) {
            $instance = new static();
        }

        return $instance;
    }

    private function __clone(){
    }

    private function __wakeup(){
    }

	protected function __construct() {

		parent::get_instance();

        // Maybe Include CMB2
        $this->maybe_include_cmb2();

        $this->title = __( 'WPE wordcamps', 'wpengine-wordcamps' );

        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_styles' ), 15 );
        add_action( 'cmb2_init',  array( $this, 'add_wpe_event_metaboxes' ) );
        add_action( 'save_post_wpe-event', 'clear_cache_on_new_event', 10, 2 );
	}

    /**
     * Include the CMB2 framework
     *
     * Only if not already loaded
     */
    private function maybe_include_cmb2() {

        global $wpe_wordcamps_path;

        if ( ! class_exists( 'CMB2_Bootstrap_208' ) && file_exists( $wpe_wordcamps_path . '/inc/cmb2/init.php' ) ) {
            require_once $wpe_wordcamps_path . '/inc/cmb2/init.php';
        }
    }

    /**
     * Enqueue all our needed styles and scripts
     *
     * @since 0.1.0
     */
    public function enqueue_admin_styles() {
        wp_enqueue_style( 'cmb2-styles' );
    }

    /**
     * Create custom metaboxes for Events
     *
     */
    public function add_wpe_event_metaboxes() {

        $prefix = 'wpe_events_';

        $cmb = new_cmb2_box( array(
            'id'            => $prefix,
            'title'         => __( 'Event Fields', 'wpengine-wordcamps' ),
            'object_types'  => array( 'wpe-event' ),
            'priority'   => 'high',
        ) );

        $cmb->add_field( array(
            'id'    => $prefix . 'title',
            'name'  => __( 'Title', 'wpengine-wordcamps' ),
            'desc'  => __( 'Where is the event\'s title?', 'wpengine-wordcamps'),
            'type'  => 'text',
            ) );
        $cmb->add_field( array(
            'id'    => $prefix . 'date',
            'name'  => __( 'Date', 'wpengine-wordcamps' ),
            'desc'  => __( 'When is the event being held?', 'wpengine-wordcamps'),
            'type'  => 'text_date',
            ) );
        $cmb->add_field( array(
            'id'    => $prefix . 'location',
            'name'  => __( 'Location', 'wpengine-wordcamps' ),
            'desc'  => __( 'Where is the event being held?', 'wpengine-wordcamps'),
            'type'  => 'text',
            ) );
        $cmb->add_field( array(
            'id'    => $prefix . 'link',
            'name'  => __( 'Link', 'wpengine-wordcamps' ),
            'desc'  => __( 'What is the event\'s link?', 'wpengine-wordcamps'),
            'type'  => 'text_url',
            ) );
        $cmb->add_field( array(
            'id'    => $prefix . 'desc',
            'name'  => __( 'Description', 'wpengine-wordcamps' ),
            'type'  => 'wysiwyg',
            ) );
    }

    /**
     * When an event CPT is saved, clear our cache
     *
     */
    function clear_cache_on_new_event( $post_id, $post ) {

        // If this isn't a 'wpe-event' post, don't update it.
        if ( $this->slug != $post->post_type ) {
            return;
        }
        delete_transient( 'wpengine-worcamps-events' );
    }

}
