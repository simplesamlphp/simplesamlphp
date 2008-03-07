<?php $this->includeAtTemplateBase('includes/header.php'); ?>


	<div id="content">

		<p>You are about to login to the service <strong><?php echo htmlspecialchars($data['sp_name']); ?></strong>. In the login proccess, the identity provider will send attributes containing information about your identity to this service. Do you accept this?</p>
		



		<form action="<?php echo htmlspecialchars($data['consenturl']); ?>">
			<input type="submit" value="Yes">
			<input type="hidden" name="consent" value="1">
			<input type="hidden" name="RequestID" value="<?php echo $this->data['requestid']; ?>">
			<?php if($this->data['usestorage']) { ?>
				<input type="checkbox" name="saveconsent" id="saveconsent" value="1"> remember consent
			<?php } ?>
		</form>
		<form action="<?php echo htmlspecialchars($this->data['noconsent']); ?>" method="GET">
			<input type="submit" value="No">
		</form>





		<table style="font-size: x-small">
<?php


			$attributes = $data['attributes'];
			foreach ($attributes AS $name => $value) {
					
				if (isset($this->data['attribute_' . htmlspecialchars(strtolower($name)) ])) {
				  $name = $this->data['attribute_' . htmlspecialchars(strtolower($name))];
			  }
					
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