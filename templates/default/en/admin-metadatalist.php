<?php $this->includeAtTemplateBase('includes/header.php'); ?>

	<div id="header">
		<h1>Metadata overview</h1>
		<div id="poweredby"><img src="/<?php echo $data['baseurlpath']; ?>resources/icons/bino.png" alt="Bino" /></div>
	</div>
	
	<div id="content">

		<h2><?php if (isset($data['header'])) { echo $data['header']; } else { echo "Metadata overview"; } ?></h2>
		
		<p>Here is a list of metadata that is configured for your installation.</p>
		
		<p>[ <a href="../">Go back to installation main page</a> ]</p>
		
		<?php
		
		
		function showEntry($header, $list) {
		
			echo '<h3>' . $header . '</h3>';
		
			foreach ($list AS $entityid => $entity) {
				$name = $entityid;
				if (isset($entity['optional.found']['name'])) $name = $entity['optional.found']['name'];

				//print_r($entity);

				echo '<h4>' . $name . '</h4>';
				if (isset($entity['optional.found']['description'])) {
					echo '<p>' . $entity['optional.found']['description'] . '</p>';
				}
				
				echo '<p>Required fields</p>';
				echo '<table style="width: 100%; border: 1px solid #eee"><tr><th>Key</th><th>Value</th></tr>';
				foreach ($entity['required.found'] AS $key => $value) {
					echo '<tr><td>' . $key . '</td><td>' . $value . '</td></tr>';
				}
				echo '</table>';
	
				if (count($entity['required.notfound']) > 0) {
					echo '<p>The following required fields was not found:<ul>';
					foreach ($entity['required.notfound'] AS $key) {
						echo '<li>' . $key . '</li>';
					}
					echo '</ul>';				
				}
				
				if (count($entity['optional.found']) > 0) {
					echo '<p>Optional fields</p>';
					echo '<table><tr><th>Key</th><th>Value</th></tr>';
					foreach ($entity['optional.found'] AS $key => $value) {
						echo '<tr><td>' . $key . '</td><td>' . $value . '</td></tr>';
					}
					echo '</table>';
				}
	
				if (count($entity['optional.notfound']) > 0) {
					echo '<p>The following optional fields was not found:<ul>';
					foreach ($entity['optional.notfound'] AS $key) {
						echo '<li>' . $key . '</li>';
					}
					echo '</ul>';				
				}
				
				if (count($entity['leftovers']) > 0) {
					echo '<p>The following fields was not reckognized:<ul>';
					foreach ($entity['leftovers'] AS $key => $value) {
						echo '<li>' . $key . '</li>';
					}
					echo '</ul>';				
				}
			
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

		
		?>

		
<?php $this->includeAtTemplateBase('includes/footer.php'); ?>
