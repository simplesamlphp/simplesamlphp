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
<!--


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
    
    ' . ($nologoutSPs ? '$("#incapablesps").show();' : '$("#incapablesps").hide();') . '

});

function toolong() {
	$("div#interrupt").show().fadeOut("fast").fadeIn("fast");
}

/* This function is called when users clicks to start single logout */
function startslo() {
	$("#confirmation").hide();
	$("#hiddeniframecontainer").html("' . str_replace('"', '\"', $iframehtml) . '");
	$("table#slostatustable tr.onhold").removeClass("onhold").addClass("inprogress");
/*	$("div.completedButWarnings").show();  */
' . ($iframehtml === '' ? 'sendResponse();' : '') . '
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
	$("div#interrupt").hide();
        setTimeout("sendResponse()", 2000);
}

function sendResponse() {
	window.location = "' .  $this->data['logoutresponse'] . '";
}
// -->
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
	#echo('<pre>'); print_r($this->data); exit;
	if (array_key_exists('requesterName', $this->data)) {
		$requesterName = is_array($this->data['requesterName']) ? 
			$this->getTranslation($this->data['requesterName']) : $this->data['requesterName'];
	}
	#echo('<p>' . $this->t('{logout:description}', array('%REQUESTERNAME%' => $requestername)) . '</p>');
	
	?>

	<!-- <div class="loggedout"><?php echo($this->t('{logout:logged_out}', array('%REQUESTERNAME%' => $requestername))); ?></div> -->

	<?php
	
		if (array_key_exists('requesterName', $this->data)) {
			echo('<div><img style="float: left; margin-right: 12px" src="/' . $this->data['baseurlpath'] . 'resources/icons/checkmark48.png" alt="Successful logout" />');
			echo('<p style="padding-top: 16px; ">' . $this->t('{logout:loggedoutfrom}', array('%SP%' => '<strong>' .$requesterName.'</strong>')) . '</p>');
			echo('<p style="height: 0px; clear: left;"></p>');
			echo('</div>');
		}

		echo('<div style="margin-top: 3em; clear: both">');
		echo('<p style="margin-bottom: .5em">' . $this->t('{logout:also_from}') . '</p>');
		
		echo '<table id="slostatustable">';

/** Remove initiated from. showed above instead
		
		echo '<tr class="initiated" id="e_initiated">' . "\n";
		echo '	<td><img style="float: left; margin: 3px" src="/' . $this->data['baseurlpath'] . 
			'resources/icons/silk/accept.png" alt="Initiated from" /></td>' . "\n";
		echo '	<td>' . $this->t('{logout:initiated}') . '</td>';
		echo '	<td>' . $requesterName . '</td>' ."\n";
		echo '</tr>' . "\n";
		
		*/


		foreach ($this->data['sparrayNoLogout'] AS $spentityid => $sp) {
			$spname = is_array($sp['name']) ? $this->getTranslation($sp['name']) : $sp['name'];
			echo '<tr class="initiated" id="e' . sha1($spentityid) . '">' . "\n";
			echo '	<td class="statustext">Logout not supported</td>';
			echo '	<td ><img style="" src="/' . $this->data['baseurlpath'] . 
				'resources/icons/silk/delete.png" alt="Initiated from" /></td>' . "\n";

			echo '	<td>' . $spname . '</td>' ."\n";
			echo '</tr>' . "\n";
		}

		
		foreach ($this->data['sparray'] AS $spentityid => $sp) {
			$spname = is_array($sp['name']) ? $this->getTranslation($sp['name']) : $sp['name'];
			
			echo '<tr class="ready onhold" id="e' . sha1($spentityid) . '">' . "\n";

			echo '	<td class="statustext">';
			echo '		<span class="completed">' . $this->t('{logout:completed}') . '</span>' . "\n";
#			echo '		<span class="onhold">' . $this->t('{logout:hold}') . '</span>' . "\n";
#			echo '		<span class="onhold"></span>' . "\n";
			echo '		<span class="inprogress">' . $this->t('{logout:progress}') . '</span>' . "\n";
			echo '		<span class="failed">' . $this->t('{logout:failed}') . '</span>' . "\n";
			echo '	</td>';

			echo '	<td class="icons">';
			echo '		<img class="completed"  src="/' . $this->data['baseurlpath'] . 'resources/icons/silk/accept.png" alt="Completed" />' . "\n";
			echo '		<img class="onhold"     src="/' . $this->data['baseurlpath'] . 'resources/icons/bullet16_grey.png" alt="SP SLO on hold" />' . "\n";
			echo '		<img class="inprogress" src="/' . $this->data['baseurlpath'] . 'resources/progress.gif" alt="Progress bar" />' . "\n";
			echo '		<img class="failed"     src="/' . $this->data['baseurlpath'] . 'resources/icons/silk/exclamation.png" alt="Failed" />' . "\n";
			echo '	</td>' . "\n";
			

			
			echo '	<td>' . $spname . '</td>' ."\n";
			
			echo '</tr>' . "\n";
			
// 			echo '<div class="inprogress" id="e' . sha1($spentityid) . '">
// 				<img style="float: left; margin: 3px" src="/' . $this->data['baseurlpath'] . 'resources/progress.gif" alt="Progress bar" />Wait... is logging out from <strong>' . $spname . '</strong></div>'  . "\n";
		}
		echo '</table></div>';

		$completed = ' class="allcompleted"';
		if (count($this->data['sparray']) > 0) {
			$completed = '';
		}
	

	?>

	<div id="confirmation" style="margin-top: 1em" >
		<p>
			<?php echo $this->t('{logout:logout_all_question}'); ?> <br />
		</p>
		<input type="button" id="ok" name="ok" value="<?php echo $this->t('{logout:logout_all}'); ?>" />
		<?php
			
			if (array_key_exists('requesterName', $this->data)) {
				echo '<input type="button" id="cancel" name="cancel" value="' . $this->t('{logout:logout_only}', array('%SP%' => $requesterName)) . '" />';
			} else {
				echo '<input type="button" id="cancel" name="cancel" value="' . $this->t('{logout:no}') . '" />';
			}
			
		?>
		
		

		<p id="incapablesps" >
			<?php echo($this->t('{logout:incapablesps}')); ?>
		</p>
		
		
	</div>
	

	
	
	
	<div id="interrupt" style="margin-top: 1em; border: 1px solid #ccc; padding: 1em; background: #eaeaea" >
		<p style="margin: 0px; padding; 0px">
			<img src="/<?php echo($this->data['baseurlpath']); ?>resources/icons/timeout.png" 
				alt="Timeout"
				style="float: left; margin: 0px 5px 0px 0px"
				/>
			<?php echo $this->t('{logout:respond_info}'); ?> <br />
			<input type="button" id="interruptbutton" name="interrupt" value="<?php echo $this->t('{logout:return}'); ?>" />
		</p>
	</div>
	

	<div id="hiddeniframecontainer" style="margin: 0px; padding: 0px;"></div>


</div> <!-- requirejavascript -->

<!--
<script type="text/javascript" language="JavaScript">
	showdiv('requirejavascript');
</script>
-->

<?php $this->includeAtTemplateBase('includes/footer.php'); ?>
