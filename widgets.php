<?php
/**
 * @package ServicePlatform
 * @version 1.0.0
 */
/*

Copyright 2012  ServicePlatform

*/

// =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=
// ** Widgets:
// =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=
	/* Add our function to the widgets_init hook. */
	add_action('widgets_init', create_function('', 'return register_widget("serviceplatform_venue_location");'));
	add_action('widgets_init', create_function('', 'return register_widget("serviceplatform_specials");'));
	add_action('widgets_init', create_function('', 'return register_widget("serviceplatform_servicelist");'));
	add_action('widgets_init', create_function('', 'return register_widget("serviceplatform_qrcode");'));
	
	class serviceplatform_servicelist extends WP_Widget {  
		function serviceplatform_servicelist() {  
			/* Widget settings. */
			$widget_ops = array( 'classname' => 'sp-servicelist', 'description' => __('Display a list of ServiceLists and their Service Groups. Supports local linking.') );

			/* Widget control settings. */
			$control_ops = array( 'width' => 400, 'height' => 350, 'id_base' => 'sp-servicelist' );

		    parent::WP_Widget('sp-servicelist', 'ServicePlatform: Service Lists', $widget_ops, $control_ops );
		}
		
		function form($instance) {  
		     // outputs the options form on admin
		     
			/* Set up some default widget settings. */ 
			$defaults = array( 
							'title' => 'Our Services', 
							'venue' => '', 
							'servicelist_url' => '', 
							'servicelist_url_title' => 'See Our Services', 
							'display_servicegroups' => true,
							'services_filter' => '', 
						);
			$instance = wp_parse_args( (array) $instance, $defaults ); ?>
			<p>
				<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e('Title'); ?></label>
				<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" value="<?php echo $instance['title']; ?>" />
			</p>
			<p>
				<label for="<?php echo $this->get_field_id( 'venue' ); ?>"><?php _e('Venue ID on ServicePlatform'); ?></label>
				<input class="widefat" id="<?php echo $this->get_field_id( 'venue' ); ?>" name="<?php echo $this->get_field_name( 'venue' ); ?>" value="<?php echo $instance['venue']; ?>" />
			</p>
			<p>
				<label for="<?php echo $this->get_field_id( 'services_filter' ); ?>"><?php _e('ServiceList Filter'); ?></label>
				<input class="widefat" id="<?php echo $this->get_field_id( 'services_filter' ); ?>" name="<?php echo $this->get_field_name( 'services_filter' ); ?>" value="<?php echo $instance['services_filter']; ?>" />
			</p>
			<p>
				<input class="checkbox" type="checkbox" <?php checked($instance['display_servicegroups'], true) ?> id="<?php echo $this->get_field_id('display_servicegroups'); ?>" name="<?php echo $this->get_field_name('display_servicegroups'); ?>" />
				<label for="<?php echo $this->get_field_id('display_servicegroups'); ?>"><?php _e('Display Service Groups'); ?></label><br />
			</p>
			<p>
				<label for="<?php echo $this->get_field_id( 'servicelist_url' ); ?>"><?php _e('Location of the services on this site (URL)'); ?></label>
				<input class="widefat" id="<?php echo $this->get_field_id( 'servicelist_url' ); ?>" name="<?php echo $this->get_field_name( 'servicelist_url' ); ?>" value="<?php echo $instance['servicelist_url']; ?>" />
			</p>
<p>
				<label for="<?php echo $this->get_field_id( 'servicelist_url_title' ); ?>"><?php _e('Title for the Services Link'); ?></label>
				<input class="widefat" id="<?php echo $this->get_field_id( 'servicelist_url_title' ); ?>" name="<?php echo $this->get_field_name( 'servicelist_url_title' ); ?>" value="<?php echo $instance['servicelist_url_title']; ?>" />
			</p>
		<?php
		}
		
		function update($new_instance, $old_instance) {  
		     // processes widget options to be saved  
			$instance = $old_instance;

			/* Strip tags (if needed) and update the widget settings. */
			$instance['title'] = strip_tags( $new_instance['title'] );
			$instance['venue'] = $new_instance['venue'];
			$instance['servicelist_url'] = $new_instance['servicelist_url'];
			$instance['servicelist_url_title'] = $new_instance['servicelist_url_title'];
			$instance['services_filter'] = strip_tags($new_instance['services_filter']);
			$instance['display_servicegroups'] = isset($new_instance['display_servicegroups']) ? 1 : 0 ;
			
			return $instance;
		}
		
		function widget($args, $instance) {  
			extract( $args );

			/* User-selected settings. */
			$title = apply_filters('widget_title', $instance['title'] );
			$venue = isset( $instance['venue'] ) ? $instance['venue'] : false;
			$servicelist_url = isset( $instance['servicelist_url'] ) ? $instance['servicelist_url'] : false;
			$servicelist_url_title = isset( $instance['servicelist_url_title'] ) && !empty($instance['servicelist_url_title']) ? $instance['servicelist_url_title'] : 'See Our Services';
			$services_filter = isset( $instance['services_filter'] ) ? $instance['services_filter'] : false;
			$display_servicegroups = isset( $instance['display_servicegroups'] ) ? $instance['display_servicegroups'] : false;
			
			/* Before widget (defined by themes). */
			echo $before_widget;

			/* Title of widget (before and after defined by themes). */
			if ( $title )
				echo $before_title . $title . $after_title;
			
			if ( $venue ) {
				$spl = _get_venue_details($venue);

				echo _get_services_and_groups( $spl, $services_filter, $display_servicegroups);

				unset($spl);
				
				if ( $servicelist_url ) {
					echo '<div id="om_widget_services_link"><a href="'.$servicelist_url.'">'.$servicelist_url_title.'</a></div>';
				}
			}

			/* After widget (defined by themes). */
			echo $after_widget;
		}  
	}
	
	class serviceplatform_specials extends WP_Widget {  
		function serviceplatform_specials() {  
			/* Widget settings. */
			$widget_ops = array( 'classname' => 'sp-specials', 'description' => __('Display a list of specials as defined in a business listing on ServicePlatform') );

			/* Widget control settings. */
			$control_ops = array( 'width' => 400, 'height' => 350, 'id_base' => 'sp-specials' );

		    parent::WP_Widget('sp-specials', 'ServicePlatform: Specials', $widget_ops, $control_ops );
		}
		
		function form($instance) {  
		     // outputs the options form on admin
		     
			/* Set up some default widget settings. */
			$defaults = array( 
							'title' => 'Our Specials', 
							'venue' => '', 
							'services_filter' => '', 
						);
			$instance = wp_parse_args( (array) $instance, $defaults ); ?>
			<p>
				<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e('Title'); ?></label>
				<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" value="<?php echo $instance['title']; ?>" />
			</p>
			<p>
				<label for="<?php echo $this->get_field_id( 'venue' ); ?>"><?php _e('Your Venue ID on ServicePlatform'); ?></label>
				<input class="widefat" id="<?php echo $this->get_field_id( 'venue' ); ?>" name="<?php echo $this->get_field_name( 'venue' ); ?>" value="<?php echo $instance['venue']; ?>" />
			</p>
			<p>
				<label for="<?php echo $this->get_field_id( 'services_filter' ); ?>"><?php _e('Filter - ServiceList Name to display specials from'); ?></label>
				<input class="widefat" id="<?php echo $this->get_field_id( 'services_filter' ); ?>" name="<?php echo $this->get_field_name( 'services_filter' ); ?>" value="<?php echo $instance['services_filter']; ?>" />
			</p>
		<?php
		}
		
		function update($new_instance, $old_instance) {  
		     // processes widget options to be saved  
			$instance = $old_instance;

			/* Strip tags (if needed) and update the widget settings. */
			$instance['title'] = strip_tags( $new_instance['title'] );
			$instance['venue'] = $new_instance['venue'];
			$instance['services_filter'] = $new_instance['services_filter'];
			
			return $instance;
		}
		
		function widget($args, $instance) {  
			extract( $args );

			/* User-selected settings. */
			$title = apply_filters('widget_title', $instance['title'] );
			$venue = isset( $instance['venue'] ) ? $instance['venue'] : false;
			$services_filter = isset( $instance['services_filter'] ) ? $instance['services_filter'] : false;
			
			/* Before widget (defined by themes). */
			echo $before_widget;

			/* Title of widget (before and after defined by themes). */
			if ( $title )
				echo $before_title . $title . $after_title;
			
			if ( $venue ) {
				$spl = _get_venue_details($venue);
				
				echo _get_specials( $spl, $services_filter );
				unset($spl);
				
			}

			/* After widget (defined by themes). */
			echo $after_widget;
		}  
	}
	
	class serviceplatform_venue_location extends WP_Widget {  
		function serviceplatform_venue_location() {  
			/* Widget settings. */
			$widget_ops = array( 'classname' => 'sp-venue-location', 'description' => __('Display a business\'s location as defined in a business listing on ServicePlatform') );

			/* Widget control settings. */
			$control_ops = array( 'width' => 400, 'height' => 350, 'id_base' => 'sp-venue-location' );

		    parent::WP_Widget('sp-venue-location', 'ServicePlatform: Business Location', $widget_ops, $control_ops );
		}
		
		function form($instance) {  
		     // outputs the options form on admin
		     
			/* Set up some default widget settings. */
			$defaults = array( 
							'title' => 'Our Location', 
							'venue' => '',
							'include_hours' => true,
						);
			$instance = wp_parse_args( (array) $instance, $defaults ); ?>
			<p>
				<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e('Title'); ?></label>
				<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" value="<?php echo $instance['title']; ?>" />
			</p>
			<p>
				<label for="<?php echo $this->get_field_id( 'venue' ); ?>"><?php _e('Venue ID on ServicePlatform'); ?></label>
				<input class="widefat" id="<?php echo $this->get_field_id( 'venue' ); ?>" name="<?php echo $this->get_field_name( 'venue' ); ?>" value="<?php echo $instance['venue']; ?>" />
			</p>
			<p>
				<input class="checkbox" type="checkbox" <?php checked($instance['include_hours'], true) ?> id="<?php echo $this->get_field_id('include_hours'); ?>" name="<?php echo $this->get_field_name('include_hours'); ?>" />
				<label for="<?php echo $this->get_field_id('include_hours'); ?>"><?php _e('Include hours'); ?></label><br />
			</p>
		<?php
		}
		
		function update($new_instance, $old_instance) {  
		     // processes widget options to be saved  
			$instance = $old_instance;

			/* Strip tags (if needed) and update the widget settings. */
			$instance['title'] = strip_tags( $new_instance['title'] );
			$instance['venue'] = $new_instance['venue'];
			$instance['include_hours'] = isset($new_instance['include_hours']) ? 1 : 0 ;
			
			return $instance;
		}
		
		function widget($args, $instance) {  
			extract( $args );

			/* User-selected settings. */
			$title = apply_filters('widget_title', $instance['title'] );
			$venue = isset( $instance['venue'] ) ? $instance['venue'] : false;
			$include_hours = isset( $instance['include_hours'] ) ? $instance['include_hours'] : false;
			
			/* Before widget (defined by themes). */
			echo $before_widget;

			/* Title of widget (before and after defined by themes). */
			if ( $title )
				echo $before_title . $title . $after_title;
			
			// Get the Venue details
			if ( $venue ) {
				$spl = _get_venue_details($venue);
		        echo _get_venue_location($spl, $include_hours);
				unset($spl);

			}

			/* After widget (defined by themes). */
			echo $after_widget;
		}  
	}

	class serviceplatform_qrcode extends WP_Widget {  
		function serviceplatform_qrcode() {  
			/* Widget settings. */
			$widget_ops = array( 'classname' => 'sp-qrcode', 'description' => __('Displays a QR Code to your mobile site on ServicePlatform') );

			/* Widget control settings. */
			$control_ops = array( 'id_base' => 'sp-qrcode' );

		    parent::WP_Widget('sp-qrcode', 'ServicePlatform: QR Code', $widget_ops, $control_ops );
		}
		
		function form($instance) {  
		     // outputs the options form on admin
		     
			/* Set up some default widget settings. */
			$defaults = array( 
							'title' => 'QR Code', 
							'venue' => '', 
							'qr_size' => '128',
							'include_link' => false,
						);
			$instance = wp_parse_args( (array) $instance, $defaults ); ?>
			<p>
				<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e('Title'); ?></label>
				<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" value="<?php echo $instance['title']; ?>" />
			</p>
			<p>
				<label for="<?php echo $this->get_field_id( 'venue' ); ?>"><?php _e('Venue ID on ServicePlatform'); ?></label>
				<input class="widefat" id="<?php echo $this->get_field_id( 'venue' ); ?>" name="<?php echo $this->get_field_name( 'venue' ); ?>" value="<?php echo $instance['venue']; ?>" />
				<br /><span style="font-size:.9em">(use the Venue ID of <em>sample</em> for testing)</span>
			</p>
			<p>
				<label for="<?php echo $this->get_field_id( 'qr_size' ); ?>"><?php _e('Size (max: 500): '); ?></label>
				<input id="<?php echo $this->get_field_id( 'qr_size' ); ?>" name="<?php echo $this->get_field_name( 'qr_size' ); ?>" value="<?php echo $instance['qr_size']; ?>" size="3" />
			</p>
			<p>
				<input class="checkbox" type="checkbox" <?php checked($instance['include_link'], true) ?> id="<?php echo $this->get_field_id('include_link'); ?>" name="<?php echo $this->get_field_name('include_link'); ?>" />
				<label for="<?php echo $this->get_field_id('include_link'); ?>"><?php _e('Include Mobile Site Link'); ?></label><br />
			</p>
		<?php
		}
		
		function update($new_instance, $old_instance) {  
		     // processes widget options to be saved  
			$instance = $old_instance;

			/* Strip tags (if needed) and update the widget settings. */
			$instance['title'] = strip_tags( $new_instance['title'] );
			$instance['venue'] = $new_instance['venue'];
			$instance['qr_size'] = $new_instance['qr_size'];
			$instance['include_link'] = isset($new_instance['include_link']) ? 1 : 0 ;
			
			return $instance;
		}
		
		function widget($args, $instance) {  
			extract( $args );

			/* User-selected settings. */
			$title = apply_filters('widget_title', $instance['title'] );
			$venue = isset( $instance['venue'] ) ? $instance['venue'] : false;
			$qr_size = isset( $instance['qr_size'] ) ? $instance['qr_size'] : '128';
			$include_link = isset( $instance['include_link'] ) ? $instance['include_link'] : false;
			
			/* Before widget (defined by themes). */
			echo $before_widget;

			/* Title of widget (before and after defined by themes). */
			if ( $title )
				echo $before_title . $title . $after_title;
			
			if ( $venue ) {
				// QR Code
				echo '<div style="text-align:center">'.serviceplatform_qrcode($venue, $qr_size).'</div>';
				
				if ( $include_link ) {
					echo '<p style="text-align:center"><a href="http://serviceplatform.com/m/venue/'.$venue.'">'.__('mobile site').'</a></p>';
				}
			}

			/* After widget (defined by themes). */
			echo $after_widget;
		}  
	}

// =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=
// ** Private functions:
// =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=
	function _get_venue_location ( $spl, $include_hours = true ) {
		// ------------------------------------- 
		//  Return a business's address
		// ------------------------------------- 
		$location = '';
		
		if ( !empty($spl) ) {
			$location .= '<div style="margin-top:5px;">';
			$location .= '<p><strong>Address:</strong><br />';
		    $location .= $spl['venue']['address_1'].'<br />';
		    $location .= $spl['venue']['city_town'].', ';
		    $location .= (!empty($spl['venue']['state_province'])) ? $spl['venue']['state_province'].', ' : '' ;
		    $location .= $spl['venue']['country'].' '.
		    		    $spl['venue']['postal_code'].'<br />'.
		    	        '<strong>Phone: </strong> '.$spl['venue']['phone'];
		    $location .= '<br /></p>';
		    
		    if ($include_hours) {
			    $location .= '<p><strong>Our Hours:</strong><br />';
			
				foreach ($spl['operating_days']['printable'] AS $daytime) {
					$location .= $daytime.'<br />';
				}
				
				$location .= '</p>';
			}
			
			$location .= '</div>';
		}
		
		return $location;
	}

	function _get_specials ( $spl, $services_filter = false ) {
		// ------------------------------------- 
		//  Return a preformatted HTML list of specials
		// ------------------------------------- 
		
		$options = get_option( 'serviceplatform_options' );
		$show_prices = ( isset($options['hide_prices']) && $options['hide_prices'] ) ? false : true ;
		
		$specials = '';
		if ( isset($spl['service_lists']) ) {
			$specials .= '<div style="margin-top:5px;">';
			foreach ( $spl['service_lists'] AS $menu ) {
				if ( !$services_filter || strcasecmp($services_filter, $menu['service_name']) == 0 ) {
					if ( isset($menu['menu_groups']) ) {
						foreach ($menu['menu_groups'] AS $group) {
							if ( isset($group['menu_items']) ) {
								foreach ($group['menu_items'] AS $item) {
									if ( $item['special'] ) {
										$price = ( $show_prices && !empty($item['menu_item_price']) ) ? ' - $'.number_format($item['menu_item_price'], 2) : '' ;
										$specials .= '<p><strong>'.$item['menu_item_name'].
											$price.'</strong> ';
										$specials .= '<br />'.$item['menu_item_description'];
										$specials .= '</p>';
									}
								}
							}
						}
					}
				}
			}
			$specials .= '</div>';
		}
		return $specials;
	}

	function _get_services_and_groups ( $spl, $services_filter = false, $include_groups = false ) {
		// ------------------------------------- 
		//  Return a preformatted HTML list of ServiceLists and Service Groups
		// ------------------------------------- 
		
		$services = '';
		if ( isset($spl['service_lists']) ) {
			$services .= '<div style="margin-top:5px;">';
			foreach ( $spl['service_lists'] AS $menu ) {
				if ( !$services_filter || strcasecmp($services_filter, $menu['service_name']) == 0 ) {
					
					$services .= '<strong>'.$menu['service_name'].'</strong>';
					
					if ( $include_groups && isset($menu['menu_groups']) ) {
						$services .= '<ul>';
						foreach ($menu['menu_groups'] AS $group) {
							$services .= '<li>'.$group['group_name'].'</li>';
						}
						$services .= '</ul>';
					}
					$services .= '<br />';
				}
			}
			$services .= '</div>';
		}
		return $services;
	}
?>