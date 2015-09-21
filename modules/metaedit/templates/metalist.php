<?php

$this->data['jquery'] = array('version' => '1.6', 'core' => TRUE, 'ui' => TRUE, 'css' => TRUE);
$this->data['head']  = '<link rel="stylesheet" type="text/css" href="/' . $this->data['baseurlpath'] . 'module.php/metaedit/resources/style.css" />' . "\n";
// $this->data['head'] .= '<script type="text/javascript">
// $(document).ready(function() {
// 	$("#tabdiv").tabs();
// });
// </script>';

$this->includeAtTemplateBase('includes/header.php');


echo('<h1>Metadata Registry</h1>');

echo('<p>Here you can register new SAML entities. You are successfully logged in as ' . htmlspecialchars($this->data['userid']) . '</p>');

echo('<h2>Your entries</h2>');
echo('<table class="metalist" style="width: 100%">');
$i = 0; $rows = array('odd', 'even');
foreach($this->data['metadata']['mine'] AS $md ) {
	$i++; 
	echo('<tr class="' . $rows[$i % 2] . '">
		<td>' . htmlspecialchars($md['name']) . '</td>
		<td><tt>' . htmlspecialchars($md['entityid']) . '</tt></td>
		<td>
			<a href="edit.php?entityid=' . urlencode($md['entityid']) . '">edit</a>
			<a href="index.php?delete=' . urlencode($md['entityid']) . '">delete</a>
		</td></tr>');
}
if ($i == 0) {
	echo('<tr><td colspan="3">No entries registered</td></tr>');
}
echo('</table>');

echo('<p><a href="edit.php">Add new entity</a> | <a href="xmlimport.php">Add from SAML 2.0 XML metadata</a></p>');

echo('<h2>Other entries</h2>');
echo('<table class="metalist" style="width: 100%">');
$i = 0; $rows = array('odd', 'even');
foreach($this->data['metadata']['others'] AS $md ) {
	$i++; 
	echo('<tr class="' . $rows[$i % 2] . '">
		<td>' . htmlspecialchars($md['name']) . '</td>
		<td><tt>' . htmlspecialchars($md['entityid']) . '</tt></td>
		<td>' . (isset($md['owner']) ? htmlspecialchars($md['owner']) : 'No owner') . '
		</td></tr>');
}
if ($i == 0) {
	echo('<tr><td colspan="3">No entries registered</td></tr>');
}
echo('</table>');

$this->includeAtTemplateBase('includes/footer.php');

