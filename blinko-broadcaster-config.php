<?php
/**
 * BlinkoTV Broadcaster configuration file
 * @author Eyepartner Development Team
 * @version 1.5.1
 * @package blinko-broadcaster
 * 
 */

// disable WSDL caching
ini_set( "soap.wsdl_cache_enabled", 0);

// include this file to gain access to user data functions
require_once(ABSPATH.'wp-includes/pluggable.php');		

// settings
$bPluginFolder = "blinko-broadcaster/";
$bPluginDomain = "blinko-broadcaster";

define('HTML_CHECKED', ' checked="checked"');
$get_broadcaster = __( "Download BlinkoTV Broadcaster desktop application", $bPluginDomain);

global $wpdb;
$wpdb->broadcaster = $wpdb->prefix.'broadcaster';

// dependencies
require_once "functions.php";

// web service description
$wsdl = "http://www.blinkotv.com/services/wordpress/blinkotv.wsdl";

// exceptions, errors and success variables
$wpBroabcasterExceptions = array();
$wpBroabcasterErrors = array();
$success = "";

$okRequirements = true;

// avoid existing class conflicts - if this class exists - use it
if ( !class_exists("nusoap")) {
	include_once "lib/nusoap.php";
}
$soapClient = new nusoap_client( $wsdl, 'wsdl',	"", "", "", "");

$test_table_exists = $wpdb->get_results("SHOW TABLES LIKE '".$wpdb->broadcaster."'");
if ( empty($test_table_exists) ) {
	
	$okRequirements = false;
	handleError( __( "Table required for this plugin not found. Try deactivating/activating the plugin.", $bPluginDomain)."<br />".__("Also check out Required WordPress Version.", $bPluginDomain) );	
	
}	
	
if ( $soapClient )	{
	// if broadcaster is set up - check its status - the account may no longer be valid or it may be expired
	 if ( broadcasterIsSetUp() ) {
	 	
	 	//
		// check the status of the evaluation/paid account - it may be expired, maybe it just doesn't exist (anymore) or maybe no more days left from paid subscription
		//
		$broadcaster_account_id = getBroadcastingAccountId();		
		
		// check user account status - if the call does not throw an exception - it's ok		
		$result = $accountStatus = $soapClient->call( "getUserAccountStatus", array( $broadcaster_account_id ) );
		if ( $soapClient->fault ) {
			$okRequirements = false;
			
			handleException( $result );
			$accountStatus = "";			
		}
	}
	
} else {
	
	// any exception is thrown in this part must be treated as fatal error
	$okRequirements = false;
	$e = __( "Could not set up soap connection or another error occured.", $bPluginDomain);
	handleException( $e );
	$content .= getSingleException( );	
	unset( $wpBroabcasterExceptions );
}

