<?php

$this->includeAtTemplateBase('includes/header.php');

?>

		<?php if (isset($this->data['error'])) { ?>
		<div id="errorframe">
			<h2>What you entered was not accepted!</h2>
		
			<p><?php echo htmlspecialchars($this->data['error']); ?> </p>
		</div>
		<?php } ?>

		<?php if ($this->data['selectorg']) { ?>
		<div id="orgframe">
			<form action="?" method="get" name="f">
			<fieldset>
			<legend>Choose your home organization</legend>
				<select name="org" tabindex="1">
				<?php
					foreach ($this->data['ldapconfig'] AS $key => $entry) {
						echo '<option ' 
						. ($key == $this->data['org'] ? 'selected="selected" ' : '')
						. 'value="' . htmlspecialchars($key) . '">' . htmlspecialchars($entry['description']) . '</option>';
					}
				?>
				</select><br />
				<input type="hidden" name="RelayState" value="<?php echo htmlspecialchars($this->data['relaystate']); ?>" />
                                <input type="hidden" name="protocol" value="<?php echo htmlspecialchars($this->data['protocol']); ?>" />
                                <input type="hidden" name="AuthId" value="<?php echo htmlspecialchars($this->data['authid']); ?>" />
				<input type="submit" id="submit" value="Next" />
			</fieldset>
			</form>
		</div>
		<?php } ?>

		<?php if (!$this->data['selectorg']) { ?>	
		<div id="inputframe">
			<form action="?" method="post" name="f">
			<fieldset>
			<legend>Enter your username and password</legend>
			<p>A service has requested you to authenticate your self. 
			That means you need to enter your username and password in the form below.</p>
				<label for="username" accesskey="u" tabindex="1">Username: </label>
				<input type="text" id="username" name="username" 
					<?php if (isset($this->data['username'])) {
						echo 'value="' . htmlspecialchars($this->data['username']) . '"';
					} ?> 
				/> @ <?php echo $this->data['org']; ?><br />

				<label for="password" accesskey="p" tabindex="2">Password: </label>
				<input type="password" id="password" name="password" /><br />

				<input type="hidden" name="RelayState" value="<?php echo htmlspecialchars($this->data['relaystate']); ?>" />
                                <input type="hidden" name="protocol" value="<?php echo htmlspecialchars($this->data['protocol']); ?>" />
                                <input type="hidden" name="AuthId" value="<?php echo htmlspecialchars($this->data['authid']); ?>" />
				<input type="hidden" name="org" value="<?php echo $this->data['org']; ?>" />
				<input type="submit" id="submit" value="Login" />
			</fieldset>
			</form>
		</div>

		<div id="rechooseorgframe">
			<form action="?" method="get" name="g">
			<fieldset>
			<legend>Change your home organization</legend>
			<p>You have chosen <b><?php echo $this->data['ldapconfig'][$this->data['org']]['description']; ?></b> as your home organization. If this is wrong you may choose
			another one.</p>
				<input type="hidden" name="RelayState" value="<?php echo htmlspecialchars($this->data['relaystate']); ?>" />
                                <input type="hidden" name="protocol" value="<?php echo htmlspecialchars($this->data['protocol']); ?>" />
                                <input type="hidden" name="AuthId" value="<?php echo htmlspecialchars($this->data['authid']); ?>" />
				<input type="hidden" name="action" value="change_org" />
				<input type="submit" id="submit" value="Choose home organization" />
			</fieldset>
			</form>
		</div>

		<div id="helpframe">
			<h3>Help! I don't remember my password.</h3>	
			<p>Too bad! - Without your username and password you cannot authenticate your self and access the service.</p>

			<?php
				$listitems = array();
				

				
				if (isset($this->data['ldapconfig'][$this->data['org']]['contactURL'])) {
					$listitems[] = '<li><a href="' . $this->data['ldapconfig'][$this->data['org']]['contactURL'] . '">Help desk homepage</a></li>';
				}
				if (isset($this->data['ldapconfig'][$this->data['org']]['contactMail'])) {
					$listitems[] = '<li><a href="mailto:' . $this->data['ldapconfig'][$this->data['org']]['contactMail'] . '">Send e-mail to help desk</a></li>';
				}
				
				if ($listitems) {
					echo '<p>Contact information:</p><ul>' . join("\n", $listitems) . '</ul>';
				}
				
			?>
		</div>
		<?php } ?>

<?php $this->includeAtTemplateBase('includes/footer.php'); ?>

