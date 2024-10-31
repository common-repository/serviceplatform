<?php
// =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=
// ** ServicePlatform http://serviceplatform.com
// =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=
// ** Copyright (C) 2012 ServicePlatform, All rights reserved
// **		Authored By: Chris Hanscom
// ** 
// **		This library is copyrighted software by ServicePlatform; you can not
// **		redistribute it and/or modify it in any way without expressed written
// **		consent from ServicePlatform or Author.
// ** 
// =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=
// ** Version: 1.0
// =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=
// 
// =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=
// ** Constants: 
// =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=

	// Days
	define('SP_WEEKDAY_1', 'mon');
	define('SP_WEEKDAY_2', 'tue');
	define('SP_WEEKDAY_3', 'wed');
	define('SP_WEEKDAY_4', 'thu');
	define('SP_WEEKDAY_5', 'fri');
	define('SP_WEEKDAY_6', 'sat');
	define('SP_WEEKDAY_7', 'sun');
	
// =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=
// ** Class
// =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=

class cSpReader {
	
	// Set if the data is being used for display purposes on a website
	//   this forces special characters into html entities
	public $use_htmlspecialchars = true;
	
	// Flags to see if we have things
	public $has_list = false;
	public $has_list_items = false;
	
	// Determine whether disabled menus/menu groups/menu items are included
	public $include_disabled = false;
	
	// Allow Private restaurants to be read
	public $include_private = true;
	
	// MD5 Hash of the menu
	public $menu_hash = '';
	
	function read_file($sp_url) {
		// -------------------------------------
		// Crawl an ServiceList and return an array of the values
		// -------------------------------------
		
		// Get the XML contents for the SPL file
		$xml = $this->get_xml_from_url($sp_url);
		
		// Update the hash
		$this->menu_hash = md5($xml);
		
		$sp = array();
		// Now parse it
		if ($xml) {
			$is_private = $this->check_attribute('private', @$xml['private'] );
			
		  if ( $this->include_private || !$is_private ) {

			// SP information
			$sp['venue_id'] = $this->_clean(@$xml['uuid']);
			$sp['omf_private'] = $is_private;
			$sp['omf_version'] = $this->_clean(@$xml->serviceplatform->version);
		    $sp['spl_business_type_id'] = $this->_clean(@$xml->venue->business_type_id, 3);
		    $sp['spl_business_type'] = $this->_clean(@$xml->venue->business_type, 120);
		
			$sp['venue']['business_name'] = $this->_clean(@$xml->venue->business_name, 255);
		    $sp['venue']['brief_description'] = $this->_clean(@$xml->venue->brief_description, 255);
		    $sp['venue']['full_description'] = $this->_clean(@$xml->venue->full_description, 2000);
		    $sp['venue']['location_id'] = $this->_clean(@$xml->venue->location_id, 25);
		    $sp['venue']['address_1'] = $this->_clean(@$xml->venue->address_1, 120);
		    $sp['venue']['address_2'] = $this->_clean(@$xml->venue->address_2, 120);
		    $sp['venue']['city_town'] = $this->_clean(@$xml->venue->city_town, 50);
		    $sp['venue']['state_province'] = $this->_clean(@$xml->venue->state_province, 2);
		    $sp['venue']['postal_code'] = $this->_clean(@$xml->venue->postal_code, 30);
		    $sp['venue']['country'] = $this->_clean(@$xml->venue->country, 2);
		    $sp['venue']['phone'] = $this->_clean(@$xml->venue->phone, 40);
			$sp['venue']['longitude'] = $this->_clean(@$xml->venue->longitude, 11);
			$sp['venue']['latitude'] = $this->_clean(@$xml->venue->latitude, 10);
			$sp['venue']['utc_offset'] = $this->_clean(@$xml->venue->utc_offset, 6);
		    $sp['venue']['fax'] = $this->_clean(@$xml->venue->fax, 40);
		    $sp['venue']['website_url'] = $this->_clean(@$xml->venue->website_url, 120);
			$sp['formatted_address'] = $this->format_address($sp['venue']['address_1'], $sp['venue']['city_town'], $sp['venue']['state_province'], $sp['venue']['postal_code'], $sp['venue']['country']);

		    // Operating Days
		    $sp['operating_days'] = array();
	    	// Start with a blank structure
			$sp['operating_days']['mon_open_time'] = '';
			$sp['operating_days']['mon_close_time'] = '';
			$sp['operating_days']['tue_open_time'] = '';
			$sp['operating_days']['tue_close_time'] = '';
			$sp['operating_days']['wed_open_time'] = '';
			$sp['operating_days']['wed_close_time'] = '';
			$sp['operating_days']['thu_open_time'] = '';
			$sp['operating_days']['thu_close_time'] = '';
			$sp['operating_days']['fri_open_time'] = '';
			$sp['operating_days']['fri_close_time'] = '';
			$sp['operating_days']['sat_open_time'] = '';
			$sp['operating_days']['sat_close_time'] = '';
			$sp['operating_days']['sun_open_time'] = '';
			$sp['operating_days']['sun_close_time'] = '';
		    if (isset($xml->venue->operating_days)) {
			    foreach ($xml->venue->operating_days->operating_day AS $day) {
			    	if (isset($day->day_of_week) && !empty($day->day_of_week)) {
			    		$sp['operating_days'][constant('SP_WEEKDAY_'.$day->day_of_week).'_open_time'] = $this->_clean(@$day->open_time);
			    		$sp['operating_days'][constant('SP_WEEKDAY_'.$day->day_of_week).'_close_time'] = $this->_clean(@$day->close_time);
			    	}
			    }
		    }
			
		    // Get a print friendly version of the operation days 
			$sp['operating_days']['printable'] = $this->print_friendly_hours($sp['operating_days']);
		    
		    // Get Accepted currencies
		    $sp['accepted_currencies'] = array();
		    if (isset($xml->venue->accepted_currencies)) {
			    foreach ($xml->venue->accepted_currencies->accepted_currency AS $currency) {
				    $sp['accepted_currencies'][]['accepted_currency'] = $this->_clean(@$currency);
			    }
		    }
			
		    // Get Logo URLs
		    $sp['logo_urls'] = array();
		    if (isset($xml->venue->logo_urls)) {
		    	$i=0;
			    foreach ($xml->venue->logo_urls->logo_url AS $logo) {
			    	if ( !empty($logo) ) {
					    $sp['logo_urls'][$i]['logo_url'] = $this->_clean(@$logo);
					    $sp['logo_urls'][$i]['width'] = $this->_clean(@$logo['width']);
					    $sp['logo_urls'][$i]['height'] = $this->_clean(@$logo['height']);
					    $sp['logo_urls'][$i]['image_type'] = $this->_clean(@$logo['type']);
					    $sp['logo_urls'][$i]['image_media'] = $this->_clean(@$logo['media']);
					    
					    $i++;
					}
			    }
		    }

		    // Get Contacts
		    $sp['contacts'] = array();
		    if (isset($xml->venue->contacts)) {
		    	$i=0;
			    foreach ($xml->venue->contacts->contact AS $contact) {
			    	if ( !empty($contact->first_name) || !empty($contact->last_name) || !empty($contact->email) ) {
					    $sp['contacts'][$i]['first_name'] = $this->_clean(@$contact->first_name);
					    $sp['contacts'][$i]['last_name'] = $this->_clean(@$contact->last_name);
					    $sp['contacts'][$i]['email'] = $this->_clean(@$contact->email);
					    $sp['contacts'][$i]['contact_type'] = $this->_clean(@$contact['type']);
					    
					    $i++;
					}
			    }
		    }
		    
		    // Now parse the menu, menu groups, menu items
		    $menu_id = 0;
			// Loop through all services;
			if (isset($xml->services->service)) {
				$this->has_list = true;
					
				foreach ($xml->services->service AS $menu) { 
					
					$is_disabled = $this->check_attribute('disabled', @$menu['disabled']);
					
					// Handle disabled items
					if ($this->include_disabled || !$is_disabled) {
					
					$sp['service_lists'][$menu_id]['service_name'] = $this->_clean(@$menu['name'], 50);
					$sp['service_lists'][$menu_id]['service_description'] = $this->_clean(@$menu->service_description, 255);
					$sp['service_lists'][$menu_id]['service_note'] = $this->_clean(@$menu->service_note, 255);
					$sp['service_lists'][$menu_id]['currency_symbol'] = $this->_clean(@$menu['currency_symbol'], 3);
					$sp['service_lists'][$menu_id]['language'] = $this->_clean(@$menu['language'], 2);
					$sp['service_lists'][$menu_id]['service_uid'] = $this->_clean(@$menu['uid']);
					$sp['service_lists'][$menu_id]['disabled'] = $is_disabled;

			    	// Loop through the groups in this menu
			    	$group_id = 0;
			    	if (isset($menu->menu_groups->menu_group)) {
				    	foreach ($menu->menu_groups->menu_group AS $group) {
				    		$is_disabled = $this->check_attribute('disabled', @$group['disabled']);
						
							// Handle disabled items
							if ($this->include_disabled || !$is_disabled) {
						
					    	// Grab the group name
					    	$sp['service_lists'][$menu_id]['menu_groups'][$group_id]['group_name'] = $this->_clean(@$group['name'], 50);
					    	$sp['service_lists'][$menu_id]['menu_groups'][$group_id]['group_description'] = $this->_clean(@$group->menu_group_description, 255);
					    	$sp['service_lists'][$menu_id]['menu_groups'][$group_id]['group_note'] = $this->_clean(@$group->menu_group_note, 255);
							$sp['service_lists'][$menu_id]['menu_groups'][$group_id]['group_uid'] = $this->_clean(@$group['uid']);
							$sp['service_lists'][$menu_id]['menu_groups'][$group_id]['disabled'] = $is_disabled;

							// Group Options
					    	$go_id = 0;
					    	if ( isset($group->menu_group_options) ) {
						    	foreach ($group->menu_group_options->menu_group_option AS $opt) { 
							    	// Menu item options
							    	$sp['service_lists'] [$menu_id] ['menu_groups'] [$group_id] ['menu_group_options'] [$go_id] ['group_options_name'] = $this->_clean(@$opt['name']);
							    	$sp['service_lists'] [$menu_id] ['menu_groups'] [$group_id] ['menu_group_options'] [$go_id] ['menu_group_option_min_selected'] = $this->_clean(@$opt['min_selected']);
							    	$sp['service_lists'] [$menu_id] ['menu_groups'] [$group_id] ['menu_group_options'] [$go_id] ['menu_group_option_max_selected'] = $this->_clean(@$opt['max_selected']);
							    	$sp['service_lists'] [$menu_id] ['menu_groups'] [$group_id] ['menu_group_options'] [$go_id] ['menu_group_option_information'] = $this->_clean(@$opt->menu_group_option_information, 255);

							    	// Check for Option Items
							    	$oi_id = 0; 
							    	foreach ($opt->menu_group_option_item AS $opt_item) { 
							    		$sp['service_lists'] [$menu_id] ['menu_groups'] [$group_id] ['menu_group_options'] [$go_id] ['option_items'] [$oi_id] ['menu_group_option_name'] = $this->_clean(@$opt_item->menu_group_option_name, 50);
							    		$sp['service_lists'] [$menu_id] ['menu_groups'] [$group_id] ['menu_group_options'] [$go_id] ['option_items'] [$oi_id] ['menu_group_option_price'] = $this->_clean(@$opt_item->menu_group_option_price);
							    		$oi_id++;
							    	}
							    	
							    	$go_id++;
							    	
						    	} // efs
						    } // es

					    	// Loop through the menu items in this group
					    	$item_id = 0;
					    	if (isset($group->menu_items->menu_item)) { 
					    		$this->has_list_items = true;
					    			
						    	foreach ($group->menu_items->menu_item AS $item) {
						    		$is_disabled = $this->check_attribute('disabled', @$item['disabled']);
								
									// Handle disabled items
									if ($this->include_disabled || !$is_disabled) {
								
							    	// Menu item details
							    	$sp['service_lists'] [$menu_id] ['menu_groups'] [$group_id] ['menu_items'] [$item_id]['menu_item_name'] = $this->_clean(@$item->menu_item_name, 75);
									$sp['service_lists'] [$menu_id] ['menu_groups'] [$group_id] ['menu_items'] [$item_id]['menu_item_description'] = $this->_clean(@$item->menu_item_description, 450);
									$sp['service_lists'] [$menu_id] ['menu_groups'] [$group_id] ['menu_items'] [$item_id]['menu_item_price'] = $this->_clean(@$item->menu_item_price, 7);
									$sp['service_lists'] [$menu_id] ['menu_groups'] [$group_id] ['menu_items'] [$item_id]['item_uid'] = $this->_clean(@$item['uid']);
									$sp['service_lists'] [$menu_id] ['menu_groups'] [$group_id] ['menu_items'] [$item_id]['disabled'] = $is_disabled;
									$sp['service_lists'] [$menu_id] ['menu_groups'] [$group_id] ['menu_items'] [$item_id]['special'] = $this->check_attribute('special', @$item['special']);
									$sp['service_lists'] [$menu_id] ['menu_groups'] [$group_id] ['menu_items'] [$item_id]['new'] = $this->check_attribute('new', @$item['new']);
									
									// Options
							    	$option_id = 0;
							    	if ( isset($item->menu_item_options) ) {
								    	foreach ($item->menu_item_options->menu_item_option AS $opt) { 
									    	// Menu item options
									    	$sp['service_lists'] [$menu_id] ['menu_groups'] [$group_id] ['menu_items'] [$item_id] ['menu_item_options'] [$option_id] ['item_options_name'] = $this->_clean(@$opt['name']);
									    	$sp['service_lists'] [$menu_id] ['menu_groups'] [$group_id] ['menu_items'] [$item_id] ['menu_item_options'] [$option_id] ['menu_item_option_min_selected'] = $this->_clean(@$opt['min_selected']);
									    	$sp['service_lists'] [$menu_id] ['menu_groups'] [$group_id] ['menu_items'] [$item_id] ['menu_item_options'] [$option_id] ['menu_item_option_max_selected'] = $this->_clean(@$opt['max_selected']);
									    	$sp['service_lists'] [$menu_id] ['menu_groups'] [$group_id] ['menu_items'] [$item_id] ['menu_item_options'] [$option_id] ['menu_item_option_information'] = $this->_clean(@$opt->menu_item_option_information, 255);
									    	
									    	
									    	// Check for Option Items
									    	$option_item_id = 0; 
									    	foreach ($opt->menu_item_option_item AS $opt_item) { 
									    		$sp['service_lists'] [$menu_id] ['menu_groups'] [$group_id] ['menu_items'] [$item_id] ['menu_item_options'] [$option_id] ['option_items'] [$option_item_id] ['menu_item_option_name'] = $this->_clean(@$opt_item->menu_item_option_name, 50);
									    		$sp['service_lists'] [$menu_id] ['menu_groups'] [$group_id] ['menu_items'] [$item_id] ['menu_item_options'] [$option_id] ['option_items'] [$option_item_id] ['menu_item_option_price'] = $this->_clean(@$opt_item->menu_item_option_price);
									    		$option_item_id++;
									    	}
									    	
									    	$option_id++;
									    	
								    	} // efs
								    } // es
									
									// Images 
							    	$image_id = 0;
							    	if (isset($item->menu_item_image_urls)) {
								    	foreach ($item->menu_item_image_urls->menu_item_image_url AS $image) {
									    	// Menu item images
									    	$sp['service_lists'] [$menu_id] ['menu_groups'] [$group_id] ['menu_items'] [$item_id] ['menu_item_images'] [$image_id] ['image_url'] = $this->_clean(@$image);
									    	$sp['service_lists'] [$menu_id] ['menu_groups'] [$group_id] ['menu_items'] [$item_id] ['menu_item_images'] [$image_id] ['width'] = $this->_clean(@$image['width']);
									    	$sp['service_lists'] [$menu_id] ['menu_groups'] [$group_id] ['menu_items'] [$item_id] ['menu_item_images'] [$image_id] ['height'] = $this->_clean(@$image['height']);
									    	$sp['service_lists'] [$menu_id] ['menu_groups'] [$group_id] ['menu_items'] [$item_id] ['menu_item_images'] [$image_id] ['image_type'] = $this->_clean(@$image['type']);
									    	$sp['service_lists'] [$menu_id] ['menu_groups'] [$group_id] ['menu_items'] [$item_id] ['menu_item_images'] [$image_id] ['image_media'] = $this->_clean(@$image['media']);
									    	$image_id++;
									    	
								    	} // efs
								    } // es
									
									// Tags
							    	$tag_id = 0;
							    	if (isset($item->menu_item_tags)) {
								    	foreach ($item->menu_item_tags->menu_item_tag AS $tag) {
									    	// Menu item size
									    	$sp['service_lists'] [$menu_id] ['menu_groups'] [$group_id] ['menu_items'] [$item_id]['menu_item_tags'] [$tag_id] ['menu_item_tag'] = $this->_clean(@$tag, 35);
									    	$tag_id++;
									    	
								    	} // efs
								    } // es
									
								    
								    $item_id++;
								} // disabled check
								} // efe
					    	} // ei
					    	$group_id++;
					    } // disable check
				    	} // efe
			    	} // ei
			    	$menu_id++;
			    } // disable check
			    } // efe
		    } // ei
		  } // private check
		}

		return $sp;
	}

// =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=
// ** Private functions
// =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=

	function format_address($address = '', $city = '', $state = '', $postal_code = '', $country = '') {
		// -------------------------------------
		//  Formats an address in the format of
		//   address, city, state, zipcode, country
		// -------------------------------------
		$retval = (!empty($address)) ? $address.', ' : '' ;
		$retval .= (!empty($city)) ? $city.', ' : '' ;
		$retval .= (!empty($state)) ? $state.', ' : '' ;
		$retval .= (!empty($postal_code)) ? $postal_code.', ' : '' ;
		$retval .= (!empty($country)) ? $country.', ' : '' ;
		// remove any trailing comma before returning
		$retval = rtrim($retval, ', ');
		return $retval;
	}

	function print_friendly_hours($operating_hours) {
		// -------------------------------------
		//  Formats the operating hours into:
		//  	Sun-Thu: 3pm - 10pm
		//  	Fri-Sat: 3pm - 11pm
		// -------------------------------------

		$retval = array();
		$current_day = 'Sun';
		// Sun/Mon
		if ($operating_hours['sun_open_time'] == $operating_hours['mon_open_time'] && 
		  $operating_hours['sun_close_time'] == $operating_hours['mon_close_time']) {
		} else {
			$retval[] = $current_day.': '.$this->format_time($operating_hours['sun_open_time']).' - '.$this->format_time($operating_hours['sun_close_time']);
			$current_day = 'Mon';
		}
		// Mon/Tue
		if ($operating_hours['mon_open_time'] == $operating_hours['tue_open_time'] && 
		  $operating_hours['mon_close_time'] == $operating_hours['tue_close_time']) {
		} else {
			$day = ($current_day == 'Mon') ? $current_day : $current_day.'-Mon' ;
			$retval[] = $day.': '.$this->format_time($operating_hours['mon_open_time']).' - '.$this->format_time($operating_hours['mon_close_time']);
			$current_day = 'Tue';
		}
		// Tue/Wed
		if ($operating_hours['tue_open_time'] == $operating_hours['wed_open_time'] && 
		  $operating_hours['tue_close_time'] == $operating_hours['wed_close_time']) {
		} else {
			$day = ($current_day == 'Tue') ? $current_day : $current_day.'-Tue' ;
			$retval[] = $day.': '.$this->format_time($operating_hours['tue_open_time']).' - '.$this->format_time($operating_hours['tue_close_time']);
			$current_day = 'Wed';
		}
		// Wed/Thu
		if ($operating_hours['wed_open_time'] == $operating_hours['thu_open_time'] && 
		  $operating_hours['wed_close_time'] == $operating_hours['thu_close_time']) {
		} else {
			$day = ($current_day == 'Wed') ? $current_day : $current_day.'-Wed' ;
			$retval[] = $day.': '.$this->format_time($operating_hours['wed_open_time']).' - '.$this->format_time($operating_hours['wed_close_time']);
			$current_day = 'Thu';
		}
		// Thu/Fri
		if ($operating_hours['thu_open_time'] == $operating_hours['fri_open_time'] && 
		  $operating_hours['thu_close_time'] == $operating_hours['fri_close_time']) {
		} else {
			$day = ($current_day == 'Thu') ? $current_day : $current_day.'-Thu' ;
			$retval[] = $day.': '.$this->format_time($operating_hours['thu_open_time']).' - '.$this->format_time($operating_hours['thu_close_time']);
			$current_day = 'Fri';
		}
		// Fri/Sat
		if ($operating_hours['fri_open_time'] == $operating_hours['sat_open_time'] && 
		  $operating_hours['fri_close_time'] == $operating_hours['sat_close_time']) {
		  	$day = $current_day.'-Sat' ;
			$retval[] = $day.': '.$this->format_time($operating_hours['fri_open_time']).' - '.$this->format_time($operating_hours['fri_close_time']);
		} else {
			$day = ($current_day == 'Fri') ? $current_day : $current_day.'-Fri' ;
			$retval[] = $day.': '.$this->format_time($operating_hours['fri_open_time']).' - '.$this->format_time($operating_hours['fri_close_time']);
			$retval[] = 'Sat: '.$this->format_time($operating_hours['sat_open_time']).' - '.$this->format_time($operating_hours['sat_close_time']);
		}
		
		return $retval;
	}

	function format_time ($time) {
		// -------------------------------------
		//  Convert pass time to the format of hh:mm AM / PM
		// -------------------------------------
		return (empty($time)) ? '' : date('g:iA', strtotime($time)) ;
	}
	
	private function _clean ($data, $length = false) {
		// -------------------------------------
		// Clean crawled data
		// -------------------------------------
		
		// Trim to length if required
		if ($length) {
			$data = substr($data, 0, $length);
		} else {
			$data = (string)$data;
		}
		
		// Return the cleaned and trimmed data
		return ($this->use_htmlspecialchars) ? htmlspecialchars($data) : $data;
	}
	
	private function check_attribute ($expected_value, $set_value) {
		// -------------------------------------
		// Check for an attribute like disabled="disabled" and 
		//   returns 1 if set, else returns blank
		// -------------------------------------
		
		return ( strcasecmp($expected_value, $set_value) === 0 ) ? 1 : '' ;
	
	}

	private function get_xml_from_url( $sp_url ) {
		// -------------------------------------
		// Get the XML from the URL
		// -------------------------------------
		
		$xml = false;
		
		// Get the XML contents for the OMF file
		if ( false && function_exists('simplexml_load_file') ) {
			$xml = @simplexml_load_file($sp_url);
		} else {
			if ( function_exists( 'curl_init' ) ) {

				$curl = curl_init ();
				curl_setopt ( $curl, CURLOPT_RETURNTRANSFER, 1 );
				curl_setopt ( $curl, CURLOPT_URL, $sp_url );
				$contents = curl_exec ( $curl );
				curl_close ( $curl );

				if ( $contents )
					$xml = @simplexml_load_string($contents);
				else 
					$xml = false;
					
			} else {
				$xml = file_get_contents ( $sp_url );
				$xml = simplexml_load_string($xml);
			}
		}

		return $xml;
	}
	
} // END CLASS

?>