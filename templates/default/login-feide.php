<?php
if(isset($this->data['header']) && $this->getTag($this->data['header']) !== NULL) {
	$this->data['header'] = $this->t($this->data['header']);
}

$this->includeAtTemplateBase('includes/header.php');

?>

<?php if (isset($this->data['error'])) { ?>
<div id="errorframe">
	<h2><?php echo $this->t('{login:error_header}'); ?></h2>
		

<?php
echo('<p>');
if($this->getTag($this->data['error']) !== NULL) {
	echo $this->t($this->data['error']);
} else {
	echo(htmlspecialchars($this->data['error']));
}
echo('</p>');
?>
		</div>
		<?php } ?>

		<?php if ($this->data['selectorg']) { ?>
		<div id="orgframe">
			<form action="?" method="get" name="f">
			<fieldset>
			<legend><?php echo($this->t('{login:select_home_org}')); ?></legend>
				<select name="org" tabindex="1">
				<?php
					foreach ($this->data['allowedorgs'] AS $key) {
						$entry = $this->data['ldapconfig'][$key];
						echo '<option ' 
						. ($key == $this->data['org'] ? 'selected="selected" ' : '')
						. 'value="' . htmlspecialchars($key) . '">' . htmlspecialchars($entry['description']) . '</option>';
					}
				?>
				</select><br />
				<input type="hidden" name="RelayState" value="<?php echo htmlspecialchars($this->data['relaystate']); ?>" />
                                <input type="hidden" name="protocol" value="<?php echo htmlspecialchars($this->data['protocol']); ?>" />
                                <input type="hidden" name="AuthId" value="<?php echo htmlspecialchars($this->data['authid']); ?>" />
				<input type="submit" id="submit" value="<?php echo($this->t('{login:next}')); ?>" />
			</fieldset>
			</form>
		</div>
		<?php } ?>

		<?php if (!$this->data['selectorg']) { ?>	
		<div id="inputframe">
			<form action="?" method="post" name="f">
			<fieldset>
			<legend><?php echo $this->t('{login:user_pass_header}'); ?></legend>
			<p><?php echo $this->t('{login:user_pass_text}'); ?></p>
				<label for="username" accesskey="u" tabindex="1"><?php echo($this->t('{login:username}')); ?></label>
				<input type="text" id="username" name="username" 
					<?php if (isset($this->data['username'])) {
						echo 'value="' . htmlspecialchars($this->data['username']) . '"';
					} ?> 
				/> @ <?php echo $this->data['org']; ?><br />

				<label for="password" accesskey="p" tabindex="2"><?php echo($this->t('{login:password}')); ?></label>
				<input type="password" id="password" name="password" /><br />

				<input type="hidden" name="RelayState" value="<?php echo htmlspecialchars($this->data['relaystate']); ?>" />
                                <input type="hidden" name="protocol" value="<?php echo htmlspecialchars($this->data['protocol']); ?>" />
                                <input type="hidden" name="AuthId" value="<?php echo htmlspecialchars($this->data['authid']); ?>" />
				<input type="hidden" name="org" value="<?php echo $this->data['org']; ?>" />
				<input type="submit" id="submit" value="<?php echo($this->t('{login:login_button}')); ?>" />
			</fieldset>
			</form>
		</div>

		<div id="rechooseorgframe">
			<form action="?" method="get" name="g">
			<fieldset>
			<legend><?php echo($this->t('{login:change_home_org_title}')); ?></legend>
			<p><?php echo($this->t('{login:change_home_org_text}', array('%HOMEORG%' => $this->data['ldapconfig'][$this->data['org']]['description']))); ?></p>
				<input type="hidden" name="RelayState" value="<?php echo htmlspecialchars($this->data['relaystate']); ?>" />
                <input type="hidden" name="protocol" value="<?php echo htmlspecialchars($this->data['protocol']); ?>" />
                <input type="hidden" name="AuthId" value="<?php echo htmlspecialchars($this->data['authid']); ?>" />
				<input type="hidden" name="action" value="change_org" />
				<input type="submit" id="submit" value="<?php echo($this->t('{login:change_home_org_button}')); ?>" />
			</fieldset>
			</form>
		</div>

		<div id="helpframe">
			<h3><?php echo($this->t('{login:help_header}')); ?></h3>
			<p><?php echo($this->t('{login:help_text}')); ?></p>

			<?php
				$listitems = array();
				

				
				if (isset($this->data['ldapconfig'][$this->data['org']]['contactURL'])) {
					$listitems[] = '<li><a href="' . $this->data['ldapconfig'][$this->data['org']]['contactURL'] . '">' . $this->t('{login:help_desk_link}') . '</a></li>';
				}
				if (isset($this->data['ldapconfig'][$this->data['org']]['contactMail'])) {
					$listitems[] = '<li><a href="mailto:' . $this->data['ldapconfig'][$this->data['org']]['contactMail'] . '">' . $this->t('{login:help_desk_email}') . '</a></li>';
				}
				
				if ($listitems) {
					echo '<p>' . $this->t('{login:contact_info}') . '</p><ul>' . join("\n", $listitems) . '</ul>';
				}
				
			?>
		</div>
		<?php } ?>

<?php $this->includeAtTemplateBase('includes/footer.php'); ?>

