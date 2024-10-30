<?php
/**
 * BlinkoTV Broadcaster Functions page
 * @author Eyepartner development team
 * @version 1.5
 * @package blinko-broadcaster
 * 
 */

/**
 * Alias of callSoap and error/exceptions setting
 * 
 */
function soapCall( $methodName, $args=array(), $setError="" ) {
	
	global $soapClient;
	
	$result = $soapClient->call( $methodName, $args );
	
	if ( $soapClient->fault ) {	
	
		handleException( $result );	
		if ( $setError ) {
			handleError( $setError );
		}			
	}
	return $result;
}
/**
 * Returns true if broadcaster was not setup, false otherwise 
 * @return bool
 */

function broadcasterIsSetUp() {
	
	global $wpdb;
	$setupStage = getSetupStage();
	
	if ( $setupStage > 1 ) {
		return true;		
	}
	return false;
}

/**
 * Returns stage for Setup page: 0 = no setup has taken place, 1 = account created, must confirm account, 2 - setup complete
 *
 * @return int
 */
function getSetupStage() {
	
	global $wpdb;
	
	$test_table_exists = $wpdb->get_results("SHOW TABLES LIKE '$wpdb->broadcaster'");
	if ( empty($test_table_exists) ) {
		
		return 0;
		
	}
	
	$test_broadcaster = $wpdb->get_results("SELECT * FROM $wpdb->broadcaster");
	
	if ( empty($test_broadcaster) ) {
		return 0;		
	} else {
		
		if ( 0 == $test_broadcaster[0]->page_id ) {
			return 1;
		} else {
			return 2;
		}
		
	}
	return 0;
	
}

/**
 * Returns broadcaster page (post) id
 * @return int
 */

function getBroadcasterPageId() {
	global $wpdb;
	
	$broadcaster = $wpdb->get_results("SELECT * FROM $wpdb->broadcaster LIMIT 1");	
	
	if ( !empty($broadcaster) ) {
	
		return $broadcaster[0]->page_id;
	}
	return 0;
}


/**
 *  Returns broadcaster page content (broadcaster embed code)
 * @return string
 */

function getBroadcasterContent() {
	global $wpdb;
	
	$broadcaster = $wpdb->get_results("SELECT * FROM $wpdb->posts AS A, $wpdb->broadcaster AS B where A.ID=B.page_id LIMIT 1");	

	if ( !empty($broadcaster) ) {
	
		return $broadcaster[0]->post_content;
	}
	return "";
}

/**
 * Returns account id corresponding to present broadcaster setup
 * @return int
 */

function getBroadcastingAccountId() {
	global $wpdb;
	
	$user =  $wpdb->get_results("SELECT user_id FROM $wpdb->broadcaster LIMIT 1");		
	if ( !empty($user) ) {
	
		return $user[0]->user_id;
	}
	return 0;
}


/**
 * Returns broadcaster skin id
 * @return int
 */

function getBroadcasterSelectedSkinId() {
	global $wpdb;
	
	$user =  $wpdb->get_results("SELECT * FROM $wpdb->broadcaster LIMIT 1");	
	
	if ( !empty($user) ) {
	
		return $user[0]->skin_id;
	}
	return 0;
}


/**
 * Returns broadcaster setting - account_id, page_id and skin_id from broadcaster table  *
 * @return mixed
 */

function getBroadcasterSettings() {
	global $wpdb;
	
	$bSettings =  $wpdb->get_results("SELECT * FROM $wpdb->broadcaster LIMIT 1");	
	if ( !empty($bSettings) ) {
		return $bSettings[0];
	}
	return "";
	
}


/**
 * Cleans up broadcaster embed code 
 * @param string $embed
 * @return string
 */

function cleanUpBroadcasterEmbedCode( $embed ) {
	
	$embed = preg_replace( array("/>\n/", "/\n/", "/\s+/"), array(">", " ", " "), $embed );
	return $embed;
}

/**
 * Checkes exception and handles it
 *
 * @param Exception $e
 */
function handleException( $e ) {
	global $wpBroabcasterExceptions, $bPluginDomain;
	if ( is_object($e) ) {
		$faultcode = $e->faultcode;
		$faultstring = $e->faultstring;
	} else {
		$faultcode = $e['faultcode'];
		$faultstring = $e['faultstring'];
	}
	if ( strpos( $faultcode, "Sender")!==false ||  strpos( $faultcode, "Receiver")!==false) {
		$wpBroabcasterExceptions[] = $faultstring;
	} else {
		$wpBroabcasterExceptions[] = __( "Unknown error occured", $bPluginDomain);		
	}
	
}

/**
 * Returns all stored exceptions
 *
 * @return string
 */

function getAllExceptions() {
	global $wpBroabcasterExceptions;
	
	$ret = "";
	if ( count($wpBroabcasterExceptions) > 0 ) {
		
		$ret = implode( "<br />", $wpBroabcasterExceptions)."<br />";
		
	} 
	return $ret;
}
/**
 * Returns single specific stored exception
 *
 * @param int $index
 * @return string
 */

function getSingleException( $index = 0 ) {
	global $wpBroabcasterExceptions;
	
	$ret = "";
	if ( isset( $wpBroabcasterExceptions[$index] ) ) {
		$ret = "<div class='updated fade broadcaster_exceptions'><p>".$wpBroabcasterExceptions[$index].
		"</p></div>";
		
	} 
	return $ret;
}

/**
 * Handles error
 *
 * @param string $errorMsg
 */
function handleError( $errorMsg ) {
	global $wpBroabcasterErrors;
	
	$wpBroabcasterErrors[] = $errorMsg;
	
}

/**
 * Returns all stored errors
 *
 * @return string
 */

function getAllErrors() {
	global $wpBroabcasterErrors;
	
	$ret = "";
	if ( count($wpBroabcasterErrors) > 0 ) {
		$ret = implode( "<br />", $wpBroabcasterErrors)."<br />";
		
	} 
	return $ret;
}


/**
 * Get output message for user examining the exceptions, errors and success messages
 *
 * @return string
 */

function getOutputMessages() {
	
	global $success, $bPluginDomain;
	
	$ret = "";
	
	// get all occured exceptions and errors
	$occuredExceptions = getAllExceptions();
	$occuredErrors = getAllErrors();
	
	if ( $occuredErrors || $occuredExceptions ) {
		
		$ret = "<div class='updated fade broadcaster_errors'><p>".__( "Sorry, could not perform action, following error(s) occured:", $bPluginDomain)."<br />".$occuredExceptions.$occuredErrors."</p></div>";
		
	} else if ( !empty($success) ) {
		
		$ret = "<div class='updated fade broadcaster_success'><p>{$success}</p></div>";
	}
	
	return $ret;
}
/**
 * Checks whether the chat is enabled 
 *
 * @return bool
 */
function hasChatEnabled() {
	global $wpdb;
	
	$broadcaster = $wpdb->get_results("SELECT * FROM $wpdb->posts AS A, $wpdb->broadcaster AS B where A.ID=B.page_id LIMIT 1");	

	if ( !empty($broadcaster) ) {
	
		$exists1 = strpos( $broadcaster[0]->post_content, 'id="chaty"');
		$exists2 = strpos( $broadcaster[0]->post_content, "id='chaty'");
		// this one for the special wordpress plugin
		$exists3 = strpos( $broadcaster[0]->post_content, "showOnlineClients");
		if ( $exists1!==false || $exists2!==false || $exists3!==false ) {
			return true;
		}
	}
	return false;
}