<?php 
	$this->data['icon'] = 'debug.png';
	$this->data['autofocus'] = 'sendbutton';
	$this->includeAtTemplateBase('includes/header.php'); 
?>


	
	<div id="content">
	


		<h2>Sending a SAML response to the service</h2>
	
		<p>You are about to send a SAML response back to the service. Hit the send response button to continue.</p>
		
		<form method="post" action="<?php echo htmlspecialchars($this->data['destination']); ?>">
			<input type="hidden" name="SAMLResponse" value="<?php echo htmlspecialchars($this->data['response']); ?>" />
			<input type="hidden" name="<?php echo htmlspecialchars($this->data['RelayStateName']); ?>" value="<?php echo htmlspecialchars($this->data['RelayState']); ?>" />
			<input type="submit" value="Submit the response to the service" id="sendbutton" />
		</form>

		<h2>Debug mode</h2>
		
		<p>As you are in debug mode you are lucky to see the content of the response you are sending:</p>
		
		<pre style="overflow: scroll; border: 1px solid #eee"><?php echo $this->data['responseHTML']; ?></pre>

<?php $this->includeAtTemplateBase('includes/footer.php'); ?>