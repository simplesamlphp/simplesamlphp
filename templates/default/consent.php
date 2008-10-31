<?php 
	$this->includeAtTemplateBase('includes/header.php');
	
	$this->includeLanguageFile('consent.php'); 
	$this->includeInlineTranslation('SPNAME', $this->data['sp_name']);
	$this->includeInlineTranslation('IDPNAME', $this->data['idp_name']);
	$this->includeInlineTranslation('SPDESC', $this->data['sp_description']);
?>


		<p>
		<?php echo $this->t('consent_accept', array('SPNAME' => '', 'IDPNAME' => '', 'SPDESC' => '')) ?>
		</p>

		<?php if ($this->data['sppp'] !== FALSE) {
			echo "<p>" . htmlspecialchars($this->t('consent_privacypolicy')) . " ";
			echo "<a target='_new_window' href='" . htmlspecialchars($this->data['sppp']) . "'>" . htmlspecialchars($this->t('spname')) . "</a>";
			echo "</p>";
		} ?>

		<form style="display: inline" action="<?php echo htmlspecialchars($this->data['consenturl']); ?>">
			<input type="submit" id="yesbutton" value="<?php echo htmlspecialchars($this->t('yes')) ?>" />
			<input type="hidden" name="consent" value="<?php echo htmlspecialchars($this->data['consent_cookie']); ?>" />
			<input type="hidden" name="RequestID" value="<?php echo htmlspecialchars($this->data['requestid']); ?>" />
			<?php if($this->data['usestorage']) { ?>
				<input type="checkbox" name="saveconsent" id="saveconsent" value="1" /> <?php echo htmlspecialchars($this->t('remember')) ?>
			<?php } ?>
		</form>
		<form style="display: inline; margin-left: .5em;" action="<?php echo htmlspecialchars($this->data['noconsent']); ?>" method="GET">
<?php
if(array_key_exists('noconsent_data', $this->data)) {
	foreach($this->data['noconsent_data'] as $name => $value) {
		echo('<input type="hidden" name="' . htmlspecialchars($name) . '" value="' . htmlspecialchars($value) . '" />');
	}
}
?>
			<input type="submit" id="nobutton" value="<?php echo htmlspecialchars($this->t('no')) ?>" />
		</form>
		<p>

		<table style="font-size: x-small">
<?php
			$attributes = $this->data['attributes'];
			foreach ($attributes AS $name => $value) {
					
				if (isset($this->data['attribute_' . htmlspecialchars(strtolower($name)) ])) {
				  $name = $this->data['attribute_' . htmlspecialchars(strtolower($name))];
			  }
				$name = $this->t('attribute_'.strtolower($name)); // translate
				if (sizeof($value) > 1) {
					echo '<tr><td>' . htmlspecialchars($name) . '</td><td><ul>';
					foreach ($value AS $v) {
						echo '<li>' . htmlspecialchars($v) . '</li>';
					}
					echo '</ul></td></tr>';
				} else {
					echo '<tr><td>' . htmlspecialchars($name) . '</td><td>' . htmlspecialchars($value[0]) . '</td></tr>';
				}
			}

?>
		</table>


<?php $this->includeAtTemplateBase('includes/footer.php'); ?>