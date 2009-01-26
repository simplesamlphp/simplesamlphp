<?php
$this->data['header'] = 'LDAP status page';
$this->includeAtTemplateBase('includes/header.php');


?>
<div id="content">

<h2>LDAP test</h2>

<table class="attributes" style="font-size: small; width: 100%; border: 1px solid #aaa">
	<tr>
		<th>Name of institusion</th>
		<th>Conf</th>
		<th>Ping</th>
		<th>Admin bind()</th>
		<th>S=bogus</th>
		<th>test</th>
		<th>S=test</th>
		<th>test bind()</th>
		<th>attributes</th>
		<th>Meta</th>
	</tr>

<?php

function showRes($key, $res, $template) {
	echo('<td>');
	if (array_key_exists($key, $res)) {
		if ($res[$key][0]) {
			echo '<img src="/' . $template->data['baseurlpath'] . 'resources/icons/accept.png" ' .
				'alt="' . htmlspecialchars($res[$key][1]) .  '" 
				title="' . htmlspecialchars($res[$key][1]) .  '" 
				/>';
		} else {
			echo '<img src="/' . $template->data['baseurlpath'] . 'resources/icons/delete.png" ' .
				'alt="' . htmlspecialchars($res[$key][1]) .  '" 
				title="' . htmlspecialchars($res[$key][1]) .  '" 
				/>';
		}
	} else {
		echo('<span style="color: #b4b4b4; font-size: x-small">NA</span>');
	}
	echo('</td>');
}




$i = 0;
$classes = array('odd', 'even');

# $this->data['results']
foreach($this->data['sortedOrgIndex'] as $orgkey) {
	$res = $this->data['results'][$orgkey];
	echo('<tr class="' . ($classes[($i++ % 2)]) . '">');
	if (array_key_exists('description', $this->data['orgconfig'][$orgkey])) {
		echo('<td>' . htmlspecialchars($this->getTranslation($this->data['orgconfig'][$orgkey]['description'])) . '</td>');
	} else {
		echo('<td><span style="color: #b4b4b4; font-size: x-small">NA</span> <tt>' . $orgkey . '</tt></td>');
	}
	showRes('config',  $res, $this);
	showRes('ping',  $res, $this);
	showRes('adminBind',  $res, $this);
	showRes('ldapSearchBogus',  $res, $this);
	showRes('configTest',  $res, $this);
	showRes('ldapSearchTestUser',  $res, $this);
	showRes('ldapBindTestUser',  $res, $this);
	showRes('ldapGetAttributesTestUser',  $res, $this);
	showRes('configMeta',  $res, $this);
	echo('</tr>');
}
?>
</table>
<?php $this->includeAtTemplateBase('includes/footer.php'); ?>