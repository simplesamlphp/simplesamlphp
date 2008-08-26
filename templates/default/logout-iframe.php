<?php 

	
	$this->data['head'] .= '<script language="JavaScript">
// use pre-formatted output for this multiplication table
var j;	// loop variables

xajax_updateslostatus();
for (j=1; j<=10; j++) {
	setTimeout(\'xajax_updateslostatus()\',j*1000)
}
</script>';


	$this->includeAtTemplateBase('includes/header.php');
	
#	$this->includeLanguageFile('consent.php'); 
#	$this->includeInlineTranslation('spname', $this->data['sp_name']);
#	$this->includeInlineTranslation('IDPNAME', $this->data['idp_name']);
?>

	<div id="content">
		<?php
		
		$requestername = is_array($this->data['requesterName']) ? 
			$this->getTranslation($this->data['requesterName']) : $this->data['requesterName'];
		
		?>
		<p>You have initiated a <strong>global logout</strong> from the service <strong><?php echo $requestername; ?></strong>. Global logout means you will be logged out from all services connected to this identity provider. This page will show the status of the logout proccess for all of the services you are logged into.</p>
	

		<?php
		

			
			foreach ($this->data['sparray'] AS $sp) {
				echo '<iframe class="hiddeniframe" style="border: 1px solid #888; width: 80%; height: 100px" src="' . $sp['url'] . '" ></iframe>';
			}
			
			foreach ($this->data['sparray'] AS $spentityid => $sp) {
			
				$spname = is_array($sp['name']) ? $this->getTranslation($sp['name']) : $sp['name'];
				echo '<div class="inprogress" id="' . $spentityid . '">
					<img style="float: left; margin: 3px" src="/' . $this->data['baseurlpath'] . 'resources/progress.gif" />Wait... is logging out from <strong>' . $spname . '</strong></div>';
			}
			
		?>
		
		<div id="interrupt">[ <a href="<?php echo $this->data['logoutresponse']; ?>">Interrupt logging out and go back to service</a> ]</div>
		<div id="iscompleted">You have successfully logged out from all services listed above.
			<!-- form method="get" action="<?php echo $this->data['logoutresponse']; ?>">
				<input type="submit" name="s" value="OK, continue back to <?php echo $this->data['requesterName']; ?> to complete the logout process." />
			</form  -->
			<br />[ <a href="<?php echo $this->data['logoutresponse']; ?>">OK, continue back to <?php echo $this->data['requesterName']; ?> to complete the logout process.</a> ]
		</div>
	</div>

<?php $this->includeAtTemplateBase('includes/footer.php'); ?>