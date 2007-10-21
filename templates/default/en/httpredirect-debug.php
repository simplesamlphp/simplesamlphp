<?php $this->includeAtTemplateBase('includes/header.php'); ?>

	<div id="header">
		<h1>simpleSAMLphp HTTP-REDIRECT debug</h1>
		<div id="poweredby"><img src="/<?php echo $data['baseurlpath']; ?>resources/icons/debug.png" alt="Debug" /></div>
	</div>
	
	<div id="content">
	


		<h2>Sending a SAML message using HTTP-REDIRECT</h2>
	
		<p>You are about to send a SAML message using HTTP REDIRECT. Here is the message:</p>
		
		<pre style="overflow: scroll; border: 1px solid #eee"><?php echo $data['message']; ?></pre>
		
		<p>[ <a href="<?php echo htmlentities($data['url']); ?>">send SAML message</a> ]</p>

		<h2>Debug mode</h2>
		
		<p>As you are in debug mode you are lucky to see the content of the response you are sending. You can turn off debug mode in the global simpleSAMLphp configuration file <tt>config/config.php</tt>.</p>
		
		

<?php $this->includeAtTemplateBase('includes/footer.php'); ?>