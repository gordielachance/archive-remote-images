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
     * class relative to options and options page.
     */
    
    var $options_class;
    

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
                    self::$instance->includes();
                    self::$instance->setup_globals();
                    self::$instance->setup_actions();
            }
            return self::$instance;
    }

    /**
     * A dummy constructor to prevent the plugin from being loaded more than once.
     */
    private function __construct() { /* Do nothing here */ }
    
    function includes(){
        require( $this->plugin_dir . 'ari-settings.php');
    }


    function setup_globals(){
        global $wpdb;

        /** Paths *************************************************************/
        $this->file       = __FILE__;
        $this->basename   = plugin_basename( $this->file );
        $this->prefix = 'ari';
        $this->plugin_dir = plugin_dir_path( $this->file );
        $this->plugin_url = plugin_dir_url ( $this->file );
        
        $this->options_class = new AriSettings(); 
        

    }
    


    function setup_actions(){

        //localization
        add_action('init', array($this, 'load_plugin_textdomain'));

        //scripts & styles
        add_action( 'admin_enqueue_scripts',  array( $this, 'scripts_styles' ) );

        //metabox
        add_action( 'add_meta_boxes',  array( $this, 'metabox_init' ) );
        add_action( 'save_post',  array( $this, 'save_post_metadata' ) );

        //post processing
        add_action( 'save_post',  array( $this, 'save_post_images' ),10, 2);

    }

    public function load_plugin_textdomain(){
        load_plugin_textdomain($this->basename, FALSE, $this->plugin_dir.'/languages/');
    }
    
    public function get_setting($slug){
        $options = self::get_settings();
        if (array_key_exists($slug, $options)) return $options[$slug];
    }
    public function get_settings(){
        return $this->options_class->options;
    }

    ////
        
    public function scripts_styles() {
            //wp_enqueue_style( 'ari-admin', $this->plugin_url .'_inc/css/ari-admin.css', array(), $this->version );
    }
    
    /**
    * This function registers a metabox with the callback archive_remote_images_meta_box_callback.
    * For reference: add_meta_box( $id, $title, $callback, $page, $context, $priority, $callback_args );
    *
    */

    function metabox_init(){
        $post_types = get_post_types();
        $supported = self::get_setting('post_types');

        foreach ($post_types as $post_type){
            if (!in_array($post_type,$supported)) continue;
            add_meta_box('ari', __('Archive Remote Images','ari'), array(&$this,'metabox_content'), $post_type, 'side', 'high');
        }

    }

    
    function metabox_content($post){
        
        $checked = self::get_setting('default_checked');

        if ($meta_value = get_post_meta($post->ID, 'transfer_image', TRUE)){
            if ($meta_value == 'yes'){
                $checked = true;
            }else{
                $checked = false;
            }
        }
        
        ?>
        <div id="post-img-select">
                <input type="checkbox"value="on" <?php checked((bool)$checked); ?> id="ari-metabox-check" name="transfer_image"> <label for="ari-metabox-check"><?php _e('Archive Remote Images','ari');?></label>
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
            $checked = "no";
            if (isset($_POST['transfer_image'])){
                $checked = "yes";
            }

            update_post_meta($post_id, 'transfer_image', $checked);

            return $post_id;

    }
    
    // retrieve images from content
    function fetch_all_images($doc){
        $save_atts = array('src','alt','title');
        $images = array();
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
 
        //checkbox not checked
        if (!isset($_POST['transfer_image'])) return $post_id;
        
        //script time limit
        if ($time_limit = self::get_setting('time_limit')){
            set_time_limit($time_limit);
        }

        //load HTML for the parser
        libxml_use_internal_errors(true); //avoid errors like duplicate IDs

        
        $doc = new DOMDocument();
        
        //try to fix bad HTML
        $doc->recover = true; 
        //$doc->strictErrorChecking = false;
        
        $doc->loadHTML($post->post_content);

        //get images urls in post content
        $images = self::fetch_all_images($doc);
        
        //remove hooks (avoid infinite loops)
        remove_action('save_post', array( $this, 'save_post_metadata' ));
        remove_action( 'save_post',  array( $this, 'save_post_images' ),10, 2);
        
        foreach ($images as $image){
            $post->post_content = self::replace_single_image($image, $post, $doc);
            
            //update post
            //is inside FOREACH so if the script breaks, 
            //successfully grabbed images still are replaced in the post content.
            wp_update_post( $post ); 
        }

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
    function replace_single_image($image, $post, $doc){
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

                $upload = media_sideload_image($url, $post->ID, $img_title);
                if (!is_wp_error($upload)){
                    $attachment_id = self::retrieve_sideload_upload_id($upload);
                }
            }

            if (isset($attachment_id)){
                $new_image_html = wp_get_attachment_image( $attachment_id, 'full' );
                $new_image_html = apply_filters('ari_get_new_image_html',$new_image_html,$attachment_id);
                $new_image_url = wp_get_attachment_url( $attachment_id );
                
                //add original URL to attachment, as meta
                add_post_meta($attachment_id, '_ari-url',$url);

                //replace image in content
                $imageTags = $doc->getElementsByTagName('img');

                $new_image_el = $doc->createDocumentFragment();
                $new_image_el->appendXML($new_image_html);

                foreach ($imageTags as $imageTag){
                    $imageTag_url = $imageTag->getAttribute('src');
                    if ($imageTag_url != $url) continue;
                    
                    $parentNode = $imageTag->parentNode;

                    //replace <img> tag
                    $parentNode->replaceChild($new_image_el, $imageTag);
                    
                    //if the parent tag of the image is a link to the (same) image,
                    //replace that link with a link to the uploaded image.
                    if (($parentNode->tagName == 'a') && (self::get_setting('replace_parent_link'))){

                        $link_src = $parentNode->getAttribute('href');
                        
                        //url and image are the same
                        if ($link_src == $url){
                            $new_image_html = wp_get_attachment_image( $attachment_id, 'medium' );
                            $new_link_html = '<a href="'.$new_image_url.'">'.$new_image_html.'</a>';
                            $new_link_html = apply_filters('ari_get_new_link_html',$new_link_html,$attachment_id);
                            
                            $new_link_el = $doc->createDocumentFragment();
                            $new_link_el->appendXML($new_link_html);
                            
                            $parentNode->parentNode->replaceChild($new_link_el, $parentNode);
                            
                        }
                        

                    }

                }
                
                //TO FIX : remove doctype, html and body tags.
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

if (is_admin()){
    ari();
}
    