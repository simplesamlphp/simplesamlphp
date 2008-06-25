<?php
$this->data['header'] = $this->t('metadata_' . $this->data['header']);
$this->includeAtTemplateBase('includes/header.php');
?>

	<div id="content">

		<h2><?php echo $this->data['header']; ?></h2>
		
		<p><?php echo $this->t('metadata_intro'); ?></p>
		
		<?php if (isset($this->data['metaurl'])) { ?>
			<p><?php echo($this->t('metadata_xmlurl', TRUE, TRUE, array('%METAURL%' => htmlspecialchars($this->data['metaurl'])))); ?><br />
			<input type="text" style="width: 90%" value="<?php echo htmlspecialchars($this->data['metaurl']); ?>" /></p>
		<?php } ?>
		<h2><?php echo($this->t('metadata_metadata')); ?></h2>
		
		<p><?php echo($this->t('metadata_xmlformat')); ?></p>
		
		<pre class="metadatabox"><?php echo $this->data['metadata']; ?></pre>
		
		
		<p><?php echo($this->t('metadata_simplesamlformat')); ?></p>
		
		<pre class="metadatabox"><?php echo $this->data['metadataflat']; ?></pre>
		
		

		
		<?php if(array_key_exists('sendmetadatato', $this->data)) {
			$param = array('%FEDERATION%' => $this->data['federationname']);
			?>
		

			<div style="border: 1px solid #444; margin: 2em; padding: 1em; background: #eee">
			
				<h2><?php echo $this->t('metadata_send_title', TRUE, TRUE, $param); ?></h2>
				
				<p><?php echo $this->t('metadata_send_hasdetected', TRUE, TRUE, $param); ?></p>
				
				<p><?php echo $this->t('metadata_send_desc', TRUE, TRUE, $param); ?></p>
					
				<form action="<?php echo $this->data['sendmetadatato']; ?>" method="post">

					<p><?php echo $this->t('metadata_send_email', TRUE, TRUE, $param); ?>
						<input type="text" size="25" name="email" value="" />
					</p>
					
					<input type="hidden" name="action" value="metadata" />
					<input type="hidden" name="metadata" value="<?php echo urlencode(base64_encode($this->data['metadata'])); ?>" />
					<input type="hidden" name="techemail" value="<?php echo $this->data['techemail']; ?>" />
					<input type="hidden" name="version" value="<?php echo $this->data['version']; ?>" />
					<input type="hidden" name="defaultidp" value="<?php echo htmlspecialchars($this->data['defaultidp']); ?>" />
					<input type="submit" name="send" value="<?php echo $this->t('metadata_send_send', TRUE, TRUE, $param); ?>" />
					
				</form>
				
			</div>
		
		<?php } ?>

<?php $this->includeAtTemplateBase('includes/footer.php'); ?>