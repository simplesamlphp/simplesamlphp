<?php 
	$this->data['icon'] = 'debug.png';
	$this->data['autofocus'] = 'sendlink';
	$this->includeAtTemplateBase('includes/header.php'); 
?>

	<h2><?php echo($this->t('{admin:debug_sending_message_title}')); ?></h2>

	<p><?php echo($this->t('{admin:debug_sending_message_text_link}')); ?></p>
	
	<p>[ <a id="sendlink" href="<?php echo htmlentities($this->data['url']); ?>"><?php echo($this->t('{admin:debug_sending_message_send}')); ?></a> ]</p>
	
	<h2><?php echo($this->t('{admin:debug_sending_message_msg_title}')); ?></h2>
	
	<p><?php echo($this->t('{admin:debug_sending_message_msg_text}')); ?></p>
	
	<pre style="overflow: scroll; border: 1px solid #eee"><?php echo $this->data['message']; ?></pre>

	<p><?php echo($this->t('{admin:debug_disable_debug_mode}')); ?></p>

<?php $this->includeAtTemplateBase('includes/footer.php'); ?>