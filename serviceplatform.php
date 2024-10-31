<?php
// =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=
// ** ServicePlatform Plugin, Copyright 2012  ServicePlatform
// =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=

/**
	@package ServicePlatform
	@version 1.0.0

	Plugin Name: ServicePlatform
	Plugin URI: http://ServicePlatform.com/wordpress-plugin.php
	Description: This plugin allows you to easily create posts that are based on your business listing at ServicePlatform.  This plugin fully integrates your services and business into an existing theme.  Widget / Menu ready themes work best.
	Author: ServicePlatform
	Version: 1.0.0
	Author URI: http://ServicePlatform.com

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation; version 3 of the License.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

// =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=
// ** Preload & Setup:
// =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=
	/** Install Folder */
	define('SP_FOLDER', '/' . dirname( plugin_basename(__FILE__)));
	
	/** Path for Includes */
	define('SP_PATH', WP_PLUGIN_DIR . SP_FOLDER);

	/** Path for front-end links */
	define('SP_URL', WP_PLUGIN_URL . SP_FOLDER);

	/** Directory path for includes of template files  */
	define('SP_TEMPLATES_PATH', WP_PLUGIN_DIR . SP_FOLDER. '/templates');
	define('SP_TEMPLATES_URL', WP_PLUGIN_URL . SP_FOLDER . '/templates');
	
	// Post type
	define('SP_POSTYPE', 'serviceplatform');
	define('SP_SLUG', 'serviceplatform');
	
	// Make sure we don't expose any info if called directly
	if ( !function_exists( 'add_action' ) ) {
		echo "ServicePlatform Plugin - http://ServicePlatform.com ...";
		exit;
	}
	
	// Include widgets module
	include SP_PATH . '/widgets.php';

// =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=
// ** Setup the style
// =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=
	add_action( 'init', 'sp_add_styles' );
	
	function sp_add_styles() {
		// ------------------------------------- 
		//  Register the stylesheet
		// ------------------------------------- 
		wp_register_style('ServicePlatform-Template-Default', SP_TEMPLATES_URL. '/default/styles/style.css');
		wp_enqueue_style( 'ServicePlatform-Template-Default');
	}
	
// =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=
// ** Activation hook for flushing 
// =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=
	
	register_activation_hook( __FILE__, 'serviceplatform_activate' );
	
	function serviceplatform_activate() { 
		// ------------------------------------- 
		//  Perform stuff on activation
		// ------------------------------------- 
		
		// plugin uses WP Rewrite, need to flush rules so they get added properly
		flush_rewrite_rules();
	}
	
// =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=
// ** Add Shortcode [serviceplatform parameter="value"]
// =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=
	
	add_shortcode('serviceplatform', 'serviceplatform_shortcode');
	
	function serviceplatform_shortcode($atts, $content = null) { 
		// ------------------------------------- 
		//  Create / Handle the serviceplatform shortcode
		// ------------------------------------- 
		
		// Get the ServicePlatform Options
		$options = get_option( 'serviceplatform_options' );
		$display_columns = ( $options['display_columns'] == 'Two' ) ? '2' : '1' ;
		$display_type = ( isset($options['display_type']) ) ? $options['display_type'] : 'servicelist' ;
		$split_on = ( isset($options['split_on']) ) ? $options['split_on'] : 'item' ;
		$background_color = ( isset($options['background_color']) && !empty($options['background_color']) ) ? $options['background_color'] : '#fff' ;
		
		$atts = shortcode_atts(array(
			'venue' => '',
			'services_filter' => '',
			'group_filter' => '',
			'background_color' => $background_color,
			'display_columns' => $display_columns,
			'split_on' => $split_on,
			'display_type' => $display_type
		), $atts);
		
		$display = '';
		if ( !empty($atts['venue']) ) {
			// Get the venue 
			$spl = _get_venue_details( $atts['venue'] ); 
			
			if ( strcasecmp($atts['display_type'], 'venue information / servicelist') == 0 || 
	 strcasecmp($atts['display_type'], 'servicelist') == 0 ) {
				$display .= build_venue_from_details($spl, $atts['display_columns'], $atts['services_filter'], $atts['group_filter'], $atts['background_color'], $atts['split_on']);
			}
			
		} else {
			$display = __('Venue ID must be provided');
		}
		
		return $display;
	}


// =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=
// ** Add Shortcode [serviceplatform_qrcode parameter="value"]
// =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=
	
	add_shortcode('serviceplatform_qrcode', 'serviceplatform_qrcode_shortcode');
	
	function serviceplatform_qrcode_shortcode($atts, $content = null) { 
		// ------------------------------------- 
		//  Create / Handle the serviceplatform_qrcode shortcode
		// ------------------------------------- 
		
		$atts = shortcode_atts(array(
			'venue' => '',
			'size' => '128'
		), $atts);
		
		$display = '';
		if ( !empty($atts['venue']) ) {
			$display = serviceplatform_qrcode($atts['venue'], $atts['size']);
		} else {
			$display = __('Venue ID must be provided');
		}
		
		return $display;
	}

// =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=
// ** Determines if ServicePlatform posts are shown on the homepage 
// =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=
	
	//if( is_home() ){
	//	$paged = (get_query_var('paged')) ? get_query_var('paged') : 1;
	//	query_posts( array('post_type'=>array( 'post', 'linkpost'),'paged'=>$paged ) );
	//}

	
	add_filter( 'pre_get_posts', 'my_get_posts' );
	 
	function my_get_posts( $query ) {
		$options = get_option('serviceplatform_options');
		if ( isset($options['show_posts_homepage']) && $options['show_posts_homepage'] ) {
			if ( is_home() )
				$query->set( 'post_type', array( 'post', SP_POSTYPE ) );
		}
		return $query;
	}

// =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=
// ** Update RSS Feed to include custom post type: 
// =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=

	add_filter('request', 'sp_myfeed_request');

	function sp_myfeed_request($qv) { 
		if (isset($qv['feed'])) {
			$qv['post_type'] = get_post_types();
		}
		return $qv; 
	} 

// =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=
// ** Rewrite rules:
// =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=

	add_filter( 'generate_rewrite_rules', 'add_rewrite_rules' );

	function add_rewrite_rules( $wp_rewrite ) {
		$new_rules = array();
		$new_rules[SP_SLUG . '/page/?([0-9]{1,})/?$'] = 'index.php?post_type=' . SP_POSTYPE . '&paged=' . $wp_rewrite->preg_index(1);
		$new_rules[SP_SLUG . '/(feed|rdf|rss|rss2|atom)/?$'] = 'index.php?post_type=' . SP_POSTYPE . '&feed=' . $wp_rewrite->preg_index(1);
		$new_rules[SP_SLUG . '/?$'] = 'index.php?post_type=' . SP_POSTYPE;

		$wp_rewrite->rules = array_merge($new_rules, $wp_rewrite->rules);
		return $wp_rewrite;
	}

// =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=
// ** Custom Post Template:
// =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=

	add_action("template_redirect", 'serviceplatform_template_redirect');

	function serviceplatform_template_redirect() { 

		global $wp;
		global $wp_query;
		
		if (isset($wp->query_vars["post_type"]) && $wp->query_vars["post_type"] == SP_POSTYPE) { 
			// Default
			if ( is_robots() || is_feed() || is_trackback() ) { 
				return;
			}

			if ( isset($wp->query_vars["name"]) && $wp->query_vars["name"] ) {
				include(SP_TEMPLATES_PATH . '/default/single-serviceplatform.php');
				die();
			} else {
				include(SP_TEMPLATES_PATH . '/default/serviceplatform.php');
				die();
			}
			$wp_query->is_404 = true;
			
		}
	}

// =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=
// ** Custom Post Type:
// =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=
	add_action( 'init', 'sp_create_post_type' );
	add_action( 'admin_head', 'sp_custom_posttype_icon' );
	
	function sp_create_post_type() {
		// ------------------------------------- 
		//  Register a custom post type
		//   custom post type = serviceplatform
		// ------------------------------------- 
		
		// Define the labels
		$labels = array(
			'name' => _x('ServicePlatform', 'post type general name'),
			'singular_name' => _x('ServicePlatform', 'post type singular name'),
			'add_new' => _x('Add New Business', 'business services'),
			'add_new_item' => __('Add New Business'),
			'edit_item' => __('Edit Business'),
			'new_item' => __('New Business'),
			'view_item' => __('View Business'),
			'search_items' => __('Search Businesses'),
			'not_found' =>  __('No businesses found'),
			'not_found_in_trash' => __('No businesses found in Trash'),
			'parent_item_colon' => ''
		);
		
		// Register the serviceplatform post type
		register_post_type(SP_POSTYPE, array(
			'labels' => $labels,
			'public' => true,
			'show_ui' => true,
			'show_in_nav_menus' => true, 
			'capability_type' => 'post',
			'hierarchical' => false,     // If true acts like a page
			'rewrite' => array('slug' => SP_SLUG),
			'query_var' => true,
			'menu_position' => '5',
			'menu_icon' => SP_URL .'/images/serviceplatform-16-color.png', 
			'register_meta_box_cb' => 'sp_add_custom_box',
			'supports' => array(
				'title',
			//	'excerpt',
				'editor')
		));

		// ------------------------------------- 
		//  Register custom for taxonomies ServicePlatform
		// ------------------------------------- 
		// Business Types
	    register_taxonomy( 'business_type', SP_POSTYPE,
			array(
				 'hierarchical' => true,   // false acts like tags
				 'public' => true,
				 'labels' => array(
						'name' => __( 'Business Types' ),
						'singular_name' => __( 'Business Type' ),
						'parent_item' => __( 'Parent Business Type' ),
						'add_new_item' => __( 'Add New Business Type' ),
						'new_item_name' => __( 'New Business Type' ),
						'edit_item' => __( 'Edit Business Type' ),
						'update_item' => __( 'Update Business Type' )
					),
				 'query_var' => 'business_type',
				 'rewrite' => array('slug' => 'business_type' )
			)
		);
		
	}

	function sp_custom_posttype_icon() {
		
		global $post_type;
		$qry_postype = ( isset($_GET['post_type']) ) ? $_GET['post_type'] : '' ; 
		
		if (($qry_postype == SP_POSTYPE) || ($post_type == SP_POSTYPE)) {
		    $icon_url = SP_URL . '/images/serviceplatform-32.png';
		    ?>
		    <style type="text/css" media="all">
		    /*<![CDATA[*/
		        .icon32 {
		            background: url(<?php echo $icon_url; ?>) no-repeat 1px !important;
		        }
		    /*]]>*/
		    </style>
		    <?php
		}
	}

// =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=
// ** Custom Filter in Edit Post by Taxonomy:
// =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=

	add_action('restrict_manage_posts','restrict_listings_by_business_type');
	
	function restrict_listings_by_business_type() {
	    global $typenow;
	    global $wp_query;
	    if ($typenow == SP_POSTYPE) {
	        $taxonomy = 'business_type';
	        $business_type_taxonomy = get_taxonomy($taxonomy);
	        $selected = (isset($wp_query->query['term'])) ? $wp_query->query['term'] : '' ;
	        wp_dropdown_categories(array(
	            'show_option_all' =>  __("Show All {$business_type_taxonomy->label}"),
	            'taxonomy'        =>  $taxonomy,
	            'name'            =>  'business_type',
	            'orderby'         =>  'name',
	            'selected'        =>  $selected,
	            'hierarchical'    =>  true,
	            'depth'           =>  3,
	            'show_count'      =>  true, // Show # listings in parens
	            'hide_empty'      =>  true, // Don't show businesses w/o listings
	        ));
	    }
	}

	add_filter('parse_query','convert_business_type_id_to_taxonomy_term_in_query');
	
	function convert_business_type_id_to_taxonomy_term_in_query($query) {
	    global $pagenow;
	    $qv = &$query->query_vars;
	    if ($pagenow=='edit.php' &&
	            isset($qv['taxonomy']) && $qv['taxonomy']=='business_type' &&
	            isset($qv['term']) && is_numeric($qv['term'])) {
	        $term = get_term_by('id',$qv['term'],'business_type');
	        $qv['term'] = $term->slug;
	    }
	}

// =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=
// ** ServicePlatform Settings:
// =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=

	register_activation_hook(__FILE__, 'sp_add_defaults_fn');
	add_action('admin_init', 'serviceplatform_options_init_fn' );
	add_action('admin_menu', 'serviceplatform_options_add_page_fn');

	// Define default option settings
	function sp_add_defaults_fn() {
		$tmp = get_option('serviceplatform_options');
	    if( !is_array($tmp) ) {
			$arr = array(
					"display_type"=>"ServiceList", 
					"hide_sidebar" => "on", 
					"display_columns" => "One", 
					"split_on" => "item",
					"hide_prices" => "off",
					"use_short_tag" => "off"
				);
			update_option('serviceplatform_options', $arr);
		}
	}

	// Register our settings. Add the settings section, and settings fields
	function serviceplatform_options_init_fn(){
		register_setting('serviceplatform_options', 'serviceplatform_options', 'serviceplatform_options_validate' );
		
		add_settings_section('lookfeel_section', __('Look &amp Feel'), 'section_lookfeel_fn', __FILE__);

		add_settings_field('drop_down1', __('Display Type'), 'setting_displaytype_fn', __FILE__, 'lookfeel_section');
		add_settings_field('radio_buttons', __('How many columns?'), 'setting_displaycolumn_fn', __FILE__, 'lookfeel_section');
		add_settings_field('radio_buttons_split', __('Split on (2 column display)'), 'setting_spliton_fn', __FILE__, 'lookfeel_section');
		add_settings_field('plugin_chk_shorttag', __('Use Short Tag'), 'setting_shorttag_fn', __FILE__, 'lookfeel_section');
		add_settings_field('drop_down2', __('Theme'), 'setting_theme_fn', __FILE__, 'lookfeel_section');
		
		add_settings_section('sl_section', __('Your ServiceList'), 'section_data_fn', __FILE__);
		add_settings_field('plugin_chk_prices', __('Hide Prices'), 'setting_hideprices_fn', __FILE__, 'sl_section');
		
		add_settings_section('sp_section', __('Business Listing'), 'section_sp_fn', __FILE__);
		add_settings_field('plugin_sp_title', __('Title'), 'setting_sp_title_fn', __FILE__, 'sp_section');
		add_settings_field('plugin_sp_description', __('Description'), 'setting_sp_description_fn', __FILE__, 'sp_section');
		
		add_settings_section('wordpress_section', __('Wordpress Theme'), 'section_wordpress_fn', __FILE__);
		add_settings_field('plugin_chk1', __('Show Posts on Homepage'), 'setting_showposts_fn', __FILE__, 'wordpress_section');
		add_settings_field('plugin_chk2', __('Hide Sidebar'), 'setting_hidesidebar_fn', __FILE__, 'wordpress_section');
		add_settings_field('plugin_text_string', __('Width Override'), 'setting_widthoverride_fn', __FILE__, 'wordpress_section');
		add_settings_field('plugin_backcolor', __('Background Color'), 'setting_backgroundcolor_fn', __FILE__, 'wordpress_section');
		
	}

	// Add sub page to the Settings Menu
	function serviceplatform_options_add_page_fn() {
		add_options_page('ServicePlatform Options', 'ServicePlatform', 'manage_options', __FILE__, 'sp_options_page_fn');
	}

	// *************************
	// Callback functions
	// *************************

	// Section HTML, displayed before the first option
	function  section_lookfeel_fn() {
		echo '<p>'.__('Control what is displayed and how it is displayed').'</p>';
	}
	function  section_wordpress_fn() {
		echo '<p>'.__('Changes how the business listing interacts with the current theme').'</p>';
	}
	function  section_sp_fn() {
		echo '<p>'.__('Controls the main ServicePlatform page (used to display a list of all businesses in the system)').'</p>';
	}
	function  section_data_fn() {
		echo '<p>'.__('What information do you want to show/hide from your business listing').'</p>';
	}

	function setting_shorttag_fn() {
		$checked = '';
		$options = get_option('serviceplatform_options');
		if( isset($options['use_short_tag']) ) { $checked = ' checked="checked" '; }
		echo "<input ".$checked." id='plugin_chk_shorttag' name='serviceplatform_options[use_short_tag]' type='checkbox' /> ".__('(shortens the display of item tags like special and new)');
	}

	function setting_hideprices_fn() {
		$checked = '';
		$options = get_option('serviceplatform_options');
		if( isset($options['hide_prices']) ) { $checked = ' checked="checked" '; }
		echo "<input ".$checked." id='plugin_chk_prices' name='serviceplatform_options[hide_prices]' type='checkbox' />";
	}
	
	function  setting_displaytype_fn() {
		$options = get_option('serviceplatform_options');
		$items = array("ServiceList", "Venue Information", "Venue Information / ServiceList");
		echo "<select id='drop_down1' name='serviceplatform_options[display_type]'>";
		foreach($items as $item) {
			$selected = ($options['display_type']==$item) ? 'selected="selected"' : '';
			echo "<option value='$item' $selected>$item</option>";
		}
		echo "</select>";
	}

	function  setting_theme_fn() {
		$options = get_option('serviceplatform_options');
		$options['theme'] = (isset($options['theme'])) ? $options['theme'] : '(default)';
		$items = array("(default)");
		echo "<select id='drop_down2' name='serviceplatform_options[theme]'>";
		foreach($items as $item) {
			$selected = ($options['theme']==$item) ? 'selected="selected"' : '';
			echo "<option value='$item' $selected>$item</option>";
		}
		echo "</select>";
	}
	
	function setting_displaycolumn_fn() {
		$options = get_option('serviceplatform_options');
		$items = array("One", "Two");
		foreach($items as $item) {
			$checked = ($options['display_columns']==$item) ? ' checked="checked" ' : '';
			echo "<label><input ".$checked." value='$item' name='serviceplatform_options[display_columns]' type='radio' /> $item</label><br />";
		}
	}
	
	function setting_spliton_fn() {
		$options = get_option('serviceplatform_options');
		$items = array("item", "group");
		foreach($items as $item) {
			$checked = (isset($options['split_on']) && $options['split_on']==$item) ? ' checked="checked" ' : '';
			echo "<label><input ".$checked." value='$item' name='serviceplatform_options[split_on]' type='radio' /> $item</label><br />";
		}
	}
	
	function setting_widthoverride_fn() {
		$options = get_option('serviceplatform_options');
		$options['width_override'] = (isset($options['width_override'])) ? $options['width_override'] : '';
		echo "<input id='plugin_text_string' name='serviceplatform_options[width_override]' size='10' type='text' value='{$options['width_override']}' /> ".__('(Used when hiding sidebar - add units: ex. 900px or 95%)');
	}

	function setting_backgroundcolor_fn() {
		$options = get_option('serviceplatform_options');
		$options['background_color'] = (isset($options['background_color'])) ? $options['background_color'] : '';
		echo "<input id='plugin_text_string' name='serviceplatform_options[background_color]' size='10' type='text' value='{$options['background_color']}' /> ".__('(Background color - HTML color format: #ffffff)');
	}
	
	function setting_sp_title_fn() {
		$options = get_option('serviceplatform_options');
		$options['sp_title'] = (isset($options['sp_title'])) ? $options['sp_title'] : '';
		echo "<input id='plugin_text_string' name='serviceplatform_options[sp_title]' size='20' type='text' value='{$options['sp_title']}' /> ".__('(defaults to ServicePlatform)');
	}

	function setting_sp_description_fn() {
		$options = get_option('serviceplatform_options');
		$options['sp_description'] = (isset($options['sp_description'])) ? $options['sp_description'] : '';
		echo "<textarea id='plugin_textarea_string' name='serviceplatform_options[sp_description]' rows='7' cols='50' type='textarea'>{$options['sp_description']}</textarea>";
	}

	function setting_hidesidebar_fn() {
		$checked = '';
		$options = get_option('serviceplatform_options');
		if( isset($options['hide_sidebar']) ) { $checked = ' checked="checked" '; }
		echo "<input ".$checked." id='plugin_chk2' name='serviceplatform_options[hide_sidebar]' type='checkbox' />";
	}

	function setting_showposts_fn() {
		$checked = '';
		$options = get_option('serviceplatform_options');
		if( isset($options['show_posts_homepage']) ) { $checked = ' checked="checked" '; }
		echo "<input ".$checked." id='plugin_chk1' name='serviceplatform_options[show_posts_homepage]' type='checkbox' />";
	}

	// Display the admin options page
	function sp_options_page_fn() {
	?>
		<div class="wrap">
			<div class="icon32" id="icon-options-general"><br></div>
			<h2><?php _e('ServicePlatform Options Page'); ?></h2>
			<?php _e('Control the overall look and feel for the business listings displayed.'); ?>
			<form action="options.php" method="post">
			<?php settings_fields('serviceplatform_options'); ?>
			<?php do_settings_sections(__FILE__); ?>
			<p class="submit">
				<input name="Submit" type="submit" class="button-primary" value="<?php esc_attr_e('Save Changes'); ?>" />
			</p>
			</form>
		</div>
	<?php
	}

	// Validate user data for some/all of your input fields
	function serviceplatform_options_validate($input) {
		// Check our textbox option field contains no HTML tags - if so strip them out
		//$input['text_string'] =  wp_filter_nohtml_kses($input['text_string']);	
		return $input; // return validated input
	}

// =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=
// ** Venue Count for Dashboard:
// =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=

	add_action('right_now_content_table_end', 'add_venue_counts');

	function add_venue_counts() {
		// ------------------------------------- 
		//  Add Venue Counts to Dashboard
		// ------------------------------------- 
		
        if (!post_type_exists(SP_POSTYPE)) {
             return;
        }

		// Get count
        $num_posts = wp_count_posts( SP_POSTYPE );
        $pending = $num_posts->pending;
        $drafts = $num_posts->draft;
        $publish = $num_posts->publish;

        // Handle Published
        $num = number_format_i18n( $publish );
        $text = _n( 'Business', 'Businesses', intval($publish) );
        if ( current_user_can( 'edit_posts' ) ) {
            $num = '<a href="edit.php?post_type=' . SP_POSTYPE . '">' . $num . '</a>';
            $text = '<a href="edit.php?post_type=' . SP_POSTYPE . '">' . $text . '</a>';
        }
        echo '<td class="first b b_pages">' . $num . '</td>';
        echo '<td class="t posts">' . $text . '</td>';

        echo '</tr>';
		
		// Handle Pending
        if ($pending > 0) {
            $num = number_format_i18n( $pending );
            $text = _n( 'Business Pending', 'Businesses Pending', intval($pending) );
            if ( current_user_can( 'edit_posts' ) ) {
                $num = '<a href="edit.php?post_status=pending&post_type=' . SP_POSTYPE . '">' . $num . '</a>';
                $text = '<a href="edit.php?post_status=pending&post_type=' . SP_POSTYPE . '">' . $text . '</a>';
            }
            echo '<td class="first b b-serviceplatform">' . $num . '</td>';
            echo '<td class="t serviceplatform">' . $text . '</td>';

            echo '</tr>';
        }
        
        // Handle Drafts
        if ($drafts > 0) {
            $num = number_format_i18n( $drafts );
            $text = _n( 'Business Draft', 'Business Drafts', intval($drafts) );
            if ( current_user_can( 'edit_posts' ) ) {
                $num = '<a href="edit.php?post_status=draft&post_type=' . SP_POSTYPE . '">'.$num.'</a>';
                $text = '<a href="edit.php?post_status=draft&post_type=' . SP_POSTYPE . '">'.$text.'</a>';
            }
            echo '<td class="first b b-serviceplatform">' . $num . '</td>';
            echo '<td class="t serviceplatform">' . $text . '</td>';

            echo '</tr>';
        }
	}
	
// =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=
// ** Custom Columns when viewing all venues:
// =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=

	add_filter('manage_edit-serviceplatform_columns', 'add_new_serviceplatform_columns');
	add_action('manage_posts_custom_column', 'manage_serviceplatform_columns', 10, 2);
	
	function manage_serviceplatform_columns($column_name, $id) {
		// ------------------------------------- 
		//  Get the data for the custum columns
		// ------------------------------------- 
		
		global $wpdb;
		
		// Get custom data
		$custom = get_post_custom($id);
		$venue = (isset($custom["_venue"][0])) ? $custom["_venue"][0] : '' ;
		$business_name = (isset($custom["_business_name"][0])) ? $custom["_business_name"][0] : '' ;
		$location = (isset($custom["_business_location"][0])) ? $custom["_business_location"][0] : '' ;
		
		// See which column we are getting data for
		switch ($column_name) {
			case 'id':
				echo $id;
				break;
			case 'venue':
				if ( !empty($venue) ) {
					echo '<a href="http://serviceplatform.com/venue/'.$venue.'" target="_blank">'.$venue.'</a>';
				}
				break;
			case 'business_location':
				echo $business_name.'<br />'.$location;
			    break;
			case 'business_type':
				$tags = get_the_terms($id, 'business_type'); //lang is the first custom taxonomy slug
				if ( !empty( $tags ) ) {
					$out = array();
					foreach ( $tags as $c ) {
						$out[] = "<a href='edit.php?post_type=" . SP_POSTYPE . "&business_type=$c->slug'> " . esc_html(sanitize_term_field('name', $c->name, $c->term_id, 'business_type', 'display')) . "</a>";
					}
					echo join( ', ', $out );
				} else {
					_e('No Business Types.');
				}
			    break;
			default:
				break;
		} // end switch
	}
		
	function add_new_serviceplatform_columns($serviceplatform_columns) {
		// ------------------------------------- 
		//  Define the columns for the ServicePlatform post type
		// ------------------------------------- 
		
		$new_columns['cb'] = '<input type="checkbox" />';
		// $new_columns['id'] = __('ID');
		$new_columns['title'] = _x('Title', 'column name');
		$new_columns['venue'] = __('Venue ID');
		$new_columns['business_location'] = __('Business / Location');
		$new_columns['business_type'] = __('Business Types');

		return $new_columns;
	}

// =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=
// ** Custom Post Fields:
// =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=

	add_action( 'save_post', 'sp_save_postdata', 1, 2 );

/*
	Each box has a name and a set of fields. Currently,
	only text and textarea fields are suppoted. 'text'
	fields are the default.
	To add a box named: "Name Box" with a field named
	"_name", add this:
	'Name Box' => array (
		array( '_name', 'Name:', 'text', 'bottom_label' ),
	),
	You can leave the 'text' field off. It is the default.
	'Name Box' => array (
		array( '_name', 'Name:' ),
	),
	
	// Displaying meta in a template
	<?php echo get_post_meta($post->ID, "_location", true); ?>
*/

	$sp_boxes = array (
		'Venue ID (required)' => array (
			array( '_venue', 'Your Venue ID on ServicePlatform:', 'text', '(sample venue id: sample)' )
		),
		'ServiceList Settings' => array (
			array( '_services_filter', 'Services Filter - ServiceList Name to display:', 'text', '(Use the <strong>ServiceList Name</strong> field to display that service list only)' ),
			array( '_group_filter', 'Group Filter - Group Name to display:', 'text', '(Use the <strong>Group Name</strong> field to display that group only)' )
		),
		'Business Information' => array (
			array( '_business_name', 'Business Name:', 'text', '' ),
			array( '_business_location', 'Location (address):', 'text', '' ),
			array( '_brief_description', 'Brief Description:', 'textarea', '' ),
		)
	);

	// Adds a custom section to the "advanced" Post and Page edit screens
	function sp_add_custom_box() {
		global $sp_boxes;
		if ( function_exists( 'add_meta_box' ) ) {
			foreach ( array_keys( $sp_boxes ) as $box_name ) {
				add_meta_box( $box_name, __( $box_name, 'sp' ), 'sp_post_custom_box', SP_POSTYPE, 'normal', 'high' );
			}
		}
	}
	function sp_post_custom_box ( $obj, $box ) {
		global $sp_boxes;
		static $sp_nonce_flag = false;
		// Run once
		if ( ! $sp_nonce_flag ) {
			echo_sp_nonce();
			$sp_nonce_flag = true;
		}
		// Genrate box contents
		foreach ( $sp_boxes[$box['id']] as $sp_box ) {
			echo field_html( $sp_box );
		}
	}
	
	function field_html ( $args ) {
		switch ( $args[2] ) {
			case 'textarea':
				return text_area( $args );
			case 'checkbox':
				// To Do
			case 'radio':
				// To Do
			case 'text':
			default:
				return text_field( $args );
		}
	}
	
	function text_field ( $args ) {
		global $post;
		// adjust data
		$label = $args[3];
		$args[2] = get_post_meta($post->ID, $args[0], true);
		$args[1] = __($args[1], 'sp' );
		$label_format =
			  '<br /><label for="%1$s">%2$s</label><br />'
			. '<input style="width: 95%%;" type="text" name="%1$s" value="%3$s" />';
		
		// labels
		if ( !empty($label) ) {
			$label_format .= '<div style="padding-top:4px;text-align:center">' . $label . '</div>';
		}
		
		$label_format .= '<br />';
		return vsprintf( $label_format, $args );
	}
	
	function text_area ( $args ) {
		global $post;
		// adjust data
		$args[2] = get_post_meta($post->ID, $args[0], true);
		$args[1] = __($args[1], 'sp' );
		$label_format =
			  '<br /><label for="%1$s">%2$s</label><br />'
			. '<textarea style="width: 95%%;" name="%1$s">%3$s</textarea><br /><br />';
		return vsprintf( $label_format, $args );
	}
	
	/* When the post is saved, saves our custom data */
	function sp_save_postdata($post_id, $post) {
		
		global $sp_boxes;
		
		// verify this came from the our screen and with proper authorization,
		// because save_post can be triggered at other times
		if (!isset($_POST['sp_nonce_name'])) $_POST['sp_nonce_name'] = '';
		if ( ! wp_verify_nonce( $_POST['sp_nonce_name'], plugin_basename(__FILE__) ) ) {
			return $post->ID;
		}
		
		// Is the user allowed to edit the post or page?
		if ( 'page' == $_POST['post_type'] ) {
			if ( ! current_user_can( 'edit_page', $post->ID ))
				return $post->ID;
		} else {
			if ( ! current_user_can( 'edit_post', $post->ID ))
				return $post->ID;
		}
		
		// OK, we're authenticated: we need to find and save the data
		// We'll put it into an array to make it easier to loop though.
		// The data is already in $sp_boxes, but we need to flatten it out.
		foreach ( $sp_boxes as $sp_box ) {
			foreach ( $sp_box as $sp_fields ) {
				$my_data[$sp_fields[0]] =  $_POST[$sp_fields[0]];
			}
		}
		
		// Add values of $my_data as custom fields
		// Let's cycle through the $my_data array!
		foreach ($my_data as $key => $value) {
			if ( 'revision' == $post->post_type  ) {
				// don't store custom data twice
				return;
			}
			// if $value is an array, make it a CSV (unlikely)
			$value = implode(',', (array)$value);
			if ( get_post_meta($post->ID, $key, FALSE) ) {
				// Custom field has a value.
				update_post_meta($post->ID, $key, $value);
			} else {
				// Custom field does not have a value.
				add_post_meta($post->ID, $key, $value);
			}
			if (!$value) {
				// delete blanks
				delete_post_meta($post->ID, $key);
			}
		}
	}
	
	function echo_sp_nonce () {
		// Use nonce for verification ... ONLY USE ONCE!
		echo sprintf(
			'<input type="hidden" name="%1$s" id="%1$s" value="%2$s" />',
			'sp_nonce_name',
			wp_create_nonce( plugin_basename(__FILE__) )
		);
	}
	
	// A simple function to get data stored in a custom field
	if ( !function_exists('sp_get_custom_field') ) {
		function sp_get_custom_field($field) {
		   global $post;
		   $custom_field = get_post_meta($post->ID, $field, true);
		   echo $custom_field;
		}
	}

// =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=
// ** Common Functions:
// =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=
	function _get_venue_details ( $venue ) {
		// ------------------------------------- 
		//  Return the venue details from a Venue ID
		// ------------------------------------- 

		$spl = false;
		if ( !empty($venue) ) {
			include_once SP_PATH.'/toolbox/class-sp-reader.php'; 
			$spr = new cSpReader; 
			$spl = $spr->read_file('http://serviceplatform.com/spl/'.$venue.'?ref=wp'); 
			unset($spr);
		}
		
		return $spl;
	}

	function build_venue_from_details ($spl, $columns = '1', $services_filter = '', $group_filter = '', 
					$background_color = false, $split_on = false) {
		// ------------------------------------- 
		//  Create a servicelist display from a business listing 
		//   shortcode can override some of the Global settings
		// ------------------------------------- 
		
		$retval = '';
		$one_column = ($columns == '1') ? true : false ;
		
		if ( $background_color ) {
			$retval .= '<style type="text/css">';
			$retval .= '#sp_list, #sp_list dt, #sp_list dd.price { background-color:'.$background_color.' }';
			$retval .= '</style>';
		}
		
		// Get the Global options
		$options = get_option( 'serviceplatform_options' );
		$show_prices = ( isset($options['hide_prices']) && $options['hide_prices'] ) ? false : true ;
		$use_short_tag = ( isset($options['use_short_tag']) && $options['use_short_tag'] ) ? true : false ;
		// Only get Split On Global if shortcode isn't overriding
		if (!$split_on) {
			$split_on = ( isset($options['split_on']) ) ? $options['split_on'] : 'group' ;
		}
		
		if ( !empty($spl) ) {
			include_once SP_PATH.'/toolbox/class-sp-render.php'; 
			$render = new cSpRender; 
			$render->disable_entities = true;
			$render->columns = $columns;
			$render->split_on = $split_on;
			$render->show_prices = $show_prices;
			$render->use_short_tag = $use_short_tag;
			$retval .= $render->get_menu_from_details($spl, $services_filter, $group_filter);
			unset($render);
		}
		
		return $retval;
	}
	
	function serviceplatform_qrcode ( $venue, $size = 128 ) { 
		// -------------------------------------
		// Create a QR Code image for a Venue
		// -------------------------------------
		
		$url = urlencode('http://serviceplatform.com/m/venue/'.$venue);
		return '<img src="http://chart.apis.google.com/chart?'.
				'chs='.$size.'x'.$size.
				'&cht=qr'.
				'&chld=L|0'.
				'&chl='.$url.'" '.
				'alt="ServicePlatform QR code" width="'.$size.'" height="'.$size.'"/>';
	}
	
	if ( ! function_exists( 'serviceplatform_posted_on' ) ) :
	function serviceplatform_posted_on() {
		printf( __( '<span class="%1$s">Posted on</span> %2$s <span class="meta-sep">by</span> %3$s', 'serviceplatform' ),
			'meta-prep meta-prep-author',
			sprintf( '<a href="%1$s" title="%2$s" rel="bookmark"><span class="entry-date">%3$s</span></a>',
				get_permalink(),
				esc_attr( get_the_time() ),
				get_the_date()
			),
			sprintf( '<span class="author vcard"><a class="url fn n" href="%1$s" title="%2$s">%3$s</a></span>',
				get_author_posts_url( get_the_author_meta( 'ID' ) ),
				sprintf( esc_attr__( 'View all posts by %s', 'serviceplatform' ), get_the_author() ),
				get_the_author()
			)
		);
	}
	endif;
?>