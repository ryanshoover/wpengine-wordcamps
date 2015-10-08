<?php

/*
Plugin Name: WordCamps Near Visitor
Plugin URI: http://wpengine.com
Description: Show WordCamps and other events near the visitor. Powered by WP Engine and GeoIP
Version: 0.1
Author: WP Engine
Author URI: http://wpengine.com
Text Domain: wpengine-wordcamps
*/

global $wpe_wordcamps_path;
global $wpe_wordcamps_url;
$wpe_wordcamps_path = plugin_dir_path( __FILE__ );
$wpe_wordcamps_url  = plugin_dir_url( __FILE__ );

require_once( $wpe_wordcamps_path . 'lib/core.php' );
require_once( $wpe_wordcamps_path . 'lib/admin.php' );

if( class_exists( 'WPE_Wordcamps' ) ) {
	$wpe_wordcamps = WPE_Wordcamps::get_instance();
}

if( class_exists( 'WPE_WordcampsAdmin' ) ) {
	$wpe_wordcamps_admin = WPE_WordcampsAdmin::get_instance();
}
