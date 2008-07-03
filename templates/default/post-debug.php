<?php 
	$this->data['icon'] = 'debug.png';
	$this->data['autofocus'] = 'sendbutton';
	$this->includeAtTemplateBase('includes/header.php'); 
?>


	
	<div id="content">
	


		<h2><?php echo($this->t('{admin:debug_sending_message_title}')); ?></h2>
	
		<p><?php echo($this->t('{admin:debug_sending_message_text_button}')); ?></p>
		
		<form method="post" action="<?php echo htmlspecialchars($this->data['destination']); ?>">
			<input type="hidden" name="SAMLResponse" value="<?php echo htmlspecialchars($this->data['response']); ?>" />
			<input type="hidden" name="<?php echo htmlspecialchars($this->data['RelayStateName']); ?>" value="<?php echo htmlspecialchars($this->data['RelayState']); ?>" />
			<input type="submit" value="<?php echo($this->t('{admin:debug_sending_message_send}')); ?>" id="sendbutton" />
		</form>

		<h2><?php echo($this->t('{admin:debug_sending_message_msg_title}')); ?></h2>
		
		<p><?php echo($this->t('{admin:debug_sending_message_msg_text}')); ?></p>
		
		<pre style="overflow: scroll; border: 1px solid #eee"><?php echo $this->data['responseHTML']; ?></pre>

		<p><?php echo($this->t('{admin:debug_disable_debug_mode}')); ?></p>

<?php $this->includeAtTemplateBase('includes/footer.php'); ?>