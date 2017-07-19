<?php
class Feed_Widget extends WP_Widget {

	public function __construct() {
		parent::__construct(
			'feed_widget', 
			__( 'Social Feed Widget', 'social-feed-shortcode' ), 
			array( 'description' => __( 'Widget to display social feeds', 'social-feed-shortcode' ) ) 
		);
	}

	
	public function message_text($data = '', $limit = 100){
		$len = strlen($data);
		if( $len > $limit ){
			return substr($data, 0, $limit).'..';
		} else {
			return $data;
		}
	}
	
	public function widget( $args, $instance ) {
		global $wpdb;
		echo $args['before_widget'];
		if ( ! empty( $instance['title'] ) ) {
			echo $args['before_title'] . apply_filters( 'widget_title', $instance['title'] ) . $args['after_title'];
		}
		$no_of_feeds = ! empty( $instance['no_of_feeds'] ) ? (int) $instance['no_of_feeds'] : '';
		
		$res = $wpdb->get_row( $wpdb->prepare("SELECT feed_file,feed_file_path	FROM ".$wpdb->prefix."social_feed_files WHERE feed_file_no = %d", 1 ), ARRAY_A );
		ob_start();
		$feed_file = $res['feed_file'];
		
		echo '<div class="sf-widget">';
		if (!file_exists($res['feed_file_path'])) {
			echo '<div class="sf-widget-list">';
			echo __('File not found','social-feed-shortcode');
			echo '</div>';
		} else {
			$social_feeds = file_get_contents($feed_file);
			$social_feeds = json_decode($social_feeds);
			$cnt = 0;
			if(is_array($social_feeds)){
				foreach($social_feeds as $key => $value){
					if( $no_of_feeds > 0 && $no_of_feeds > $cnt ){
						echo '<div class="sf-widget-list smbox '.$value->type.'">';
							echo '<a href="'.$value->link.'"><div>';
							if(!empty($value->image)){				
							  echo '<img alt="feed-image" class="sf-widget-list-thumbnail" src="'.$value->image.'">';
							}
						  echo '<span class="smfooter">'.$value->user.'<span>'.date("j M, Y", strtotime($value->created_time)).'</span></span>
							<div class="smbox_info">'.$this->message_text($value->message, 150).'</div>
						  </div></a>';
						echo '</div>';
					}
					$cnt++;
				}
			}
		}
		echo '</div>';
		echo $args['after_widget'];
	}

	public function form( $instance ) {
		$title = ! empty( $instance['title'] ) ? $instance['title'] : __( 'New title', 'text_domain' );
		$no_of_feeds = ! empty( $instance['no_of_feeds'] ) ? $instance['no_of_feeds'] : 5;
		?>
		<p>
		<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php _e( esc_attr( 'Title:' ) ); ?></label> 
		<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
		</p>
        <p>
		<label for="<?php echo esc_attr( $this->get_field_id( 'no_of_feeds' ) ); ?>"><?php _e( esc_attr( 'No of Feeds:' ) ); ?></label> 
		<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'no_of_feeds' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'no_of_feeds' ) ); ?>" type="number" step="1" value="<?php echo esc_attr( $no_of_feeds ); ?>"> 
		</p>
		<?php 
	}

	public function update( $new_instance, $old_instance ) {
		$instance = array();
		$instance['title'] = ( ! empty( $new_instance['title'] ) ) ? sanitize_text_field( $new_instance['title'] ) : '';
		$instance['no_of_feeds'] = ( ! empty( $new_instance['no_of_feeds'] ) ) ? sanitize_text_field( $new_instance['no_of_feeds'] ) : '';

		return $instance;
	}
}

function register_social_feed_widget() {
    register_widget( 'Feed_Widget' );
}
add_action( 'widgets_init', 'register_social_feed_widget' );