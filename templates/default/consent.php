<?php 
	$this->includeAtTemplateBase('includes/header.php');
	
	$this->includeLanguageFile('consent.php'); 
	$this->includeInlineTranslation('spname', $this->data['sp_name']);
?>

	<div id="content">

		<p><?php echo htmlspecialchars($this->t('consent_notice')); ?> <strong><?php echo htmlspecialchars($this->t('spname')); ?></strong>.
		<?php echo htmlspecialchars($this->t('consent_accept')) ?> 
		</p>

		<form style="display: inline" action="<?php echo htmlspecialchars($this->data['consenturl']); ?>">
			<input type="submit" value="<?php echo htmlspecialchars($this->t('yes')) ?>" />
			<input type="hidden" name="consent" value="<?php echo htmlspecialchars($this->data['consent_cookie']); ?>" />
			<input type="hidden" name="RequestID" value="<?php echo htmlspecialchars($this->data['requestid']); ?>" />
			<?php if($this->data['usestorage']) { ?>
				<input type="checkbox" name="saveconsent" id="saveconsent" value="1" /> <?php echo htmlspecialchars($this->t('remember')) ?>
			<?php } ?>
		</form>
		<form style="display: inline; margin-left: .5em;" action="<?php echo htmlspecialchars($this->data['noconsent']); ?>" method="GET">
			<input type="submit" value="<?php echo htmlspecialchars($this->t('no')) ?>" />
		</form>


		<table style="font-size: x-small">
<?php
			$attributes = $this->data['attributes'];
			foreach ($attributes AS $name => $value) {
					
				if (isset($this->data['attribute_' . htmlspecialchars(strtolower($name)) ])) {
				  $name = $this->data['attribute_' . htmlspecialchars(strtolower($name))];
			  }
				$name = $this->t('attribute_'.strtolower($name), true); // translate	
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