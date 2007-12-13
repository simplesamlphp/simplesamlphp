<?php $this->includeAtTemplateBase('includes/header.php'); ?>

	<div id="header">
		<h1>simpleSAMLphp error page</h1>
		<div id="poweredby"><img src="/<?php echo $data['baseurlpath']; ?>resources/icons/bomb_l.png" alt="Login screen" /></div>
	</div>
	
	<div id="content">
	



		<h2><?php if (isset($data['header'])) { echo $data['header']; } else { echo "Some error occured"; } ?></h2>
		
		<p>
			
			<?php echo $data['message']; ?>

		</p>


<?php
/* Print out exception only if the exception is available. */
if (array_key_exists('e', $data)) {
?>

		<p>The debug information below may be interesting for the administrator / help desk:</p>
		
		<div style="border: 1px solid #eee; padding: 1em; font-size: x-small">
			<p style="margin: 1px"><?php echo htmlentities($data['e']->getMessage()); ?></p>
			<div style=" padding: 1em; font-family: monospace; ">
				<?php echo htmlentities($data['e']->getTraceAsString()); ?>
			</div>
		</div>
<?php
}
?>
		
		<h2 style="clear: both">How to get help</h2>
		
		
		<p>This error probably is due to some unexpected behaviour or to misconfiguration of simpleSAMLphp. Contact the administrator of this login service, and send them the error message above.</p>
		


<?php $this->includeAtTemplateBase('includes/footer.php'); ?>