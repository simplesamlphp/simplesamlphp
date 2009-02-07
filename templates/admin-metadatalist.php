<?php
$this->data['header'] = $this->t('metaover_header');
$this->data['icon'] = 'bino.png';

$this->includeAtTemplateBase('includes/header.php');
?>



		<p><?php echo $this->t('metaover_intro'); ?></p>

		<?php
		
		
		function showEntry($t, $id) {

			if (!array_key_exists($id, $t->data)) {
				/* This metadata does not exist. */
				return;
			}


			$header = $t->t('metaover_group_' . $id);
			$list = $t->data[$id];
			$baseurl = $t->data['baseurlpath'];

			echo '<h3>' . $header . '</h3>';
			

			
			foreach ($list AS $entityid => $entity) {
			
				$encodedEntityID = preg_replace('/=/', '_', base64_encode($entityid . $header));
				$name = $entityid;
				if (isset($entity['optional.found']['name'])) $name = $entity['optional.found']['name'];

				//print_r($entity);
				
				$warning = false;
				if (count($entity['leftovers']) > 0) $warning = TRUE;
				if (count($entity['required.notfound']) > 0) $warning = TRUE;

				$t->includeInlineTranslation('spname', $name);
				$name = $t->t('spname', array(), false, true);

				echo '<h4 style="padding-left: 2em; clear: both;" onclick="document.getElementById(\'metadatasection-' . $encodedEntityID . '\').style.display=\'block\';">' . htmlspecialchars($name) . '</h4>';
				
				if ($warning) {
					echo '<div><img src="/' . $baseurl . 'resources/icons/caution.png" style="float: left; margin-right: 1em" />';
					echo $t->t('metaover_errorentry') . '</div>';
				}
				
				echo '<div id="metadatasection-' . $encodedEntityID . '" style="display: none">';
				
				if (isset($entity['optional.found']['description'])) {
					$t->includeInlineTranslation('spdescription', $entity['optional.found']['description']);
					$description = $t->t('spdescription');
					echo '<p>' . htmlspecialchars($description) . '</p>';
				}
				
				echo '<div style="margin-left: 1em">';
				echo '<div class="efieldlist"><h5>' . $t->t('metaover_required_found') . '</h5>';
				echo '<dl>';
				foreach ($entity['required.found'] AS $key => $value) {
					echo '<dt>' . htmlspecialchars($key) . '</dt><dd>' . htmlspecialchars(var_export($value, TRUE)) . '</dd>';
				}
				echo '</dl>' . "\n\n";


	
				if (count($entity['required.notfound']) > 0) {
					echo '</div><div class="efieldlist warning">';
					echo '<h5>' . $t->t('metaover_required_not_found') . '</h5><ul>';
					foreach ($entity['required.notfound'] AS $key) {
						echo '<li>' . htmlspecialchars($key) . '</li>';
					}
					echo '</ul>';				
				}
			
				
				if (count($entity['optional.found']) > 0) {
					echo '</div><div class="efieldlist">';
					echo '<h5>' . $t->t('metaover_optional_found') . '</h5>';
					echo '<dl>';
					foreach ($entity['optional.found'] AS $key => $value) {
						echo '<dt>' . htmlspecialchars($key) . '</dt><dd>' . htmlspecialchars(var_export($value, TRUE)) . '</dd>';
					}
					echo '</dl>';
				}
				
				
	
				if (count($entity['optional.notfound']) > 0) {
					echo '</div><div class="efieldlist info">';				
					echo '<h5>' . $t->t('metaover_optional_not_found') . '</h5><ul>';
					foreach ($entity['optional.notfound'] AS $key) {
						echo '<li>' . htmlspecialchars($key) . '</li>';
					}
					echo '</ul>';				
				}
				
				if (count($entity['leftovers']) > 0) {
					echo '</div><div class="efieldlist warning">';
					echo '<h5>' . $t->t('metaover_unknown_found') . '</h5><ul>';
					foreach ($entity['leftovers'] AS $key => $value) {
						echo '<li>' . htmlspecialchars($key) . '</li>';
					}
					echo '</ul>';				
				}
				echo '</div></div>';
				echo '</div>' . "\n\n";;
			}
		}
		
		
		showEntry($this, 'metadata.saml20-sp-hosted');
		showEntry($this, 'metadata.saml20-sp-remote');
		showEntry($this, 'metadata.saml20-idp-hosted');
		showEntry($this, 'metadata.saml20-idp-remote');

		showEntry($this, 'metadata.shib13-sp-hosted');
		showEntry($this, 'metadata.shib13-sp-remote');
		showEntry($this, 'metadata.shib13-idp-hosted');
		showEntry($this, 'metadata.shib13-idp-remote');

		showEntry($this, 'metadata.wsfed-sp-hosted');
		showEntry($this, 'metadata.wsfed-idp-remote');

		
		?>

		
<?php $this->includeAtTemplateBase('includes/footer.php'); ?>
