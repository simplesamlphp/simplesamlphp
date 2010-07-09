<?php
$this->data['header'] = $this->t('metadata_' . $this->data['header']);
$this->includeAtTemplateBase('includes/header.php');
?>


		<h2><?php echo $this->data['header']; ?></h2>
		
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