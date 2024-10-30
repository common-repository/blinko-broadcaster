<?php
/**
 * BlinkoTV Broadcaster Setup
 * 
 * Setup the broadcaster (broadcasting account and broadcasting page/post)
 * 
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
	
	// get the setup  stage
	$setupStage = getSetupStage();	
	// current user data
	$current_user = wp_get_current_user();
	$current_user_id = $current_user->data->ID;
	
	// handle actions here
	switch ( $setupStage ) {
		
		// broadcaster is not setup yet
		case 0:
			
			if (isset($_POST['signin_account'])) {
			
				//try {		
					// set data to be send for signup
					$domain = "http://".$_SERVER['HTTP_HOST'];
					
					
					$current_user_id = $current_user->data->ID;					
					$userFirstname = $current_user->first_name;
					$userLastname = $current_user->last_name;
					$blogName = get_option( "blogname" );
					$userData = array( "firstname" => $userFirstname, "lastname" => $userLastname, "blogname" => "-".$blogName);					
					// sign up to blinko					
					$setError = __( "Could not create account.", $bPluginDomain);
					$accountId = soapCall( "SignUp", array( $_POST['email'], $domain, $userFirstname, $userLastname, $blogName ), $setError );
					
					if ( is_int($accountId) && 0!==$accountId ) {
						// insert account_id into broadcast table
						$insert_account_id = $wpdb->insert( $wpdb->broadcaster, array( "user_id" => $accountId, "page_id" => 0, "skin_id" => 0) );
						$success = __( "Account created successfully.", $bPluginDomain);
						$accCreatedOk = true;
					} else {
						
						$accCreatedOk = false;
						
					}					
			}
		break;
		
		// activate account
		case 1:			
			
			if (isset($_POST['activate_acc'])) {
				
				$validationNumber = $_POST['validation_number'];
				
				$accountId = ( !isset($accountId) )? getBroadcastingAccountId() : $accountId;				
				
				// confirm account existance	
				$verifyVN = soapCall( "confirmAccount", array( $accountId, $validationNumber) );		
				
				if ( $verifyVN===true ) {
					
					// get default skin id
					$default_skin_id =  soapCall( "getDefaultSkinId", array());
					
					// get broadcaster embed code
					$playerEmbedCode = html_entity_decode( soapCall( "getPlayerEmbedCodeForUser", array( $accountId, $default_skin_id, true) ) );				
					
					$pageEmbedCode = cleanUpBroadcasterEmbedCode( $playerEmbedCode );
					
					// prepare data to be inserted into posts
					$data = array( 
					"post_author" 		=> $current_user_id,
					"post_date" 		=> current_time('mysql'),
					"post_date_gmt" 	=> current_time( 'mysql', 1 ),
					"post_content" 		=> $pageEmbedCode,
					"post_title" 		=> __('My Blinko Show', $bPluginDomain),
					"post_name" 		=> __('my-blinko-show', $bPluginDomain),
					"post_modified" 	=> current_time('mysql'),
					"post_modified_gmt" => current_time( 'mysql', 1 ),
					"post_type" 		=> "page"
					);
					
					// insert data into posts
					$resInsertPost = $wpdb->insert( $wpdb->posts, $data );
					if ( false===$resInsertPost ) {
						
						handleError( __( "Could not create broadcaster page.", $bPluginDomain));
						$accCreatedOk = false;
						
					} else {
						// also insert details about account, broadcaster skin id and broadcaster page
						$post_ID = (int) $wpdb->insert_id;
						
						
						// update broadcaster settings
						$update_settings = $wpdb->update( $wpdb->broadcaster, array( "page_id" => $post_ID, "skin_id" => $default_skin_id), array( "user_id" => $accountId) );
						
						if ( false === $update_settings ) {
							// error occured on update
							handleError( __( "Could not insert info about BlinkoTV Player.", $bPluginDomain));
							$accCreatedOk = false;
							
						} else {
							// success
							$success = __( "Account activated successfully. Broadcasting page created successfully.");
							$accCreatedOk = true;
						}
					}
				}		
				
			}
		break;
		
	}
	
	// handle content display here
	$setupStageNow = getSetupStage();
	switch ( $setupStageNow ) {
		
		case 0:
			// broadcaster is not setup yet
			$msg = __( "This form will setup BlinkoTV Broadcaster for Wordpress.", $bPluginDomain)."<br />".
			__( "Follow the instructions during setup and have fun using the BlinkoTV Broadcaster Plugin.", $bPluginDomain);
			$email_adr = __( "Your email address:", $bPluginDomain);
			$setup_broadcaster = __( "Setup Account!", $bPluginDomain);
			$content = <<<S
			
			<div style="margin-top:10px; margin-bottom:10px;">
			{$msg}
			</div>			
			<form action="" method="post">
				<table>
					<tr>
						<td>{$email_adr}</td>
						<td><input type"text" name="email" value="{$_POST['email']}" class="postform" /></td>
					</tr>
					
					
					<tr>
						<td>&nbsp;</td>
						<td style="text-align:right;"><input type="submit" name="signin_account" value="{$setup_broadcaster}" class="button-secondary" /></td>
					</tr>
				</table>
			</form>
S;

		break;
		
		
		// must activate account
		
		case 1:
			
			$activate_txt = __( "Validation number", $bPluginDomain);
			$confirm_btn = __( "Confirm account", $bPluginDomain);
			$activateAccForm = <<<S
			<form action="" method="post">
				<table>
					<tr>
						<td>{$activate_txt}</td>
						<td><input type"text" name="validation_number" value="{$_POST['validation_number']}" class="postform" /></td>
					</tr>					
					<tr>
						<td>&nbsp;</td>
						<td style="text-align:right;"><input type="submit" name="activate_acc" value="{$confirm_btn}" class="button-secondary" /></td>
					</tr>
				</table>
			</form>
S;
			//__( "Your evaluation broadcasting account was created.", $bPluginDomain)
			$content .= "<br />".
			__( "To complete the setup stage you must confirm the account using the validation number received via email.", $bPluginDomain).
			$activateAccForm;
			
		break;
		
		case 2:
			// setup complete						
			$link = soapCall( "getDownloadBroadcasterUrl", array() );	
			
			
			$content = 
			"<span style='font-size:13px;'>".__( "Your BlinkoTV Broadcaster is setup and ready to go. ", $bPluginDomain)."</span>"
			."<br />". 
			__("Before you can start broadcasting please download and install the following application:", $bPluginDomain)
			."<br /><a href='{$link}'>{$get_broadcaster}</a>";
		break;
		
	}
	
	
}

$sectionTitle = __( "Setup BlinkoTV Broadcaster",  $bPluginDomain);
include "footer.php";