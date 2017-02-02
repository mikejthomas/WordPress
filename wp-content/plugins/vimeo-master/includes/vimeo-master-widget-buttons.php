<?php
//Hook Widget
add_action( 'widgets_init', 'vimeo_master_widget_buttons' );
//Register Widget
function vimeo_master_widget_buttons() {
register_widget( 'vimeo_master_widget_buttons' );
}

class vimeo_master_widget_buttons extends WP_Widget {
	function __construct(){
	$widget_ops = array( 'classname' => 'Vimeo Master Button', 'description' => __('Vimeo Master Button Widget allows you to display the Vimeo Connect to Profile Buttons. ', 'Vimeo Master Button') );
	$control_ops = array( 'width' => 300, 'height' => 350, 'id_base' => 'vimeo_master_widget_buttons' );
	parent::__construct( 'vimeo_master_widget_buttons', __('Vimeo Master Button', 'vimeo_master'), $widget_ops, $control_ops );
	}
	
	function widget( $args, $instance ) {
		extract( $args );
		//Our variables from the widget settings.
		$vimeo_title = isset( $instance['vimeo_title'] ) ? $instance['vimeo_title'] :false;
		$vimeo_title_new = isset( $instance['vimeo_title_new'] ) ? $instance['vimeo_title_new'] :false;
		$vimeospacer ="'";
		$show_vimeobutton = isset( $instance['show_vimeobutton'] ) ? $instance['show_vimeobutton'] :false;
		$vimeobutton_page = $instance['vimeobutton_page'];
		echo $before_widget;
		
	// Display the widget title
		if ( $vimeo_title ){
			if (empty ($vimeo_title_new)){
			$vimeo_title_new = constant('VIMEO_MASTER_NAME');
			echo $before_title . $vimeo_title_new . $after_title;
		}
		else{
			echo $before_title . $vimeo_title_new . $after_title;
		}
	}
	else{
	}
	//Display Vimeo Profile Button
		if ( $show_vimeobutton ){
		$url_loc = plugins_url();
		echo '<div style="padding-bottom:3px;">' .
		'<a href="'.$vimeobutton_page.'" target="_blank"><img src="'.$url_loc.'/vimeo-master/images/techgasp-vimeo-button.png"></a>' .
		'</div>';
		}
		else{
		}
	echo $after_widget;
	}
	//Update the widget
	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		//Strip tags from title and name to remove HTML
		$instance['down_link_vimeo'] = $new_instance['down_link_vimeo'];
		update_option('down_link_vimeo', $new_instance['down_link_vimeo']);
		$instance['vimeo_title'] = strip_tags( $new_instance['vimeo_title'] );
		$instance['vimeo_title_new'] = $new_instance['vimeo_title_new'];
		$instance['show_vimeobutton'] = $new_instance['show_vimeobutton'];
		$instance['vimeobutton_page'] = $new_instance['vimeobutton_page'];
		return $instance;
	}
	function form( $instance ) {
	$plugin_master_name = constant('VIMEO_MASTER_NAME');
	//Set up some default widget settings.
	$defaults = array( 'vimeo_title_new' => __('Vimeo Master', 'Vimeo master'), 'vimeo_title' => true, 'vimeo_title_new' => false, 'show_vimeobutton' => false, 'vimeobutton_page' => false );
	$instance = wp_parse_args( (array) $instance, $defaults );
	?>
		<br>
		<b>Check the buttons to be displayed:</b>
	<p>
	<img src="<?php echo plugins_url('images/techgasp-minilogo-16.png', dirname(__FILE__)); ?>" style="float:left; height:18px; vertical-align:middle;" />
	&nbsp;
	<input type="checkbox" <?php checked( (bool) $instance['vimeo_title'], true ); ?> id="<?php echo $this->get_field_id( 'vimeo_title' ); ?>" name="<?php echo $this->get_field_name( 'vimeo_title' ); ?>" />
	<label for="<?php echo $this->get_field_id( 'vimeo_title' ); ?>"><b><?php _e('Display Widget Title', 'vimeo_master'); ?></b></label></br>
	</p>
	<p>
	<label for="<?php echo $this->get_field_id( 'vimeo_title_new' ); ?>"><?php _e('Change Title:', 'vimeo_master'); ?></label>
	<br>
	<input id="<?php echo $this->get_field_id( 'vimeo_title_new' ); ?>" name="<?php echo $this->get_field_name( 'vimeo_title_new' ); ?>" value="<?php echo $instance['vimeo_title_new']; ?>" style="width:auto;" />
	</p>
<div style="background: url(<?php echo plugins_url('images/techgasp-hr.png', dirname(__FILE__)); ?>) repeat-x; height: 10px"></div>
	<p>
	<img src="<?php echo plugins_url('images/techgasp-minilogo-16.png', dirname(__FILE__)); ?>" style="float:left; width:18px; vertical-align:middle;" />
	&nbsp;
	<input type="checkbox" <?php checked( (bool) $instance['show_vimeobutton'], true ); ?> id="<?php echo $this->get_field_id( 'show_vimeobutton' ); ?>" name="<?php echo $this->get_field_name( 'show_vimeobutton' ); ?>" />
	<label for="<?php echo $this->get_field_id( 'show_vimeobutton' ); ?>"><b><?php _e('Vimeo Profile Button', 'vimeo_master'); ?></b></label></br>
	</p>
	<p>
	<label for="<?php echo $this->get_field_id( 'vimeobutton_page' ); ?>"><?php _e('insert your Vimeo Profile Link:', 'vimeo_master'); ?></label></br>
	<input id="<?php echo $this->get_field_id( 'vimeobutton_page' ); ?>" name="<?php echo $this->get_field_name( 'vimeobutton_page' ); ?>" value="<?php echo $instance['vimeobutton_page']; ?>" style="width:auto;" />
	</p>
<div style="background: url(<?php echo plugins_url('images/techgasp-hr.png', dirname(__FILE__)); ?>) repeat-x; height: 10px"></div>
		<p>
		<img src="<?php echo plugins_url('images/techgasp-minilogo-16.png', dirname(__FILE__)); ?>" style="float:left; width:18px; vertical-align:middle;" />
		&nbsp;
		<b><?php echo $plugin_master_name; ?> Website</b>
		</p>
		<p><a class="button-secondary" href="https://wordpress.techgasp.com/vimeo-master/" target="_blank" title="<?php echo $plugin_master_name; ?> Info Page">Info Page</a> <a class="button-secondary" href="https://wordpress.techgasp.com/vimeo-master-documentation/" target="_blank" title="<?php echo $plugin_master_name; ?> Documentation">Documentation</a> <a class="button-primary" href="https://wordpress.techgasp.com/vimeo-master/" target="_blank" title="Visit Website">Get Add-ons</a></p>
	<?php
	}
 }
?>
