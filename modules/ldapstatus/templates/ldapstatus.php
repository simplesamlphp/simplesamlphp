<?php
$this->data['header'] = 'LDAP status page';
$this->data['head'] = '<style>
table.statustable td {
	border-bottom: 1px solid #eee;
}
a {
	color: #333;
	text-decoration: none;
	border-bottom: 1px dotted #aaa;
}
a:hover {
	border-bottom: 1px solid #aaa;
}
div#content {
	margin: .4em ! important;
}
body {
	padding: 0px ! important;
}
div.corner_t {
    max-width: none ! important;
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
		<th colspan="3">Cert</th>
		<th>Admin</th>
		<th>S=bogus</th>
		<th>test</th>
		<th>S=test</th>
		<th>T-bind()</th>
		<th>Org-info</th>
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
	$ress = $this->data['results'][$orgkey];
	foreach($ress AS $i => $res) {

		echo('<tr class="' . ($classes[($i++ % 2)]) . '">');
		if (array_key_exists('description', $this->data['orgconfig'][$orgkey])) {
			echo('<td><a href="?orgtest=' . htmlentities($orgkey) . '">');
			echo htmlspecialchars(
				$this->getTranslation(
						SimpleSAML_Utilities::arrayize($this->data['orgconfig'][$orgkey]['description'], 'en')
					)
				);
			if(count($ress) > 1) {
				echo(' (location ' . ($i) . ')');
			}
			echo('</a></td>');
		} else {
			echo('<td><span style="color: #b4b4b4; font-size: x-small">NA</span> <tt>' . $orgkey . '</tt></td>');
		}
		showRes('config',  $res, $this);
		showRes('ping',  $res, $this);
		
		showRes('cert',  $res, $this);
		
		echo('<td>' . 
			(isset($res['cert']['expire']) ? $res['cert']['expire'] . '' : 
				'<span style="color: #b4b4b4; font-size: x-small">NA</span>'  ). 
			'</td>');

		echo('<td>' . 
			(isset($res['cert']['expireText']) ? $res['cert']['expireText'] : 
				'<span style="color: #b4b4b4; font-size: x-small">NA</span>'  ). 
			'</td>');
		
		showRes('adminBind',  $res, $this);
		showRes('ldapSearchBogus',  $res, $this);
		showRes('configTest',  $res, $this);
		showRes('ldapSearchTestUser',  $res, $this);
		showRes('ldapBindTestUser',  $res, $this);
		showRes('getTestOrg',  $res, $this);
		showRes('configMeta',  $res, $this);
		
		
		
		echo('<td style="text-align: right">' . ceil($res['time']*1000) . '&nbsp;ms</td>');
		echo('</tr>');
		
		if ($this->data['showcomments'] && array_key_exists('comment', $this->data['orgconfig'][$orgkey])) {
			echo('<tr><td style="color: #400; padding-left: 5em; font-family: \'Arial Narrow\'; font-size: 85%" colspan="11">' . $this->data['orgconfig'][$orgkey]['comment'] . '</td></tr>');
		}
	}	
	
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
	echo('<p>[ <a href="?">load more entries</a> | <a href="?reset=1">reset all entries</a> ]');
} else {
	echo('<p>[ <a href="?reset=1">reset all entries</a> ]');
}
if (!$this->data['showcomments']) {
	echo('<p>[ <a href="?showcomments=1">show comments</a> ]');	
}


?>

<?php $this->includeAtTemplateBase('includes/footer.php'); ?>
