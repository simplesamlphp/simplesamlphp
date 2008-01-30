<?php $this->includeAtTemplateBase('includes/header.php'); ?>


	
	<div id="content">

		<h2><?php if (isset($data['header'])) { echo $data['header']; } else { echo "Select your IdP"; } ?></h2>
		
		<p>Please select the identity provider where you want to authenticate:</p>
		
		<form method="get" action="<?php echo $data['urlpattern']; ?>">
		<input type="hidden" name="entityID" value="<?php echo htmlspecialchars($data['entityID']); ?>" />
		<input type="hidden" name="return" value="<?php echo htmlspecialchars($data['return']); ?>" />
		<input type="hidden" name="returnIDParam" value="<?php echo htmlspecialchars($data['returnIDParam']); ?>" />
		<select name="idpentityid">
		<?php
		
		foreach ($data['idplist'] AS $idpentry) {

			echo '<option value="'.htmlspecialchars($idpentry['entityid']).'"';
			if ($idpentry['entityid'] == $data['preferedidp']) echo ' selected="selected"';
			echo '>'.htmlspecialchars($idpentry['name']).'</option>';
		
		}
		?>
		</select>
		<input type="submit" value="Select"/>
		</form>

		
<?php $this->includeAtTemplateBase('includes/footer.php'); ?>
