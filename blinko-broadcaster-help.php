<?php
/**
 * BlinkoTV Broadcaster Help page 
 * 
 * Contains useful links and information about the BlinkoTV Broadcaster Plugin
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
	
	$setupHelp = soapCall( "getHelpPage", array() );
				
	$content .= $setupHelp;
}

$sectionTitle = __( "BlinkoTV Broadcaster Plugin Help", $bPluginDomain);
include "footer.php";