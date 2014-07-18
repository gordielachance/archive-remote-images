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
    }
    
    public function get_settings(){
        $options = get_option( $this->option_name, self::get_default_settings() );
        return apply_filters('ari_get_settings',$options);
    }
    
    public function get_default_settings(){
        $default = array(
            'default_checked'       => false,
            'post_types'            => self::option_post_type_allowed(),
            'replace_parent_link'   => true,
            'time_limit'            => ini_get('max_execution_time'),
        );
        return $default;
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
            'post_types', // ID
            __('Supported post types','ari'), // Title 
            array( $this, 'post_types_callback' ), // Callback
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
            'replace_parent_link', 
            __('Replace parent link','ari'), 
            array( $this, 'replace_parent_link_callback' ), 
            'ari-setting-admin', 
            'settings_general'
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
            $new_input['post_types'] = array();
            foreach ((array)$input['post_types'] as $post_type => $value){
                if (!post_type_exists( $post_type )) continue;
                $new_input['post_types'][] = $post_type;
            }

            //default checked
            if( isset( $input['default_checked'] ) )
                $new_input['default_checked'] = (bool)( $input['default_checked'] );

            //parent link
            if( isset( $input['replace_parent_link'] ) )
                $new_input['replace_parent_link'] = (bool)( $input['replace_parent_link'] );
            
            //time limit
            if( isset( $input['time_limit'] ) ){
                $new_input['time_limit'] = absint($input['time_limit']);
            }
            
        }
        
        

        $new_input = array_filter($new_input);
        
        return $new_input;
       
    }

    /** 
     * Print the Section text
     */
    public function section_general_desc(){
    }
    
    public function option_post_type_allowed(){
        $supported = array();
        $post_types = get_post_types();
        $disabled = apply_filters('ari_option_post_type_disabled',array(
                'attachment',
                'revision',
                'nav_menu_item'
            )
        );
        foreach ((array)$post_types as $slug){
            if (in_array($slug,$disabled)) continue;
            $supported[] = $slug;
            
        }
        return $supported;
    }

    /** 
     * Get the settings option array and print one of its values
     */
    public function post_types_callback(){

        $option = (array)ari()->get_setting('post_types');

        $post_types_allowed = self::option_post_type_allowed();
        foreach ((array)$post_types_allowed as $slug){

            $post_type = get_post_type_object($slug);
            $name = $post_type->name;
            $checked = checked( in_array($slug,$option), true, false );
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
    
    public function replace_parent_link_callback(){
        
        $option = ari()->get_setting('replace_parent_link');

        $checked = checked( (bool)$option, true, false );
                
        printf(
            '<input type="checkbox" name="%1$s[replace_parent_link]" value="on" %2$s/> %3$s',
            $this->option_name,
            $checked,
            __("If the remote image is wrapped into a link pointing to the same remote file, replace that link.","ari")
        );
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
