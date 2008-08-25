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

	<p style="margin: .2em">[ <a href="<?php echo $this->data['logoutresponse']; ?>">Interrupt logging out and go back to service</a> ]</p>
	
	<?php
	
		foreach ($this->data['sparray'] AS $sp) {
			echo '<iframe class="hiddeniframe" style="width: 200px; height: 100px" src="' . $sp['url'] . '" ></iframe>';
		}
		
		foreach ($this->data['sparray'] AS $spentityid => $sp) {
			echo '<div class="inprogress" style="border: 1px solid #888; background: #eee; color: #444; padding: .2em; margin: .7em" id="' . $spentityid . '">
				<img style="float: left; margin: 3px" src="/' . $this->data['baseurlpath'] . 'resources/progress.gif" />Wait... is logging out from ' . $spentityid . '</div>';
		}
		
	
	?>
	</div>


	

<?php $this->includeAtTemplateBase('includes/footer.php'); ?>
