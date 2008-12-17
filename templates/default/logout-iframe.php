<?php 

	$iframehtml = '';
	foreach ($this->data['sparray'] AS $sp) {
		$iframehtml .= '<iframe class="hiddeniframe" onload="xajax_updateslostatus()" style="border: 1px solid #888; width: 80%; height: 100px" src="' . htmlentities($sp['url']) . '" ></iframe>';
	}
#	$iframehtml = str_replace('"', '\"', $iframehtml);
#	$iframehtml = str_replace("\n", '', $iframehtml);
#	$iframehtml = str_replace("\r", '', $iframehtml);
	
	$this->data['hideLanguageBar'] = TRUE;
	$this->data['head']  .= '<script type="text/javascript" src="/' . $this->data['baseurlpath'] . 'resources/jquery.js"></script>';
	$this->data['head']  .= '<link rel="stylesheet" type="text/css" href="/' . $this->data['baseurlpath'] . 'resources/slo.css" />';

	$nologoutSPs = (count($this->data['sparrayNoLogout']) > 0);

	$this->data['head'] .= '
<script type="text/javascript" language="JavaScript">



$(document).ready(function() {
	$("div#requirejavascript").show();
/*	$("div.completedButWarnings").hide(); */
	$("div#interrupt").hide();
	$("input#ok").click(function () { 
      startslo();
    });
	$("input#cancel").click(function () { 
      sendResponse();
    });	
	$("input#returnanyway").click(function () { 
      sendResponse();
    });
	$("input#interruptbutton").click(function () { 
      sendResponse();
    });
    
    ' . ($nologoutSPs ? '$("div#incapablesps").show();' : '$("div#incapablesps").hide();') . '

});

function toolong() {
	$("div#interrupt").show().fadeOut("fast").fadeIn("fast");
}

/* This function is called when users clicks to start single logout */
function startslo() {
	$("#confirmation").hide();
	$("#hiddeniframecontainer").html("' . str_replace('"', '\"', $iframehtml) . '");
	$("table#slostatustable tr.onhold").removeClass("onhold").addClass("inprogress");
	$("div.completedButWarnings").show(); 
	setTimeout("toolong()", 16000);
}

/* This function is called from the AJAX response with xajax with the hash of the entityid of the SP   */
function slocompletesp($entityhash) {
	$("table#slostatustable tr#" + $entityhash).filter(".inprogress").removeClass("inprogress").addClass("completed").
		children().fadeOut("fast").fadeIn("fast");
}


/* SLO completed for all sps. */
function slocompleted() {
/*	$("div.completedButWarnings").show(); */
' . ($nologoutSPs ? ' ' : 'setTimeout("sendResponse()", 2000);') . '
}

function sendResponse() {
	window.location = "' .  $this->data['logoutresponse'] . '";
}
</script>';
	
	$this->includeAtTemplateBase('includes/header.php');
	
?>


<!-- Proper fallback for browsers that do not support javascript or have javascript turned off -->
<noscript> 
	<div id="nojavascriptframe">
		<iframe style="margin: 1em; width: 90%; height: 5em; border: 1px solid #eee" src="SingleLogoutServiceiFrameNoJavascript.php?response=<?php echo urlencode($this->data['logoutresponse']); ?>"></iframe>			
	</div>

<?php

	foreach ($this->data['sparray'] AS $sp) {
		echo '<iframe class="hiddeniframe" onload="xajax_updateslostatus()" style="border: 1px solid #888; width: 80%; height: 100px" 
			src="' . htmlentities($sp['url']) . '" ></iframe>' . "\n";
	}

?>

</noscript>


<div id="requirejavascript" style="display: none">

	<?php
	
	$requestername = is_array($this->data['requesterName']) ? 
		$this->getTranslation($this->data['requesterName']) : $this->data['requesterName'];
	
	?>
	<p>You have initiated a <strong>global logout</strong> from the service <strong><?php echo $requestername; ?></strong>. Global logout means you will be logged out from all of the services listed below.</p>



	<!-- <div class="loggedout">Logout was started from <?php echo $requestername; ?>.</div> -->

	<?php


		
		
		echo '<table id="slostatustable">';
		
		echo '<tr class="initiated" id="e' . sha1($spentityid) . '">' . "\n";
		echo '	<td><img style="float: left; margin: 3px" src="/' . $this->data['baseurlpath'] . 
			'resources/icons/silk/accept.png" alt="Initiated from" /></td>' . "\n";
		echo '	<td>Initiated logout</td>';
		echo '	<td>' . $requestername . '</td>' ."\n";
		echo '</tr>' . "\n";
		
		


		foreach ($this->data['sparrayNoLogout'] AS $spentityid => $sp) {
			$spname = is_array($sp['name']) ? $this->getTranslation($sp['name']) : $sp['name'];
			echo '<tr class="initiated" id="e' . sha1($spentityid) . '">' . "\n";
			echo '	<td><img style="float: left; margin: 3px" src="/' . $this->data['baseurlpath'] . 
				'resources/icons/silk/delete.png" alt="Initiated from" /></td>' . "\n";
			echo '	<td>Logout not supported</td>';
			echo '	<td>' . $spname . '</td>' ."\n";
			echo '</tr>' . "\n";
		}

		
		foreach ($this->data['sparray'] AS $spentityid => $sp) {
			$spname = is_array($sp['name']) ? $this->getTranslation($sp['name']) : $sp['name'];
			
			echo '<tr class="ready onhold" id="e' . sha1($spentityid) . '">' . "\n";

			echo '	<td class="icons">';
			echo '		<img class="completed"  src="/' . $this->data['baseurlpath'] . 'resources/icons/silk/accept.png" alt="Completed" />' . "\n";
			echo '		<img class="onhold"     src="/' . $this->data['baseurlpath'] . 'resources/icons/silk/control_pause.png" alt="SP SLO on hold" />' . "\n";
			echo '		<img class="inprogress" src="/' . $this->data['baseurlpath'] . 'resources/progress.gif" alt="Progress bar" />' . "\n";
			echo '		<img class="failed"     src="/' . $this->data['baseurlpath'] . 'resources/icons/silk/exclamation.png" alt="Failed" />' . "\n";
			echo '	</td>' . "\n";
			
			echo '	<td class="statustext">';
			echo '		<span class="completed">Completed</span>' . "\n";
			echo '		<span class="onhold">On hold</span>' . "\n";
			echo '		<span class="inprogress">Logging out…</span>' . "\n";
			echo '		<span class="failed">Logout failed</span>' . "\n";
			echo '	</td>';
			echo '	<td>' . $spname . '</td>' ."\n";
			
			echo '</tr>' . "\n";
			
// 			echo '<div class="inprogress" id="e' . sha1($spentityid) . '">
// 				<img style="float: left; margin: 3px" src="/' . $this->data['baseurlpath'] . 'resources/progress.gif" alt="Progress bar" />Wait... is logging out from <strong>' . $spname . '</strong></div>'  . "\n";
		}
		echo '</table>';

		$completed = ' class="allcompleted"';
		if (count($this->data['sparray']) > 0) {
			$completed = '';
		}
	

	?>

	<p id="confirmation" style="margin-top: 1em" >Do you want to continue global logout? <br />
		<input type="button" id="ok" name="ok" value="Yes, continue logout" />
		<input type="button" id="cancel" name="cancel" value="Cancel logout" />
	</p>
	
	<div id="interrupt" style="margin-top: 1em; border: 1px solid #ccc; padding: 1em; background: #eaeaea" >
		<p style="margin: 0px; padding; 0px">
			<img src="/<?php echo($this->data['baseurlpath']); ?>resources/icons/timeout.png" 
				style="float: left; margin: 0px 5px 0px 0px"
				/>
			If some of the service providers do not respond in reasonable time, you are encouraged to close your browser to ensure sessions are closed. <br />
			<input type="button" id="interruptbutton" name="interrupt" value="Return to service" />
		</p>
	</div>
	
	<div id="incapablesps" style="margin-top: 1em; border: 1px solid #ccc; padding: 1em; background: #eaeaea" >
		<p style="margin: 0px; padding; 0px">
			<img src="/<?php echo($this->data['baseurlpath']); ?>resources/icons/caution.png" 
				style="float: left; margin: 0px 5px 0px 0px"
				/>
			One or more of the services you are logged into <i>do not support logout</i>. To ensure that all your sessions are closed, you are encouraged to <i>close your webbrowser</i>.
		</p>

		<div class="completedButWarnings">
			<input type="button" id="returnanyway" name="ok" value="Return to service" />
		</div>

	</div>

	<div id="hiddeniframecontainer" stye="margin: 0px; padding: 0px;"></div>


</div>

<!--
<script type="text/javascript" language="JavaScript">
	showdiv('requirejavascript');
</script>
-->

<?php $this->includeAtTemplateBase('includes/footer.php'); ?>