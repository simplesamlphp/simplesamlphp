<?php $this->includeAtTemplateBase('includes/header.php'); ?>


	
	<div id="content">

		<h2><?php if (isset($this->data['header'])) { echo $this->data['header']; } else { echo "Select your IdP"; } ?></h2>
		
		<p>Please select the identity provider where you want to authenticate:</p>

		<form method="get" action="<?php echo $this->data['urlpattern']; ?>">
		<input type="hidden" name="entityID" value="<?php echo htmlspecialchars($this->data['entityID']); ?>" />
		<input type="hidden" name="return" value="<?php echo htmlspecialchars($this->data['return']); ?>" />
		<input type="hidden" name="returnIDParam" value="<?php echo htmlspecialchars($this->data['returnIDParam']); ?>" />
		<select name="idpentityid">
		<?php
			
		foreach ($this->data['idplist'] AS $idpentry) {

			echo '<option value="'.htmlspecialchars($idpentry['entityid']).'"';
			if (isset($this->data['preferredidp']) && 
				$idpentry['entityid'] == $this->data['preferredidp']) 
				echo ' selected="selected"';
				
			echo '>'.htmlspecialchars($idpentry['name']).'</option>';
		
		}
		?>
		</select>
		<input type="submit" value="Select"/>
		</form>

		
<?php $this->includeAtTemplateBase('includes/footer.php'); ?>
