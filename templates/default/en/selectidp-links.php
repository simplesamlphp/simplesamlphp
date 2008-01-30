<?php $this->includeAtTemplateBase('includes/header.php'); ?>

	
	<div id="content">

		<h2><?php if (isset($data['header'])) { echo $data['header']; } else { echo "Select your IdP"; } ?></h2>
		
		<p>Please select the identity provider where you want to authenticate:</p>
		
		
		<?php
		
		foreach ($data['idplist'] AS $idpentry) {
			
			echo '<h3>' . htmlspecialchars($idpentry['name']) . '</h3>';
			echo '<p>' . htmlspecialchars($idpentry['description']) . '<br />';
			echo '[ <a href="' . $data['urlpattern'] . htmlspecialchars($idpentry['entityid']) . '">Select this IdP</a>]</p>';
		
		}
		
		?>

		
<?php $this->includeAtTemplateBase('includes/footer.php'); ?>
