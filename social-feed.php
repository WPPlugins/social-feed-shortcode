<?php
/*
Plugin Name: Social Feed Shortcode
Plugin URI: https://wordpress.org/plugins/social-feed-shortcode/
Description: Display social feeds in your site in a different way. This version supports display feeds from facebook. Enter your Facebook ID or Facebook Page ID and put the given shortcode in a page.
Version: 2.1.2
Text Domain: social-feed-shortcode
Domain Path: /languages
Author: aviplugins.com
Author URI: http://www.aviplugins.com/
*/

/**
	  |||||   
	<(`0_0`)> 	
	()(afo)()
	  ()-()
**/

define('sf_social_api_url', 'http://www.aviplugins.com/api/social-feed-api/facebook-feeds.php');
define('sf_social_api_key', 'f295bc59c2aeecb35f8328dce146201c'); // key for the free version. do not change this.

include_once dirname( __FILE__ ) . '/settings.php';
include_once dirname( __FILE__ ) . '/feed-shortcode.php';
include_once dirname( __FILE__ ) . '/feed-widget.php';

function sf_social_feed_init_start() {
	global $wpdb;
	$wpdb->query("CREATE TABLE IF NOT EXISTS `".$wpdb->prefix."social_feed_files` (
	`feed_id` int(11) NOT NULL AUTO_INCREMENT,
	`feed_file` varchar(255) NOT NULL,
	`feed_file_path` varchar(255) NOT NULL,
	`feed_file_no` int(5) NOT NULL,
	`created_on` datetime NOT NULL,
	PRIMARY KEY (`feed_id`)
	)");
	
	wp_schedule_event(time(), 'daily', 'sf_feed_schedule_afo');
}

function sf_social_feed_init_stop() {
	wp_clear_scheduled_hook('sf_feed_schedule_afo');
}

function sf_feed_schedule_afo_once_a_day() {
		// get social feeds //
		$url = sf_social_api_url;

		$postdata = array(
			'social_feed_key' => sf_social_api_key,
			'facebook_name' => get_option('facebook_user_id_1'),
			'number_of_feed' => (int)get_option('social_feed_fetch_number_1') == ''?5:get_option('social_feed_fetch_number_1'),
			'user_ip' => $_SERVER['REMOTE_ADDR'],
			'site_url' => site_url(),
		);
		
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$social_feeds = curl_exec($ch);
		curl_close($ch);
		
		$social_feeds_return = json_decode($social_feeds);
		
		if($social_feeds_return->error != ''){
			return;
		}
					
		$upload_dir = wp_upload_dir(); 
		$upload_dir_path = $upload_dir['path'];
		$feed_file_name = '1-feed.json';
		$feed_file = $upload_dir['path'].'/'.$feed_file_name;
		
		// create or update entry //
		global $wpdb;
		$res = $wpdb->get_row( $wpdb->prepare("SELECT feed_file FROM ".$wpdb->prefix."social_feed_files WHERE feed_file_no = %d", 1), ARRAY_A );
		if($res['feed_file']){
			$udt['feed_file'] = $upload_dir['url'].'/'.$feed_file_name;
			$udt['feed_file_path'] = $feed_file;
			$udt['created_on'] = date("Y-m-d H:i:s");
			$where = array('feed_file_no' => 1);
			$wpdb->update( $wpdb->prefix."social_feed_files", $udt, $where );
		} else {
			$ins['feed_file'] = $upload_dir['url'].'/'.$feed_file_name;
			$ins['feed_file_path'] = $feed_file;
			$ins['feed_file_no'] = 1;
			$ins['created_on'] = date("Y-m-d H:i:s");
			$wpdb->insert( $wpdb->prefix."social_feed_files", $ins );
		}
		
		// create or update entry //
		if (is_writable($upload_dir_path)) {
			$feed_file = fopen($feed_file, "w");
			fwrite($feed_file, $social_feeds);
			fclose($feed_file);
		}
		// get social feeds //			
}

register_activation_hook( __FILE__, 'sf_social_feed_init_start' );
register_deactivation_hook( __FILE__, 'sf_social_feed_init_stop' );
add_action( 'sf_feed_schedule_afo', 'sf_feed_schedule_afo_once_a_day' );