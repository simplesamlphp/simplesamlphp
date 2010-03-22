<?php
$this->data['header'] = $this->t('{login:user_pass_header}');

if (strlen($this->data['username']) > 0) {
	$this->data['autofocus'] = 'password';
} else {
	$this->data['autofocus'] = 'username';
}
$this->includeAtTemplateBase('includes/header.php');

?>

<?php
if ($this->data['errorcode'] !== NULL) {
?>
	<div style="border-left: 1px solid #e8e8e8; border-bottom: 1px solid #e8e8e8; background: #f5f5f5">
		<img src="/<?php echo $this->data['baseurlpath']; ?>resources/icons/experience/gtk-dialog-error.48x48.png" style="float: left; margin: 15px " />
		<h2><?php echo $this->t('{login:error_header}'); ?></h2>
		<p><b><?php echo $this->t('{errors:title_' . $this->data['errorcode'] . '}'); ?></b></p>
		<p><?php echo $this->t('{errors:descr_' . $this->data['errorcode'] . '}'); ?></p>
	</div>
<?php
}
?>
	<h2 style="break: both"><?php echo $this->t('{login:user_pass_header}'); ?></h2>

	<p><?php echo $this->t('{login:user_pass_text}'); ?></p>

	<form action="?" method="post" name="f">
	<table>
		<tr>
			<td rowspan="3"><img src="/<?php echo $this->data['baseurlpath']; ?>resources/icons/experience/gtk-dialog-authentication.48x48.png" alt="" /></td>
			<td style="padding: .3em;"><?php echo $this->t('{login:username}'); ?></td>
			<td>
<?php
if ($this->data['forceUsername']) {
	echo '<strong style="font-size: medium">' . htmlspecialchars($this->data['username']) . '</strong>';
} else {
	echo '<input type="text" id="username" tabindex="1" name="username" value="' . htmlspecialchars($this->data['username']) . '" />';
}
?>
			</td>
			<td style="padding: .4em;" rowspan="3">
				<input type="submit" tabindex="4" value="<?php echo $this->t('{login:login_button}'); ?>" />
			</td>
		</tr>
		<tr>
			<td style="padding: .3em;"><?php echo $this->t('{login:password}'); ?></td>
			<td><input id="password" type="password" tabindex="2" name="password" /></td>
		</tr>

<?php
if (array_key_exists('organizations', $this->data)) {
?>
		<tr>
			<td style="padding: .3em;"><?php echo $this->t('{login:organization}'); ?></td>
			<td><select name="organization" tabindex="3">
<?php
if (array_key_exists('selectedOrg', $this->data)) {
	$selectedOrg = $this->data['selectedOrg'];
} else {
	$selectedOrg = NULL;
}

foreach ($this->data['organizations'] as $orgId => $orgDesc) {
	if (is_array($orgDesc)) {
		$orgDesc = $this->t($orgDesc);
	}

	if ($orgId === $selectedOrg) {
		$selected = 'selected="selected" ';
	} else {
		$selected = '';
	}

	echo '<option ' . $selected . 'value="' . htmlspecialchars($orgId) . '">' . htmlspecialchars($orgDesc) . '</option>';
}
?>
			</select></td>
		</tr>
<?php
}
?>

	</table>

<?php
foreach ($this->data['stateparams'] as $name => $value) {
	echo('<input type="hidden" name="' . htmlspecialchars($name) . '" value="' . htmlspecialchars($value) . '" />');
}
?>

	</form>

<?php

if(!empty($this->data['links'])) {
	echo '<ul class="links" style="margin-top: 2em">';
	foreach($this->data['links'] AS $l) {
		echo '<li><a href="' . htmlspecialchars($l['href']) . '">' . htmlspecialchars($this->t($l['text'])) . '</a></li>';
	}
	echo '</ul>';
}




echo('<h2>' . $this->t('{login:help_header}') . '</h2>');
echo('<p>' . $this->t('{login:help_text}') . '</p>');

$this->includeAtTemplateBase('includes/footer.php');
?>