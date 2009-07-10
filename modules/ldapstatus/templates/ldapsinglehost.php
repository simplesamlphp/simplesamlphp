<?php

$this->data['header'] = 'LDAP status for ' . $this->getTranslation($this->data['org']['description']);
$this->data['head'] = '<style type="text/css">
table.statustable td {
	border-bottom: 1px solid #eee;
}
.ui-tabs-panel { padding: .5em }
div#content {
	margin: .4em ! important;
}
p {
	margin: 1em 0px 2px 0px
}
div.inbox p { margin: 0; }

div#ldapstatus p {
	margin: none;
}
div#ldapstatus .testtext p {
	margin: 3px ! important; 
	padding: 0px ;
}
</style>';

$this->data['head'] .= '<script type="text/javascript">

$(document).ready(function() {
	$("#tabs").tabs();
	$("#tabdiv").tabs();
	$("#ldapstatus").accordion({
		header: "h3"
	});
});
</script>';

$this->data['jquery'] = array('version' => '1.6','core' => TRUE, 'ui' => TRUE, 'css' => TRUE);
$this->data['hideLanguageBar'] = TRUE;
$this->includeAtTemplateBase('includes/header.php');

?>




<p style="text-align: right; margin-bottom: 1em;">[ <a href="?">return to list of all organizations</a> ]</p>

<?php

$t = $this;

function presentRes($restag, $header = 'na', $descr = '') {

	global $t;
	
#	echo('<div>');
	if (array_key_exists($restag, $t->data['res'])) {
		$res = $t->data['res'][$restag];
		if ($res[0]) {	
			echo('<h3><a href="#">');
			echo('<img style="display: inline; border: none; position: relative; top: 3px" src="/' . $t->data['baseurlpath'] . 'resources/icons/accept.png" />&nbsp;');
			echo($header);
			if (isset($res['time'])) {
				if ($res['time'] > 0.7) {
					echo('<span style="color: #a00; font-weight: bold"> (' . number_format(1000*$res['time'], 0) . ' ms)</span> slow response'); 
				} else {
					echo(' (' . number_format(1000*$res['time'], 0) . ' ms)'); 
				}
			}
			echo('</a></h3>');
			echo('<div class="testtext ok">');
			if (!empty($descr))
				echo('<p>' .  $descr . '</p>');
			echo('<p>OK: ' . $res[1] . '</p>');
			if (isset($res['expire'])) {
				echo('<p>Certificate expires in ' . $res['expire'] . ' days</p>');
			}
			if (isset($res['expireText'])) {
				echo('<p>Certificate expires on ' . $res['expireText'] . '</p>');
			}
			echo('</div>');
		} else {
			echo('<h3><a href="#">');
			echo('<img style="display: inline; border: none; position: relative; top: 3px" src="/' . $t->data['baseurlpath'] . 'resources/icons/delete.png" />&nbsp;');
			echo($header);
			echo('</a></h3>');
			echo('<div class="testtext failed">');
			if (!empty($descr))
				echo('<p>' .  $descr . '</p>');
			echo('<p>' . $res[1] . '</p>');
			if (isset($res['expire'])) {
				echo('<p>Certificate expires in ' . $res['expire'] . ' days</p>');
			}
			if (isset($res['expireText'])) {
				echo('<p>Certificate expires on ' . $res['expireText'] . '</p>');
			}
			echo('</div>');
		}
	} else {
// 		echo('<h3><a href="#">');
// 		echo('<img style="display: inline; position: relative; top: 3px" src="/' . $t->data['baseurlpath'] . 'resources/icons/bullet16_grey.png" />&nbsp;');
// 		echo($header);
// 		echo(' (NA)</a></h3>');
// 		echo('<div>NA</div>');
	}
#	echo('</div>');
}


$ok = TRUE;
foreach ($this->data['res'] AS $tag => $res) {
	if ($tag == 'time') continue;
	if ($res[0] == 0)  $ok = FALSE;
#	echo ('failed: ' . $tag . '[' . $res[0] . ']'); }
}



echo('<div id="tabdiv">
	<ul class="tabset_tabs">
		<li><a href="#ldaptests">LDAP Tests</a></li>
		<li><a href="#debug">Debug log</a></li>');

if (array_key_exists('secretURL', $this->data)) {
	echo('<li><a href="#access">Access URL</a></li>');
}

	echo('<li><a href="#cli">Command line</a></li>');

echo ('</ul>');
	
echo '<div id="ldaptests" class="tabset_content">';


?>





<div id="ldapstatus" >

<?php
if ($ok) {
	echo('<h3><a href="#">');
	echo('<img style="display: inline; position: relative; border: none; top: 3px" src="/' . $t->data['baseurlpath'] . 'resources/icons/accept.png" />&nbsp;');
	echo('Overall status');
	echo('</a></h3>');
	echo('<div>All checks was OK</div>');
} else {
	echo('<h3><a href="#">');
	echo('<img style="display: inline; position: relative; border: none; top: 3px" src="/' . $t->data['baseurlpath'] . 'resources/icons/delete.png" />&nbsp;');
	echo('Overall status');
	echo('</a></h3>');
	echo('<div>At least one test failed.</div>');
}

presentRes('config', 'Check configuration', 'Checking configuration if all parameters are set properly'); 
presentRes('ping', 'Ping', 'Trying to setup a TCP socket against the LDAP host.');
presentRes('cert', 'Check certificate');
presentRes('adminBind', 'Admin bind()', 'Trying to bind() with the LDAP admin user');
presentRes('ldapSearchBogus', 'Bogus search', 'Trying to search LDAP with a bogus user (should return zero results, and no error)');
presentRes('configTest', 'Test user configured', 'Check if test-user is configured.');
presentRes('ldapSearchTestUser', 'Search for test user', 'Search LDAP for the DN of the test user given a specific eduPersonPrincipalName');
presentRes('ldapBindTestUser', 'Test user bind()', 'Trying to bind() as the DN found when searching for the test user');
presentRes('getTestOrg', 'Get organization attributes', 'Getting attributes from referred eduOrgDN and eduOrgUnitDN (from test user)');
presentRes('configMeta', 'Contact information registered', 'Checking for additional contact addresss in configuration.');
presentRes('schema', 'Schema version', 'Checking if most recent version of the LDAP schema is used.');

?>
</div><!-- end ldap status -->
</div><!-- end ldap test tab -->


<?php

echo '<div id="cli" class="tabset_content">';
foreach($this->data['cli'] AS $clientry) {
	echo('<p>' . $clientry[0] . '</p>');
	echo('<pre>' . $clientry[1] . '</pre>');
}
echo '</div>';



echo '<div id="debug" class="tabset_content">';

#echo('<h3><a href="#">Debug log</a></h3>');
echo('<pre >');
echo join("\n", $this->data['debugLog']);
echo('</pre>');

echo('</div><!-- end debug tab -->');


if (array_key_exists('secretURL', $this->data)) {
	
	echo('<div id="access">');
	echo('<p>This page can be accessed by this secret URL:<br />');
#	echo('<pre  style="border: 1px solid #aaa; background: #eee; color: #999;c padding: .1em; margin: .2em;">');

	echo('<input type="text" style="width: 95%" value="' . htmlentities($this->data['secretURL']) . '" />');
#	echo('</pre>');
	echo('</p></div>');
	
}

echo('</div><!-- end all tabs -->');



$this->includeAtTemplateBase('includes/footer.php'); 

