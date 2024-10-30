<?php
/*
Plugin Name: BlinkoTV Broadcaster
Plugin URI: http://blog.eyepartner.com/?p=121
Description: The BlinkoTV Broadcaster Plugin gives any WordPress user the opportunity to create their own broadcasting station, by signing up for a BlinkoTV account, all through a user-friendly interface in the Admin Panel.
Version: 1.5.1
Author: Eyepartner Development Team
Author URI: http://www.eyepartner.com/
*/

/*  Copyright 2008  Eyepartner  (email : contact@eyepartner.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

/**
 * 
 * BlinkoTV Broadcaster Plugin
 * 
 * @author Eyepartner Development Team
 * @version 1.5.1
 * @package blinko-broadcaster
 * 
 */

require "blinko-broadcaster-config.php";

//
// ADD ACTIONS HOOKS
//

// activate plugin
add_action('activate_blinko-broadcaster/blinko-broadcaster.php', 'activateBroadcastPlugin');

// build plugin menu and submenus
add_action( "admin_menu", "createBroadcastMenu");

// add translation
add_action('init', 'broadcasterTextDomain');

// add style and script to admin header
add_action('admin_head', 'addStylingAndScripts');

// save clean broadcasting embed code
add_action( "save_post", cleanSaveBroadcasterPage);

/**
 * Load translation text domain
 *
 */
function broadcasterTextDomain() {
	
	global $bPluginDomain, $bPluginFolder;
	
	load_plugin_textdomain( $bPluginDomain, 'wp-content/plugins/'.substr( $bPluginFolder, 0, -1));
}


/**
 * 
 * Add plugin style
 */

function addStylingAndScripts() {
	
	global $bPluginFolder;
	
	$siteurl = get_option('siteurl');
	
	echo '<link rel="stylesheet" href="'.$siteurl.'/wp-content/plugins/'.$bPluginFolder.'style.css" type="text/css" media="screen" />'."\n";	
}


/**
 * 
 * On activating the Broadcaster Plugin - create all dependecies - table and capabilities
 *
 */
function activateBroadcastPlugin() {
	
	global $wpdb;
	
	if(@is_file(ABSPATH.'/wp-admin/upgrade-functions.php')) {
		include_once(ABSPATH.'/wp-admin/upgrade-functions.php');
	} elseif(@is_file(ABSPATH.'/wp-admin/includes/upgrade.php')) {
		include_once(ABSPATH.'/wp-admin/includes/upgrade.php');
	} else {
		die('We have problem finding your \'/wp-admin/upgrade-functions.php\' and \'/wp-admin/includes/upgrade.php\'');
	}

	$charset_collate = '';
	if( $wpdb->supports_collation()) {
		if(!empty($wpdb->charset)) {
			$charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
		}
		if(!empty($wpdb->collate)) {
			$charset_collate .= " COLLATE $wpdb->collate";
		}
	}
	$create_table = array();
	// create table statemant
	$create_table['broadcaster'] = "CREATE TABLE $wpdb->broadcaster (".
									"user_id int(10) NOT NULL,".
									"page_id int(10) NOT NULL,".
									"skin_id int(10) NOT NULL,".							
									"PRIMARY KEY (user_id)) $charset_collate;";
	
	// run create table
	maybe_create_table($wpdb->broadcaster, $create_table['broadcaster']);
	
	// Set 'manage_broadcaster' Capabilities To Administrator	
	$role = get_role('administrator');
	if(!$role->has_cap('manage_broadcaster')) {
		$role->add_cap('manage_broadcaster');
	}
	
}


/**
 * 
 * Create Broadcaster Plugin Menu and Submenus
 */
function createBroadcastMenu() {
	
	global $bPluginDomain, $bPluginFolder;
	
	// Broadcaster menu
	add_menu_page( __('Broadcaster', $bPluginDomain), __('BlinkoTV Player', $bPluginDomain), "manage_broadcaster", $bPluginFolder."blinko-broadcaster-manager.php");
	$manager_page = $bPluginFolder."blinko-broadcaster-manager.php";
	// Manage submenu
	add_submenu_page( $manager_page, __('Manage', $bPluginDomain), __('Manage', $bPluginDomain), "manage_broadcaster", $manager_page);
	
	// Setup submenu
	add_submenu_page( $manager_page, __('Setup', $bPluginDomain), __('Setup', $bPluginDomain), "manage_broadcaster", $bPluginFolder."blinko-broadcaster-setup.php");
	
	// Skins submenu
	add_submenu_page( $manager_page, __('Skins/Settings', $bPluginDomain), __('Skins/Settings', $bPluginDomain), "manage_broadcaster", $bPluginFolder."blinko-broadcaster-skins.php");
	
	// Help submenu
	add_submenu_page( $manager_page, __('Help', $bPluginDomain), __('Help', $bPluginDomain), "manage_broadcaster", $bPluginFolder."blinko-broadcaster-help.php");
	
	
}


/**
 * 
 * When the user inserts the embed code directly into the page editor, saves it and acceses the page, any \n will be replaced with <br /> so i replace them with empty spaces - the function checks if the post being saved is the broadcasting page - if not - no changes are made, if yes, save clean embed code
 * 
 * @param int $post_ID
 * 
 */

function cleanSaveBroadcasterPage( $post_ID ) {
	
	global $wpdb;
	global $soapClient;
	
	$broadcasterId = getBroadcasterPageId();
	
	// if the post being updated is the brodcaster page then clean the embed code and save it
	if ( $broadcasterId == $post_ID ) {
		
		$sql = "SELECT post_content FROM $wpdb->posts WHERE ID='{$post_ID}' LIMIT 1";
		$record = $wpdb->get_row($sql);
		
		if ( !is_null($record) || empty($record)) {
			
			$embedCode = $record->post_content;
			
			// clean up just the embed code not the whole content
			
			$embedCode = preg_replace_callback( 
		        "/<object(.|\s)*<\/object>/",
		        create_function(		            
		            '$matches',
		            'return cleanUpBroadcasterEmbedCode($matches[0]);'
		        ),
		        $embedCode
		    );
		    
		    
		    // update with new clean content
			$wpdb->update( $wpdb->posts, array( "post_content" => $embedCode), array( "ID" => $post_ID));
			
			// try to identify the skin_id and save it to broadcaster table
			preg_match_all( "/\/([a-zA-Z0-9]+)\.swf/", $embedCode, $matches);			
			
			if ( count($matches)>0 ) {					
				$user_id = getBroadcastingAccountId();
				// get all available skins
				$availableSkins = soapCall( "getAvailableSkins", array( $user_id ));
				// identify skin between available skins
				$newSkinId = 0;
				foreach ( $availableSkins as $skin ) {
					
					if ( $skin->skin_name == $matches[1][0]) {
						
						$newSkinId = $skin->skin_id;
						break;
					}
					
				}
				if ( 0 != $newSkinId ) {
					// update new skin_id to broadcaster settings
					$update_settings = $wpdb->update( $wpdb->broadcaster, array( "skin_id" => $newSkinId), array( "user_id"=> $user_id) );
				}				
			}
			
			
			
			
		}
	}
	
}