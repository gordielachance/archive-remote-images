<?php
class AriSettings{
    /**
     * Holds the values to be used in the fields callbacks
     */
    
    public $db_version_name = '_ari_db_version';
    public $db_version = '0104';
    
    public $option_name = '_ari_options';
    public $options = array();

    /**
     * Start up
     */
    public function __construct(){
        $this->options = self::get_settings();
        
        add_action( 'admin_menu', array( $this, 'add_plugin_page' ) );
        add_action( 'admin_init', array( $this, 'page_init' ) );
        add_action( 'plugins_loaded', array($this, 'upgrade'));//install and upgrade
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts_styles' ) );
    }
    
    public function get_settings(){
        $options = get_option( $this->option_name, self::get_default_settings() );
        return apply_filters('ari_get_settings',$options);
    }
    
    public function get_default_settings(){
        $default = array(
            'default_checked'       => false,
            'remember_status'       => false,
            'ignored_post_type'     => array(),
            'replace_parent_link'   => true,
            'time_limit'            => ini_get('max_execution_time'),
            'image_size'            => "full",
            'image_linked_size'     => "medium",
            'image_linked_target'   => "file"
        );
        return $default;
    }
    
    public function get_default_setting($name){
        $settings = self::get_default_settings();
        if (!array_key_exists($name, $settings)) return false;
        return $settings[$name];
    }
    
    function enqueue_scripts_styles($hook){
        if ($hook!='settings_page_ari-admin') return;
        wp_enqueue_script('ari-settings', ari()->plugin_url.'_inc/js/settings.js', array('jquery'),ari()->version);
        
    }
    
    public function allowed_post_types(){
        $post_types = get_post_types();
        $disabled = apply_filters('ari_option_post_type_disabled',array(
            'attachment',
            'revision',
            'nav_menu_item'
            )
        );
        $allowed = array();
        foreach ((array)$post_types as $post_type){
            if (in_array($post_type,$disabled)) continue;
            $allowed[] = $post_type;
        }
        return $allowed;
    }
    
    function upgrade(){
        global $wpdb;

        $current_version = get_option($this->db_version_name);

        if ( $current_version==$this->db_version ) return false;

        //install
        if(!$current_version){
            //handle SQL
            //require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            //dbDelta($sql);
            add_option($this->option_name,$this->get_default_settings()); // add settings
        }

        //upgrade DB version
        update_option($this->db_version_name, $this->db_version );//upgrade DB version
    }

    /**
     * Add options page
     */
    public function add_plugin_page()
    {
        // This page will be under "Settings"
        add_options_page(
                __('Archive Remote Images','ari'),
                __('Archive Remote Images','ari'),
                'manage_options',
                'ari-admin',
                array( $this, 'create_admin_page' )
        );
    }

    /**
     * Options page callback
     */
    public function create_admin_page(){
        // Set class property
        
        ?>
        <div class="wrap">
            <?php screen_icon(); ?>
            <h2><?php _e('Archive Remote Images','ari');?></h2>  
            
            <?php
            $count = ari()->count_archived_attachments();
            if ($count){
                ?>
                <p>
                    <?php
                    printf(
                        _n(
                            '<strong>1</strong> media has been downloaded using Archive Remote Images !',
                            'Already <strong>%s</strong> medias have been downloaded using Archive Remote Images !',
                            $count,
                            'ari' ),
                        $count
                    );
                    ?>
                </p>
                <?php
            }
            ?>
            
            
            <form method="post" action="options.php">
            <?php
                // This prints out all hidden setting fields
                settings_fields( 'ari_option_group' );   
                do_settings_sections( 'ari-setting-admin' );
                submit_button(); 
            ?>
            </form>
        </div>
        <?php
    }

    /**
     * Register and add settings
     */
    public function page_init()
    {        
        register_setting(
            'ari_option_group', // Option group
            $this->option_name, // Option name
            array( $this, 'sanitize' ) // Sanitize
        );

        add_settings_section(
            'settings_general', // ID
            __('General Options','ari'), // Title
            array( $this, 'section_general_desc' ), // Callback
            'ari-setting-admin' // Page
        );  

        add_settings_field(
            'ignored_post_type', // ID
            __('Supported post types','ari'), // Title 
            array( $this, 'post_type_callback' ), // Callback
            'ari-setting-admin', // Page
            'settings_general' // Section           
        );      

        add_settings_field(
            'default_checked', 
            __('Check archiving by default','ari'), 
            array( $this, 'default_checked_callback' ), 
            'ari-setting-admin', 
            'settings_general'
        );
        
        add_settings_field(
            'remember_status', 
            __('Remember status','ari'), 
            array( $this, 'remember_status_callback' ), 
            'ari-setting-admin', 
            'settings_general'
        );  
        
        add_settings_section(
            'settings_image', // ID
            __('Image Options','ari'), // Title
            array( $this, 'section_image_desc' ), // Callback
            'ari-setting-admin' // Page
        );
        
        add_settings_field(
            'image_size', 
            __('Image size','ari'), 
            array( $this, 'image_size_callback' ), 
            'ari-setting-admin', 
            'settings_image'
        );
        
        add_settings_field(
            'replace_parent_link', 
            __('Linked image','ari'), 
            array( $this, 'replace_parent_link_callback' ), 
            'ari-setting-admin', 
            'settings_image'
        );
        
        add_settings_field(
            'image_linked_size', 
            __('Linked image size','ari'), 
            array( $this, 'image_linked_size_callback' ), 
            'ari-setting-admin', 
            'settings_image'
        );
        
        add_settings_field(
            'image_linked_target', 
            __('Linked image target','ari'), 
            array( $this, 'image_linked_target_callback' ), 
            'ari-setting-admin', 
            'settings_image'
        );
        
        add_settings_section(
            'settings_system', // ID
            __('System Options','ari'), // Title
            array( $this, 'section_system_desc' ), // Callback
            'ari-setting-admin' // Page
        );
        
        add_settings_field(
            'time_limit', 
            __('Time Limit','ari'), 
            array( $this, 'time_limit_callback' ), 
            'ari-setting-admin', 
            'settings_system'
        );
        
        add_settings_field(
            'reset_options', 
            __('Reset Options','ari'), 
            array( $this, 'reset_options_callback' ), 
            'ari-setting-admin', 
            'settings_system'
        );
        
    }

    /**
     * Sanitize each setting field as needed
     *
     * @param array $input Contains all settings fields as array keys
     */
    public function sanitize( $input ){

        $new_input = array();
        
        if( isset( $input['reset_options'] ) ){
            
            $new_input = self::get_default_settings();
            
        }else{
            
            //post types
            $post_types = self::allowed_post_types();
            if( isset( $input['post_types'] ) ){
                $new_input['ignored_post_type'] = array();
                foreach ((array)$post_types as $post_type){
                    if (!array_key_exists($post_type, $input['post_types'])) $new_input['ignored_post_type'][] = $post_type;
                }
            }else{
                $new_input['ignored_post_type'] = $post_types;
            }

            //default checked
            if( isset( $input['default_checked'] ) )
                $new_input['default_checked'] = (bool)( $input['default_checked'] );
            
            //remember status
            if( isset( $input['remember_status'] ) )
                $new_input['remember_status'] = (bool)( $input['remember_status'] );
            
            //image size
             if( isset( $input['image_size'] ) )
                $new_input['image_size'] = self::sanitize_image_size("image_size",$input['image_size']);
             
            //linked image size
             if( isset( $input['image_linked_size'] ) )
                $new_input['image_linked_size'] = self::sanitize_image_size("image_linked_size",$input['image_linked_size']);

            //parent link
            if( isset( $input['replace_parent_link'] ) )
                $new_input['replace_parent_link'] = (bool)( $input['replace_parent_link'] );
            
            //linked image target
            if ( isset( $input['image_linked_target'] )){
                $new_input['image_linked_target'] = self::sanitize_linked_image_target($input['image_linked_target']);
            };
            
            
            //time limit
            if( isset( $input['time_limit'] ) ){
                $new_input['time_limit'] = absint($input['time_limit']);
            }
            
        }
        
        

        $new_input = array_filter($new_input);
        
        return $new_input;
       
    }
    
    function sanitize_image_size($option,$value){
        $default = self::get_default_setting($option);
        $available = self::available_image_size();

        if (in_array($value,$available)){
            return $value;
        }else{
            return $default;
        }
    }
    
    function sanitize_linked_image_target($value){
        $default = self::get_default_setting('image_linked_target');
        $available = self::image_linked_available_target();
        $available_slugs = array_keys($available);
        
        if (in_array($value,$available_slugs)){
            return $value;
        }else{
            return $default;
        }
        
    }
    
    public function image_linked_available_target(){
        $available = array(
            'file' => __('Media File'), 
            'post' => __('Attachment Page'),
        );
        return $available;
    }
    
    public function available_image_size(){
        $sizes[] = 'full';
        $sizes = array_merge($sizes,get_intermediate_image_sizes());
        return $sizes;
    }

    /** 
     * Print the Section text
     */
    public function section_general_desc(){
    }

    /** 
     * Get the settings option array and print one of its values
     */
    public function post_type_callback(){

        $ignored = (array)ari()->get_setting('ignored_post_type');
        $post_types = self::allowed_post_types();

        foreach ((array)$post_types as $slug){

            $post_type = get_post_type_object($slug);
            $name = $post_type->name;
            $checked = checked( in_array($slug,$ignored), false, false );
            printf(
                '<input type="checkbox" name="%1$s[post_types][%2$s]" value="on" %3$s/> %4$s<br/>',
                $this->option_name,
                $slug,
                $checked,
                $name
            );
        }
    }

    /** 
     * Get the settings option array and print one of its values
     */
    public function default_checked_callback(){
        
        $option = ari()->get_setting('default_checked');

        $checked = checked( (bool)$option, true, false );
                
        printf(
            '<input type="checkbox" name="%1$s[default_checked]" value="on" %2$s/>',
            $this->option_name,
            $checked
        );
    }
    
    public function remember_status_callback(){
        
        $option = ari()->get_setting('remember_status');

        $checked = checked( (bool)$option, true, false );
                
        printf(
            '<input type="checkbox" name="%1$s[remember_status]" value="on" %2$s/> %3$s',
            $this->option_name,
            $checked,
            __("Remember archiving status for posts, so you don't need to click the checkbox each time.","ari")
        );
    }
    
    public function section_image_desc(){
    }
    

    
    public function image_size_callback(){
        
        $option = ari()->get_setting('image_size');
        
        $box = '<select name="'.$this->option_name.'[image_size]">';
        foreach (self::available_image_size() as $size){
            $box .= '<option value="'.$size.'" '.selected( $option, $size, false ).'>'.$size.'</option>';
        }
        $box.='</select> ';
        
        printf(
            __('Display %1$s image size','ari'),
            $box
        );
    }
    
    public function image_linked_size_callback(){
        
        $option = ari()->get_setting('image_linked_size');
        
        $box = '<select name="'.$this->option_name.'[image_linked_size]">';
        foreach (self::available_image_size() as $size){
            $box .= '<option value="'.$size.'" '.selected( $option, $size, false ).'>'.$size.'</option>';
        }
        $box.='</select> ';
        
        printf(
            __('Display %1$s image size','ari'),
            $box
        );
    }
    
    public function replace_parent_link_callback(){
        
        $option = ari()->get_setting('replace_parent_link');
                
        printf(
            '<input type="checkbox" name="%1$s[replace_parent_link]" value="on" %2$s/> %3$s',
            $this->option_name,
            checked( (bool)$option, true, false ),
            __("If the remote image is wrapped into a link pointing to the same remote file, replace that link.","ari")
        );
    }
    
    public function image_linked_target_callback(){
        
        $option = ari()->get_setting('image_linked_target');
        
        $box = '<select name="'.$this->option_name.'[image_linked_target]">';
        foreach (self::image_linked_available_target() as $slug=>$name){
            $box .= '<option value="'.$slug.'" '.selected( $option, $slug, false ).'>'.$name.'</option>';
        }
        $box.='</select> ';
        
        echo $box;

    }
    
    public function section_system_desc(){
    }
    
    public function time_limit_callback(){
        
        $option = absint(ari()->get_setting('time_limit'));
        $min = 0;
        $max = 600;
        
        printf(
            '<input type="number" name="%1$s[time_limit]" value="%2$d" class="small-text" min="%3$d" max="%4$d"/> %5$s',
            $this->option_name,
            $option,
            $min,
            $max,
            __("Limits the maximum execution time for the script (seconds).","ari")
        );
    }
    
    public function reset_options_callback(){
        printf(
            '<input type="checkbox" name="%1$s[reset_options]" value="on"/> %2$s',
            $this->option_name,
            __("Reset options to their default values.","ari")
        );
    }
    
}
