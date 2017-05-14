<?php
	/**
	 * Plugin Name: YouTube To Posts
	 * Plugin URI: http://walkerdigital.co
	 * Description: This plugin imports YouTube videos posted to a given channel URL as posts.
	 * Version: 1.0.3
	 * Author: Jon Walker
	 * Author URI: http://walkerdigital.co
	 * License: MIT License
	 */

	defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

	/**
	* INCLUDE THE YOUTUBE WRAPPER FILES
	*/
	require_once(plugin_dir_path( __FILE__ )."lib/php-youtube-api-master/src/Constants.php");
	require_once(plugin_dir_path( __FILE__ )."lib/php-youtube-api-master/src/Youtube.php");


	/**
	 *  The deactivation hook is executed when the plugin is activated 
	 */
	register_activation_hook(__FILE__,'yttp_activation');
	
	/**
	 *  The deactivation hook is executed when the plugin is deactivated 
	 */
	register_deactivation_hook(__FILE__,'yttp_deactivation');
	
	/**
	 * This function is executed when the user activates the plugin
	 */
	function yttp_activation(){  wp_schedule_event(time(), 'hourly', 'yttp_scrape_channel');}
	
	/**
	 * This function is executed when the user deactivates the plugin
	 */
	function yttp_deactivation(){  wp_clear_scheduled_hook('yttp_scrape_channel');}
	

	add_action('admin_menu', 'plugin_admin_add_page');

	function plugin_admin_add_page() {
		add_options_page('Options', 'YT2P', 'manage_options', 'youtube-to-posts', 'plugin_options_page');
	}

	add_action('admin_init', 'plugin_admin_init');

	/**
	 * INIT OUR OPTIONS
	 */
	function plugin_admin_init(){
		register_setting( 'plugin_options', 'plugin_options', 'plugin_options_validate' );
		add_settings_section('plugin_main', 'YouTube URL', null, 'youtube-to-posts');
		add_settings_field('ytp_youtube_url', 'URL', 'youtube_channel_url', 'youtube-to-posts', 'plugin_main');
		add_settings_field('youtube_api_key', 'YouTube API Key', 'yt_api_key', 'youtube-to-posts', 'plugin_main');
		add_settings_field('ytp_post_cat', 'Post Category', 'video_post_category', 'youtube-to-posts', 'plugin_main');
	}

	/**
	 * SET THE YOUTUBE CHANNEL URL YOU WANT TO GET VIDEOS FROM
	 */
	function youtube_channel_url() {
	$options = get_option('plugin_options');
	echo "<input id='ytp_youtube_url' name='plugin_options[ytp_youtube_url]' placeholder='https://www.youtube.com/user/YourSuperUsername' size='40' type='text' value='{$options['ytp_youtube_url']}'>";
	}

	/**
	 * OUR API KEY
	 */
	function yt_api_key() {
	$options = get_option('plugin_options');
	echo "<input id='youtube_api_key' name='plugin_options[youtube_api_key]' placeholder='##########################' size='47' type='text' value='".$options['youtube_api_key']."'><br>*You can get a YouTube Data API Key if you have a Google account via the <a href='https://console.developers.google.com' target='_blank'>API Control Panel.</a>";
	}

	/**
	 * THE CATEGORY TO ASSIGN YOUR VIDEOS TO WITHIN WORDPRESS
	 */
	function video_post_category() {
	$options = get_option('plugin_options');
	$cats = get_categories(array('hide_empty'=>0));
	?>
		<select name="plugin_options[ytp_post_cat]" id="ytp_post_cat">
			<option value="">--</option>
			<?php foreach($cats as $c) { ?>
				<option value="<?php echo $c->cat_ID; ?>" <?php if($c->cat_ID == $options['ytp_post_cat']) { echo "selected"; } ?>><?php echo $c->cat_name; ?></option>
			<?php } ?>
		</select>
	<?php
	}

	/**
	 * OUR PLUGIN OPTIONS PAGE - PRETTY SIMPLE
	 */

	function plugin_options_page() { ?>
	<div>
		<h2>YouTube to Posts</h2>
		<p>Provide the YouTube Channel URL</p>
		<form action="options.php" method="post">
		<?php settings_fields('plugin_options'); ?>
		<?php do_settings_sections('youtube-to-posts'); ?>
		<input style='background-color: #000; color: #fff; padding: .5rem 1rem; margin-top: 2rem; text-decoration: none; border: none;' name="Submit" type="submit" value="<?php esc_attr_e('Save Changes'); ?>" />
		</form><br>
		<hr><br>
		<h3>Run Now</h3>
		<p>Click the button below to scrape your channel immediately.</p>
		<div id="scrapeButton">
			<a style='background-color: #000; color: #fff; padding: .5rem 1rem; margin-top: 2rem; text-decoration: none;' href="#" id="runYTScrape">Run Now</a>
		</div>
		<div id="scrapeStatus"></div>
		<script>
			jQuery(document).ready(function() {
				jQuery(document).on('click', '#runYTScrape', function(e) {
					e.preventDefault();
					jQuery("#scrapeButton").hide();
					jQuery("#scrapeStatus").html('<p><img src="./images/wpspin_light.gif"> Scraping your youtube feed.</p>');
					jQuery.getJSON('<?php echo admin_url( 'options-general.php?page=youtube-to-posts&yt_scrape=true'); ?>', function(data) {
						if(data.success) {
							jQuery("#scrapeStatus").html('<p>Scraped '+data.scraped+' videos...</p>');
						}
						jQuery("#scrapeButton").show();
					});
				});
			});
		</script>
	</div>
<?php }

	/**
	 * IF WE'RE RUNNING MANUALLY CALL THE MAIN METHOD UPON LOAD
	 */
	if(isset($_GET['yt_scrape'])) {
		add_action('plugins_loaded', 'yttp_scrape_channel');
	}


	/**
	 * THIS IS THE MAIN METHOD WHICH FETCHES DATA VIA THE YOUTUBE API
	 */
	function yttp_scrape_channel() {


		$options = get_option('plugin_options');
		
		$url = $options['ytp_youtube_url'];
		
		$youtube = new Madcoda\Youtube\Youtube(array('key' => $options['youtube_api_key']));
		
		$channel = $youtube->getChannelFromURL($url);

		$i=0;
		
		if($channel->id) {

			$playlist_id = $channel->contentDetails->relatedPlaylists->uploads;

			$videos = $youtube->getPlaylistItemsByPlaylistId($playlist_id);

			foreach($videos as $v) {
				$videoID = $v->snippet->resourceId->videoId;
			
				// IS THIS A UNIQUE VIDEO ID?

				$body = '<iframe width="853" height="480" src="https://www.youtube.com/embed/'.$videoID.'" frameborder="0" allowfullscreen></iframe>';
				
				$body .= $v->snippet->description;

				$query = new WP_Query(array('post_type'=>'post', 'meta_query' => array(
						array(
							'key'     => 'video_id',
							'value'   => $v->id
						))));

				if(!$query->have_posts()) {

					$dat = wp_insert_post(array(
					'post_date'=>date("Y-m-d h:i:s", strtotime($v->snippet->publishedAt)),
					'post_title'=>wp_strip_all_tags($v->snippet->title),
					'post_content'=>$body,
					'post_status'   => 'publish',
					'post_category'=>array($options['ytp_post_cat']),
					'meta_input'   => array(
				        'video_id' => $v->id,
				    )
					), true);
					
					$i++;

					$img = '';

					if(isset($v->snippet->thumbnails->standard->url)) {
						$img = $v->snippet->thumbnails->standard->url;
					} elseif(isset($v->snippet->thumbnails->high->url)) {
						$img = $v->snippet->thumbnails->high->url;
					}
					
					if($img != '') {
						Generate_Featured_Image($img, $dat, null);
					}
				}
			}
		}

		header("Content-Type: application/json");
		echo json_encode(array('success'=>true, 'scraped'=>$i));
		exit;
	}


	/**
	 * ADD A LINK IN OUR SETTINGS AREA
	 */
	function yt2p_settings_link($links) { 
	  $settings_link = '<a href="options-general.php?page=youtube-to-post.php">Settings</a>'; 
	  array_unshift($links, $settings_link); 
	  return $links; 
	}
	 
	$plugin = plugin_basename(__FILE__); 
	add_filter("plugin_action_links_".$plugin, 'yt2p_settings_link');


	/**
	 * THIS CREATES A FEATURED IMAGE / THUMBNAIL BASED ON IMAGES ATTACHED TO THE VIDEO IF AVAILABLE
	 */
	function Generate_Featured_Image($file=null, $post_id=null, $desc=null) {

		require_once(ABSPATH . "wp-admin" . '/includes/image.php');
	    require_once(ABSPATH . "wp-admin" . '/includes/file.php');
	    require_once(ABSPATH . "wp-admin" . '/includes/media.php');

	    // Set variables for storage, fix file filename for query strings.
	    preg_match( '/[^\?]+\.(jpe?g|jpe|gif|png)\b/i', $file, $matches );

	    if ( ! $matches ) {
	         return new WP_Error( 'image_sideload_failed', __( 'Invalid image URL' ) );
	    }

	    $file_array = array();
	    $file_array['name'] = basename( $matches[0] );

	    // Download file to temp location.
	    $file_array['tmp_name'] = download_url( $file );

	    // If error storing temporarily, return the error.
	    if ( is_wp_error( $file_array['tmp_name'] ) ) {
	        return $file_array['tmp_name'];
	    }

	    // Do the validation and storage stuff.
	    $id = media_handle_sideload( $file_array, $post_id, $desc );

	    // If error storing permanently, unlink.
	    if ( is_wp_error( $id ) ) {
	        @unlink( $file_array['tmp_name'] );
	        return $id;
	    }

	    return set_post_thumbnail( $post_id, $id );

	}