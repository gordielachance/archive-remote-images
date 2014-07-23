<?php
/*
 * Plugin Name: Archive Remote Images
 * Plugin URI: https://wordpress.org/plugins/archive-remote-images
 * Description: Archive Remote Images allows you to scan a post to fetch remote images; then updates its content automatically.
 * Author: Kason Zhao, G.Breant
 * Version: 1.0.4
 * Author URI: https://profiles.wordpress.org/kasonzhao/
 * License: GPL2+
 * Text Domain: ari
 * Domain Path: /languages/
 */


class ArchiveRemoteImages{
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
     * @uses ::setup_globals() Setup the globals needed
     * @uses ::includes() Include the required files
     * @uses ::setup_actions() Setup the hooks and actions
     * @return The instance
     */
    public static function instance() {
            if ( ! isset( self::$instance ) ) {
                    self::$instance = new ArchiveRemoteImages;
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

        //post processing
        add_action( 'save_post',  array( $this, 'save_archiving_status' ) );
        add_action( 'save_post',  array( $this, 'save_post_images' ),10, 2);
        
        add_filter('ari_get_remote_image_url',  array( $this, 'get_url_from_special_domain' ));
        add_filter('ari_get_image_attributes',array(&$this,'image_attributes_class_id'),10,2);

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
        
        //capabilities
        if (!current_user_can('edit_post', get_the_ID())) return false;
        if (!current_user_can('upload_files', get_the_ID())) return false;
        
        $post_types = $this->options_class->allowed_post_types();
        $ignored = self::get_setting('ignored_post_type');

        foreach ($post_types as $post_type){
            if (($ignored) && (in_array($post_type,$ignored))) continue;
            add_meta_box('ari', __('Archive Remote Images','ari'), array(&$this,'metabox_content'), $post_type, 'side', 'high');
        }

    }

    function metabox_content($post){
        
        $checked = self::get_setting('default_checked');
        
        if (self::get_setting('remember_status')){
            if ($meta_value = get_post_meta($post->ID, 'ari_enabled', TRUE)){
                if ($meta_value == 'yes'){
                    $checked = true;
                }else{
                    $checked = false;
                }
            }
        }
        
        ?>
        <div id="post-img-select">
                <?php
                if ($count = self::count_archived_attachments(get_the_ID())){
                    ?>
                    <small>
                    <?php
                    printf(
                        _n(
                            '<strong>1</strong> media has been downloaded for this post using Archive Remote Images !',
                            'Already <strong>%s</strong> medias have been downloaded for this post using Archive Remote Images !',
                            $count,
                            'ari' ),
                        $count
                    );
                    ?>
                    </small>
                    <hr/>
                    <?php
                }
                ?>
                
                
                <input type="checkbox"value="on" <?php checked((bool)$checked); ?> id="ari-metabox-check" name="do_remote_archive"> <label for="ari-metabox-check"><?php _e('Download Remote Images for  this post','ari');?></label>
                <?php wp_nonce_field($this->basename,'ari_form',false);?>
            </p>	
        </div>
        <?php
    }

    function save_archiving_status($post_id){
        
            if (!self::get_setting('remember_status')) return $post_id;
        
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
            if (isset($_POST['do_remote_archive'])){
                $checked = "yes";
            }
            
            update_post_meta($post_id, 'ari_enabled', $checked);

            return $post_id;

    }
    
    // retrieve images from content
    function fetch_remote_images($doc){
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
            if (!self::is_absolute_url($image['src'])) continue; //is relative URL
            if (self::is_local_image($image['src'])) continue; //is local image

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
        
        //capabilities
        if (!current_user_can('edit_post', $post_id)) return $post_id;
        if (!current_user_can('upload_files', $post_id)) return $post_id;
 
        //checkbox not checked
        if (!isset($_POST['do_remote_archive'])) return $post_id;
        
        //script time limit
        if ($time_limit = self::get_setting('time_limit')){
            set_time_limit($time_limit);
        }

        //DOMDocument
        libxml_use_internal_errors(true); //avoid errors like duplicate IDs
        $doc = new DOMDocument();
        
        //try to fix bad HTML
        $doc->recover = true; 
        //$doc->strictErrorChecking = false;
        
        $doc->loadHTML($post->post_content);

        //get images urls in post content
        $images = self::fetch_remote_images($doc);
        if (empty($images)) return $post_id;
        
        //hooks START (avoid infinite loops, disable revisions)
        remove_action('save_post', array( $this, 'save_archiving_status' ));
        remove_action( 'save_post',  array( $this, 'save_post_images' ),10, 2);
        $new_post = array('ID'=>$post_id);wp_update_post( $new_post );//saving post once before disabling revisions
        add_filter( 'wp_revisions_to_keep',  array( $this, 'disable_post_revisions' ));
        add_filter( 'wp_get_attachment_image_attributes',  array( $this, 'image_attributes_hook' ),10, 2);
        
        foreach ((array)$images as $image){
            $new_post['post_content'] = self::replace_single_image($image, $post, $doc);
            
            //update post
            //is inside FOREACH so if the script breaks, 
            //successfully grabbed images still are replaced in the post content.
            wp_update_post( $new_post ); 
        }

        //hooks STOP 
        add_action('save_post', array( $this, 'save_archiving_status' ));
        add_action( 'save_post',  array( $this, 'save_post_images' ),10, 2);
        add_action('pre_post_update', 'wp_save_post_revision');//  enable revisions again
        remove_filter( 'wp_revisions_to_keep',  array( $this, 'disable_post_revisions' ));
        remove_filter( 'wp_get_attachment_image_attributes',  array( $this, 'image_attributes_hook' ),10, 2);
        
        return $post_id;
    }
    
    function is_local_image($url){

        $is_local = strpos($url, home_url());
        
        return (bool)($is_local !== false);
        
        if ($is_local !== false) {
            return true;
        }
        

    }
    
    /**
     * Checks the URL is absolute
     * @param type $url
     */
    
    function is_absolute_url($url){
        $parse = parse_url($url);
        if (array_key_exists("host", $parse)) return true;
        return false;
    }
    
    /*
     * Get domain (without subdomain like www.)
     */
    
    function get_domain($url){
        if (!self::is_absolute_url($url)) return false;
        $parse = parse_url($url);
        $host_names = explode(".", $parse['host']);
        $domain = $host_names[count($host_names)-2] . "." . $host_names[count($host_names)-1];
        return $domain;
    }
    
    /*
     * Gets the allowed file extensions allowed for 
     * a specific mime ('image','video','audio',...)
     * and an optional type ('jpeg','gif','mp4',...)
     */
    
    function get_allowed_extensions($mime_check,$type_check=false){
    	$extensions = array();
	$allowed = get_allowed_mime_types();
	foreach ((array)$allowed as $ext_str => $mimetype_str) {
		$mimetype = explode('/',$mimetype_str); //eg. 'image/jpeg'
		$mime = $mimetype[0]; //'image'
		$type = $mimetype[1]; //'jpeg'
		if ( $mime!=$mime_check ) continue;
		if (isset($type_check) && ( $type_check!=$type )) continue;
		$mimetype_ext = explode('|',$ext_str);
		$extensions = array_merge($mimetype_ext,$extensions);
	}
	return $extensions;
    }
    
    /*
     * Gets the allowed file extensions allowed for images
     */
    
    function get_allowed_image_extensions(){
    	return get_allowed_extensions('image');
    }
    
    /**
     * TO FIX rename / give more informations on this function
     * @param type $url
     * @param type $image
     * @return type
     */
    
    function get_url_from_special_domain($url){
        
        $domain = self::get_domain($url);

        $check_domains = array(
            'blogspot.com',
            'blogger.com',
            'ggpht.com',
            'googleusercontent.com',
            'gstatic.com'
        );
        
        if (in_array($domain,$check_domains)){
        
            $response = wp_remote_request($url);
                
            if (!is_wp_error($response)){
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
    
    /*
     * Used for disabling revisions temporary
     */
    function disable_post_revisions($num){
        return 0;
    }
    
    /*
     * Runs a hook so we can filter the image attributes
     */
    
    function image_attributes_hook($attr, $attachment){
        return apply_filters('ari_get_image_attributes',$attr,$attachment);
    }
    
    /*
     * Adds the class wp-image-ID
     * Because it's easier to read when editing the code of the post content
     */

    function image_attributes_class_id($attr, $attachment){
        $attr['class'].= " wp-image-".$attachment->ID;
        return $attr;
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

    function get_existing_attachment_id($img_url){
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
        
        $id = $query->posts[0]->ID;
        return apply_filters("ari_get_existing_attachment_id",$id,$img_url);
    }

    
    /**
     * Archive image from content
     */
    function replace_single_image($image, $post, $doc){
        global $wpdb;
        
        $post_content = $post->post_content;
        $image_url = $image['src'];

        //this image url already has been uploaded
        $already_uploaded_id = self::get_existing_attachment_id($image_url);

        if ($already_uploaded_id){

            $attachment_id = $already_uploaded_id;

        }else{

            //get image title
            $img_title = self::get_image_title($image);

            /*
            media_sideload_image() do not returns the attachment ID.
            hook and unhook a function to get over that.
            it that function, we will save the source URL as post meta for the attachment.
            The value will be $this->attachment_source, which is only used here.
            This is kind of a hack, hope that media_sideload_image() will be able to return
            The attachment ID in the future.
            https:core.trac.wordpress.org/ticket/19629
            */

            //START HACK
            $this->attachment_source = $image_url; 
            add_action('add_attachment',array( $this, 'uploaded_image_save_source' ));

            //filter that allows to update the file URL if needed (eg. depending of the domain)
            $upload_url = apply_filters('ari_get_remote_image_url',$image_url); 
            $upload = media_sideload_image($upload_url, $post->ID, $img_title);

            //STOP HACK
            remove_action('add_attachment',array( $this, 'uploaded_image_save_source' )); //hook
            $this->attachment_source = '';

            if (!is_wp_error($upload)){
                $attachment_id = self::get_existing_attachment_id($image_url);
            }
        }

        if (isset($attachment_id)){
            
            $image_size = self::get_setting('image_size');
            
            $new_image_html = wp_get_attachment_image( $attachment_id, $image_size );
            $new_image_html = apply_filters('ari_get_new_image_html',$new_image_html,$attachment_id);
            
            if ($new_image_html){

                $imageTags = $doc->getElementsByTagName('img'); //get all images
                $new_image_el = $doc->createDocumentFragment();
                $new_image_el->appendXML($new_image_html);

                foreach ($imageTags as $imageTag){
                    
                    $imageTag_url = $imageTag->getAttribute('src');
                    if ($imageTag_url != $image_url) continue;


                    $parentNode = $imageTag->parentNode;

                    //replace <img> tag
                    $parentNode->replaceChild($new_image_el, $imageTag);

                    //if the parent tag of the image is a link to the (same) image,
                    //replace that link with a link to the uploaded image.
                    if (($parentNode->tagName == 'a') && (self::get_setting('replace_parent_link'))){

                        $link_src = $parentNode->getAttribute('href');

                        //link url and image source are the same
                        if ( $link_src == $image_url ){

                            $linked_image_url = self::get_linked_image_url($attachment_id);

                            $image_linked_size = self::get_setting('image_linked_size');

                            $new_linked_image_html = wp_get_attachment_image( $attachment_id, $image_linked_size );
                            
                            $new_link_html = '<a href="'.$linked_image_url.'">'.$new_linked_image_html.'</a>';
                            $new_link_html = apply_filters('ari_get_new_link_html',$new_link_html,$attachment_id);
                            
                            if ($new_link_html){
                                $new_link_el = $doc->createDocumentFragment();
                                $new_link_el->appendXML($new_link_html);

                                //replace <a> tag
                                $parentNode->parentNode->replaceChild($new_link_el, $parentNode);
                            }



                        }

                    }

                }

                //TO FIX : remove doctype, html and body tags.
                $post_content =  $doc->saveHTML();
                
            }

        }
        
        return $post_content;
    }
    
    function get_linked_image_url($attachment_id){
        
        $option = self::get_setting('image_linked_target');
        
        switch ($option) {
            case 'post': //attachment page
                $url = get_attachment_link( $attachment_id );
            break;
            default : // media url
                $url = wp_get_attachment_url( $attachment_id );
        }
        
        return apply_filters('ari_get_linked_image_url',$url, $attachment_id);
        
    }
    
    function uploaded_image_save_source($attachment_id){
        
        $source = $this->attachment_source;

        //add original URL to attachment, as meta
        add_post_meta($attachment_id, '_ari-url',$source);
    }
    
    /**
     * Count the number of medias already downloaded with Archive Remote Image
     * @return int
     */
    
    public function count_archived_attachments($post_id = false){
        
        $query_args = array(
            'post_type' => 'attachment',
            'meta_key'  => '_ari-url',
            'posts_per_page' => -1
        );
        
        if ($post_id) 
            $query_args['post_parent'] = $post_id;
        
        
        $meta_posts = get_posts( $query_args );
        
        $meta_post_count = count( $meta_posts );
        unset( $meta_posts);
        return (int)$meta_post_count;
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
	return ArchiveRemoteImages::instance();
}

if (is_admin()){
    ari();
}
    
