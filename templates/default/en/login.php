<?php 
	if (!array_key_exists('icon', $this->data)) $this->data['icon'] = 'lock.png';
	$this->includeAtTemplateBase('includes/header.php'); 
?>

	<div id="content">
	
		<?php if (isset($data['error'])) { ?>
		<div style="border-left: 1px solid #e8e8e8; border-bottom: 1px solid #e8e8e8; background: #f5f5f5"
		<img src="/<?php echo $data['baseurlpath']; ?>resources/icons/bomb.png" style="float: left; margin: 15px " />
		<h2>What you entered was not accepted!</h2>
		
		<p><?php echo htmlspecialchars($data['error']); ?> </p>
		</div>
		<?php } ?>
	
		<h2 style="break: both">Enter your username and password</h2>
		
		<p>
			A service has requested you to authenticate your self. That means you need to enter your username and password in the form below.
		</p>
		
		<form action="?" method="post" name="f">

		<table>
			<tr>
				<td rowspan="2"><img src="/<?php echo $data['baseurlpath']; ?>resources/icons/pencil.png" /></td>
				<td style="padding: .3em;">Username</td>
				<td><input type="text" tabindex="1" name="username" 
					<?php if (isset($data['username'])) {
						echo 'value="' . htmlspecialchars($data['username']) . '"';
					} ?> /></td>
				<td style="padding: .4em;" rowspan="2">
					<input type="submit" tabindex="3" value="Login" />
					<input type="hidden" name="RelayState" value="<?php echo htmlspecialchars($data['relaystate']); ?>" />
				</td>
			</tr>
			<tr>
				<td style="padding: .3em;">Password</td>
				<td><input type="password" tabindex="2" name="password" /></td>
			</tr>
		</table>
		
		
		</form>
		
		
		<h2>Help! I don't remember my password.</h2>
		
		
		<p>Too bad! - Without your username and password you cannot authenticate your self and access the service.
		There may be someone that can help you. Contact the help desk at your university!</p>
		
		<h2>About simpleSAMLphp</h2>
		<p>Hey! This simpleSAMLphp thing is pretty cool, where can I read more about it?
		You can find more information about simpleSAMLphp at <a href="http://rnd.feide.no">the Feide RnD blog</a> over at <a href="http://uninett.no">UNINETT</a>.</p>
		

<?php $this->includeAtTemplateBase('includes/footer.php'); ?>