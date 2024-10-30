<?php
/**
 * BlinkoTV Broadcaster Plugin manage page
 * @author Eyepartner Development Team
 * @version 1.5.1
 * @package blinko-broadcaster
 * 
 */

// Check whether user can manage this section
if( !current_user_can('manage_broadcaster') ) {
	die( __("Access Denied on this section of the administrative area.", $bPluginDomain) );
}

if ( $okRequirements ) {
	
	if ( broadcasterIsSetUp() ) {
		$bpage_id = getBroadcasterPageId();
		$siteurl = get_option('siteurl'); 
		$content = __( "Your BlinkoTV Broadcaster is set up.", $bPluginDomain)."
		<br /><br />".
		__( "Go here to see your BlinkoTV Player in action:", $bPluginDomain)."
		<br />				
		<a href='../?page_id={$bpage_id}'>".__( "Check out your player", $bPluginDomain)."</a><br /><br />".__("Go here to customize your BlinkoTV Player page:", $bPluginDomain)."
		<br />
		<a href='page.php?action=edit&post={$bpage_id}'>".__( "Customize your player page", $bPluginDomain)."</a><br /><br />".
		__("Go here for Blinko Player appearance and settings:", $bPluginDomain)."<br />
		<a href='admin.php?page={$bPluginFolder}blinko-broadcaster-skins.php'>".
		__( "Customize appearance/settings", $bPluginDomain)."</a><br /><br />".
		__( "Refer to this page for further information:", $bPluginDomain)."<br />
		<a href='admin.php?page={$bPluginFolder}blinko-broadcaster-help.php'>".
		__( "Help", $bPluginDomain)."</a><br /><br />
		
		".__("Share the broadcast page with your friends:", $bPluginDomain)."<br /><a href='../?page_id={$bpage_id}'>{$siteurl}/?page_id={$bpage_id}</a>".
		"<div style='margin-top:20px;'>".__( "This plugin is brought to you by Blinko.", $bPluginDomain)."</div>";

	
	} else {
		$content = __( "Your BlinkoTV Broadcaster is not setup yet.", $bPluginDomain)."<br />
		<a href='admin.php?page={$bPluginFolder}blinko-broadcaster-setup.php'>".
		__( "Setup your broadcaster", $bPluginDomain)."</a>";

	}
}

$sectionTitle = __( "Manage BlinkoTV Player", $bPluginDomain);
include "footer.php";