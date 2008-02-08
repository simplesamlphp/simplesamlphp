<?php 
	$this->data['header'] = 'simpleSAMLphp error';
	$this->data['icon'] = 'bomb_l.png';
	$this->includeAtTemplateBase('includes/header.php'); 
?>


	<div id="content">
	
		<h2><?php echo (isset($this->data['title']) ? $this->data['title'] : 'simpleSAMLphp error'); ?></h2>

<?php
if(array_key_exists('descr', $this->data)) {
	echo '<p>' . $this->data['descr'] . '</p>';
}
?>

<?php
/* Print out the track id if it exists. */
if(array_key_exists('trackid', $this->data)) {
?>
		<div class="trackidtext">
			If you report this error the track ID makes it possible to track your session in the logs available to the system adinistrator: 
				<span class="trackid"><?php echo $this->data['trackid']; ?><span>

		</div>
<?php
}
?>
		

<?php
/* Print out exception only if the exception is available. */
if (array_key_exists('showerrors', $this->data) && $this->data['showerrors']) {
?>
		<h2>Debug information</h2>
		<p>The debug information below may be interesting for the administrator / help desk:</p>
		
		<div style="border: 1px solid #eee; padding: 1em; font-size: x-small">
			<p style="margin: 1px"><?php echo htmlentities($this->data['exceptionmsg']); ?></p>
			<div style=" padding: 1em; font-family: monospace; ">
				<?php echo htmlentities($this->data['exceptiontrace']); ?>
			</div>
		</div>
<?php
}
?>

<?php
/* Print out exception only if the exception is available. */
if (!empty($this->data['errorreportaddress'])) {
?>

		<h2>Report errors</h2>		
		<form action="<?php echo $this->data['errorreportaddress']; ?>" method="post">
	
			<p>Optionally enter your email address, for the administrators to be able contact you for further questions about your issue:			</p>
				<p>E-mail address: <input type="text" size="25" name="email" value="" />

			<p>
			<textarea style="width: 300px; height: 100px" name="text">Explain what you did to get this error...</textarea>
			</p></p>
			<input type="hidden" name="action" value="error" />
			<input type="hidden" name="techemail" value="<?php echo $this->data['email']; ?>" />
			<input type="hidden" name="version" value="<?php echo $this->data['version']; ?>" />
			<input type="hidden" name="trackid" value="<?php echo $this->data['trackid']; ?>" />
			<input type="hidden" name="exceptionmsg" value="<?php echo urlencode(base64_encode($this->data['exceptionmsg'])); ?>" />
			<input type="hidden" name="exceptiontrace" value="<?php echo urlencode(base64_encode($this->data['exceptiontrace'])); ?>" />
			
			<input type="submit" name="send" value="Send error report" />
			</p>
		</form>
<?php
}
?>



		
		<h2 style="clear: both">How to get help</h2>
		
		
		<p>This error probably is due to some unexpected behaviour or to misconfiguration of simpleSAMLphp. Contact the administrator of this login service, and send them the error message above.</p>
		


<?php $this->includeAtTemplateBase('includes/footer.php'); ?>