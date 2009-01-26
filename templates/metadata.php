<?php
$this->data['header'] = $this->t('metadata_' . $this->data['header']);
$this->includeAtTemplateBase('includes/header.php');
?>


		<h2><?php echo $this->data['header']; ?></h2>
		
		<?php 
		
		if(array_key_exists('idpsend', $this->data) && is_array($this->data['idpsend']) && count($this->data['idpsend']) > 0 ) {

			if ($this->data['adminok']) {

		?>
			<div style="border: 1px solid #444; margin: .5em 2em .5em 2em; padding: .5em 1em 1em 1em; background: #FFFFCC">

				


				<h2 style="margin-top: 0px" ><?php echo $this->t('metadata_send_title2'); ?></h2>
				
				<?php

				if ($this->data['sentok'] === TRUE) {
				
					echo '<p><strong>' . $this->t('metadata_send_success') . '</strong></p>';
				
				}
				
				?>
				

				<form action="metadata.php" method="post">

					<p><?php echo $this->t('metadata_send_select'); ?>					
					<select name="sendtoidp">
					<?php
						foreach ($this->data['idpsend'] AS $entityid => $idpmeta) {
							$name = array_key_exists('name', $idpmeta) ? $idpmeta['name'] : $entityid;
							echo '<option value="' . htmlspecialchars($entityid) . '">';
							if (is_array($name)) {
								echo htmlspecialchars($this->t($name));
							} else {
								echo htmlspecialchars($name);
							}
							echo '</option>';
						}
					?>
					</select> </p>

					<p><?php echo $this->t('metadata_send_email2'); ?><br />
						
						<input type="text" size="25" name="email" value="<?php echo ($this->data['techemail']) ? $this->data['techemail'] : ''  ?>" />
					</p>
					<input type="hidden" name="output" value="xhtml" />
					<input type="submit" name="send" value="<?php echo $this->t('metadata_send_sendbutton'); ?>" />
					
				</form>


			</div>
		
		<?php 
		
			} else {
				
				echo '<div style="border: 1px solid #444; margin: .5em 2em .5em 2em; padding: .5em 1em 1em 1em; background: #FFFFCC">';
				echo '	<a href="' . htmlentities($this->data['adminlogin']) . '">';
				echo $this->t('metadata_send_adminlogin');
				echo '	</a>';
				echo '</div>';
				
				
			}

		
		} 
		?>
		
		
		
		
		<p><?php echo $this->t('metadata_intro'); ?></p>
		
		<?php if (isset($this->data['metaurl'])) { ?>
			<p><?php echo($this->t('metadata_xmlurl', array('%METAURL%' => htmlspecialchars($this->data['metaurl'])))); ?><br />
			<input type="text" style="width: 90%" value="<?php echo htmlspecialchars($this->data['metaurl']); ?>" /></p>
		<?php } ?>
		<h2><?php echo($this->t('metadata_metadata')); ?></h2>
		
		<p><?php echo($this->t('metadata_xmlformat')); ?></p>
		
		<pre class="metadatabox"><?php echo $this->data['metadata']; ?>
</pre>
		
		
		<p><?php echo($this->t('metadata_simplesamlformat')); ?></p>
		
		<pre class="metadatabox"><?php echo $this->data['metadataflat']; ?>
</pre>
		
		

		


<?php $this->includeAtTemplateBase('includes/footer.php'); ?>