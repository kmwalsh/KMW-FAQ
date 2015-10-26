<?php

/**
 * Plugin Name: KMW | FAQs
 * Plugin URI: http://katemwalsh.com
 * Description: Fancy FAQs with custom post type, custom taxonomy, toggle-able FAQ bodies, and a "show all" FAQ button.
 * Author: KMW
 * Author URI: http://katemwalsh.com
 * Version: 0.1.0
 */

/* ====================================
	custom faq archive template
--------------------------------------------------------------------------- */
if ( ! function_exists('kmw__faq_template_archive') ) {
	function kmw__faq_template_archive( $template ) {
	  if ( is_post_type_archive('web-faq') ) {
		$theme_files = array('archive-web-faq.php', 'myplugin/archive-web-faq.php');
		$exists_in_theme = locate_template($theme_files, false);
		if ( $exists_in_theme != '' ) {
		  return $exists_in_theme;
		} else {
		  return plugin_dir_path(__FILE__) . '/kmw-faq/archive-web-faq.php';
		}
	  }
	  return $template;
	}
}
add_filter('template_include', 'kmw__faq_template_archive');

/* ====================================
	enqueue the JS for show/hide and show all button
	enqueue the CSS for the category "dropdown"
--------------------------------------------------------------------------- */

if ( ! function_exists('kmw___faq_scripts') ) {
	function kmw___faq_scripts() {
		wp_register_script( 'faq-js', plugins_url( '/js/faq-js.js', __FILE__ ), array( 'jQuery' ), '1.0.0', true );
		wp_enqueue_script( 'faq-js' );
		
		wp_enqueue_style( 'kmw-fs-css', plugins_url( 'css/style.css', __FILE__ ) );
	}
}
add_action( 'wp_enqueue_scripts', 'kmw___faq_scripts' );


/* ====================================
	create function for "dropdown" sorting, used in FAQ archive
--------------------------------------------------------------------- */
// display the filter/dropdown sorting
if ( ! function_exists('kmw__faq_sort_category_header') ) {
	function kmw__faq_sort_category_header( $taxtype ) {
		ob_start(); ?>
		<div class="filter-categories">
			<?php echo kmw__faq_sort_categories( $taxtype ); ?>
		</div>
		<?php
		$return = ob_get_clean();
		return $return;
	}
}

/* ====================================
	get categories for the sorter
--------------------------------------------------------------------- */
if ( ! function_exists('kmw__faq_sort_categories') ) {
	function kmw__faq_sort_categories( $taxtype ) {
		$args = array(
			'parent'        => 0,
			'orderby'            => 'name',
			'ord'              => 'ASC',
			'style'              => 'list',
			'hide_empty'         => 0,
			'title_li'           => '',
			'number'             => null,
			'taxonomy'           => $taxtype,
		);
		$get_tax_name = get_taxonomy($taxtype);
		$cats = get_categories( $args ); 
		ob_start(); ?>
			<div class="dropdown-sorter">
				<span class="button dropdown-button">
					<?php echo $get_tax_name->label; ?>
					<ul>
					<?php foreach ( $cats as $category ) { ?>
						<li><a href="<?php echo get_term_link( $category ); ?>"><?php echo $category->name; ?></a></li>
					<?php } ?>
					</ul>
				</span>
			</div>		
		<?php 
		$return = ob_get_clean();
		return $return;
	}
}

/* ====================================
	kick user out of single FAQ view, redirect to archive view
--------------------------------------------------------------------- */
if ( ! function_exists('kmw__redirect_faq') ) {
	function kmw__redirect_faq() {
		if ( ! is_singular( 'web-faq' ) )
			return;
		wp_redirect( get_post_type_archive_link( 'web-faq' ), 301 );
		exit;
	}
}
add_action( 'template_redirect', 'kmw__redirect_faq' );


/* ====================================
	change FAQ archive loop:
	show 100 questions minimum
	change order
--------------------------------------------------------------------- */
if ( ! function_exists('kmw__edit_faq_archive_loop') ) {
	function kmw__edit_faq_archive_loop( $query ){
		if( ! is_admin()
			&& $query->is_post_type_archive( 'web-faq' )
			&& $query->is_main_query() ){
				$query->set( 'posts_per_page', 100 );
				$query->set( 'orderby', 'date' );
				$query->set( 'order', 'ASC' );
		}
	}
}
add_action( 'pre_get_posts', 'kmw__edit_faq_archive_loop' );


/* ====================================
	register custom taxonomy for FAQ
	before the CPT to prevent weird-ass 404s??
--------------------------------------------------------------------- */
if ( ! function_exists('kmw_get_faq_taxonomy') ) {
	function kmw_get_faq_taxonomy() {
		$labels = array(
			'name'                       => _x( 'FAQ Categories', 'Taxonomy General Name', '_s' ),
			'singular_name'              => _x( 'FAQ Category', 'Taxonomy Singular Name', '_s' ),
			'menu_name'                  => __( 'FAQ Type', '_s' ),
			'all_items'                  => __( 'All Types', '_s' ),
			'parent_item'                => __( 'Parent Type', '_s' ),
			'parent_item_colon'          => __( 'Parent Type:', '_s' ),
			'new_item_name'              => __( 'New Type', '_s' ),
			'add_new_item'               => __( 'Add New Type', '_s' ),
			'edit_item'                  => __( 'Edit Type', '_s' ),
			'update_item'                => __( 'Update Type', '_s' ),
			'view_item'                  => __( 'View Type', '_s' ),
			'separate_items_with_commas' => __( 'Separate types with commas', '_s' ),
			'add_or_remove_items'        => __( 'Add or remove type', '_s' ),
			'choose_from_most_used'      => __( 'Choose from the most used', '_s' ),
			'popular_items'              => __( 'Popular Types', '_s' ),
			'search_items'               => __( 'Search Types', '_s' ),
			'not_found'                  => __( 'Not Found', '_s' ),
		);
		$rewrite = array(
			'slug'                       => 'web-development-faq',
			'with_front'                 => true ,
			'hierarchical'               => false,
		);
		$args = array(
			'labels'                     => $labels,
			'hierarchical'               => 'true',
			'public'                     => true,
			'show_ui'                    => true,
			'show_admin_column'          => true,
			'show_in_nav_menus'          => true,
			'show_tagcloud'              => true,
			'rewrite'                    => $rewrite,
		);
		register_taxonomy( 'faq_category', array( 'web-faq' ), $args );
	}
}
add_action( 'init', 'kmw_get_faq_taxonomy', 0 );


/* ====================================
	register FAQ custom post type
--------------------------------------------------------------------- */

if ( ! function_exists('kmw_get_faq') ) {
	function kmw_get_faq() {
		$labels = array(
			'name'                => _x( 'Frequently Asked Questions', 'Post Type General Name', '_s' ),
			'singular_name'       => _x( 'FAQ Item', 'Post Type Singular Name', '_s' ),
			'menu_name'           => __( 'FAQ', '_s' ),
			'name_admin_bar'      => __( 'FAQ', '_s' ),
			'parent_item_colon'   => __( 'FAQ', '_s' ),
			'all_items'           => __( 'FAQ', '_s' ),
			'add_new_item'        => __( 'Add New FAQ', '_s' ),
			'add_new'             => __( 'Add FAQ', '_s' ),
			'new_item'            => __( 'New FAQ', '_s' ),
			'edit_item'           => __( 'Edit FAQ', '_s' ),
			'update_item'         => __( 'Update FAQ', '_s' ),
			'view_item'           => __( 'View FAQ', '_s' ),
			'search_items'        => __( 'Search FAQ', '_s' ),
			'not_found'           => __( 'Not found', '_s' ),
			'not_found_in_trash'  => __( 'Not found in Trash', '_s' ),
		);
		$rewrite = array(
			'slug'                => 'faq',
			'with_front'          => true,
			'pages'               => true,
			'feeds'               => true,
		);
		$args = array(
			'label'               => __( 'web-faq', '_s' ),
			'description'         => __( 'Web design and development FAQ items.', '_s' ),
			'labels'              => $labels,
			'supports'            => array( 'title', 'editor', 'thumbnail', 'revisions', ),
			'hierarchical'        => false,
			'public'              => true,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'menu_position'       => 20,
			'menu_icon'           => 'dashicons-editor-help',
			'show_in_admin_bar'   => true,
			'show_in_nav_menus'   => true,
			'can_export'          => true,
			'has_archive'         => true,
			'exclude_from_search' => false,
			'publicly_queryable'  => true,
			'rewrite'             => $rewrite,
			    'capabilities' => array(
					'edit_post' => 'edit_faq',
					'edit_posts' => 'edit_faqs',
					'edit_others_posts' => 'edit_other_faqs',
					'publish_posts' => 'publish_faqs',
					'read_post' => 'read_faq',
					'read_private_posts' => 'read_private_faqs',
					'delete_post' => 'delete_faq'
				),
			'map_meta_cap' => true

		);
		register_post_type( 'web-faq', $args );
	}
}
add_action( 'init', 'kmw_get_faq', 0 );


if ( ! function_exists('kmw_add_faq_caps') ) {
	function kmw_add_faq_caps() {
		// gets the administrator role
		$admins = get_role( 'administrator' );

		$admins->add_cap( 'edit_faq' ); 
		$admins->add_cap( 'edit_faqs' ); 
		$admins->add_cap( 'edit_other_faqs' ); 
		$admins->add_cap( 'publish_faqs' ); 
		$admins->add_cap( 'read_faq' ); 
		$admins->add_cap( 'read_private_faqs' ); 
		$admins->add_cap( 'delete_faq' ); 
	}
}
add_action( 'admin_init', 'kmw_add_faq_caps');