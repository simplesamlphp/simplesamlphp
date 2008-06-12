<?php

if(!array_key_exists('header', $this->data)) {
	$this->data['header'] = 'selectidp';
}
$this->data['header'] = $this->t($this->data['header']);

$this->data['autofocus'] = 'preferredidp';

$this->includeAtTemplateBase('includes/header.php');

foreach ($this->data['idplist'] AS $idpentry) {
	if (isset($idpentry['name']))
		$this->includeInlineTranslation('idpname_' . $idpentry['entityid'], $idpentry['name']);
	if (isset($idpentry['description']))
		$this->includeInlineTranslation('idpdesc_' . $idpentry['entityid'], $idpentry['description']);
}


?>
	<div id="content">

		<h2><?php echo $this->data['header']; ?></h2>
		
		<p><?php echo $this->t('selectidp_full'); ?></p>
		
		
		<?php

		
		if (!empty($this->data['preferredidp']) && array_key_exists($this->data['preferredidp'], $this->data['idplist'])) {
			$idpentry = $this->data['idplist'][$this->data['preferredidp']];
			echo '<div class="preferredidp">';
			echo '	<img src="/' . $this->data['baseurlpath'] .'resources/icons/star.png" style="float: right" />';

			echo '	<h3>';
			if(array_key_exists('icon', $idpentry) && $idpentry['icon'] !== NULL) {
				$iconUrl = SimpleSAML_Utilities::resolveURL($idpentry['icon']);
				echo '<img style="display: inline" src="' . htmlspecialchars($iconUrl) . '" />';
			}
			echo htmlspecialchars($this->t('idpname_' . $idpentry['entityid'])) . '</h3>';

			echo '	<p>' . htmlspecialchars($this->t('idpdesc_' . $idpentry['entityid'])) . '<br />';
			echo '	[ <a id="preferredidp" href="' . $this->data['urlpattern'] . htmlspecialchars($idpentry['entityid']) . '">Select this IdP</a>]</p>';
			echo '</div>';
		}
		
		
		foreach ($this->data['idplist'] AS $idpentry) {
			if ($idpentry['entityid'] != $this->data['preferredidp']) {
				echo '	<h3>';
				if(array_key_exists('icon', $idpentry) && $idpentry['icon'] !== NULL) {
					$iconUrl = SimpleSAML_Utilities::resolveURL($idpentry['icon']);
					echo '<img style="display: inline" src="' . htmlspecialchars($iconUrl) . '" />';
				}
				echo htmlspecialchars($this->t('idpname_' . $idpentry['entityid'])) . '</h3>';

				echo '	<p>' . htmlspecialchars($this->t('idpdesc_' . $idpentry['entityid'])) . '<br />';
				echo '[ <a href="' . $this->data['urlpattern'] . htmlspecialchars($idpentry['entityid']) . '">Select this IdP</a>]</p>';
			}
		}
		
		?>

		
<?php $this->includeAtTemplateBase('includes/footer.php'); ?>
