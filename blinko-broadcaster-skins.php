<?php
/**
 * Visualize and change BlinkoTV Player skins and settings
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

if (  $okRequirements ) {	
	
	// if the user has requested change skin - form submitted
	if ( isset($_POST['change_skin'])) {
		
		// get broadcaster setting 
		$broadcasterSetting = getBroadcasterSettings();
		$include_chat = false;
		// get chat embed code only if user didn't require disable chat			
		if ( !isset($_POST['disable_chat']) ) {
			$include_chat = true;
		}
		
		// get new embed code for skin id and new chat embed code for skin id (skin id is required for chat embed to set the width and height of the chat)
		$playerEmbedCode = html_entity_decode( soapCall( "getPlayerEmbedCodeForUser", array( $broadcasterSetting->user_id, $_POST['skins'], $include_chat) ) );		
		
		$newEmbedCode = cleanUpBroadcasterEmbedCode( $playerEmbedCode );
		
		// update broadcaster settings
		$update_settings = $wpdb->update( $wpdb->broadcaster, array( "skin_id" => $_POST['skins']), array( "user_id"=>$broadcasterSetting->user_id) );
		// update broadcaster embed code
		$update_broadcaster = $wpdb->update( $wpdb->posts, array( "post_content" => $newEmbedCode), array( "ID" => $broadcasterSetting->page_id) );
		
		if ( false === $update_broadcaster || false === $update_settings) {
			// set error message
			handleError( __("Could not update skin.", $bPluginDomain) );
		} else {
			// set success message
			$success = __( "Settings for BlinkoTV Player updated successfully.", $bPluginDomain);
		}
		
	}
	
	if ( broadcasterIsSetUp() ) {
		
		// display skins
		$skinsPerRow = 3;
					
		$user_id = getBroadcastingAccountId();
		// get available skins			
		$availableSkins = soapCall( "getAvailableSkins" );
		
		// get selected skin id
		$selectedSkinId = getBroadcasterSelectedSkinId();
		
		if ( is_array($availableSkins) && count($availableSkins) > 0 ) {
			
			$checkedChatDis = (hasChatEnabled()===false)? "checked='checked'" : "";
			
			$content .= '
			<form method="post" >
			<fieldset class="b_fieldset">
			<legend class="b_legend"> Settings</legend>
			<input type="checkbox" name="disable_chat" value="1" '.$checkedChatDis.' /> '.__( "Chat disabled", $bPluginDomain).'<br />
			</fieldset>
			<fieldset class="b_fieldset">
			<legend class="b_legend">Skins</legend>
			<table cellspacing="10" cellpadding="10" id="skins" >';
			
			foreach ( $availableSkins as $i => $skin ) {
				
				if ( is_object($skin))	{
					
					$title = $skin->skin_title;
					$pic = $skin->skin_pic;
					$skin_id = $skin->skin_id;
				} else {
					
					$title = $skin['skin_title'];
					$pic = $skin['skin_pic'];
					$skin_id = $skin['skin_id'];
				}
				
				$checked[$i] = ( $selectedSkinId==$skin_id )? HTML_CHECKED : "";
				
				$skinImgs .= <<<S
				\n<td><img src="{$pic}" alt="$title" /></td>
S;

				$skinNames .= <<<S
				\n<td><input type="radio" name="skins" value="$skin_id" {$checked[$i]} />&nbsp;$title</td>
S;



				if ( ($i+1)%$skinsPerRow == 0 || $i+1==count($availableSkins) ) {
					
					if ( ( $r = $i+1%$skinsPerRow ) != 0 ) {
						for ( $jj = 0; $jj < $skinsPerRow-$r; $jj ++ ) {
							$skinImgs .= "\n<td>&nbsp;</td>";
							$skinNames .= "\n<td>&nbsp;</td>";
						}
					}
					$content .= "\n<tr>$skinImgs\n</tr>";
					$content .= "\n<tr>$skinNames\n</tr>";
					$skinImgs = $skinNames = "";
				}				
				
			}
			
			
			$content .= '
			</table>
			</fieldset>
			<input type="submit" class="button-secondary" name="change_skin" style="font-size:13px;" value="'.__( "Change", $bPluginDomain).'" />
			</form>';
			
		
		} else {
			
			// not expected response
			handleError( __("No available skins found.", $bPluginDomain) );
		}			
		
	} else {
		
		$content .= __( "To be able to select your favorite broadcaster skin please refer first to Setup area.", $bPluginDomain);
		
	}
}

$sectionTitle = __( "BlinkoTV Player Skins and Setting", $bPluginDomain);
include "footer.php";