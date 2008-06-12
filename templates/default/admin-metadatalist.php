<?php $this->includeAtTemplateBase('includes/header.php'); ?>

	
	<div id="content">

		<p>To look at the details for an SAML entity, click on the SAML entity header.</p>

		<?php
		
		
		function showEntry($header, $list, $baseurl) {
		
			echo '<h3>' . $header . '</h3>';
			

			
			foreach ($list AS $entityid => $entity) {
			
				$encodedEntityID = preg_replace('/=/', '_', base64_encode($entityid . $header));
				$name = $entityid;
				if (isset($entity['optional.found']['name'])) $name = $entity['optional.found']['name'];

				//print_r($entity);
				
				$warning = false;
				if (count($entity['leftovers']) > 0) $warning = TRUE;
				if (count($entity['required.notfound']) > 0) $warning = TRUE;



				echo '<h4 style="padding-left: 2em; clear: both;" onclick="document.getElementById(\'metadatasection-' . $encodedEntityID . '\').style.display=\'block\';">' . htmlspecialchars($name) . '</h4>';
				
				if ($warning) {
					echo '<div><img src="/' . $baseurl . 'resources/icons/caution.png" style="float: left; margin-right: 1em" />';
					echo 'Error in this metadata entry.</div>';
				}
				
				echo '<div id="metadatasection-' . $encodedEntityID . '" style="display: none">';
				
				if (isset($entity['optional.found']['description'])) {
					echo '<p>' . htmlspecialchars($entity['optional.found']['description']) . '</p>';
				}
				
				echo '<div style="margin-left: 1em">';
				echo '<div class="efieldlist"><h5>Required fields</h5>';
				echo '<dl>';
				foreach ($entity['required.found'] AS $key => $value) {
					echo '<dt>' . htmlspecialchars($key) . '</dt><dd>' . htmlspecialchars(var_export($value, TRUE)) . '</dd>';
				}
				echo '</dl>' . "\n\n";


	
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
						echo '<dt>' . htmlspecialchars($key) . '</dt><dd>' . htmlspecialchars(var_export($value, TRUE)) . '</dd>';
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
				echo '</div>' . "\n\n";;
			}
		}
		
		
		if (array_key_exists('metadata.saml20-sp-hosted', $this->data)) 
			showEntry('SAML 2.0 Service Provider (Hosted)', $this->data['metadata.saml20-sp-hosted'], $this->data['baseurlpath']);
		if (array_key_exists('metadata.saml20-sp-remote', $this->data)) 
			showEntry('SAML 2.0 Service Provider (Remote)', $this->data['metadata.saml20-sp-remote'], $this->data['baseurlpath']);
		if (array_key_exists('metadata.saml20-idp-hosted', $this->data)) 
			showEntry('SAML 2.0 Identity Provider (Hosted)', $this->data['metadata.saml20-idp-hosted'], $this->data['baseurlpath']);
		if (array_key_exists('metadata.saml20-idp-remote', $this->data)) 
			showEntry('SAML 2.0 Identity Provider (Remote)', $this->data['metadata.saml20-idp-remote'], $this->data['baseurlpath']);

		if (array_key_exists('metadata.shib13-sp-hosted', $this->data)) 
			showEntry('Shib 1.3 Service Provider (Hosted)', $this->data['metadata.shib13-sp-hosted'], $this->data['baseurlpath']);
		if (array_key_exists('metadata.shib13-sp-remote', $this->data)) 
			showEntry('Shib 1.3 Service Provider (Remote)', $this->data['metadata.shib13-sp-remote'], $this->data['baseurlpath']);
		if (array_key_exists('metadata.shib13-idp-hosted', $this->data)) 
			showEntry('Shib 1.3 Identity Provider (Hosted)', $this->data['metadata.shib13-idp-hosted'], $this->data['baseurlpath']);
		if (array_key_exists('metadata.shib13-idp-remote', $this->data)) 
			showEntry('Shib 1.3 Identity Provider (Remote)', $this->data['metadata.shib13-idp-remote'], $this->data['baseurlpath']);

		if (array_key_exists('metadata.wsfed-sp-hosted', $this->data))
			showEntry('WS-Federation Service Provider (Hosted)', $this->data['metadata.wsfed-sp-hosted'], $this->data['baseurlpath']);
		if (array_key_exists('metadata.wsfed-idp-remote', $this->data))
			showEntry('WS-Federation Identity Provider (Remote)', $this->data['metadata.wsfed-idp-remote'], $this->data['baseurlpath']);

		
		?>

		
<?php $this->includeAtTemplateBase('includes/footer.php'); ?>
