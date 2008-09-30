<?php 
	
	
	$this->data['head'] .= '
<script type="text/javascript" src="/' . $this->data['baseurlpath']. 'resources/script.js"></script>	
<script type="text/javascript" language="JavaScript">

function showdiv(id) {
	//safe function to show an element with a specified id
		  
	if (document.getElementById) { // DOM3 = IE5, NS6
		document.getElementById(id).style.display = \'block\';
	}
	else {
		if (document.layers) { // Netscape 4
			document.id.display = \'block\';
		}
		else { // IE 4
			document.all.id.style.display = \'block\';
		}
	}
}
</script>';
	
	$this->includeAtTemplateBase('includes/header.php');
	
?>


		<div id="a" style="display: none; background: blue; width: 10px; height: 10px">Poot</div>
		
	<div id="content">
	

		
		<noscript>
			<div id="nojavascriptframe">
				<iframe style="margin: 1em; width: 90%; height: 5em; border: 1px solid #eee" src="SingleLogoutServiceiFrameNoJavascript.php?response=<?php echo urlencode($this->data['logoutresponse']); ?>"></iframe>			
			</div>
		</noscript>
		<div id="requirejavascript" style="display: none">
		
			<noscript><div style="background: #500; color: white; border: 1px solod #300">Ignore the logout indicators below. They will not be updated as your browser do not support javascript. Logout will still work.</div></noscript>
		
			<?php
			
			$requestername = is_array($this->data['requesterName']) ? 
				$this->getTranslation($this->data['requesterName']) : $this->data['requesterName'];
			
			?>
			<p>You have initiated a <strong>global logout</strong> from the service <strong><?php echo $requestername; ?></strong>. Global logout means you will be logged out from all services connected to this identity provider. This page will show the status of the logout proccess for all of the services you are logged into.</p>
		
	
			<?php

				foreach ($this->data['sparray'] AS $sp) {
					echo '<iframe class="hiddeniframe" onload="xajax_updateslostatus()" style="border: 1px solid #888; width: 80%; height: 100px" src="' . $sp['url'] . '" ></iframe>' . "\n";
				}
				
				foreach ($this->data['sparray'] AS $spentityid => $sp) {
				
					$spname = is_array($sp['name']) ? $this->getTranslation($sp['name']) : $sp['name'];
					echo '<div class="inprogress" id="e' . sha1($spentityid) . '">
						<img style="float: left; margin: 3px" src="/' . $this->data['baseurlpath'] . 'resources/progress.gif" alt="Progress bar" />Wait... is logging out from <strong>' . $spname . '</strong></div>'  . "\n";
				}
				
			?>
			
			<div id="interrupt">[ <a href="<?php echo $this->data['logoutresponse']; ?>">Interrupt logging out and go back to service</a> ]</div>
			<div id="iscompleted">You have successfully logged out from all services listed above.
				<!-- form method="get" action="<?php echo $this->data['logoutresponse']; ?>">
					<input type="submit" name="s" value="OK, continue back to <?php echo $this->data['requesterName']; ?> to complete the logout process." />
				</form  -->
				<br />[ <a href="<?php echo $this->data['logoutresponse']; ?>">OK, continue back to <?php echo $requestername; ?> to complete the logout process.</a> ]	
			</div>

		
		</div>
		
		<script type="text/javascript" language="JavaScript">
			showdiv('requirejavascript');
		</script>

<?php $this->includeAtTemplateBase('includes/footer.php'); ?>