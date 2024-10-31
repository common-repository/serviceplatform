=== ServicePlatform ===
Contributors: serviceplatform
Donate link: http://serviceplatform.com
Tags: serviceplatform, service, services, business
Requires at least: 3.0
Tested up to: 3.4.1
Stable tag: 1.0.0
 
Easily create posts that are based on your Business Listing / Services at ServicePlatform.  Fully integrates your business's services into an existing theme.

== Description ==
This is the official Wordpress Plugin for ServicePlatform.  From creating custom posts, to widgets to display specials and business information, to the ability to add your services anywhere (any post or page) in your Wordpress Theme, this plugin handles it all.

Get your Business Listing at: http://ServicePlatform.com

Features:

* ServicePlatform Custom Post Type
* Widgets: Venue Location / Specials / ServiceLists / QR Code
* [serviceplatform] and [serviceplatform_qrcode] Shortcodes
* Custom Functions
* Site wide setiings
* Lots of settings to control the look and feel of the way yoru services will look


== Detailed Features ==
ServicePlatform Custom Post Type: 
	Create custom posts which are services based off of your Business Listing on ServicePlatform.  Choose what to display, how to display it and the plugin does the rest.
	
	Settings:
		Venue - This is a required field that points to your business listing
		
		Filters
			ServiceList to display: If your Business Listing contains multiple ServiceLists you can choose which ServiceList to display in your post by entering the ServiceList name here. (supports a comma-separated list)
			Service Group Name to display: If your Business Listing contains multiple service groups you can choose which group to display in your post by entering the group name here. (supports a comma-separated list)

			Venue Information: >Stores basic information about the venue that is referenced by the venue id. This is primarly used in scenarios where many businesses will be displayed.  Information, along with the excerpt, will be used to generate a single page of all business listings.

			Business Types: Define which business type describes this venue.

Widgets:
	ServicePlatform: Location  - Displays the business's location and hours
	ServicePlatform: Specials  - Displays the service items marked as special
	ServicePlatform: ServiceLists - Displays all ServiceLists for your business
	ServicePlatform: QR Code - Displays a QR Code to your mobile site on ServicePlatform 

Short code:
	[serviceplatform]
	
	Parameters:
		venue			 = Your Venue ID on ServicePlatform
		display_type     = servicelist | venue information | venue information / servicelist - What will be displayed from a Business Listing
		services_filter  = Will display only the ServiceList name matching this filter (supports a comma-separated list)
		group_filter     = Will display only the group name matching this filter (supports a comma-separated list)
		display_columns  = 1 | 2 - How many columns to display a ServiceList in
		split_on  		 = item | group - In 2 column display what do we split on
		background_color = Set the background color the ServiceList will display on

		[defaults to ServicePlatform Options setting]

	Samples: 
		[serviceplatform venue="sample"]
		[serviceplatform venue="sample" display_type="servicelist" display_columns="1"]

	[serviceplatform_qrcode]
	
	Parameters:
		venue	= Your Venue ID on ServicePlatform
		size	= size for the QR Code (max 500) - defaults to 128

	Samples: 
		[serviceplatform_qrcode venue="sample"]
		[serviceplatform_qrcode venue="sample" size="256"]


Site Wide ServicePlatform Settings:
	
	Look & Feel: 
		Display Type: What information will be displayed: ServiceList, Venue Information or Both
		How many columns: How many columns will be used to display a ServiceList (1 or 2)
		Use Short Tags: Service Item tags like special and new will be shortened
		Theme: only default is currently supported

	Your ServiceList: 
		Hide Prices: Determines if prices are shown for your service items

	Wordpress Theme: 
		Show posts on homepage: Determines whether ServicePlatform post types are displayed on the homepage blog post listing and in the RSS feed for the website.
		Hidesidebar: Forces the sidebar of a post to be hidden.  Gives the impression of a full-width page and may be more desirable when displaying ServiceLists.
		Width override: Attempts to force the width of the post to this amount.  Can be helpful for adjusting the display on troublesome themes.
		Background color: Set the background color the servicelist will display on (defaults to white - #fff)


== Installation ==

1. Unzip the serviceplatform.zip file
2. Upload the entire 'serviceplatform' folder to the '/wp-content/plugins/' directory
3. Activate the plugin through the 'Plugins' menu in WordPress
4. Update Site Wide options through the Settings -> ServicePlatform Options


== Frequently Asked Questions ==

= How do I get my business onto ServicePlatform so I can use this awesome plugin? =

Goto: http://ServicePlatform.com/about.php and read about ServicePlatform
Business Manager: http://ServicePlatform.com/creator

= How do I find out about updates to this plugin? =

Any updates will be posted on the ServicePlatform - http://ServicePlatform.com/blog

= Can I display ServiceLists for multiple businesses? =

Yes.  This is the main reason for using custom post types.  This allows you to create an entire Wordpress website of businesses and the services they offer

= Can I add a ServiceList to a page? =

Yes.  All you need to do is use the shortcode described above.  Very simple and can be added anywhere in a page in minutes.

= My listing breaks my theme, what can I do? =

An issue that sometimes comes up is the slug of the page conflicts with theme styles.  Avoid a page slug like 'serviceplatform' which may conflict with servicelist stylings.

== Screenshots ==
1. ServicePlatform Overview
2. Adding/Editing a Business Listing
3. ServicePlatform Options
4. Sample Rendered ServiceList
5. Powerful Widgets

== Changelog ==
= 1.0.0 =
* Initial public release


== Upgrade Notice ==

= 1.0.0 =
* Initial public release