<?php
function sf_socialfeed_shortcode_func( $atts ) {
    $a = shortcode_atts( array(
        'file' => '',
    ), $atts );
	
	
	if(!$a['file'])
	return 'file not selected';
	
	$feed = new sf_social_feeds;
	$r = $feed->view($a['file']);
	return $r;
}

add_shortcode( 'socialfeed', 'sf_socialfeed_shortcode_func' );

class sf_social_feeds{
	
	public function message_text($data = '', $limit = 100){
		$len = strlen($data);
		if( $len > $limit ){
			return substr($data, 0, $limit).'..';
		} else {
			return $data;
		}
	}
	
	public function view($file = ''){
		
	global $wpdb;
	$res = $wpdb->get_row( $wpdb->prepare("SELECT feed_file,feed_file_path	FROM ".$wpdb->prefix."social_feed_files WHERE feed_file_no = %d", $file ), ARRAY_A );
	ob_start();
	$feed_file = $res['feed_file'];
	
	if (!file_exists($res['feed_file_path'])) {
		return __('File not found','social-feed-shortcode');
	}
	?> 
  
  <div id="parent" class="feed-parent">
    <?php
$social_feeds = file_get_contents($feed_file);
$social_feeds = json_decode($social_feeds);
$id = 0;
if(is_array($social_feeds)){
foreach($social_feeds as $key => $value){
	if($value->image){
	$id++;
	?>
    <div class="smbox <?php echo $value->type;?>"><a href="<?php echo $value->link;?>">
      <div>
      <img src="<?php echo plugin_dir_url( __FILE__ );?>/images/loader.gif" alt="<?php echo $value->type;?>" id="im<?php echo $id;?>" class="loader"/> 
	  <script>FeedImageLoaded('<?php echo $value->image;?>','im<?php echo $id;?>');</script>
      <span class="smfooter"><?php echo $value->user;?><span><?php echo date("j M, Y", strtotime($value->created_time));?></span></span>
        <div class="smbox_info"><?php echo $this->message_text($value->message, 150);?></div>
      </div>
      </a> </div>
    <?php
	} else {
	?>
    <div class="smbox <?php echo $value->type;?>">
      <div>
        <div><?php echo $this->message_text($value->message, 150);?></div>
        <a href="<?php echo $value->link;?>" class="smfooter"><?php echo $value->user;?><span><?php echo date("j M, Y", strtotime($value->created_time));?></span></a></div>
    </div>
    <?php
	}
}
}
?>
  </div>
	<?php
	return ob_get_clean();
	}
	
}