<?php
class sf_feed_settings {
		
	public function __construct() {
		$this->load_settings();
	}
	
	public function feed_afo_save_settings(){
		
		if(isset($_POST['option']) and $_POST['option'] == "feed_afo_save_settings_v1"){
			
			if ( ! isset( $_POST['feed_afo_field'] )  || ! wp_verify_nonce( $_POST['feed_afo_field'], 'feed_afo_action' ) ) {
			   wp_die( 'Sorry, your nonce did not verify.' );
			   exit;
			} 
			$data = '';
			
			update_option( 'social_feed_fetch_number_1',  sanitize_text_field($_POST['social_feed_fetch_number_1']) );
			update_option( 'facebook_user_id_1',  sanitize_text_field($_POST['facebook_user_id_1']) );
			
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
			
			if($social_feeds_return->error){
				wp_die($social_feeds_return->error);
				exit;
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
			
			
			if (!is_writable($upload_dir_path)) {
				$data .= 'It seems that the file cannot be created. This can be caused if the Parent Directory <strong>uploads</strong> is not <span style="color:red;">writable</span>.';
				$data .= '<br>';
				$data .= 'Please create a file named <strong>'.$feed_file_name.'</strong> at <strong>'.$upload_dir_path.'</strong> and put the below content.';
				$data .= '<br><br>';
				$data .= $social_feeds;
				$data .= '<br>';
				$data .= str_repeat("-", 125);
				$data .= '<br><br><br><br>';
				//wp_die($data);
			} else {
				$feed_file = fopen($feed_file, "w");
				fwrite($feed_file, $social_feeds);
				fclose($feed_file);
				$GLOBALS['msg'] = 'Feed successfully fetched';
			}

			// get social feeds //
			
			if($data != ''){
				wp_die($data);
			}				
		}
	}
	
	public function feed_afo_options() {
	global $wpdb;
	$this->show_message();
	$this->help_support();
	$this->social_feeds_pro_add();
	?>    
	<form name="f" method="post" action="">
	<?php wp_nonce_field('feed_afo_action','feed_afo_field'); ?>
	<input type="hidden" name="option" value="feed_afo_save_settings_v1" />
	<table width="98%" style="background-color:#FFFFFF; border:1px solid #CCCCCC; padding:0px 0px 0px 10px; margin:2px;">
 	  <tr>
		<td width="45%"><h1><?php echo __('Facebook Feed' ,'social-feed-shortcode'); ?></h1></td>
		<td width="55%">&nbsp;</td>
	  </tr>
      <tr>
		<td><strong><?php _e('Number of Feeds to fetch','social-feed-shortcode');?>:</strong></td>
		<td><input type="number" name="social_feed_fetch_number_1" value="<?php echo get_option('social_feed_fetch_number_1');?>"/></td>
	  </tr>
      <tr>
		<td><strong><?php _e('Facebook Page ID','social-feed-shortcode');?>:</strong></td>
		<td><input type="text" name="facebook_user_id_1" value="<?php echo get_option('facebook_user_id_1');?>"/></td>
	  </tr>
      <tr>
		<td colspan="2">
        <p style="color:#0073AA;"><strong><?php _e('Shortcode','social-feed-shortcode');?>:</strong> [socialfeed file="1"]</p>
        <?php
		$res = $wpdb->get_row( $wpdb->prepare("SELECT feed_file,feed_file_path	FROM ".$wpdb->prefix."social_feed_files WHERE feed_file_no = %d", 1), ARRAY_A );
		$feed_file = $res['feed_file'];
		
		if (file_exists($res['feed_file_path'])) {
			echo '<p><a style="color:green;" href="'.$feed_file.'" target="_blank">'.$feed_file.'</a></p>';
		} else {
			echo '<p><span style="color:red;">'.__('Feed file not found','social-feed-shortcode').' <strong>'.$feed_file.'</strong>';
			echo '<br>';
			echo __('This can be caused if the Feed is not generated / Parent Directory <strong>uploads</strong> is not writable.','social-feed-shortcode').'</span></p>';
		}
		?>
        </td>
	  </tr>
      <tr>
		<td>&nbsp;</td>
		<td><input type="submit" name="submit" value="<?php _e('Fetch & Save','social-feed-shortcode');?>" class="button button-primary button-large" /></td>
	  </tr>
      <tr>
		<td>&nbsp;</td>
		<td>&nbsp;</td>
	  </tr>
	</table>
	</form>
	<?php 
	$this->donate();
	}
	
	public function show_message(){		
		if(isset($GLOBALS['msg'])){
			echo '<div class="msg_success">'.$GLOBALS['msg'].'</div>';
			unset($GLOBALS['msg']);
		}
	}
	
	public function social_feeds_pro_add(){ ?>
	<table width="98%" border="0" style="background-color:#FFFFD2; border:1px solid #E6DB55; padding:0px 5px 0px 10px; margin:2px;">
  <tr>
    <td><p>Check out the <strong>Social Feeds PRO</strong> plugin that support feeds from <strong>Facebook</strong>, <strong>Twitter</strong>, <strong>YouTube</strong>, <strong>Flickr</strong> accounts. It requires no Setups, no Maintanance, no need to create any APPs, APIs, Client Ids, Client Secrets or anything ( Everything is maintained by aviplugins.com ). <a href="http://www.aviplugins.com/social-feeds-pro/" target="_blank">More Details</a></p></td>
  </tr>
</table>
	<?php }
	
	public function help_support(){ ?>
	<table width="98%" border="0" style="background-color:#FFFFFF; border:1px solid #CCCCCC; padding:2px 0px 2px 10px; margin:2px;">
	  <tr>
		<td align="right"><a href="http://www.aviplugins.com/support.php" target="_blank">Help and Support</a> <a href="http://www.aviplugins.com/rss/news.xml" target="_blank"><img src="<?php echo plugin_dir_url( __FILE__ ) . '/images/rss.png';?>" style="vertical-align: middle;" alt="RSS"></a></td>
	  </tr>
	</table>
	<?php
	}
	
	public function donate(){	?>
	<table width="98%" border="0" style="background-color:#FFF; border:1px solid #ccc; margin:2px;">
	 <tr>
	 <td align="right"><a href="http://www.aviplugins.com/donate/" target="_blank">Donate</a> <img src="<?php echo  plugin_dir_url( __FILE__ ) . '/images/paypal.png';?>" style="vertical-align: middle;" alt="PayPal"></td>
	  </tr>
	</table>
	<?php
	}
	
	public function social_feed_afo_text_domain(){
		load_plugin_textdomain('social-feed-shortcode', FALSE, basename( dirname( __FILE__ ) ) .'/languages');
	}
	
	public function feed_style(){
		wp_enqueue_style( 'feeds-style', plugin_dir_url( __FILE__ ) . 'feeds-style.css' );
		wp_enqueue_script( 'jquery' );
		wp_enqueue_script( 'script', plugin_dir_url( __FILE__ ) . 'script.js' );
	}
	
	public function feed_style_admin(){
		wp_enqueue_style( 'feeds-style-admin', plugin_dir_url( __FILE__ ) . 'feeds-style-admin.css' );
	}	
	
	public function feed_afo_menu() {
		add_menu_page( 'Social Feed', 'Social Feed', 'activate_plugins', 'feed_afo_options', array( $this,'feed_afo_options' ) );
		add_submenu_page( 'feed_afo_options', 'Social Feed', 'Social Feed', 'activate_plugins', 'feed_afo_options', array( $this,'feed_afo_options' ) );
	}
		
	public function load_settings(){
		add_action( 'admin_menu' , array( $this, 'feed_afo_menu' ) );
		add_action( 'admin_init', array( $this, 'feed_afo_save_settings' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'feed_style' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'feed_style_admin' ) );
		add_action( 'plugins_loaded',  array( $this, 'social_feed_afo_text_domain' ) );
	}
	
}
new sf_feed_settings;
