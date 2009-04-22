<?php
$this->data['header'] = $this->t('{modinfo:dict:modlist_header}');
$this->includeAtTemplateBase('includes/header.php');

#$icon_enabled  = '<img src="/' . $this->data['baseurlpath'] . 'resources/icons/accept.png" alt="' .
#htmlspecialchars($this->t(...)" />';
#$icon_disabled = '<img src="/' . $this->data['baseurlpath'] . 'resources/icons/delete.png" alt="disabled" />';

?>

<h2><?php echo($this->data['header']); ?></h2>

<table>
<tr>
<th><?php echo($this->t('{modinfo:dict:modlist_name}')); ?></th>
<th><?php echo($this->t('{modinfo:dict:modlist_status}')); ?></th>
</tr>
<?php
foreach($this->data['modules'] as $id => $info) {
	echo('<tr>');
	echo('<td>' . htmlspecialchars($id) . '</td>');
	if($info['enabled']) {
		echo('<td><img src="/' . $this->data['baseurlpath'] . 'resources/icons/accept.png" alt="' .
			htmlspecialchars($this->t('{modinfo:dict:modlist_enabled}')) . '" /></td>');
	} else {
		echo('<td><img src="/' . $this->data['baseurlpath'] . 'resources/icons/delete.png" alt="' .
			htmlspecialchars($this->t('{modinfo:dict:modlist_disabled}')) . '" /></td>');
	}
	echo('</tr>');
}
?>
</table>
<?php $this->includeAtTemplateBase('includes/footer.php'); ?>