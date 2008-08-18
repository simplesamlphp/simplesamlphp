<?php
$this->data['icon'] = 'lock.png';
$this->data['header'] = $this->t('{login:user_pass_header}');

if (strlen($this->data['username']) > 0) {
	$this->data['autofocus'] = 'password';
} else {
	$this->data['autofocus'] = 'username';
}
$this->includeAtTemplateBase('includes/header.php');

?>
<div id="content">

<?php
if ($this->data['errorcode'] !== NULL) {
?>
	<div style="border-left: 1px solid #e8e8e8; border-bottom: 1px solid #e8e8e8; background: #f5f5f5">
		<img src="/<?php echo $this->data['baseurlpath']; ?>resources/icons/bomb.png" style="float: left; margin: 15px " />
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
			<td rowspan="2"><img src="/<?php echo $this->data['baseurlpath']; ?>resources/icons/pencil.png" alt="" /></td>
			<td style="padding: .3em;"><?php echo $this->t('{login:username}'); ?></td>
			<td><input type="text" id="username" tabindex="1" name="username" value="<?php echo htmlspecialchars($this->data['username']); ?>" /></td>
			<td style="padding: .4em;" rowspan="2">
				<input type="submit" tabindex="3" value="<?php echo $this->t('{login:login_button}'); ?>" />
			</td>
		</tr>
		<tr>
			<td style="padding: .3em;"><?php echo $this->t('{login:password}'); ?></td>
			<td><input id="password" type="password" tabindex="2" name="password" /></td>
		</tr>
	</table>

<?php
foreach ($this->data['stateparams'] as $name => $value) {
	echo('<input type="hidden" name="' . htmlspecialchars($name) . '" value="' . htmlspecialchars($value) . '" />');
}
?>

	</form>

<?php
echo('<h2>' . $this->t('{login:help_header}') . '</h2>');
echo('<p>' . $this->t('{login:help_text}') . '</p>');

$this->includeAtTemplateBase('includes/footer.php');
?>