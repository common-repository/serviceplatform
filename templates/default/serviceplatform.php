<?php
/*
	Template Name: OpenMenu (page)
*/
	
	$options = get_option( 'serviceplatform_options' );
	$sp_title = ( isset($options['sp_title']) && !empty($options['sp_title']) ) ? $options['sp_title'] : 'ServicePlatform' ;
	$sp_description = ( isset($options['sp_description']) && !empty($options['sp_description']) ) ? '<p>'.$options['sp_description'].'</p>' : '<br />' ;
	
	
?>
<?php get_header() ?>

	<div id="container">
		<div id="content" class="serviceplatform">
			<h1 class="entry-title"><?php echo $sp_title; ?></h1>
			<?php echo $sp_description; ?>

<?php 

		$paged = (get_query_var('paged')) ? get_query_var('paged') : 1;
		$args = array(
			'post_type' => 'serviceplatform',
			'post_status' => 'publish',
			'paged' => $paged,
			'posts_per_page' => 10,
			'caller_get_posts'=> 1
		);
		$loop = null;
		$loop = new WP_Query( $args );

		while ($loop->have_posts()) : $loop->the_post(); 

?>

				<div id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
					
					<h2 class="entry-title"><a href="<?php the_permalink(); ?>" title="<?php the_title(); ?>" rel="bookmark"><?php the_title(); ?></a></h2>
						<div class="entry-meta">
							<?php serviceplatform_posted_on(); ?>
						</div>
						
						<div class="entry-content">
<?php
	// Get the post options
	$custom = get_post_custom( get_the_ID() ); 
	$business_name = ( isset($custom["_business_name"][0]) ) ? $custom["_business_name"][0] : '' ;
	$location = ( isset($custom["_business_location"][0]) ) ? $custom["_business_location"][0] : '' ;
	$description = ( isset($custom["_brief_description"][0]) ) ? $custom["_brief_description"][0] : '' ;
	
	echo '<div class="sp_list_venue">' . $business_name . '</div>';
	echo '<div class="sp_list_location">' . $location . '</div>';
	echo $description;

?>
						</div>
					<br />
					<div class="entry-utility">
						<span class="cat-links">
							<?php printf( __( '<span class="%1$s">Business Type(s): </span> %2$s', 'serviceplatform' ), 'entry-utility-prep entry-utility-prep-cat-links', get_the_term_list( get_the_ID(), 'business_type', '', ', ', '' ) ); ?>
						</span>
					</div>
						
					</div><!-- .post -->

			<?php endwhile; ?>

			<div class="navigation"><p><?php posts_nav_link(); ?></p></div>

		</div><!-- #content -->
	</div><!-- #container -->

<?php get_sidebar() ?>
<?php get_footer() ?>