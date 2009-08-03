<?php 
	$this->data['icon'] = 'debug.png';
	$this->data['autofocus'] = 'sendbutton';
	$this->includeAtTemplateBase('includes/header.php'); 

if (array_key_exists('post', $this->data)) {
	$post = $this->data['post'];
} else {
	/* For backwards compatibility. */
	assert('array_key_exists("response", $this->data)');
	assert('array_key_exists("RelayStateName", $this->data)');
	assert('array_key_exists("RelayState", $this->data)');

	$post = array(
		'SAMLResponse' => $this->data['response'],
		$this->data['RelayStateName'] => $this->data['RelayState'],
	);
}

/**
 * Write out one or more INPUT elements for the given name-value pair.
 *
 * If the value is a string, this function will write a single INPUT element.
 * If the value is an array, it will write multiple INPUT elements to
 * recreate the array.
 *
 * @param string $name  The name of the element.
 * @param string|array $value  The value of the element.
 */
function printItem($name, $value) {
	assert('is_string($name)');
	assert('is_string($value) || is_array($value)');

	if (is_string($value)) {
		echo '<input type="hidden" name="' . htmlspecialchars($name) . '" value="' . htmlspecialchars($value) . '" />';
		return;
	}

	/* This is an array... */
	foreach ($value as $index => $item) {
		printItem($name . '[' . var_export($index, TRUE) . ']', $item);
	}
}

foreach ($post as $name => $value) {
	printItem($name, $value);
}

?>



		<h2><?php echo($this->t('{admin:debug_sending_message_title}')); ?></h2>
	
		<p><?php echo($this->t('{admin:debug_sending_message_text_button}')); ?></p>
		
		<form method="post" action="<?php echo htmlspecialchars($this->data['destination']); ?>">
<?php
foreach ($post as $name => $value) {
	printItem($name, $value);
}
?>
			<input type="submit" value="<?php echo($this->t('{admin:debug_sending_message_send}')); ?>" id="sendbutton" />
		</form>

		<h2><?php echo($this->t('{admin:debug_sending_message_msg_title}')); ?></h2>
		
		<p><?php echo($this->t('{admin:debug_sending_message_msg_text}')); ?></p>
		
		<pre style="overflow: scroll; border: 1px solid #eee"><?php echo $this->data['responseHTML']; ?></pre>

		<p><?php echo($this->t('{admin:debug_disable_debug_mode}')); ?></p>

<?php $this->includeAtTemplateBase('includes/footer.php'); ?>