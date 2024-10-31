<?php
/**
 * The Template for displaying all single posts.
 *
 * @plugin OpenMenu
 */

	// Load Stuff for Google Maps
	wp_register_script('google-maps', 'http://maps.google.com/maps/api/js?sensor=true');
	wp_enqueue_script( 'google-maps' );
	wp_enqueue_script( 'jquery' );
	
 	// Get Post Settings
 	the_post();
	$custom = get_post_custom(); 
	$business_name = (isset($custom["_business_name"][0])) ? $custom["_business_name"][0] : '' ;
	$venue = (isset($custom["_venue"][0])) ? $custom["_venue"][0] : '' ;
	$services_filter = ( !empty($custom["_services_filter"][0]) ) ? $custom["_services_filter"][0] : false ;
	$group_filter = ( !empty($custom["_group_filter"][0]) ) ? $custom["_group_filter"][0] : false ;

	// Get the ServicePlatform Options
	$options = get_option( 'serviceplatform_options' );
	
	$hide_sidebar = ( isset($options['hide_sidebar']) && $options['hide_sidebar'] ) ? true : false ;
	$display_columns = ( isset($options['display_columns']) && $options['display_columns'] == 'One' ) ? '1' : '2' ;
	$display_type = (isset($options['display_type'])) ? $options['display_type'] : 'ServiceList' ;;
	$background_color = ( !empty($options['background_color']) ) ? $options['background_color'] : '#fff' ;
	
	// See if we should override the width
	if ( $hide_sidebar ) {
		$content_width_css = ( !empty($options['width_override']) ) ? 'style="width:'.$options['width_override'].';margin:0 auto"' : 'style="width:95%; margin:0 auto"' ; 
	} else { 
		$content_width_css = ''; 
	} 
	
	// Get the venue
	$spl = _get_venue_details( $venue ); 
?>

<?php get_header() ?>

	<style type="text/css">
		#sp_list, #sp_list dt, #sp_list dd.price { background-color:<?php echo $background_color; ?> }
	</style>

	<div id="container">
		<div id="content" class="serviceplatform" <?php echo $content_width_css; ?>>
			<h1 class="entry-title"><?php the_title(); ?></h1>

			<?php the_content(); ?>

			<div id="serviceplatform">

<?php 
	// Display the restaurant information
	if ( strcasecmp($display_type, 'venue information / servicelist') == 0 || 
	  strcasecmp($display_type, 'venue information') == 0 ) {
?>

	<script type="text/javascript">
		var $j = jQuery.noConflict();
		var geocoder;
		var map;
	    var image = '<?php echo SP_TEMPLATES_URL; ?>/default/images/ico-32-venue.png';

		$j(document).ready(function() {
			// Initialize the mapping stuff
			initialize();

<?php

	// If we have the cords then no need to geo-code
	if (!empty($spl['venue']['latitude']) && !empty($spl['venue']['longitude'])) {
		echo 'map_cords("'.$spl['venue']['latitude'].'", "'.$spl['venue']['longitude'].'");';
	} else {
		echo 'map_address("'.$spl['formatted_address'].'");';
	}
	
?>
		});

	  function initialize() {
	    geocoder = new google.maps.Geocoder();
	    var myOptions = {
	      zoom: 14,
	      mapTypeId: google.maps.MapTypeId.ROADMAP
	    }
	    map = new google.maps.Map(document.getElementById("locationmap"), myOptions);
	  }

	  function map_cords(lat, lng) {
		var myLatLng = new google.maps.LatLng(lat, lng);
		var marker = new google.maps.Marker({
		    position: myLatLng,
		    map: map,
		    icon: image
		});
		map.setCenter(new google.maps.LatLng(lat, lng));
	  }

	  function map_address(address) {
	    if (geocoder) {
	      geocoder.geocode( { 'address': address}, function(results, status) {
	        if (status == google.maps.GeocoderStatus.OK) {
	          map.setCenter(results[0].geometry.location);
	          var marker = new google.maps.Marker({
	              map: map, 
	              icon: image, 
	              position: results[0].geometry.location,
	              title: '<?php echo addslashes($spl['venue']['business_name']); ?>'
	          });
	        } else {
	          // alert("Geocode could not process the requested address\n" + status);
	        }
	      });
	    }
	  }
	</script>

			<div id="sp_venue">

				<div id="location-map"> 
					<div id="locationmap"></div>
				</div>

				<div id="details">
		            <p><?php echo $spl['venue']['brief_description']; ?></p>
		            <p>
		            	<strong><?php _e('Address') ?>:</strong><br />
		            	<?php echo $spl['venue']['address_1']; ?><br />
		            	<?php echo $spl['venue']['city_town']; ?>, <?php echo $spl['venue']['state_province']; ?> <?php echo $spl['venue']['postal_code']; ?> <?php echo $spl['venue']['country']; ?>
		            </p>
		            <p>
			            <strong><?php _e('Phone') ?>: </strong> <?php echo $spl['venue']['phone']; ?><br />
			            <strong><?php _e('Website') ?>: </strong> <a href="<?php echo $spl['venue']['website_url']; ?>"><?php echo $spl['venue']['website_url']; ?></a>
		            </p>
		            
		            <p>
		            	<strong><?php _e('Hours') ?>:</strong><br />
<?php 
	if (isset($spl['operating_days']) && is_array($spl['operating_days'])) {
		foreach ($spl['operating_days']['printable'] AS $daytime) {
			echo $daytime.'<br />';
		}
	}
?>
			        </p>
			        
			        <div><strong><?php _e('Type') ?>:</strong> <?php echo $spl['spl_business_type']; ?></div>
				</div>

			<div class="clear"></div>
		</div>
		
		
<?php 
	} // end venue info

	// Display the ServiceList
	if ( strcasecmp($display_type, 'venue information / servicelist') == 0 || 
	 strcasecmp($display_type, 'servicelist') == 0 ) {
		 echo build_venue_from_details($spl, $display_columns, $services_filter, $group_filter, false); 
	
	}
?>

			</div> <!-- #serviceplatform -->

		</div><!-- #content -->
	</div><!-- #container -->


<?php 
	unset($spl);

	if ( !$hide_sidebar ) {
		get_sidebar();
	}
	
	get_footer();
?>