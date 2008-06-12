<?php 
	$this->data['icon'] = 'debug.png';
	$this->data['autofocus'] = 'sendlink';
	$this->includeAtTemplateBase('includes/header.php'); 
?>

	<div id="content">
	


		<h2>Sending a SAML message using HTTP-REDIRECT</h2>
	
		<p>You are about to send a SAML message using HTTP REDIRECT. Here is the message:</p>
		
		<pre style="overflow: scroll; border: 1px solid #eee"><?php echo $this->data['message']; ?></pre>
		
		<p>[ <a id="sendlink" href="<?php echo htmlentities($this->data['url']); ?>">send SAML message</a> ]</p>

		<h2>Debug mode</h2>
		
		<p>As you are in debug mode you are lucky to see the content of the response you are sending. You can turn off debug mode in the global simpleSAMLphp configuration file <tt>config/config.php</tt>.</p>
		
		

<?php $this->includeAtTemplateBase('includes/footer.php'); ?>