<?php

    /**
    * @desc 
    * 
    * Return UserLevel
    * 
    */
    function userdata_get_user_level($return_as_numeric = FALSE)
        {
            global $userdata;
            
            $user_level = '';
            for ($i=10; $i >= 0;$i--)
                {
                    if (current_user_can('level_' . $i) === TRUE)
                        {
                            $user_level = $i;
                            if ($return_as_numeric === FALSE)
                                $user_level = 'level_'.$i; 
                            break;
                        }    
                }        
            return ($user_level);
        }    
        
    
    /**
    * @desc 
    * 
    * Reset Order for given post type
    * 
    */
    function reset_post_order($post_type)
        {
            global $wpdb;
            
             $query = "UPDATE ". $wpdb->posts ." SET `menu_order` = 0
                        WHERE `post_type` = '".$post_type ."'";
             $result = $wpdb->get_results($query);           
             
        } 
        
    /**
    * @desc 
    * 
    * Check the latest plugin version
    * 
    */
    function cpto_check_plugin_version($plugin)
        {
            if( strpos( CPTPATH . '/advanced-post-types-order.php', $plugin ) !== FALSE )
                {
                    //check last update check attempt
                    $last_check = get_option('acpto_last_version_check');
                    if (is_numeric($last_check) && (time() - 60*60*12) > $last_check)
                        {
                            $last_version_data = wp_remote_fopen(CPT_VERSION_CHECK_URL);
                            update_option('acpto_last_version_check_data', $last_version_data);    
                        }
                        else
                            {
                                $last_version_data = get_option('acpto_last_version_check_data');  
                            }
                    
                    if($last_version_data !== FALSE && $last_version_data != '') 
                        {
                            $info_raw = explode( '/',$last_version_data );
                            $info = array();
                            foreach ($info_raw as $line)
                                {
                                    list($name, $value)= explode("=", $line);
                                    $info[$name] = $value;
                                }
                                
                            if( ( version_compare( strval( $info['version'] ), CPTVERSION , '>' ) == 1 ) ) 
                                {
                                    ?>
                                        <tr class="plugin-update-tr">
                                            <td colspan="3" class="plugin-update colspanchange">
                                                <div class="update-message">There is a new version of Advanced Post Types Order. Use your purchase link to update or contact us if you lost it.</div>
                                            </td>
                                        </tr>
                                    <?php
                                } 
                        }
                        
                    //update last version check attempt
                    update_option('acpto_last_version_check', time());
                }   
            
        }
        

?>