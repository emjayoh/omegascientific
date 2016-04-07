<?php
/*
Plugin Name: Advanced Post Types Order
Plugin URI: http://www.nsp-code.com
Description: Order Post Types Objects using a Drag and Drop Sortable javascript capability
Author: Nsp Code
Author URI: http://www.nsp-code.com 
Version: 2.4.0.8.1
*/

define('CPTPATH',   WP_PLUGIN_DIR .'/advanced-post-types-order');
define('CPTURL',    WP_PLUGIN_URL.'/advanced-post-types-order');

define('CPTVERSION', '2.4.0.8.1');
define('CPT_VERSION_CHECK_URL', 'http://www.nsp-code.com/version-check/vcheck.php?app=advanced-post-types-order');

include_once(CPTPATH . '/include/functions.php');

register_deactivation_hook(__FILE__, 'CPTO_deactivated');
register_activation_hook(__FILE__, 'CPTO_activated');

function CPTO_activated() 
    {
        //make sure the vars are set as default
        $options = get_option('cpto_options');
        if (!isset($options['autosort']))
            $options['autosort'] = '1';
            
        if (!isset($options['adminsort']))
            $options['adminsort'] = '1';
            
        if (!isset($options['level']))
            $options['level'] = 8;
            
        update_option('cpto_options', $options);
    }

function CPTO_deactivated() 
    {
        
    }
    
add_action('admin_print_scripts', 'CPTO_admin_scripts');
function CPTO_admin_scripts()
    {
        wp_enqueue_script('jquery'); 
        
        if (!isset($_GET['page']))
            return;
        
        if (isset($_GET['page']) && strpos($_GET['page'], 'order-post-types-') === FALSE)
            return;
           
        $myJavascriptFile = CPTURL . '/js/interface.js';
        wp_register_script('interface.js', $myJavascriptFile);
        wp_enqueue_script( 'interface.js');

        
        $myJavascriptFile = CPTURL . '/js/inestedsortable.js';
        wp_register_script('inestedsortable.js', $myJavascriptFile);
        wp_enqueue_script( 'inestedsortable.js');
        
        $myJavascriptFile = CPTURL . '/js/apto-javascript.js';
        wp_register_script('apto-javascript.js', $myJavascriptFile);
        wp_enqueue_script( 'apto-javascript.js');
           
    }


add_filter('pre_get_posts', 'CPTO_pre_get_posts');
function CPTO_pre_get_posts($query)
    {
        //check for the force_no_custom_order param
        if (isset($query->query_vars['force_no_custom_order']) && $query->query_vars['force_no_custom_order'] === TRUE)
            return $query;
        
        $options = get_option('cpto_options');
        if (is_admin())
            {
                //no need if it's admin interface
                return false;   
            }
        //if auto sort    
        if ($options['autosort'] > 0)
            {
                //check if the current post_type is active in the setings
                $query_post_type = (isset($query->query_vars['post_type']) && $query->query_vars['post_type'] != '') ? $query->query_vars['post_type'] : 'post';
                if (isset($options['allow_post_types']) && !in_array($query_post_type, $options['allow_post_types']))
                    return $query;
                
                //remove the supresed filters;
                if (isset($query->query['suppress_filters']))
                    $query->query['suppress_filters'] = FALSE;    
                
                if (isset($query->query_vars['suppress_filters']))
                    $query->query_vars['suppress_filters'] = FALSE;
                    
                //update the sticky if required or not
                if (isset($options['ignore_sticky_posts']) && $options['ignore_sticky_posts'] == "1")
                    {
                        if (!isset($query->query_vars['ignore_sticky_posts']))
                            $query->query_vars['ignore_sticky_posts'] = TRUE;
                    }
            }
            
        //check 
            
        return $query;
    }

add_filter('posts_orderby', 'CPTOrderPosts', 99, 2);
function CPTOrderPosts($orderBy, $query) 
    {
        //check for the force_no_custom_order param
        if (isset($query->query_vars['force_no_custom_order']) && $query->query_vars['force_no_custom_order'] === TRUE)
            return $orderBy;
        
        global $wpdb;
        
        $options = get_option('cpto_options');
        
        if (is_admin())
                {
                    if ($options['adminsort'] == "1")
                        $orderBy = "{$wpdb->posts}.menu_order, {$wpdb->posts}.post_date DESC";
                }
            else
                {
                    //check if the current post_type is active in the setings
                     $query_post_type = (isset($query->query_vars['post_type']) && $query->query_vars['post_type'] != '') ? $query->query_vars['post_type'] : 'post';
                     if (isset($options['allow_post_types']) && !in_array($query_post_type, $options['allow_post_types']))
                        return $orderBy;
                    
                    //check if is feed
                    if (is_feed())
                        {
                            if ($options['feedsort'] != "1")
                                return $orderBy;
                                
                            //else use the set order
                            $orderBy = "{$wpdb->posts}.menu_order, {$wpdb->posts}.post_date DESC";
                            
                            return($orderBy);
                        }
                    
                    
                    if ($options['autosort'] == "1")
                        {
                            $orderBy = "{$wpdb->posts}.menu_order, {$wpdb->posts}.post_date DESC";  
                        }
                    if ($options['autosort'] == "2")
                        {
                            //check if the user didn't requested another order
                            if (!isset($query->query['orderby']))
                                $orderBy = "{$wpdb->posts}.menu_order, {$wpdb->posts}.post_date DESC";  
                        }
                }

        return($orderBy);
    }
    
    
add_action('wp_loaded', 'initCPTO' );
add_action('admin_menu', 'cpto_plugin_menu', 1);

add_action( 'plugins_loaded', 'cpto_load_textdomain', 2 );

add_filter('get_previous_post_where', 'cpto_get_previous_post_where');
add_filter('get_previous_post_sort', 'cpto_get_previous_post_sort');
add_filter('get_next_post_where', 'cpto_get_next_post_where');
add_filter('get_next_post_sort', 'cpto_get_next_post_sort');


function cpto_load_textdomain() 
    {
        $locale = get_locale();
        $mofile = CPTPATH . '/lang/cpt-' . $locale . '.mo';
        if ( file_exists( $mofile ) ) {
            load_textdomain( 'cppt', $mofile );
        }
    }
  

function cpto_plugin_menu() 
    {
        include (CPTPATH . '/include/options.php');
        add_options_page('Post Types Order', '<img class="menu_pto" src="'. CPTURL .'/images/menu-icon.gif" alt="" />Post Types Order', 'manage_options', 'cpto-options', 'cpt_plugin_options');
    }
	
function initCPTO() 
    {
	    global $custom_post_type_order, $userdata;

        $options = get_option('cpto_options');

        if (is_admin())
            {
                //check for new version once per day
                add_action( 'after_plugin_row','cpto_check_plugin_version' );
                                
                if (is_numeric($options['level']))
                    {
                        if (userdata_get_user_level(TRUE) >= $options['level'])
                            $custom_post_type_order = new CPTO();     
                    }
                    else
                        {
                            $custom_post_type_order = new CPTO();   
                        }
            }        
    }
    
function cpto_get_previous_post_where($where)
    {
        global $post, $wpdb;

        if ( empty( $post ) )
            return $where;
        
        $options = get_option('cpto_options');
            
        //check if the current post_type is active in the setings
        if (isset($options['allow_post_types']) && !in_array($post->post_type, $options['allow_post_types']))
            return $where;

        $current_post_date = $post->post_date;

        $join = '';
        $posts_in_ex_cats_sql = '';
        if ( $in_same_cat || !empty($excluded_categories) ) 
            {
                $join = " INNER JOIN $wpdb->term_relationships AS tr ON p.ID = tr.object_id INNER JOIN $wpdb->term_taxonomy tt ON tr.term_taxonomy_id = tt.term_taxonomy_id";

                if ( $in_same_cat ) {
                    $cat_array = wp_get_object_terms($post->ID, 'category', array('fields' => 'ids'));
                    $join .= " AND tt.taxonomy = 'category' AND tt.term_id IN (" . implode(',', $cat_array) . ")";
                }

                $posts_in_ex_cats_sql = "AND tt.taxonomy = 'category'";
                if ( !empty($excluded_categories) ) {
                    $excluded_categories = array_map('intval', explode(' and ', $excluded_categories));
                    if ( !empty($cat_array) ) {
                        $excluded_categories = array_diff($excluded_categories, $cat_array);
                        $posts_in_ex_cats_sql = '';
                    }

                    if ( !empty($excluded_categories) ) {
                        $posts_in_ex_cats_sql = " AND tt.taxonomy = 'category' AND tt.term_id NOT IN (" . implode($excluded_categories, ',') . ')';
                    }
                }
            }
        $current_menu_order = $post->menu_order;
        
        //check if there are more posts with lower menu_order
        $query = "SELECT p.* FROM $wpdb->posts AS p
                    WHERE p.menu_order > '".$current_menu_order."' AND p.post_type = '". $post->post_type ."' AND p.post_status = 'publish' $posts_in_ex_cats_sql";
        $results = $wpdb->get_results($query);
                
        if (count($results) > 0)
            {
                $where = $wpdb->prepare("WHERE p.menu_order > '".$current_menu_order."' AND p.post_type = '". $post->post_type ."' AND p.post_status = 'publish' $posts_in_ex_cats_sql");        
            }
            else
                {
                    //$where = $wpdb->prepare("WHERE p.post_date < '".$current_post_date."' AND p.post_type = '". $post->post_type ."' AND p.post_status = 'publish' AND p.ID != '". $post->ID ."' $posts_in_ex_cats_sql");            
                    $where = $wpdb->prepare("WHERE 1 = 2");
                }
        
        return $where;
    }
    
function cpto_get_previous_post_sort($sort)
    {
        global $post, $wpdb;
        
        $options = get_option('cpto_options');
            
        //check if the current post_type is active in the setings
        if (isset($options['allow_post_types']) && !in_array($post->post_type, $options['allow_post_types']))
            return $sort;
        
        $current_menu_order = $post->menu_order; 
        
        $query = "SELECT p.* FROM $wpdb->posts AS p
                    WHERE p.menu_order > '".$current_menu_order."' AND p.post_type = '". $post->post_type ."' AND p.post_status = 'publish' $posts_in_ex_cats_sql";
        $results = $wpdb->get_results($query);
        
        if (count($results) > 0)
                {
                    $sort = 'ORDER BY p.menu_order ASC, p.post_date ASC LIMIT 1';
                }
            else
                {
                    $sort = 'ORDER BY p.post_date DESC LIMIT 1';
                }

        return $sort;
    }

function cpto_get_next_post_where($where)
    {
        global $post, $wpdb;

        if ( empty( $post ) )
            return null;
            
        $options = get_option('cpto_options');
            
        //check if the current post_type is active in the setings
         if (isset($options['allow_post_types']) && !in_array($post->post_type, $options['allow_post_types']))
            return $where;

        $current_post_date = $post->post_date;

        $join = '';
        $posts_in_ex_cats_sql = '';
        if ( $in_same_cat || !empty($excluded_categories) ) 
            {
                $join = " INNER JOIN $wpdb->term_relationships AS tr ON p.ID = tr.object_id INNER JOIN $wpdb->term_taxonomy tt ON tr.term_taxonomy_id = tt.term_taxonomy_id";

                if ( $in_same_cat ) {
                    $cat_array = wp_get_object_terms($post->ID, 'category', array('fields' => 'ids'));
                    $join .= " AND tt.taxonomy = 'category' AND tt.term_id IN (" . implode(',', $cat_array) . ")";
                }

                $posts_in_ex_cats_sql = "AND tt.taxonomy = 'category'";
                if ( !empty($excluded_categories) ) {
                    $excluded_categories = array_map('intval', explode(' and ', $excluded_categories));
                    if ( !empty($cat_array) ) {
                        $excluded_categories = array_diff($excluded_categories, $cat_array);
                        $posts_in_ex_cats_sql = '';
                    }

                    if ( !empty($excluded_categories) ) {
                        $posts_in_ex_cats_sql = " AND tt.taxonomy = 'category' AND tt.term_id NOT IN (" . implode($excluded_categories, ',') . ')';
                    }
                }
            }
        
        $current_menu_order = $post->menu_order;
        
        //check if there are more posts with lower menu_order
        $query = "SELECT p.* FROM $wpdb->posts AS p
                    WHERE p.menu_order < '".$current_menu_order."' AND p.post_type = '". $post->post_type ."' AND p.post_status = 'publish' $posts_in_ex_cats_sql";
        $results = $wpdb->get_results($query);
        
        if (count($results) > 0)
            {
                $where = $wpdb->prepare("WHERE p.menu_order < '".$current_menu_order."' AND p.post_type = '". $post->post_type ."' AND p.post_status = 'publish' $posts_in_ex_cats_sql");        
            }
            else
                {
                    //$where = $wpdb->prepare("WHERE p.menu_order < '".$current_menu_order."' AND p.post_type = '". $post->post_type ."' AND p.post_status = 'publish' $posts_in_ex_cats_sql");
                    $where = $wpdb->prepare("WHERE 1 = 2");
                }
        
        return $where;
    }

function cpto_get_next_post_sort($sort)
    {
        global $post, $wpdb;
        
        $options = get_option('cpto_options');
            
        //check if the current post_type is active in the setings
        if (isset($options['allow_post_types']) && !in_array($post->post_type, $options['allow_post_types']))
            return $sort; 
        
        $current_menu_order = $post->menu_order; 
        
        $query = "SELECT p.* FROM $wpdb->posts AS p
                    WHERE p.menu_order < '".$current_menu_order."' AND p.post_type = '". $post->post_type ."' AND p.post_status = 'publish' $posts_in_ex_cats_sql";
        $results = $wpdb->get_results($query);
        if (count($results) > 0)
                {
                    $sort = 'ORDER BY p.menu_order DESC, p.post_date DESC LIMIT 1';
                }
            else
                {
                    $sort = 'ORDER BY p.post_date ASC LIMIT 1';
                }
        
        return $sort;    
    }
    
    
class Post_Types_Order_Walker extends Walker 
    {

        var $db_fields = array ('parent' => 'post_parent', 'id' => 'ID');


        function start_lvl(&$output, $depth, $args) 
            {
                extract($args, EXTR_SKIP);
                
                $post_type_data = get_post_type_object($post_type);
                if ($post_type_data->hierarchical === TRUE)
                    $wrapper_html = 'ol';
                    else
                    $wrapper_html = 'ul';
                
                $indent = str_repeat("\t", $depth);
                $output .= "\n$indent<$wrapper_html class='children'>\n";
            }


        function end_lvl(&$output, $depth, $args) 
            {
                extract($args, EXTR_SKIP);
                
                $post_type_data = get_post_type_object($post_type);
                if ($post_type_data->hierarchical === TRUE)
                    $wrapper_html = 'ol';
                    else
                    $wrapper_html = 'ul';
                    
                $indent = str_repeat("\t", $depth);
                $output .= "$indent</$wrapper_html>\n";
            }


        function start_el(&$output, $post_type, $depth, $args) 
            {
                if ( $depth )
                    $indent = str_repeat("\t", $depth);
                else
                    $indent = '';

                extract($args, EXTR_SKIP);

                $options = get_option('cpto_options');
                
                //check post thumbnail
                if (function_exists('get_post_thumbnail_id'))
                        {
                            $image_id = get_post_thumbnail_id( $post_type->ID , 'post-thumbnail' );
                        }
                    else
                        {
                            $image_id = NULL;    
                        }
                if ($image_id > 0)
                    {
                        $image = wp_get_attachment_image_src( $image_id , array(64,64)); 
                        $image_html =  '<img src="'. $image[0] .'" width="64" alt="" />';
                    }
                    else
                        {
                            $image_html =  '<img src="'. CPTURL .'/images/nt.gif" width="64" alt="" />';    
                        }
                
                $output .= $indent . '<li class="post_type_li" id="item_'.$post_type->ID.'"><div class="item"><div class="post_type_thumbnail"';
                
                if (isset($options['always_show_thumbnails']) && $options['always_show_thumbnails'] == "1")
                    $output .= ' style="display: block"';
                    
                $output .= '>'. $image_html .'</div><span>'.apply_filters( 'the_title', $post_type->post_title, $post_type->ID ).' ('.$post_type->ID.')';
                
                if ($post_type->post_status != 'publish')
                    $output .= ' <span>'.$post_type->post_status.'</span>';
                 
                $output .= '</span><span class="edit"><a href="'. get_bloginfo('wpurl') .'/wp-admin/post.php?post='.$post_type->ID.'&action=edit">Edit</a></span></div>';
            }


        function end_el(&$output, $post_type, $depth) 
            {
                $output .= "</li>\n";
            }

    }


class CPTO 
    {
	    var $current_post_type = null;
	    
	    function CPTO() 
            {
		        add_action( 'admin_init', array(&$this, 'registerFiles'), 11 );
                add_action( 'admin_init', array(&$this, 'checkPost'), 10 );
		        
                if (isset($_GET['page']) && $_GET['page'] == 'cpto-options')
                    {
                        add_action( 'admin_menu', 'cpt_optionsUpdate' );
                        add_action( 'admin_head', 'cpt_optionsUpdateMessage', 10 );
                    }
                    
                add_action( 'admin_menu', array(&$this, 'addMenu'), 99 );
                
                
		        
		        add_action( 'wp_ajax_update-custom-type-order', array(&$this, 'saveAjaxOrder') );
                add_action( 'wp_ajax_update-custom-type-order-hierarchical', array(&$this, 'saveAjaxOrderHierarchical') );
                
	        }

	    function registerFiles() 
            {
		        if ( $this->current_post_type != null ) 
                    {
                        wp_enqueue_script('jQuery');
                        wp_enqueue_script('jquery-ui-sortable');
		            }
                    
                wp_register_style('CPTStyleSheets', CPTURL . '/css/cpt.css');
                wp_enqueue_style( 'CPTStyleSheets');
	        }
	    
	    function checkPost() 
            {
		        if ( isset($_GET['page']) && substr($_GET['page'], 0, 17) == 'order-post-types-' ) 
                    {
			            $this->current_post_type = get_post_type_object(str_replace( 'order-post-types-', '', $_GET['page'] ));
			            if ( $this->current_post_type == null) 
                            {
				                wp_die('Invalid post type');
			                }
		            }
	        }
	    
	    function saveAjaxOrder() 
            {
		        global $wpdb;
		        
		        parse_str($_POST['order'], $data);
		        
		        if (is_array($data))
                foreach($data as $key => $values ) 
                    {
			            foreach( $values as $position => $postID ) 
                            {
                                $wpdb->update( $wpdb->posts, array('menu_order' => ($position + 1)), array('ID' => $postID) );
                            } 
		            }
                    
	        }
            
        function saveAjaxOrderHierarchical($data)
            {
                global $wpdb;
                
                parse_str($_POST['order'], $data);
                $data = $data['opt_sortable'];
                
                $this->hierarchicalRecurringProcess($data);
            }
            
        function hierarchicalRecurringProcess($data, $page_parentID = 0)
            {
                global $wpdb;
                
                $position = 0;        
                foreach ($data as $key => $pageData) 
                    {

                        $pageID = str_replace('item_', '', $pageData['id']);
                                
                        $wpdb->update( $wpdb->posts, array('menu_order' => $position, 'post_parent' => $page_parentID), array('ID' => $pageID) );
                                
                        $position++;
                        
                        if (is_array($pageData['children'])) 
                            {
                                $this->hierarchicalRecurringProcess($pageData['children'], $pageID);
                            }
                    }
            }
	    

	    function addMenu() 
            {
		        global $userdata;
                
                $options = get_option('cpto_options');
                
                //put a menu for all custom_type
                $post_types = get_post_types();
                $ignore = array (
                                    'attachment',
                                    'revision',
                                    'nav_menu_item'
                                    );
                foreach( $post_types as $post_type_name ) 
                    {
                        if (in_array($post_type_name, $ignore))
                            continue;
                        
                        //check for exclusion
                        if (isset($options['allow_post_types']) && !in_array($post_type_name, $options['allow_post_types']))
                            continue;

                        if ($post_type_name == 'post')
                            add_submenu_page('edit.php', 'Re-Order', 'Re-Order', userdata_get_user_level(), 'order-post-types-'.$post_type_name, array(&$this, 'SortPage') );
                        else
                            add_submenu_page('edit.php?post_type='.$post_type_name, 'Re-Order', 'Re-Order', userdata_get_user_level(), 'order-post-types-'.$post_type_name, array(&$this, 'SortPage') );
		            }
	        }
	    

	    function SortPage() 
            {
		        global $wpdb, $wp_locale;
                
                $wpdb->flush();

                $post_type = $this->current_post_type->name;
                
                //check for order reset
                if (isset($_POST['order_reset']) && $_POST['order_reset'] == '1' && $post_type != '')
                    {
                        reset_post_order($post_type);
                    }
                
                $is_hierarchical = $this->current_post_type->hierarchical;
                
                $current_taxonomy   = isset($_GET['current_taxonomy']) ? $_GET['current_taxonomy'] : '';
                if (!taxonomy_exists($current_taxonomy))
                    $current_taxonomy = '';
                 
                $m                  = isset($_GET['m']) ? (int)$_GET['m'] : 0;
                $cat                = isset($_GET['cat']) ? (int)$_GET['cat'] : -1;
                $s                  = isset($_GET['s']) ? $_GET['s'] : '';
                
                //hold the current_taxonomy selection to be restored on new access
                $cpto_taxonomy_selections = get_option('cpto_taxonomy_selections');
                if (!is_array($cpto_taxonomy_selections))
                    $cpto_taxonomy_selections = array();
                
                //save the current taxonomy selection
                if ($current_taxonomy != '' && taxonomy_exists($current_taxonomy) && is_taxonomy_hierarchical($current_taxonomy))
                    {
                        if (!is_array($cpto_taxonomy_selections[$post_type]))
                            $cpto_taxonomy_selections[$post_type] = array();
                            
                        $cpto_taxonomy_selections[$post_type]['taxonomy'] = $current_taxonomy; 
                    }
                    
                //save the current term selection
                if ($cat > -1)
                    {
                        if (!is_array($cpto_taxonomy_selections[$post_type]))
                            $cpto_taxonomy_selections[$post_type] = array();
                        
                        $cpto_taxonomy_selections[$post_type]['term_id'] = $cat; 
                    }
                
                //try to restore if it's emtpy
                if ($current_taxonomy == '')
                    {
                        if (array_key_exists($post_type, $cpto_taxonomy_selections) && is_array($cpto_taxonomy_selections[$post_type]) && array_key_exists('taxonomy', $cpto_taxonomy_selections[$post_type]))
                            $current_taxonomy   = $cpto_taxonomy_selections[$post_type]['taxonomy'];
                        
                        if ($current_taxonomy != '' && !is_taxonomy_hierarchical($current_taxonomy))
                            $current_taxonomy = '';
                            
                        //restore the term if it's not empty
                        if ($cat < 0)
                            {
                                if (array_key_exists($post_type, $cpto_taxonomy_selections) && is_array($cpto_taxonomy_selections[$post_type]) && array_key_exists('term_id', $cpto_taxonomy_selections[$post_type]))
                                    $cat   = $cpto_taxonomy_selections[$post_type]['term_id'];
                                    
                                if (term_exists($cat, $current_taxonomy) === FALSE)
                                    $cat = -1;
                            }
                    }
                
                //$current_taxonomy = '';
                
                ?>
		        <div class="wrap">
			        <div class="icon32" id="icon-edit"><br></div>
                    <h2><?php echo $this->current_post_type->labels->singular_name . ' -  Re-order '?></h2>

			        <div id="ajax-response"></div>
			        
			        <noscript>
				        <div class="error message">
					        <p>This plugin can't work without javascript, because it's use drag and drop and AJAX.</p>
				        </div>
			        </noscript>

                    <div class="clear"></div>
                    
                    <form action="<?php echo admin_url('edit.php'); ?>" method="get" id="apto_form">
                        <?php
                            if ($post_type != 'post')
                                {
                                    ?>
                        <input type="hidden" value="<?php echo $post_type ?>" name="post_type" />
                        <?php } ?>
                        <input type="hidden" value="order-post-types-<?php echo $post_type ?>" name="page" />
                        
                    <?php
                        
                        //check the post taxonomies.
                        $object_taxonomies = get_object_taxonomies($post_type);
                        
                        //use only the hierarchical
                        foreach ($object_taxonomies as $key => $taxonomy)
                            {
                                if (!is_taxonomy_hierarchical($taxonomy))
                                    {
                                        unset($object_taxonomies[$key]);   
                                    }
                            }
                            
                        if ($current_taxonomy == '' && count($object_taxonomies) >= 1)
                            {
                                //use categories as default
                                if (in_array('category', $object_taxonomies))
                                    {
                                        $current_taxonomy = 'category';   
                                    }
                                    else
                                        {
                                            reset($object_taxonomies);
                                            $current_taxonomy = current($object_taxonomies);
                                        }
                                $cpto_taxonomy_selections[$post_type]['taxonomy'] = $current_taxonomy;
                            }
                            
                        update_option('cpto_taxonomy_selections', $cpto_taxonomy_selections);
                        $current_taxonomy_info = get_taxonomy($current_taxonomy);
                        
                            
                        if (count($object_taxonomies) > 1)
                            {
                    
                                ?>
                                
                                <h2 class="subtitle"><?php echo $this->current_post_type->labels->singular_name ?> Taxonomies</h2>
                                <table cellspacing="0" class="wp-list-taxonomy widefat fixed">
                                    <thead>
                                    <tr>
                                        <th style="" class="column-cb check-column" id="cb" scope="col">&nbsp;</th><th style="" class="" id="author" scope="col">Taxonomy Title</th><th style="" class="manage-column" id="categories" scope="col">Total <?php echo $this->current_post_type->labels->singular_name ?> Posts</th>    </tr>
                                    </thead>

                                    <tfoot>
                                    <tr>
                                        <th style="" class="column-cb check-column" id="cb" scope="col">&nbsp;</th><th style="" class="" id="author" scope="col">Taxonomy Title</th><th style="" class="manage-column" id="categories" scope="col">Total <?php echo $this->current_post_type->labels->singular_name ?> Posts</th>    </tr>
                                    </tfoot>

                                    <tbody id="the-list">
                                    <?php
                                        
                                        $alternate = FALSE;
                                        
                                        foreach ($object_taxonomies as $key => $taxonomy)
                                            {
                                                $alternate = $alternate === TRUE ? FALSE :TRUE;
                                                $taxonomy_info = get_taxonomy($taxonomy);
                                                
                                                $taxonomy_terms = get_terms($taxonomy);
                                                
                                                $taxonomy_terms_ids = array();
                                                foreach ($taxonomy_terms as $taxonomy_term)
                                                    $taxonomy_terms_ids[] = $taxonomy_term->term_id;    
                                                
                                                if (count($taxonomy_terms_ids) > 0)
                                                    {
                                                        $term_ids = array_map('intval', $taxonomy_terms_ids );
                                                                                                                      
                                                        $term_ids = "'" . implode( "', '", $term_ids ) . "'";
                                                                                                                                 
                                                        $query = "SELECT COUNT(DISTINCT tr.object_id) as count FROM $wpdb->term_relationships AS tr 
                                                                        INNER JOIN $wpdb->term_taxonomy AS tt ON tr.term_taxonomy_id = tt.term_taxonomy_id 
                                                                        INNER JOIN $wpdb->posts as posts ON tr.object_id = posts.ID
                                                                        WHERE tt.taxonomy IN ('$taxonomy') AND tt.term_id IN ($term_ids) AND  posts.post_type = '$post_type' AND posts.post_status = 'publish' AND posts.post_parent = '0'
                                                                        ORDER BY tr.object_id" ;
                                                        $count = $wpdb->get_var($query);
                                                    }
                                                    else
                                                        {
                                                            $count = 0;   
                                                        }
                                                
                                                ?>
                                                    <tr valign="top" class="<?php if ($alternate === TRUE) {echo 'alternate ';} ?>" id="taxonomy-<?php echo $taxonomy  ?>">
                                                            <th class="check-column" scope="row"><input type="radio" onclick="apto_change_taxonomy(this)" value="<?php echo $taxonomy ?>" <?php if ($current_taxonomy == $taxonomy) {echo 'checked="checked"';} ?> name="current_taxonomy">&nbsp;</th>
                                                            <td class="categories column-categories"><?php echo $taxonomy_info->label ?></td>
                                                            <td class="categories column-categories"><?php echo $count ?></td>
                                                    </tr>
                                                
                                                <?php
                                            }
                                    ?>
                                    </tbody>
                                </table>
                                <br /><br /> 
                                <?php
                            }
                                ?>

                    <div id="order-post-type">
                        
                        <div id="nav-menu-header">
                            <div class="major-publishing-actions">

                                    <div class="alignleft actions"> 
                                    <?php
                                    
                                        $arc_query = $wpdb->prepare("SELECT DISTINCT YEAR(post_date) AS yyear, MONTH(post_date) AS mmonth FROM $wpdb->posts WHERE post_type = %s ORDER BY post_date DESC", $post_type);

                                        $arc_result = $wpdb->get_results( $arc_query );

                                        $month_count = count($arc_result);

                                        if ( $month_count && !( 1 == $month_count && 0 == $arc_result[0]->mmonth ) ) {
                                        
                                        ?>
                                        <select name='m'>
                                                                                
                                        <option<?php selected( $m, 0 ); ?> value='0'><?php _e('Show all dates'); ?></option>
                                        <?php
                                        foreach ($arc_result as $arc_row) {
                                            if ( $arc_row->yyear == 0 )
                                                continue;
                                            $arc_row->mmonth = zeroise( $arc_row->mmonth, 2 );

                                            if ( $arc_row->yyear . $arc_row->mmonth == $m )
                                                $default = ' selected="selected"';
                                            else
                                                $default = '';

                                            echo "<option$default value='" . esc_attr("$arc_row->yyear$arc_row->mmonth") . "'>";
                                            echo $wp_locale->get_month($arc_row->mmonth) . " $arc_row->yyear";
                                            echo "</option>\n";
                                        }
                                        ?>
                                        </select>
                                        <?php } ?>

                                        <?php
                                        
                                        if ( is_object_in_taxonomy($post_type, $current_taxonomy) ) 
                                            {
                                                //check if there are any terms in that taxonomy before ouptut the dropdown
                                                $argv = array(
                                                                'hide_empty'    =>   0
                                                                );
                                                $terms = get_terms($current_taxonomy, $argv);
                                                
                                                $dropdown_options = array(
                                                                            'show_option_all'   => 'View all '. $current_taxonomy_info->label .' Terms', 
                                                                            'hide_empty'        => 0, 
                                                                            'hierarchical'      => 1,
                                                                            'show_count'        => 1, 
                                                                            'orderby'           => 'name', 
                                                                            'taxonomy'          =>  $current_taxonomy,
                                                                            'selected'          => $cat);
                                                
                                                if (count($terms) > 0)
                                                    wp_dropdown_categories($dropdown_options);
                                            }
                                    
                                    
                                    
                                    ?>

                                        <input type="submit" class="button-secondary" value="Filter" id="post-query-submit">
                                    </div>
                                    
                                    <div class="alignright actions">
                                        <p class="actions">
                                            
                                            <a class="button-secondary alignleft toggle_thumbnails" title="Cancel" href="javascript:;" onclick="toggle_thumbnails(); return false;">Toggle Thumbnails</a>
                                            
                                            <?php if ($is_hierarchical === FALSE)
                                                {
                                                    ?>
                                            <input type="text" value="<?php if (isset($_GET['s'])) {echo $_GET['s'];} ?>" name="s" id="post-search-input">
                                            <input type="submit" class="button" value="Search">
                                            <?php  } ?>
                                            <span class="img_spacer">&nbsp;
                                                <img alt="" src="<?php echo CPTURL ?>/images/wpspin_light.gif" class="waiting pto_ajax_loading" style="display: none;">
                                            </span>
                                            <a href="javascript:;" class="save-order button-primary">Update</a>
                                        </p>
                                    </div>
                                    
                                    <div class="clear"></div>

                            </div><!-- END .major-publishing-actions -->
                        </div><!-- END #nav-menu-header -->

                        <?php
                        
                            if ($is_hierarchical === TRUE)
                                $html_wrapper = "ol";
                                else
                                $html_wrapper = "ul";
                        
                        ?>
                        
                        <div id="post-body">                    
			                
				                <<?php echo $html_wrapper ?> id="opt_sortable">
					                <?php $this->listPostType('s='. $s .'&m='.$m.'&cat='.$cat.'&hide_empty=0&title_li=&post_type='.$this->current_post_type->name.'&taxonomy='.$current_taxonomy); ?>
				                </<?php echo $html_wrapper ?>>
				                
				                <div class="clear"></div>
			            </div>
                        
                        <div id="nav-menu-footer">
                            <div class="major-publishing-actions">
                                        
                                    <div class="alignright actions">
                                        <p class="submit">
                                            <img alt="" src="<?php echo CPTURL ?>/images/wpspin_light.gif" class="waiting pto_ajax_loading" style="display: none;">
                                            <a href="javascript:;" class="save-order button-primary">Update</a>
                                        </p>
                                    </div>
                                    
                                    <div class="clear"></div>

                            </div><!-- END .major-publishing-actions -->
                        </div><!-- END #nav-menu-header -->
                        
                    </div> 

			        </form>
                    <br />
                    <form action="" method="post">
                        <input type="hidden" name="order_reset" value="1" />
                        <a id="order_Reset" class="button-primary" href="javascript: void(0)" onclick="confirmSubmit()">Reset Order</a>
                    </form>
                    
			        <script type="text/javascript">
				        
                        function confirmSubmit()
                            {
                                var agree=confirm("Are you sure you want to reset the order??");
                                if (agree)
                                    {
                                        jQuery('a#order_Reset').closest('form').submit();   
                                    }
                                    else
                                    {
                                        return false ;
                                    }
                            }
                        
                        jQuery(document).ready(function() {
					        jQuery("ul#opt_sortable").sortable({
						        'tolerance':'intersect',
						        'cursor':'pointer',
                                'items':'li',
						        'placeholder':'placeholder',
						        'nested': 'ul'
					        });
                            
                            
                            var NestedSortableSerializedData;
                            jQuery('ol#opt_sortable').NestedSortable({
                                    accept: 'post_type_li',
                                    opacity: 0.8,
                                    helperclass: 'placeholder',
                                    nestingPxSpace: 20,
                                    currentNestingClass: 'current-nesting',
                                    fx:400,
                                    revert: true,
                                    autoScroll: false,
                                    onChange : function(serialized) {
                                                            NestedSortableSerializedData = serialized[0].hash; 
                                                        }
                                });

					        jQuery(".save-order").bind( "click", function() {
						        jQuery(this).parent().find('img').show();
                                                                                            
                                if (jQuery('#order-post-type ol#opt_sortable').length > 0)
                                    {
                                        if (NestedSortableSerializedData !== undefined)
                                            {
                                                jQuery.post( ajaxurl, { action:'update-custom-type-order-hierarchical', order:NestedSortableSerializedData }, function() {
                                                        jQuery("#ajax-response").html('<div class="message updated fade"><p>Items Order Updates</p></div>');
                                                        jQuery("#ajax-response div").delay(3000).hide("slow");
                                                        jQuery('img.pto_ajax_loading').hide();
                                                    });
                                            }
                                            else
                                                {
                                                    //fake, if no resort no need to send any data
                                                    jQuery("#ajax-response").html('<div class="message updated fade"><p>Items Order Updates</p></div>');
                                                    jQuery("#ajax-response div").delay(3000).hide("slow");
                                                    jQuery('img.pto_ajax_loading').hide();
                                                }  
                                    }
                                    else
                                        {
                                            jQuery.post( ajaxurl, { action:'update-custom-type-order', order:jQuery("#opt_sortable").sortable("serialize") }, function() {
							                    jQuery("#ajax-response").html('<div class="message updated fade"><p>Items Order Updates</p></div>');
							                    jQuery("#ajax-response div").delay(3000).hide("slow");
                                                jQuery('img.pto_ajax_loading').hide();
						                    });
                                        }
					        });
				        });
			        </script>
                    
		        </div>
		        <?php
	        }

	    function listPostType($args = '') 
            {
		        $defaults = array(
			        'depth' => 0, 'show_date' => '',
			        'date_format' => get_option('date_format'),
			        'child_of' => 0, 
                    'exclude' => '',
			        'title_li' => __('Pages'), 
                    'echo' => 1,
			        'authors' => '', 
                    'sort_column' => 'menu_order',
			        'link_before' => '', 
                    'link_after' => '', 
                    'walker' => ''
		        );

		        $r = wp_parse_args( $args, $defaults );
		        extract( $r, EXTR_SKIP );

		        $output = '';

		        // Query pages.
                $args = array(
                            'sort_column'       =>  'menu_order',
                            'post_type'         =>  $post_type,
                            'posts_per_page'    => -1,
                            'orderby'           => 'menu_order',
                            'order'             => 'ASC'

                );

                //filter a taxonomy term
                $tax_query = array(); 
                if ($taxonomy != '')
                    {
                        global $wp_version;
                        //wp under 3.1 fix
                        if(version_compare( $wp_version, strval('3.1') , '<' ) )
                            {
                                if ($cat > 0)
                                    {
                                        $update_tax_name = $taxonomy;
                                        $term_data = get_term_by('id', $cat, $taxonomy);
                                        
                                        if ($taxonomy == 'category')
                                            {
                                                $args['cat'] = $term_data->term_id;    
                                            }
                                            else
                                                {
                                                    $args[$taxonomy] = $term_data->name;   
                                                }
                                    }       
                            }
                            else
                            { 
                                if ($cat > 0)
                                    {
                                        $tax_query = array(
                                                                    array(
                                                                            'taxonomy'  => $taxonomy,
                                                                            'field'     => 'id',
                                                                            'terms'     => $cat
                                                                                    )
                                                                    );                             
                                        
                                    }
                            }

                    }
                        
                $args['tax_query'] = $tax_query;
                    
                //filter a date
                if ($m > 0)
                    {
                        $year   = substr($m, 0, 4);
                        $month  = substr($m, 4, 2);
                        $args['year'] = $year;
                        $args['monthnum'] = $month;
                    }
                    
                //search filter
                if ($s != '')
                    {
                        $args['s'] = $s;
                    }
                
                $the_query = new WP_Query($args);
                $post_types = $the_query->posts;

		        if ( !empty($post_types) ) 
                    {
			            $output = $this->walkTree($post_types, $r['depth'], $r);
		            }

		        if ( $r['echo'] )
			        echo $output;
		        else
			        return $output;
	        }
	    
	    function walkTree($post_types, $depth, $r) 
            {
		        if ( empty($r['walker']) )
			        $walker = new Post_Types_Order_Walker;
		        else
			        $walker = $r['walker'];

		        $args = array($post_types, $depth, $r);
		        return call_user_func_array(array(&$walker, 'walk'), $args);
	        }
    }

?>