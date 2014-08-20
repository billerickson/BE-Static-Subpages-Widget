<?php
/*
Plugin Name: BE Static Subpages Widget
Plugin URI: http://www.billerickson.net
Description: Select a page, and widget will list that page's subpages
Version: 1.0
Author: Bill Erickson
Author URI: http://www.billerickson.net
License: GPLv2
*/

/** 
 * Register Widget
 *
 */
function be_static_subpages_load_widgets() {
	register_widget( 'BE_Static_Subpages_Widget' );
}
add_action( 'widgets_init', 'be_static_subpages_load_widgets' );

/**
 * Subpages Widget Class
 *
 * @author       Bill Erickson <bill@billerickson.net>
 * @copyright    Copyright (c) 2011, Bill Erickson
 * @license      http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */
class BE_Static_Subpages_Widget extends WP_Widget {
	
    /**
     * Constructor
     *
     * @return void
     **/
	function BE_Static_Subpages_Widget() {
		load_plugin_textdomain( 'be-static-subpages', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
		$widget_ops = array( 'classname' => 'widget_static_subpages', 'description' => __( 'Select a page, and widget will list that page\'s subpages', 'be-static-subpages' ) );
		$this->WP_Widget( 'static-subpages-widget', __( 'Static Subpages Widget', 'be-static-subpages' ), $widget_ops );
	}

    /**
     * Outputs the HTML for this widget.
     *
     * @param array, An array of standard parameters for widgets in this theme 
     * @param array, An array of settings for this widget instance 
     * @return void Echoes it's output
     **/
	function widget( $args, $instance ) {
	
		extract( $args, EXTR_SKIP );
		
		$page = (int) $instance['page'];
		if( empty( $page ) )
			return;
		
		// Build a menu listing top level parent's children
		$args = array(
			'child_of' => $page,
			'parent' => $page,
			'sort_column' => 'menu_order',
			'post_type' => 'page',
		);
		$depth = 1;
		$subpages = get_pages( apply_filters( 'be_static_subpages_widget_args', $args, $depth ) );
		
		// If there are pages, display the widget
		if ( empty( $subpages ) ) 
			return;
			
		echo $before_widget;
		
		// Build title
		$title = esc_attr( $instance['title'] );
		if( 1 == $instance['title_from_parent'] ) {
			$title = get_the_title( $page );
			if( 1 == $instance['title_link'] )
				$title = '<a href="' . get_permalink( $page ) . '">' . apply_filters( 'be_static_subpages_widget_title', $title ) . '</a>';
		}	

		if( !empty( $title ) ) 
			echo $before_title . $title . $after_title;
		
		// Print the tree
		$active = is_singular() && ! $instance['active'] ? get_the_ID() : $instance['active'];
		$this->build_subpages( $subpages, $page, $active );
		
		echo $after_widget;			
	}
	
	/**
	 * Build the Subpages
	 *
	 * @param array $subpages, array of post objects
	 * @param array $parents, array of parent IDs
	 * @param bool $deep_subpages, whether to include current page's subpages
	 * @return string $output
	 */
	function build_subpages( $subpages, $page, $active ) {
		if( empty( $subpages ) )
			return;
			
		// Build the page listing	
		echo '<ul>';
		foreach ( $subpages as $subpage ) {
			$class = array();
			
			// Set special class for current page
			if ( $subpage->ID == $active )
				$class[] = 'widget_subpages_current_page';
						
			$class = apply_filters( 'be_static_subpages_widget_class', $class, $subpage );
			$class = !empty( $class ) ? ' class="' . implode( ' ', $class ) . '"' : '';

			echo '<li' . $class . '><a href="' . get_permalink( $subpage->ID ) . '">' . apply_filters( 'be_subpages_page_title', $subpage->post_title, $subpage ) . '</a></li>';

		}
		echo '</ul>';
	}

	/**
	 * Sanitizes form inputs on save
	 * 
	 * @param array $new_instance
	 * @param array $old_instance
	 * @return array $new_instance
	 */
	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;

		/* Strip tags (if needed) and update the widget settings. */
		$instance['page'] = (int) $new_instance['page'];
		$instance['active'] = (int) $new_instance['active'];
		$instance['title'] = esc_attr( $new_instance['title'] );
		$instance['title_from_parent'] = (int) $new_instance['title_from_parent'];
		$instance['title_link'] = (int) $new_instance['title_link'];
		
		return $instance;
	}

	/**
	 * Build the widget's form
	 *
	 * @param array $instance, An array of settings for this widget instance 
	 * @return null
	 */
	function form( $instance ) {

		/* Set up some default widget settings. */
		$defaults = array( 'page' => false, 'active' => false, 'title' => '', 'title_from_parent' => 0, 'title_link' => 0 );
		$instance = wp_parse_args( (array) $instance, $defaults ); 
			
			$dropdown_args = array(
				'post_type'   => 'page',
				'selected'    => $instance['page'],
				'name'        => $this->get_field_name( 'page' ),
				'sort_column' => 'menu_order, post_title', 
				'echo'        => 0,
			);
			$dropdown_args = apply_filters( 'be_static_subpages_dropdown_args', $dropdown_args );
			echo '<p><label for="' . $this->get_field_id( 'page' ) . '">Show Subpages of:</label><br />' . wp_dropdown_pages( $dropdown_args ) . '</p>';
			
			$active_args = array(
				'post_type'   => 'page',
				'selected'    => $instance['active'],
				'name'        => $this->get_field_name( 'active' ),
				'sort_column' => 'menu_order, post_title', 
				'echo'        => 0,
				'show_option_none' => '--None',
			);
			$active_args = apply_filters( 'be_static_subpages_active_args', $active_args );
			echo '<p><label for="' . $this->get_field_id( 'active' ) . '">Show as Active Page:</label><br />' . wp_dropdown_pages( $active_args ) . '</p>';
		
		?>
		 
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:', 'be-subpages' );?></label>
			<input class="widefat" type="text" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" value="<?php echo $instance['title']; ?>" />
		</p>
		
		<p>
			<input class="checkbox" type="checkbox" value="1" <?php checked( $instance['title_from_parent'], 1 ); ?> id="<?php echo $this->get_field_id( 'title_from_parent' ); ?>" name="<?php echo $this->get_field_name( 'title_from_parent' ); ?>" />
			<label for="<?php echo $this->get_field_id( 'title_from_parent' ); ?>"><?php _e( 'Use top level page as section title.', 'be-subpages' );?></label>
		</p>		

		<p>
			<input class="checkbox" type="checkbox" value="1" <?php checked( $instance['title_link'], 1 ); ?> id="<?php echo $this->get_field_id( 'title_link' ); ?>" name="<?php echo $this->get_field_name( 'title_link' ); ?>" />
			<label for="<?php echo $this->get_field_id( 'title_link' ); ?>"><?php _e( 'Make title a link', 'be-subpages' ); echo '<br /><em>('; _e( 'only if "use top level page" is checked', 'be-subpages' ); echo ')</em></label>';?>
		</p>

		<?php
	}	
}
