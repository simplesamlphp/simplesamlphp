<?php
$this->data['header'] = 'LDAP status page';
$this->includeAtTemplateBase('includes/header.php');

?>

<h2>Certificate check</h2>

<table class="attributes" style="font-size: small; width: 100%; border: 1px solid #aaa">
	<tr>
		<th>Host</th>
		<th colspan="3">Expires</th>
		<th>Issuer</th>
	</tr>

<?php

$i = 0;
$classes = array('odd', 'even');

# $this->data['results']
foreach($this->data['results'] as $orgkey => $org) {
	echo('<tr class="' . ($classes[($i++ % 2)]) . '">');
	
	
	if (array_key_exists('error', $this->data['resultsm'][$orgkey])) {
	
		
		echo '<td colspan="2">' . $orgkey . '</td><td>';
		echo '<img src="/' . $this->data['baseurlpath'] . 'resources/icons/delete.png" /></td>';
		echo '<td colspan="2">' . $this->data['resultsm'][$orgkey]['error'];
		echo '</td>';

	
	} else {
		
		echo '<td>' . $orgkey . '</td><td>' . $org . ' days</td><td>';
		
		if ($org < 30) {
			echo '<img src="/' . $this->data['baseurlpath'] . 'resources/icons/delete.png" />';
		} else {
			echo '<img src="/' . $this->data['baseurlpath'] . 'resources/icons/accept.png" />';
		}
		echo '</td>';
		echo '<td>';
		if (array_key_exists('expire', $this->data['resultsm'][$orgkey])) echo $this->data['resultsm'][$orgkey]['expire'];
		echo '</td>';
		echo '<td>';
		if (array_key_exists('issuer', $this->data['resultsm'][$orgkey])) echo $this->data['resultsm'][$orgkey]['issuer'];
		echo '</td>';

	}
	echo('</tr>');
	
}
?>
</table>
<?php $this->includeAtTemplateBase('includes/footer.php'); ?>
