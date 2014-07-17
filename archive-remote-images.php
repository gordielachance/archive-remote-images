<?php
/*
 * Plugin Name: Archive Remote Image
 * Plugin URI: https://wordpress.org/plugins/archive-remote-images
 * Description: Advanced remote images grabber, automatically save the remote img to local, saves them directly into your blog media directory and attaches to the app
 * Author: Kason Zhao, G.Breant
 * Version: 1.0.4
 * Author URI: https://profiles.wordpress.org/kasonzhao/
 * License: GPL2+
 * Text Domain: ari
 * Domain Path: /languages/
 */


class ArchiveRemoteImage{
    /** Version ***************************************************************/

    /**
     * @public string plugin version
     */
    public $version = '1.04';
    public $db_version = '0104';

    /** Paths *****************************************************************/

    public $file = '';

    /**
     * @public string Basename of the plugin directory
     */
    public $basename = '';

    /**
     * @public string Prefix for the plugin
     */
    public $prefix = '';

    /**
     * @public string Absolute path to the plugin directory
     */
    public $plugin_dir = '';

    /**
     * @public string Absolute path to the plugin directory
     */
    public $plugin_url = '';

    /**
     * @var The one true Instance
     */
    private static $instance;

    /**
     * Main Instance
     *
     * Insures that only one instance of the plugin exists in memory at any one
     * time. Also prevents needing to define globals all over the place.
     *
     * @staticvar array $instance
     * @uses ukeGeeks::setup_globals() Setup the globals needed
     * @uses ukeGeeks::includes() Include the required files
     * @uses ukeGeeks::setup_actions() Setup the hooks and actions
     * @see ukegeeks()
     * @return The instance
     */
    public static function instance() {
            if ( ! isset( self::$instance ) ) {
                    self::$instance = new ArchiveRemoteImage;
                    self::$instance->setup_globals();
                    self::$instance->includes();
                    self::$instance->setup_actions();
            }
            return self::$instance;
    }

    /**
     * A dummy constructor to prevent the plugin from being loaded more than once.
     */
    private function __construct() { /* Do nothing here */ }


    function setup_globals(){
        global $wpdb;

        /** Paths *************************************************************/
        $this->file       = __FILE__;
        $this->basename   = plugin_basename( $this->file );
        $this->prefix = 'ari';
        $this->plugin_dir = plugin_dir_path( $this->file );
        $this->plugin_url = plugin_dir_url ( $this->file );

        $default_options = array(
            'overall_switch'     => "on",
            'default_checked'   => "",
            'display_box'       => "on",
        );

        $this->options = apply_filters('ari-options',$default_options);

    }
    
    function includes(){
    }

    function setup_actions(){

        //localization
        add_action('init', array($this, 'load_plugin_textdomain'));

        //upgrade
        add_action( 'plugins_loaded', array($this, 'upgrade'));

        //scripts & styles
        add_action( 'admin_enqueue_scripts',  array( $this, 'scripts_styles' ) );

        //register settings page
        add_action('admin_menu',  array( $this, 'settings_page' ) );

        //metabox
        add_action( 'add_meta_boxes',  array( $this, 'metabox_init' ) );
        add_action( 'save_post',  array( $this, 'save_post_metadata' ) );

        //post processing
        add_action( 'save_post',  array( $this, 'save_post_images' ),10, 2);

    }

    public function load_plugin_textdomain(){
        load_plugin_textdomain($this->basename, FALSE, $this->plugin_dir.'/languages/');
    }

    function upgrade(){
        global $wpdb;

        $db_meta_name = "_ari_db_version";
        $current_version = get_option($db_meta_name);

        if ($current_version==$this->db_version) return false;

        //install
        if(!$current_version){
            //handle SQL
            //require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            //dbDelta($sql);
            add_option("_ari_options",$this->options); // add settings
        }

        //upgrade
        update_option($db_meta_name, $this->db_version );//upgrade DB version
    }

    ////
        
    public function scripts_styles() {
            wp_enqueue_style( 'ari-admin', $this->plugin_url .'_inc/css/ari-admin.css', array(), $this->version );
    }
    
    /**
     * Description: Add a submenu under Settings tab.
     *
     */
    function settings_page(){
        add_options_page('Archive Remote Images', 'Archive Remote Images', 'manage_options', 'archive-remote-images', array( $this, 'settings_page_content' ));
    }
    
    function settings_page_content(){
        ?>
        <h2><img style="vertical-align: bottom;"  width="20px" src="<?php echo $this->plugin_url;?>/_inc/images/options.png"/><?php _e('General options','ari');?></h2>
        <?php
        /**
         * Check whether the form submitted or not.
         */
        if (isset($_POST['option-save'])) {
            
            $new_options = array();
            
            foreach ($this->options as $slug=>$value){
                $new_options[$slug] = trim($_POST['ari_options'][$slug]);
            }
            
            if (update_option("_ari_options",$new_options)){
                ?>
                <div class='update-nag'><?php _e('Options saved !','ari');?></div>
                <?php
            }
        }
        ?>
        <div class="main-form">
            <div class="ari-leftbar">
              <p>Archive images from remote website base on url, automatic archive images when save post/page, able to customize setting for each post.</p>
              <p>Following links can help you:</p>

               <ul style="margin-left:40px;"> 
               <li><a href="http://www.lookingimage.com/wordpress-plugin/wordpress-archive-remote-images/" target="_blank">Details and video tutorial (FAQ .etc)</a></li>
               <li><a href="http://www.lookingimage.com/forums/discussion/" target="_blank">Support forum</a></li>
               <li><a href="http://lookingimage.com/" target="_blank">Author home page</a></li>
               <li><a href="http://www.lookingimage.com/wordpress-themes/" target="_blank">Free WordPress themes</a></li>
               <li><a href="http://www.lookingimage.com/wordpress-plugin/" target="_blank">Other plugins from lookingimage.com</a></li>
               </ul>



                    <form id="option-form" method="post" name="option-form">
                            <table id="aws-option-table" class="ari-table">
                                    <tr>
                                            <td>Enable 'Archive Remote Images' overall switch: <a href="http://www.runinweb.com/projects/archive-remote-images/#overall" target="_blank">(help?)</a></td>
                                            <td><input<?php checked( $this->options["overall_switch"], 'on' ); ?>  type="checkbox" id="ari_options[overall_switch]" name="ari_overall_switch" /></td>
                                    </tr>
                                    <tr>
                                            <td>Auto archive when post saving as default setting:  <a href="http://www.runinweb.com/projects/archive-remote-images/#default_checked" target="_blank">(help?)</a></td>
                                            <td><input<?php checked( $this->options['default_checked'], 'on' ); ?> type="checkbox" id="ari_options[default_checked]" name="ari_default_checked" /></td>
                                    </tr>
                                <tr>
                                            <td>Display Archive Remote Images option box in post page:  <a href="http://www.runinweb.com/projects/archive-remote-images/#Display_control" target="_blank">(help?)</a></td>
                                            <td><input<?php checked( $this->options['display_box'], 'on' ); ?> type="checkbox" id="ari_options[display_box]" name="ari_display_box" /></td>
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
    
    /**
    * This function registers a metabox with the callback archive_remote_images_meta_box_callback.
    * For reference: add_meta_box( $id, $title, $callback, $page, $context, $priority, $callback_args );
    *
    */

    function metabox_init(){
        $post_types = get_post_types();

        foreach ($post_types as $post_type){
            if ($this->options['overall_switch'] == 'on' and $this->options['display_box'] == 'on')
                add_meta_box('ari', __('Archive Image Options','ari'), array(&$this,'metabox_content'), $post_type, 'side', 'high');
        }

    }

    
    function metabox_content($post){
        $transfer_image_value = get_post_meta($post->ID, 'transfer_image', TRUE);
        $check_one            = '';
        $check_two            = '';

        if ($transfer_image_value != 'yes' && $transfer_image_value != 'no') {

            if ($this->options['default_checked'] == "on") {
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
        <div id="post-img-select">
            <p>Archive: &nbsp;
                <input type="radio"value="yes" <?php echo $check_one; ?> id="img-cache-yes-0" class="img-cache-yes" name="transfer_image"> <label for="img-cache-yes-0">Yes</label>
                <input type="radio" value="no" <?php echo $check_two; ?>  id="img-cache-yes-video" class="img-cache-yes" name="transfer_image"> <label for="img-cache-yes-video">No</label>
                <?php wp_nonce_field($this->basename,'ari_form',false);?>
            </p>	
        </div>
        <?php
    }

    function save_post_metadata($post_id){
            //check save status
            $is_autosave = wp_is_post_autosave( $post_id );
            $is_revision = wp_is_post_revision( $post_id );
            $is_valid_nonce = false;
            if ( isset( $_POST[ 'ari_form' ]) && wp_verify_nonce( $_POST['ari_form'], $this->basename)) $is_valid_nonce=true;
            
            if ($is_autosave || $is_revision || !$is_valid_nonce) return $post_id;
            
            //capabilities
            if (!current_user_can('edit_post', $post_id)) return $post_id;
            if (!current_user_can('upload_files', $post_id)) return $post_id;
            
            // OK, we're authenticated: we need to find and save the data  
            update_post_meta($post_id, 'transfer_image', esc_attr($_POST['transfer_image']));

            return $post_id;

    }
    
    // retrieve images from content
    function archive_find_images($content){
        $save_atts = array('src','alt','title');
        $images = array();
        $doc = new DOMDocument(); 
        $doc -> loadHTML($content); 
        $out = simplexml_import_dom($doc); 
        $img_el_all = $out -> xpath('//img'); 

        foreach ($img_el_all as $img_el) {

            $image = array();
            
            foreach($img_el->attributes() as $att => $value) {
                
                if (!in_array($att, $save_atts)) continue;
                
                $value = (string)$value;
                if (!$value) continue;
                
                $image[$att] = $value;

            }
            
            
            $image = array_filter($image);
            if (!array_key_exists('src', $image)) continue;
            $images[] = $image;
            
        }
        
        $images = array_filter($images);
        return $images;
    }
    
    /*
     * Archive images on while saving
     */
    
    function save_post_images($post_id, $post){
        global $wpdb;
        
        //check save status
        $is_autosave = wp_is_post_autosave( $post_id );
        $is_revision = wp_is_post_revision( $post_id );
        
        if ($is_revision) return $post_id;
        if ($is_autosave) return $post_id;
        if ( $this->options["overall_switch"] != "on" && $_POST['transfer_image'] == "no") return $post_id;
        
        //get images urls in post content
        $images = self::archive_find_images($post->post_content);

        foreach ($images as $image){
            $post->post_content = self::content_replace_remote_image($image, $post);
        }
        
        
        //remove hooks (avoid infinite loops)
        remove_action('save_post', array( $this, 'save_post_metadata' ));
        remove_action( 'save_post',  array( $this, 'save_post_images' ),10, 2);
        
        //update post
        $post->post_content = apply_filters('ari_get_replaced_post_content',$post->post_content,$post);
        wp_update_post( $post );
        
        //re-hooks
        add_action('save_post', array( $this, 'save_post_metadata' ));
        add_action( 'save_post',  array( $this, 'save_post_images' ),10, 2);
        
        
        return $post_id;
    }
    
    function is_local_image($url){

        $is_local = strpos($url, home_url());
        
        return (bool)($is_local !== false);
        
        if ($is_local !== false) {
            return true;
        }
        

    }
    
    function get_url_from_special_domain($url){
        $check_domains = array(
            'blogspot.com',
            'blogger.com',
            'ggpht.com',
            'googleusercontent.com',
            'gstatic.com'
        );
        
        foreach ($check_domains as $check_domain){
            if (strpos($url, $check_domain)){
                
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
        }
        
        return $url;
        
    }
    
    function get_image_title($image){
        $title = '';
        if (array_key_exists('title', $image)){
            $title = $image['title'];
        }elseif (array_key_exists('alt', $image)){
            $title = $image['alt'];
        }
        return apply_filters('ari_get_image_title',$title);
    }
    
    function get_id_from_already_uploaded_source($img_url){
        $query_args = array(
            'post_type'         => 'attachment',
            'post_status'       => 'inherit',
            'meta_query'        => array(
                array(
                    'key'     => '_ari-url',
                    'value'   => $img_url,
                    'compare' => '='
                )
            ),
            'posts_per_page'    => 1
        );

        $query = new WP_Query($query_args);
        if (!$query->have_posts()) return false;
        return $query->posts[0]->ID;
    }

    
    /**
     * Archive image from content
     */
    function content_replace_remote_image($image, $post){
        global $wpdb;
        
        $post_content = $post->post_content;
        $url = $image['src'];

        if (!self::is_local_image($image['src'])){
            
            //this image url already has been uploaded
            $already_uploaded_id = self::get_id_from_already_uploaded_source($image['src']);
            
            if ($already_uploaded_id){
                
                $attachment_id = $already_uploaded_id;
                
            }else{
                //check if image is from one of those domains
                //TO FIX what's the purpose of this ?

                $url = self::get_url_from_special_domain($image['src']);

                //get image title
                $img_title = self::get_image_title($image);

                set_time_limit(300);
                $upload = media_sideload_image($url, $post->ID, $img_title);
                if (!is_wp_error($upload)){
                    $attachment_id = self::retrieve_sideload_upload_id($upload);
                }
            }

            if ($attachment_id){
                $attachment_html = wp_get_attachment_image( $attachment_id, 'full' );
                $attachment_html = apply_filters('ari_get_attachment_html',$attachment_html,$attachment_id);

                //add original URL to attachment, as meta
                add_post_meta($attachment_id, '_ari-url',$url);

                //replace image in content
                $doc = new DOMDocument();
                $doc->loadHTML($post_content);
                $imageTags = $doc->getElementsByTagName('img');

                $frag = $doc->createDocumentFragment();
                $frag->appendXML($attachment_html);

                foreach ($imageTags as $imageTag){
                    $imageTag_url = $imageTag->getAttribute('src');
                    if ($imageTag_url != $url) continue;


                    $imageTag->parentNode->replaceChild($frag, $imageTag);


                }

                # remove <html><body></body></html> 
                //TO FIX
                
                $post_content =  $doc->saveHTML();
            }

        }
        
        
        return $post_content;
    }
    
    /* little hack to get back the ID of the attachment
     * TO FIX should be improved ?
     */
    function retrieve_sideload_upload_id($upload){
        global $wpdb;
        
        $doc = new DOMDocument();
        $doc->loadHTML($upload);
        $imageTags = $doc->getElementsByTagName('img');
        
        foreach($imageTags as $tag) {
            $image_url = $tag->getAttribute('src');
            $attachment = $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM {$wpdb->prefix}posts WHERE guid RLIKE %s;", $image_url ) );

            return $attachment[0];
        }
    }
        

}

/**
 * The main function responsible for returning the one true Instance
 * to functions everywhere.
 *
 * Use this function like you would a global variable, except without needing
 * to declare the global.
 *
 * @return The one true Instance
 */

function ari() {
	return ArchiveRemoteImage::instance();
}

ari();