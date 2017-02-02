<?php
/**
Plugin Name: Vimeo Master
Plugin URI: http://wordpress.techgasp.com/vimeo-master/
Version: 5.0.4
Author: TechGasp
Author URI: http://wordpress.techgasp.com
Text Domain: vimeo-master
Description: Vimeo Master for let's you integrate the superb Vimeo Video quality into any Wordpress widget position. Only for professional websites.
License: GPL2 or later
*/
/* Copyright 2013 TechGasp  (email : info@techgasp.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/
if(!class_exists('vimeo_master')) :
///////DEFINE///////
define( 'VIMEO_MASTER_VERSION', '5.0.4' );
define( 'VIMEO_MASTER_NAME', 'Vimeo Master' );

class vimeo_master{
public static function content_with_quote($content){
$quote = '<p>' . get_option('tsm_quote') . '</p>';
	return $content . $quote;
}
//SETTINGS LINK IN PLUGIN MANAGER
public static function vimeo_master_links( $links, $file ) {
if ( $file == plugin_basename( dirname(__FILE__).'/vimeo-master.php' ) ) {
		if( is_network_admin() ){
		$techgasp_plugin_url = network_admin_url( 'admin.php?page=vimeo-master' );
		}
		else {
		$techgasp_plugin_url = admin_url( 'admin.php?page=vimeo-master' );
		}
	$links[] = '<a href="' . $techgasp_plugin_url . '">'.__( 'Settings' ).'</a>';
	}
	return $links;
}

//END CLASS
}
add_filter('the_content', array('vimeo_master', 'content_with_quote'));
add_filter( 'plugin_action_links', array('vimeo_master', 'vimeo_master_links'), 10, 2 );
endif;

// HOOK ADMIN
require_once( dirname( __FILE__ ) . '/includes/vimeo-master-admin.php');
// HOOK ADMIN ADDONS
require_once( dirname( __FILE__ ) . '/includes/vimeo-master-admin-addons.php');
// HOOK ADMIN WIDGETS
require_once( dirname( __FILE__ ) . '/includes/vimeo-master-admin-widgets.php');
// HOOK WIDGET BUTTONS
require_once( dirname( __FILE__ ) . '/includes/vimeo-master-widget-buttons.php');
