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
// ** 
// =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=
// ** Class
// =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=

class cSpRender { 
	
	// How many columns to render the menu in (1 | 2)
	public $columns = 1;
	
	// What do we split the columns on (item | group)
	public $split_on = 'group';

	// Determines if we use a short item tag (1 or 2 letter)
	//   i.e. Specials = S / New = N
	public $use_short_tag = false;
	
	// Determines if the attribute to ServicePlatform is displayed
	public $hide_attribute = false;

	// Should we disable cleaning the data with html entities
	public $disable_entities = false;

	// Switches for data display
	public $show_item_images = true;
	public $allow_image_zoom = false;  // uses the om_item_zoom class for lightboxing
	public $show_prices = true;
	public $show_logo = true;
	public $show_options = true;

	function get_venue_information ($sp) {
		// ------------------------------------- 
		//  Create a block of venue information
		// ------------------------------------- 
		
		$retval = '';
		$logo = ($this->show_logo) ? $this->extract_logo($sp['logo_urls'], 'Full', 'Web') : false ;
		
		if ( !empty($sp) ) {
			$retval .= '<div class="sp_venue">';
			if ( !empty($logo) ) { 
				$retval .= '<div id="restaurant_logo"><img src="'.$logo.'" alt="'.$sp['venue']['business_name'].'" alt="" /></div>';
			}
			$retval .= '<div id="business_name">'.$sp['venue']['business_name'].'</div>';
			$retval .= '<p>'.$sp['venue']['brief_description'].'</p>';
			
	        $retval .= '<p>';
			$retval .= '<strong>Address:</strong><br />';
			$retval .= $sp['venue']['address_1'].'<br />';
			$retval .= $sp['venue']['city_town'].', '.
					  	$sp['venue']['state_province'] .' '.
						$sp['venue']['postal_code'].'<br />'.
						$sp['venue']['country'];
			$retval .= '</p><p>';
			$retval .= '<strong>Phone: </strong> '.$sp['venue']['phone'].'<br />';
			$retval .= '<strong>Website: </strong> <a href="' . $sp['venue']['website_url'] . '">' . 
						$sp['venue']['website_url'] . '</a>';
			$retval .= '</p><p>';
			$retval .= '<strong>Our Hours:</strong><br />';
			
			foreach ($sp['operating_days']['printable'] AS $daytime) {
				$retval .= $daytime.'<br />';
			}

			$retval .= '</p>';
 
			$retval .= '</div>';
		}
		
		return $retval;
		
	}
	
	function get_menu_from_details ($sp, $list_filter = '', $group_filter = '', 
				$hl_primary = '', $hl_secondary = '') {
		// ------------------------------------- 
		//  Create a ServiceList
		//   $list_filter / $group_filter = filter the display to a specific servicelist or service group
		//     this can be a comma-seperata list or just a single entry
		//   $hl_primary / $hl_secondary = Provide highlighting of text
		// ------------------------------------- 

		$retval = '';

		// If an invalid split on is passed force a one column display
		$one_column = ($this->columns == '2') ? false : true ;
		$one_column = (($this->split_on != 'group' && $this->split_on != 'item') || $one_column ) ? true : false ;
		
		// Make sure a proper split on was passed
		$this->split_on = ($this->split_on == 'group' || $this->split_on == 'item') ? $this->split_on : 'group' ;
		
		// Split the filters into an array
		$list_filter = $this->process_filter($list_filter);
		$group_filter = $this->process_filter($group_filter);

		if ( !empty($sp) ) {
			$retval .= '<div id="sp_list">';

		  if ( isset($sp['service_lists']) && !empty($sp['service_lists']) ) {

			foreach ($sp['service_lists'] AS $menu) {

				// Check for a filter
				if ( !$list_filter || in_array(strtolower($menu['service_name']), $list_filter) ) {

					// Start a new menu
					$sp_m = $this->clean($menu['service_name']);
					$m_disabled = ( $menu['disabled'] ) ? ' m_disabled' : '' ;
					$retval .= '<div id="sp_s_'.$menu['service_uid'].'" class="menu_name'.$m_disabled.'">'.$sp_m;

					// Check for a description
					if ( !empty($menu['service_description']) ) {
						$retval .= '<br /><span>'.$menu['service_description'].'</span>';
					}
					$retval .= '</div><div id="sp_sc_'.$menu['service_uid'].'" class="menu_content">';

					// How many groups or items are there in this menu
					//  used for 2 column displays
					$current_group = 1;
					$current_item = 1;
					if ( $this->split_on == 'group' && !$one_column ) {
						$group_count = count($menu['menu_groups']);
					} elseif ( $this->split_on == 'item' && !$one_column ) {
						$item_count = $this->get_service_item_count($menu, $group_filter);
					}

					foreach ($menu['menu_groups'] AS $group) {
						// Check for a group filter
						if ( !$group_filter || in_array(strtolower($group['group_name']), $group_filter) ) {
							
							// Should we start the left or right column 
							if ( !$one_column && $this->split_on == 'group' ) {
								if ($current_group == 1) { 
									// Start the left Column
									$retval .= '<div class="left-menu">';
								} elseif ($current_group == (1 + (int)($group_count/2)) ) {
									// Close the left column and start the right
									$retval .= '</div><!-- END left menu -->';
									$retval .= '<div class="right-menu">';
								}
							}
							
							// Start a group
							$sp_g = $this->clean($group['group_name']);
							$g_disabled = ( $group['disabled'] ) ? ' class="g_disabled"' : '' ;
							$retval .= '<h2 id="sp_sg_'.$group['group_uid'].'"'.$g_disabled.'>'.$sp_g;
							
							if ( !empty($group['group_description']) ) {
								$retval .= '<br /><span class="sm_norm">'.$group['group_description'].'</span>';
							}
							$retval .= '</h2>';
							
							if ( !empty($group['menu_items']) ) {
								foreach ($group['menu_items'] AS $item) {
									// Should we start the left or right column 
									if ( !$one_column && $this->split_on == 'item' ) {
										if ($current_item == 1) { 
											// Start the left Column
											$retval .= '<div class="left-menu">';
										} elseif ($current_item == (1 + (int)($item_count/2)) ) {
											// Close the left column and start the right
											$retval .= '</div><!-- END left menu -->';
											$retval .= '<div class="right-menu">';
										}
									}

									// Generate the item option tags list
									$is_special = $this->get_tag_text('special', $item['special']);
									$is_new = $this->get_tag_text('new', $item['new']);
									$tags = $is_special . $is_new;
									
									$price = ($this->show_prices) ? $this->fix_price($item['menu_item_price'], $menu['currency_symbol']) : '';
									// See if a thumbnail exists
									$thumbnail = '';
									if ( $this->show_item_images && isset($item['menu_item_images']) ) {
										$thumbnail = $this->extract_item_image($item['menu_item_images'], 'thumbnail', 'web');
										if ($thumbnail) {
											$full_size = ($this->allow_image_zoom) ? $this->extract_item_image($item['menu_item_images'], 'full', 'web') : false ;
											$thumbnail = '<img class="mi_thumb" src="'.$thumbnail.'" alt="" />';
											$thumbnail = ($full_size) ? '<a class="sp_item_zoom" href="' . $full_size . '" title="' . $this->clean($item['menu_item_name']) . '" target="_blank">'.$thumbnail.'</a>' : $thumbnail ;
										}
									}
										
						            $retval .= '<dl>';
						            $i_disabled = ( $item['disabled'] ) ? ' i_disabled' : '' ;
						            $retval .= '<dt class="'. $i_disabled  . '">' . $thumbnail . $tags .
						            		 $this->hl_service( $this->clean($item['menu_item_name']), $hl_primary, $hl_secondary ) . '</dt>';
						            $retval .= '<dd class="price">'.$price.'</dd>';
									
						            $retval .= '<dd class="description">' . $this->hl_service( nl2br($this->clean($item['menu_item_description'])), $hl_primary, $hl_secondary ) . '</dd>';

							    	// Check for options
						            if ( $this->show_options && isset($item['menu_item_options']) && !empty($item['menu_item_options']) && is_array($item['menu_item_options']) ) {
						            	$retval .= '<dd class="item_options">';
							            foreach ($item['menu_item_options'] AS $option) {
							            	$retval .= '<div><strong>'.$this->clean($option['item_options_name']).'</strong>: ';

							            	 if ( isset($option['option_items']) && !empty($option['option_items']) ) {
							            	 	 foreach($option['option_items'] AS $option_item) { 
							            	 	 	$opt_price = ($this->show_prices) ? $this->fix_price($option_item['menu_item_option_price'], $menu['currency_symbol'], ' - ') : '' ;
							            	 	 	$retval .= $this->clean($option_item['menu_item_option_name']).$opt_price.' | ';
							            	 	 }
							            	 	 // Strip the trailing |
												$retval = rtrim($retval, ' | ');
							            	 }
							            	
							            	$retval .= '</div>';
							            }
							            $retval .= '</dd>';
							        }
							        
							        // close the item
						            $retval .= '</dl>';
						            
									$current_item++;
									
								} // end item
							}
							// Display Group Options
							if ( $this->show_options && isset($group['menu_group_options']) && is_array($group['menu_group_options']) ) { 
								foreach($group['menu_group_options'] AS $option) { 
									$retval .= '<div class="goptions">';
									$retval .= '<div class="goptions-title">'.$this->clean($option['group_options_name']);
									if ( !empty($option['menu_group_option_information']) ) {
										$retval .= '<br /><span class="goptions-desc">'.$option['menu_group_option_information'].'</span>';
									}
									$retval .= '</div>';
									
									// Check for Option Items
									if ( isset($option['option_items']) && is_array($option['option_items']) ) { 
										foreach($option['option_items'] AS $option_item) { 
											$opt_price = ($this->show_prices) ?  $this->fix_price($option_item['menu_group_option_price'], $menu['currency_symbol'], ' - ') : '' ;
											$retval .= $this->clean($option_item['menu_group_option_name']).$opt_price.' | ';
										}
										// Strip the trailing |
										$retval = rtrim($retval, ' | ');
									}
									
									$retval .= '</div>';
								} 
							}
							
							// Check for a note
							if ( !empty($group['group_note']) ) {
								$retval .= '<div class="group_note">('.$group['group_note'].')</div>';
							}
							
							// End a group
							if ( $one_column ) {
								$retval .= '<span class="separator big"></span>';
							} elseif ( true || $this->split_on == 'group' ) {
								$retval .= '<span class="separator small"></span>';
							}

							$current_group++;
							
						} // end group filter
					} // end group
					
					if ( !$one_column ) {
						// Close the menu colums
						if ( $current_group > 1 || $item_count > 1 ) {
							$retval .= '</div><!-- END right sl -->';
						}
						$retval .= '<div class="clear"></div>';
					}
					
					// Close the menu 
					$retval .= '</div><!-- END #sl -->';
					
					if ( !$one_column ) {
						$retval .= '<div class="page-break"></div>';
					}
				
					// Check for a note
					if ( !empty($menu['service_note']) ) {
						$retval .= '<div id="sp_sn_'.$menu['service_uid'].'" class="menu_note">('.$menu['service_note'].')</div>';
					}
				
				} // end menu filter
					
			} // end menu loop
		} else {
			$retval .= 'There was an error displaying. Please contact <a href="http://serviceplatform.com" target="_blank">ServicePlatform</a> for assistance.';
		}
		
		// Should we add a key for short tags
		if ( $this->use_short_tag ) {
			$retval .=  '<div id="stk">';
			$retval .=  '<span class="item_tag special">S</span>Special '.
						'<span class="item_tag new">N</span>New</div>';
		}
		
		if (!$this->hide_attribute) {
			$retval .= '<small><a href="http://serviceplatform.com" style="font-size:.9em;color:#00f;text-align:center;display:block">powered by ServicePlatform</a></small>';
		}
		
		$retval .= '</div><!-- #sp_list -->';
		}
		
		return $retval;
	}

// =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=
// ** Helper functions (publically available)
// =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=
	function fix_price ( $price, $currency_code, $prefix = '', $suffix = '' ) { 
		// -------------------------------------
		// Handles localization of prices with adding currency symbols
		// -------------------------------------
		$retval = '' ;
		if ( !empty($price) ) {
			$retval = $this->format_currency($price, $currency_code);
			$retval = $this->get_currency_symbol($currency_code, $retval, true);
			$retval = $prefix.$retval.$suffix;
		}
		return $retval;
	}

	function extract_logo( $logo_images, $type = 'Full', $media = 'Web' ) {
		// ------------------------------------- 
		//  Attempt to extract a logo image
		// ------------------------------------- 
		$retval = '';
		if ( !empty($logo_images) && is_array($logo_images) ) {
			foreach ($logo_images AS $img) {
				if ( strcasecmp($img['image_type'], $type ) === 0 && 
					 (strcasecmp($img['image_media'], $media ) === 0 || 
						strcasecmp($img['image_media'], 'All' ) === 0 ) && 
					 !empty($img['logo_url']) ) {
					$retval = $img['logo_url'];
					break;
				}
			}
		}
		return $retval;
	}
	
// =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=
// ** Private functions
// =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=
	private function get_service_item_count ($sl, $filter = false) { 
		// -------------------------------------
		// Count the items in a all groups for a servicelist
		// -------------------------------------
		
		$retval = 0;
		if ( !empty($sl['menu_groups']) ) {
			foreach ($sl['menu_groups'] AS $group) {
				if ( !$filter || in_array(strtolower($group['group_name']), $filter) ) {
					$retval += count($group['menu_items']);
				}
			}
		}
		
		return $retval;
	}
	
	
	private function clean ($str) { 
		// -------------------------------------
		// Clean menu information for displaying
		// -------------------------------------
		
		return ($this->disable_entities) ? $str : htmlentities($str, ENT_COMPAT, 'UTF-8');
	}
	
	private function extract_item_image( $item_images, $type = 'Thumbnail', $media = 'Web'  ) {
		// ------------------------------------- 
		//  Attempt to extract a thumbnail image from a service item's image list
		//    Looks for a Thumbnail for a media type of Web
		// ------------------------------------- 

		$retval = '';
		foreach ($item_images AS $img) {
			if ( strcasecmp($img['image_type'], $type ) === 0 && 
				 (strcasecmp($img['image_media'], $media ) === 0 || 
				  strcasecmp($img['image_media'], 'All' ) === 0 ) && 
				 !empty($img['image_url']) ) {
				$retval = $img['image_url'];
			}
		}

		return $retval;
	}
	
	private function hl_service ($text, $primary, $secondary = false, $make_bold = false) {
		// ------------------------------------- 
		//  Highlight services in a passed string
		//   If primary is found then its highlight is passed back
		//   If not then we look at the secondary
		// ------------------------------------- 
		
		if (!empty($primary)) {
			// Highlight the primary
			$count = 0;
			$replace = ($make_bold) ? '<strong>$1</strong>' : '<span class="hl_a">$1</span>' ;
			$new_text = preg_replace("/(".$primary.")/i", $replace, $text, -1, $count);
			if ($count > 0) {
				return $new_text;
			} elseif ( !empty($secondary) ) {
				// Try and highlight the secondary 
				$replace = ($make_bold) ? '<strong>$1</strong>' : '<span class="hl_b">$1</span>' ;
				return preg_replace("/(".$secondary.")/i", $replace, $text);
			} else {
				return $text;
			}
		} else { 
			if (!empty($secondary)) {
				// Highlight the secondary
				$replace = ($make_bold) ? '<strong>$1</strong>' : '<span class="hl_b">$1</span>' ;
				return preg_replace("/(".$secondary.")/i", $replace, $text);
			} else { 
				return $text;
			}
		}
	}

	private function format_currency ($amount, $currency_code) { 
		// ------------------------------------- 
		//  Handle localizing a price into the proper format
		// ------------------------------------- 

		// Define the format
		$currency_code_styles = array(
				"ARS" => "dotThousandsCommaDecimal", 
				"ATS" => "dotThousandsCommaDecimal", 
				"BEF" => "dotThousandsCommaDecimal", 
				"BHD" => "dotThousandsNoDecimals", 
				"REAL" => "dotThousandsCommaDecimal", 
				"DKR" => "dotThousandsCommaDecimal", 
				"FIM" => "spaceThousandsCommaDecimal", 
				"FRF" => "spaceThousandsCommaDecimal", 
				"DEM" => "dotThousandsCommaDecimal", 
				"GRD" => "dotThousandsCommaDecimal", 
				"HRK" => "dotThousandsCommaDecimal", 
				"ISK" => "dotThousandsCommaDecimal", 
				"INR" => "indian", 
				"ITL" => "dotThousandsCommaDecimal", 
				"YPY" => "noDecimals", 
				"LTL" => "dotThousandsCommaDecimal", 
				"NLG" => "dotThousandsCommaDecimal", 
				"NOK" => "dotThousandsCommaDecimal", 
				"KRW" => "noDecimals",
				"ESP" => "dotThousandsCommaDecimal", 
				"SEK" => "spaceThousandsDotDecimal", 
				"CHF" => "apostropheThousandsDotDecimal", 
				"CZK" => "dotThousandsCommaDecimal",
				"LUF" => "apostropheThousandsDotDecimal",
				"PLZ" => "spaceThousandsCommaDecimal",
				"PTE" => "dotThousandsCommaDecimal"
		);
		
		$currency_code = strtoupper($currency_code);
		$style = array_key_exists($currency_code, $currency_code_styles) ? $currency_code_styles[$currency_code] : '' ;

		switch ($style) {
			case "dotThousandsCommaDecimal" :
				$retval = number_format($amount, 2, ",", ".");
				break;
			case "dotThousandsNoDecimals" :
				$str = number_format($amount, 2, ",", ".");
				$retval = substr($str, 0, -3);
				break;
			case "spaceThousandsCommaDecimal" :
				$retval = number_format($amount, 2, ",", " ");
				break;
			case "indian" :
				list( $digits, $decimals ) = explode(".", $amount);
				if( ($len = strlen($digits)) >= 5 ) {
					$bit = substr($digits, 0, $len - 3) / 100;
					$retval = number_format($bit, 2, ",", "," )
							.",".substr($digits, $len - 3) 
							.".$decimals";
				}
				else
					$retval = number_format($amount, 2);
				break;
			case "noDecimals" :
				$str = number_format($amount, 2, ".", ",");
				$retval = substr($str, 0, -3);
				break;
			case "spaceThousandsDotDecimal" :
				$retval = number_format($amount, 2, ".", " ");
				break;
			case "apostropheThousandsNoDecimals" :
				$retval = number_format($amount, 2, ".", "'");
				$retval = substr($str, 0, -3);
				break;
			case "apostropheThousandsDotDecimal" :
				$retval = number_format($amount, 2, ".", "'");
				break;
			default :
				$retval = number_format($amount, 2);
				break;
		}
		
		return $retval;
	}
	
	private function get_currency_symbol ($currency_code, $amount = '', $html_encode = false) {
		
		$currency_symbols = array(
			'AFN' => '',
			'ALL' => 'Lek',
			'DZD' => '',
			'AOA' => '',
			'ARS' => '',
			'AMD' => '',
			'AWG' => 'ƒ',
			'AUD' => '$',
			'AZN' => '',
			'BSD' => '$',
			'BHD' => 'BHD ',
			'BDT' => '',
			'BBD' => '$',
			'BYR' => '',
			'BZD' => '',
			'BMD' => '$',
			'BTN' => '',
			'BOB' => '$b',
			'BAM' => '',
			'BWP' => '',
			'BRL' => 'R$',
			'BND' => '$',
			'BGN' => '',
			'BIF' => '',
			'KHR' => '',
			'CAD' => '$',
			'CVE' => '',
			'KYD' => '$',
			'CLP' => '$',
			'CNY' => '¥',
			'COP' => '',
			'XOF' => '',
			'XAF' => '',
			'KMF' => '',
			'XPF' => '',
			'CDF' => '',
			'CRC' => '',
			'HRK' => ' kn',
			'CUP' => '',
			'CYP' => '',
			'CZK' => ' Kč',
			'DKK' => ' kr',
			'DJF' => '',
			'DOP' => 'RD$',
			'XCD' => '$',
			'EGP' => '£',
			'SVC' => '$',
			'ERN' => '',
			'EEK' => '',
			'ETB' => '',
			'EUR' => '€',
			'FKP' => '£',
			'FJD' => '',
			'GMD' => '',
			'GEL' => '',
			'GHS' => '',
			'GIP' => '£',
			'XAU' => '',
			'GTQ' => 'Q',
			'GGP' => '',
			'GNF' => '',
			'GYD' => '$',
			'HTG' => '',
			'HNL' => 'L',
			'HKD' => '$',
			'HUF' => ' Ft',
			'ISK' => ' kr',
			'INR' => '',
			'IDR' => '',
			'XDR' => '',
			'IRR' => '',
			'IQD' => '',
			'IMP' => '£',
			'ILS' => '',
			'JMD' => 'J$',
			'JPY' => '¥',
			'JEP' => '£',
			'JOD' => '',
			'KZT' => '',
			'KES' => '',
			'KPW' => '',
			'KRW' => '',
			'KWD' => '',
			'KGS' => '',
			'LAK' => '',
			'LVL' => '',
			'LBP' => '£',
			'LSL' => '',
			'LRD' => '$',
			'LYD' => '',
			'LTL' => ' Lt',
			'MOP' => '',
			'MKD' => '',
			'MGA' => '',
			'MWK' => '',
			'MYR' => '',
			'MVR' => '',
			'MTL' => '',
			'MRO' => '',
			'MUR' => '',
			'MXN' => '$',
			'MDL' => '',
			'MNT' => '',
			'MAD' => '',
			'MZN' => '',
			'MMK' => '',
			'NAD' => '$',
			'NPR' => '',
			'ANG' => '',
			'NZD' => '',
			'NIO' => 'C$',
			'NGN' => '',
			'NOK' => ' kr',
			'OMR' => '',
			'PKR' => '',
			'XPD' => '',
			'PAB' => '',
			'PGK' => '',
			'PYG' => 'Gs',
			'PEN' => '',
			'PHP' => '',
			'XPT' => '',
			'PLN' => '',
			'QAR' => '',
			'RON' => '',
			'RUB' => '',
			'RWF' => '',
			'STD' => '',
			'SHP' => '£',
			'WST' => '',
			'SAR' => '',
			'SPL' => '',
			'RSD' => '',
			'SCR' => '',
			'SLL' => '',
			'XAG' => '',
			'SGD' => '$',
			'SBD' => '',
			'SOS' => 'S',
			'ZAR' => 'R',
			'LKR' => '',
			'SDG' => '',
			'SRD' => '$',
			'SZL' => '',
			'SEK' => ' kr',
			'CHF' => 'CHF',
			'SYP' => '£',
			'TWD' => '',
			'TJS' => '',
			'TZS' => '',
			'THB' => ' ฿',
			'TOP' => '',
			'TTD' => 'TT$',
			'TND' => '',
			'TRY' => 'TL',
			'TMM' => '',
			'TVD' => '$',
			'UGX' => '',
			'UAH' => '',
			'AED' => '',
			'GBP' => '£',
			'USD' => '$',
			'UYU' => '',
			'UZS' => '',
			'VUV' => '',
			'VEB' => '',
			'VEF' => '',
			'VND' => '₫',
			'YER' => '',
			'ZMK' => '',
			'ZWD' => 'Z$'
		);
		
		$symbol_after_amount = array(
				"ISK", "ITL", "LTL", "NOK", "SEK", "THB", "CZK", "DKK", "HUF", "HRK"
		);
		
		$currency_symbol = '';
		if ( isset($currency_symbols[$currency_code]) ) {
			$currency_symbol = ( $html_encode ) ? htmlentities($currency_symbols[$currency_code], ENT_COMPAT, 'UTF-8') : $currency_symbols[$currency_code] ; 
		}
		
		// formatted value
		if ( strlen($amount) > 0 ) {
			// Format with the passed amount
			$retval = ( in_array($currency_code, $symbol_after_amount) ) ? $amount.$currency_symbol : $currency_symbol.$amount ;
		} else {
			// Just return any found currency symbol
			$retval = $currency_symbol;
		}
		
		return $retval;
	}

	private function process_filter( $filter ) {
		// ------------------------------------- 
		//  Process a filter by splitting the string into an array
		//   Handles commas and quotes in menu names
		//     escape character is \
		// ------------------------------------- 

		if (!empty($filter)) { 
			if ( function_exists('str_getcsv') ) {
				$retval = array_map('trim', str_getcsv($filter, ',', '"'));
			} else {
				$retval = array_map('trim', explode(',', $filter));
			}
			$retval = array_map('strtolower', $retval);
		} else {
			$retval = false;
		}
		return $retval;
	}

	function get_tag_text ($type, $value) {
		// ------------------------------------- 
		//  Handle getting the test for menu item option tags
		// ------------------------------------- 
		
		// Set the tags text
		switch ( $type ) {
			case 'special':
				$text = ( $this->use_short_tag ) ? 'S' : 'Special' ;
				break;
			case 'new':
				$text = ( $this->use_short_tag ) ? 'N' : 'New' ;
				break;	
			default:
				$retval = '';
		}

		$retval = ($value == 1) ? '<span class="item_tag '.$type.'">' . $text .'</span>' : '' ;
		return $retval;
	}
} // END CLASS

?>