<?php $this->includeAtTemplateBase('includes/header.php'); ?>

	<div id="header">
		<h1>simpleSAMLphp authentication</h1>
		<div id="poweredby"><img src="/<?php echo $data['baseurlpath']; ?>resources/icons/debug.png" alt="Debug" /></div>
	</div>
	
	<div id="content">
	


		<h2>Sending a SAML response to the service</h2>
	
		<p>You are about to send a SAML response back to the service. Hit the send response button to continue.</p>
		
		<form method="post" action="<?php echo htmlspecialchars($data['destination']); ?>">
			<input type="hidden" name="SAMLResponse" value="<?php echo htmlspecialchars($data['response']); ?>" />
			<input type="hidden" name="<?php echo htmlspecialchars($data['RelayStateName']); ?>" value="<?php echo htmlspecialchars($data['RelayState']); ?>">
			<input type="submit" value="Submit the response to the service" id="sendbutton" />
		</form>

		<script type="text/javascript">
			document.getElementById('sendbutton').focus();
		</script>

		<h2>Debug mode</h2>
		
		<p>As you are in debug mode you are lucky to see the content of the response you are sending:</p>
		
		<pre style="overflow: scroll; border: 1px solid #eee"><?php echo $data['responseHTML']; ?></pre>

<?php $this->includeAtTemplateBase('includes/footer.php'); ?>