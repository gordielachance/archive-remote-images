<?php
/*
Plugin Name: Archive Remote Images
Plugin URI: http://www.lookingimage.com/wordpress-plugin/
Description: Archive images from remote website base on url, automatic cache images when save, customize setting for each post.
Version: 1.0.3
Author: Kason Zhao
Author URI: http://www.lookingimage.com/wordpress-plugin/
*/
// Archive Remote Images -> ari
// after install will update the values
register_activation_hook(__FILE__, 'ari_install');
function ari_install()
{
    update_option('ari_overall_swich', 'on');
    update_option('ari_default_setting', 'on');
    update_option('ari_display_box', 'on');
    update_option('ari_archive_autosave', '');
}
/*Call 'LZ_option_link' function to Add a submenu link under Profile tab.*/
add_action('admin_menu', 'archive_remote_images_option_link');
/**
 * Function Name: archive_remote_images_option_link
 * Description: Add a submenu under Settings tab.
 *
 */
function archive_remote_images_option_link()
{
    add_options_page('Archive Remote Images', 'Archive Remote Images', 'manage_options', 'archive-remote-images', 'archive_remote_images_option_form');
}
function archive_remote_images_option_form()
{
    $ari_path = plugin_dir_url(__FILE__);
    echo '<h2><img style="vertical-align: bottom;"  width="20px" src=' . $ari_path . '/images/options.png /> General option  </h2> ';
    /**
     * Check whether the form submitted or not.
     */
    if (isset($_POST['option-save'])) {
        echo "<div class='update-nag'>Options saved!</div>";
        update_option('ari_overall_swich', trim($_POST['ari_overall_swich']));
        update_option('ari_default_setting', trim($_POST['ari_default_setting']));
        update_option('ari_display_box', trim($_POST['ari_display_box']));
        update_option('ari_archive_autosave', trim($_POST['ari_archive_autosave']));
    }
?>
<style type="text/css">
.main-form{
min-width:1024px;
}
.ari-button {
    display: inline-block;
    background: #3079ed;
    background: -webkit-gradient(linear, left top, left bottom, color-stop(0%,#327BEF), color-stop(100%,#2E77EB));
    background: -moz-linear-gradient(center top, #327BEF 0%, #2E77EB 100%);
    -webkit-border-radius: 2px;
    -moz-border-radius: 2px;
    border-radius: 2px;
    -webkit-transition: border-color .218s 0!important;
    -moz-transition: border-color .218s 0!important;
    -o-transition: border-color .218s 0!important;
    transition: border-color .218s 0!important;
    text-shadow: 1px 0px 0px #1a378e!important;
    padding: 7px 12px;
    margin: 0px 12px 0px 0px;
    display: inline-block;
    border-color: #0066cc!important;
    border-width: 1px;
    border-style: solid;
    font-family: Helvetica, Arial, sans-serif;
    font-size: 12px;
    color: #ffffff!important;
    font-weight: bold;
}
.ari-button:hover {
    background: #2D71EE;
    -webkit-box-shadow: 1px 1px #d8d8d8;
    -moz-box-shadow: 1px 1px #d8d8d8;
    box-shadow: 1px 1px #d8d8d8;
    text-shadow: 1px 1px 0px #001AA6;
    border-color: #291f93;
}
.ari-button:active {
    background: #2A69EF;
    background: -webkit-gradient(linear, left top, left bottom, color-stop(0%,#327aef), color-stop(100%,#2e76eb));
    background: -moz-linear-gradient(center top, #327aef 0%, #2e76eb 100%);
    -webkit-box-shadow: 1px 1px 3px 0px #0066cc inset;
    -moz-box-shadow: 1px 1px 3px 0px #0066cc inset;
    box-shadow: 1px 1px 3px 0px #0066cc inset;
    text-shadow: 1px 1px 0px #001AA6;
    border-color: #444444;
}
.ari-table{
padding:10px;

}


.ari-table td{
padding:5px;
width:350px;
}

.ari-leftbar{
width:60%;
min-width:500px;
float:left;
}
.ari-rightbar{
margin-right:50px;
width:300px;
float:right;
}
</style>
<div class="main-form">
<div class="ari-leftbar">
  <p>Archive images from remote website base on url, automatic archive images when save post/page, able to customize setting for each post.</p>
  <p>Following links can help you:</p>
   
   <ul style="margin-left:40px;"> 
   <li><a href="http://www.lookingimage.com/wordpress-plugin/wordpress-archive-remote-images/" target="_blank">Details and video tutorial (FAQ .etc)</a></li>
   <li><a href="http://www.lookingimage.com/forums/discussion/" target="_blank">Support forum</a></li>
   <li><a href="http://lookingimage.com/" target="_blank">Author home page</a></li>
   <li><a href="http://www.lookingimage.com/wordpress-themes/" target="_blank">Free WordPress themes</a></li>
   <li><a href="http://www.lookingimage.com/wordpress-plugin/" target="_blank">Other pulgins from lookingimage.com</a></li>
   </ul>

	<form id="option-form" method="post" name="option-form">
		<table id="aws-option-table" class="ari-table">
			<tr>
				<td>Enable 'Archive Remote Images' overall switch: <a href="http://www.runinweb.com/projects/archive-remote-images/#overall" target="_blank">(help?)</a></td>
				<td><input <?php if(get_option('ari_overall_swich') == "on") echo "checked=checked"; ?> type="checkbox" id="ari_overall_swich" name="ari_overall_swich" /></td>
			</tr>
			<tr>
				<td>Auto archive when post saving as default setting:  <a href="http://www.runinweb.com/projects/archive-remote-images/#default_setting" target="_blank">(help?)</a></td>
				<td><input <?php if(get_option('ari_default_setting') == "on") echo "checked=checked"; ?> type="checkbox" id="ari_default_setting" name="ari_default_setting" /></td>
			</tr>
		    <tr>
				<td>Display Archive Remote Images option box in post page:  <a href="http://www.runinweb.com/projects/archive-remote-images/#Display_control" target="_blank">(help?)</a></td>
				<td><input <?php if (get_option('ari_display_box') == "on") echo "checked=checked"; ?> type="checkbox" id="ari_display_box" name="ari_display_box" /></td>
			</tr>
			 <tr>
				<td>Archive Remote Images for auto save:  <a href="http://www.runinweb.com/projects/archive-remote-images/#auto_save" target="_blank">(help?)</a></td>
				<td><input <?php if(get_option('ari_archive_autosave') == "on") echo "checked=checked"; ?> type="checkbox" id="ari_archive_autosave" name="ari_archive_autosave" /></td>
			</tr>
			
			<tr><td colspan="2"><br/><input id="option-save" class="ari-button" type="submit" name="option-save" value="Save options"/></td></tr>
		</table>
		
		
	</form>
</div>

<div class="ari-rightbar">
<iframe width="300" height="530" frameborder="1" src="http://www.runinweb.com/news.html"></iframe>
<div style="clear:both;"></div>
</div>
</div>

 <?php
}
// add option to new/edit post page
add_action('add_meta_boxes', 'archive_remote_images_meta_box');

/**

* This function registers a metabox with the callback archive_remote_images_meta_box_callback.

* For reference: add_meta_box( $id, $title, $callback, $page, $context, $priority, $callback_args );

*

*/

function archive_remote_images_meta_box()
{
    $post_types = get_post_types();
    
    foreach ($post_types as $post_type)
        if (get_option('ari_overall_swich') == 'on' and get_option('ari_display_box') == 'on')
            add_meta_box('', 'Archive image option', 'archive_remote_images_meta_box_callback', $post_type, 'side', 'high');
}
?>
<?php
function archive_remote_images_meta_box_callback($post)
{
    $transfer_image_value = get_post_meta($post->ID, 'transfer_image', TRUE);
    $check_one            = '';
    
    if ($transfer_image_value != 'yes' && $transfer_image_value != 'no') {
        if (get_option('ari_default_setting') == "on") {
            $check_one = 'checked="checked"';
        } else {
            $check_two = 'checked="checked"';
        }
    } else {
        if ($transfer_image_value == 'yes') {
            $check_one = 'checked="checked"';
        } else {
            $check_two = 'checked="checked"';
        }
    }
?>
<input type="hidden" name="transfer_image_noncename" id="transfer_image_noncename" value="<?php echo wp_create_nonce('transfer_image' . $post->ID); ?>" />	
<div id="post-img-select">
<p>Archive: &nbsp;
<input type="radio"value="yes" <?php echo $check_one; ?> id="img-cache-yes-0" class="img-cache-yes" name="transfer_image"> <label for="img-cache-yes-0">Yes</label>
<input type="radio" value="no" <?php echo $check_two; ?>  id="img-cache-yes-video" class="img-cache-yes" name="transfer_image"> <label for="img-cache-yes-video">No</label>
	</p>	
	</div>
<?php
}
add_action('save_post', 'save_pst_metadata');
function save_pst_metadata($post_id)
{
    // verify this came from the our screen and with proper authorization.
    if (!wp_verify_nonce($_POST['transfer_image_noncename'], 'transfer_image' . $post_id)) {
        return $post_id;
    }
    // verify if this is an auto save routine. If it is our form has not been submitted, so we dont want to do anything
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
        return $post_id;
    
    // Check permissions
    if (!current_user_can('edit_post', $post_id))
        return $post_id;
    // OK, we're authenticated: we need to find and save the data  
    $post = get_post($post_id);
    update_post_meta($post_id, 'transfer_image', esc_attr($_POST['transfer_image']));
    
    return (esc_attr($_POST['transfer_image']));
}
?>
<?php
// checking img urls in content
function archive_find_images($content, $domains)
{
    preg_match_all('|<img.*?src=[\'"](.*?)[\'"].*?>|i', $content, $matches);
    foreach ($matches[1] as $url):
        $url = parse_url($url);
        $domains[$url['host']]++;
    endforeach;
    
    return $domains;
}
/**
 * Archive image from content
 */
function archive_image_from_remote($url, $postid)
{
    global $wpdb;
    $orig_url = $url;
    
    if (strpos($url, 'blogspot.com') || strpos($url, 'blogger.com') || strpos($url, 'ggpht.com') || strpos($url, 'googleusercontent.com') || strpos($url, 'gstatic.com')) {
        $response = wp_remote_request($url);
        if (is_wp_error($response))
            return 'error1';
        
        $my_body = wp_remote_retrieve_body($response);
        
        if (strpos($my_body, 'src')) {
            preg_match_all('|<img.*?src=[\'"](.*?)[\'"].*?>|i', $my_body, $matches);
            foreach ($matches[1] as $url):
                $spisak = $url;
            endforeach;
            
            $url = $spisak;
        }
    }
    set_time_limit(300);
    require_once(ABSPATH . 'wp-admin/includes/media.php');
    require_once(ABSPATH . 'wp-admin/includes/file.php');
    $upload = media_sideload_image($url, $postid);
    if (!is_wp_error($upload)) {
        preg_match_all('|<img.*?src=[\'"](.*?)[\'"].*?>|i', $upload, $locals);
        foreach ($locals[1] as $newurl):
            $wpdb->query("UPDATE $wpdb->posts SET post_content = REPLACE(post_content, '$orig_url', '$newurl');");
        endforeach;
    }
    return $url;
}
/*
 * Archive images on while saving
 */
function archive_save_post($post_ID, $post)
{
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE && get_option('ari_archive_autosave') != "on")
        return $post_id;
    if (get_option('ari_overall_swich') != "on" && $_POST['transfer_image'] == "no") {
        return $post_ID;
    }
    global $wpdb;
    $domains = archive_find_images($post->post_content, $domains);
    if (!$domains)
        return $post_ID;
    $local_domain = parse_url(get_option('siteurl'));
    foreach ($domains as $domain => $num):
        if (strstr($domain, $local_domain['host']))
            continue; // check if is local images
        
        preg_match_all('|<img.*?src=[\'"](.*?)[\'"].*?>|i', $post->post_content, $matches);
        foreach ($matches[1] as $url):
            if (strstr($url, get_option('siteurl') . '/' . get_option('upload_path')) || !strstr($url, $domain) || (($res) && in_multi_array($url, $res)))
                continue; // check if is local images
            archive_image_from_remote($url, $post_ID);
        endforeach;
    endforeach;
    return $post_ID;
}
add_action('save_post', 'archive_save_post', 10, 2);
?>