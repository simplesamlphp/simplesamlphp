<?php $this->includeAtTemplateBase('includes/header.php'); ?>

	
	<div id="content">


		<?php
		
		
		function showEntry($header, $list) {
		
			echo '<h3>' . $header . '</h3>';
		
			foreach ($list AS $entityid => $entity) {
				$name = $entityid;
				if (isset($entity['optional.found']['name'])) $name = $entity['optional.found']['name'];

				//print_r($entity);

				echo '<h4>' . htmlspecialchars($name) . '</h4>';
				if (isset($entity['optional.found']['description'])) {
					echo '<p>' . htmlspecialchars($entity['optional.found']['description']) . '</p>';
				}
				
				echo '<div style="margin-left: 1em">';
				echo '<div class="efieldlist"><h5>Required fields<h5>';
				echo '<dl>';
				foreach ($entity['required.found'] AS $key => $value) {
					echo '<dt>' . htmlspecialchars($key) . '</dt><dd>' . htmlspecialchars($value) . '</dd>';
				}
				echo '</dl>';


	
				if (count($entity['required.notfound']) > 0) {
					echo '</div><div class="efieldlist warning">';
					echo '<h5>The following required fields was not found</h5><ul>';
					foreach ($entity['required.notfound'] AS $key) {
						echo '<li>' . htmlspecialchars($key) . '</li>';
					}
					echo '</ul>';				
				}
			
				
				if (count($entity['optional.found']) > 0) {
					echo '</div><div class="efieldlist">';
					echo '<h5>Optional fields</h5>';
					echo '<dl>';
					foreach ($entity['optional.found'] AS $key => $value) {
						echo '<dt>' . htmlspecialchars($key) . '</dt><dd>' . htmlspecialchars($value) . '</dd>';
					}
					echo '</dl>';
				}
				
				
	
				if (count($entity['optional.notfound']) > 0) {
					echo '</div><div class="efieldlist info">';				
					echo '<h5>The following optional fields was not found:</h5><ul>';
					foreach ($entity['optional.notfound'] AS $key) {
						echo '<li>' . htmlspecialchars($key) . '</li>';
					}
					echo '</ul>';				
				}
				
				if (count($entity['leftovers']) > 0) {
					echo '</div><div class="efieldlist warning">';
					echo '<h5>The following fields was not reckognized</h5><ul>';
					foreach ($entity['leftovers'] AS $key => $value) {
						echo '<li>' . htmlspecialchars($key) . '</li>';
					}
					echo '</ul>';				
				}
				echo '</div></div>';
			}
		}
		
		
		if (array_key_exists('metadata.saml20-sp-hosted', $data)) 
			showEntry('SAML 2.0 Service Provider (Hosted)', $data['metadata.saml20-sp-hosted']);
		if (array_key_exists('metadata.saml20-sp-remote', $data)) 
			showEntry('SAML 2.0 Service Provider (Remote)', $data['metadata.saml20-sp-remote']);
		if (array_key_exists('metadata.saml20-idp-hosted', $data)) 
			showEntry('SAML 2.0 Identity Provider (Hosted)', $data['metadata.saml20-idp-hosted']);
		if (array_key_exists('metadata.saml20-idp-remote', $data)) 
			showEntry('SAML 2.0 Identity Provider (Remote)', $data['metadata.saml20-idp-remote']);

		if (array_key_exists('metadata.shib13-sp-hosted', $data)) 
			showEntry('Shib 1.3 Service Provider (Hosted)', $data['metadata.shib13-sp-hosted']);
		if (array_key_exists('metadata.shib13-sp-remote', $data)) 
			showEntry('Shib 1.3 Service Provider (Remote)', $data['metadata.shib13-sp-remote']);
		if (array_key_exists('metadata.shib13-idp-hosted', $data)) 
			showEntry('Shib 1.3 Identity Provider (Hosted)', $data['metadata.shib13-idp-hosted']);
		if (array_key_exists('metadata.shib13-idp-remote', $data)) 
			showEntry('Shib 1.3 Identity Provider (Remote)', $data['metadata.shib13-idp-remote']);

		
		?>

		
<?php $this->includeAtTemplateBase('includes/footer.php'); ?>
