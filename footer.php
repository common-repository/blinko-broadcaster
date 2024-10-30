<?php
/**
 * BlinkoTV Broadcaster Footer page
 * @author Eyepartner development team
 * @version 1.5.1
 * @package blinko-broadcaster
 * 
 */
// get any exception/error/success messages
$outputMsg = getOutputMessages();

// display output
echo "
<div class='wrap'>
<div style='float:right; margin-top: 10px; color: #D54E21; text-align:right;'>$accountStatus</div>
<h2>{$sectionTitle}</h2><br />
{$outputMsg}{$content}
</div>
";