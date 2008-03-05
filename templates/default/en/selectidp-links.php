<?php $this->includeAtTemplateBase('includes/header.php'); ?>

	
	<div id="content">

		<h2><?php if (isset($data['header'])) { echo $data['header']; } else { echo "Select your IdP"; } ?></h2>
		
		<p>Please select the identity provider where you want to authenticate:</p>
		
		
		<?php

		
		if (!empty($this->data['preferredidp']) && array_key_exists($this->data['preferredidp'], $this->data['idplist'])) {
			$idpentry = $this->data['idplist'][$this->data['preferredidp']];
			echo '<div class="preferredidp">';
			echo '	<img src="/' . $this->data['baseurlpath'] .'resources/icons/star.png" style="float: right" />';
			echo '	<h3>' . htmlspecialchars($idpentry['name']) . '</h3>';
			echo '	<p>' . htmlspecialchars($idpentry['description']) . '<br />';
			echo '	[ <a href="' . $data['urlpattern'] . htmlspecialchars($idpentry['entityid']) . '">Select this IdP</a>]</p>';
			echo '</div>';
		}
		
		
		foreach ($data['idplist'] AS $idpentry) {
			if ($idpentry['entityid'] != $this->data['preferredidp']) {
				echo '<h3>' . htmlspecialchars($idpentry['name']) . '</h3>';
				echo '<p>' . htmlspecialchars($idpentry['description']) . '<br />';
				echo '[ <a href="' . $data['urlpattern'] . htmlspecialchars($idpentry['entityid']) . '">Select this IdP</a>]</p>';
			}
		}
		
		?>

		
<?php $this->includeAtTemplateBase('includes/footer.php'); ?>
