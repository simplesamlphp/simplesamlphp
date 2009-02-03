<?php
$this->data['header'] = 'LDAP status page';
$this->data['head'] = '<style>
table.statustable td {
	border-bottom: 1px solid #eee;
}

</style>';
$this->includeAtTemplateBase('includes/header.php');

?>

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
		<th>Time</th>
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
		echo('<td>' . htmlspecialchars(
			$this->getTranslation(
					SimpleSAML_Utilities::arrayize($this->data['orgconfig'][$orgkey]['description'], 'en')
				)
			) . '</td>');
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
	echo('<td style="text-align: right">' . ceil($res['time']*1000) . ' ms</td>');
	echo('</tr>');
}
?>
</table>


<?php

echo('<p>Loaded ' . $this->data['completeNo'] . ' of ' . $this->data['completeOf'] . ' organizations</p>');

$sum =  $this->data['lightCounter'][0] + $this->data['lightCounter'][1] + $this->data['lightCounter'][2];


if ($sum > 0) {
	echo('<table class="statustable" style="border: 1px solid #ccc; width: 400px">');
	echo('<tr><th>Type</th><th>Counter</th><th>Percentage</th></tr>');
	echo('<tr><td><img src="/' . $this->data['baseurlpath'] . 'resources/icons/accept.png" /></td><td>' . $this->data['lightCounter'][0] . '</td><td>' . 
		number_format(100 * $this->data['lightCounter'][0] / $sum, 1) . ' %</td></tr>');
	echo('<tr><td><img src="/' . $this->data['baseurlpath'] . 'resources/icons/delete.png" /></td><td>' . $this->data['lightCounter'][2] . '</td><td>' . 
		number_format(100 * $this->data['lightCounter'][2] / $sum, 1) . ' %</td></tr>');
	echo('<tr><td><span style="color: #b4b4b4; font-size: x-small">NA</span></td><td>' . $this->data['lightCounter'][1] . '</td><td>' . 
		number_format(100 * $this->data['lightCounter'][1] / $sum, 1) . ' %</td></tr>');
	echo('<tr><th>Sum</th><th>' . $sum . '</th><th>100 %</th></tr>');
	echo('</table>');
}
if ($this->data['completeOf'] > $this->data['completeNo']) {
	echo('<p>[ <a href="?reload=1">load more entries</a> | <a href="?reset=1">reset all entries</a> ]');
} else {
	echo('<p>[ <a href="?reset=1">reset all entries</a> ]');
}


?>

<?php $this->includeAtTemplateBase('includes/footer.php'); ?>
