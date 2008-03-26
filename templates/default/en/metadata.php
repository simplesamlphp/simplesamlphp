<?php $this->includeAtTemplateBase('includes/header.php'); ?>

	<div id="content">

		<h2><?php if (isset($this->data['header'])) { echo $this->data['header']; } else { echo "Some error occured"; } ?></h2>
		
		<p>Here is SAML 2.0 metadata that simpleSAMLphp has generated for you. You may send this SAML 2.0 Metadata document to trusted partners to setup a trusted federation.</p>
		
		<?php if (isset($this->data['metaurl'])) { ?>
			<p>You can <a href="<?php echo htmlspecialchars($this->data['metaurl']); ?>">get the metadata xml on a dedicated URL</a>:<br />
			<input type="text" style="width: 90%" value="<?php echo htmlspecialchars($this->data['metaurl']); ?>" /></p>
		<?php } ?>
		<h2>Metadata</h2>
		
		<p>In SAML 2.0 Meta data XML format:</p>
		
		<pre class="metadatabox"><?php echo $this->data['metadata']; ?></pre>
		
		
		<p>In simpleSAMLphp flat file format - use this if you are using a simpleSAMLphp entity on the other side:</p>
		
		<pre class="metadatabox"><?php echo $this->data['metadataflat']; ?></pre>
		
		

		
		<?php if(array_key_exists('sendmetadatato', $this->data)) { ?>
		

			<div style="border: 1px solid #444; margin: 2em; padding: 1em; background: #eee">
			
				<h2>Send your metadata to <?php echo $this->data['federationname']; ?></h2>
				
				<p>simpleSAMLphp has detected that you have configured <?php echo $this->data['federationname']; ?> as your default IdP.</p>
				
				<p>Before you can connect to <?php echo $this->data['federationname']; ?>, <?php echo $this->data['federationname']; ?> needs to add your service in its trust configuration. When you
					contact <?php echo $this->data['federationname']; ?> to add you as a new service, you will be asked to send your metadata. Here you can easily send
					the metadata to <?php echo $this->data['federationname']; ?> by clicking the button below.</p>
					
				<form action="<?php echo $this->data['sendmetadatato']; ?>" method="post">

					<p><?php echo $this->data['federationname']; ?> needs to know how to get in contact with you, so you need to type in <strong>your email address</strong>:
						<input type="text" size="25" name="email" value="" />
					</p>
					
					<input type="hidden" name="action" value="metadata" />
					<input type="hidden" name="metadata" value="<?php echo urlencode(base64_encode($this->data['metadata'])); ?>" />
					<input type="hidden" name="techemail" value="<?php echo $this->data['techemail']; ?>" />
					<input type="hidden" name="version" value="<?php echo $this->data['version']; ?>" />
					<input type="hidden" name="defaultidp" value="<?php echo htmlspecialchars($this->data['defaultidp']); ?>" />
					<input type="submit" name="send" value="Send my metadata to <?php echo $this->data['federationname']; ?>" />
					
				</form>
				
			</div>
		
		<?php } ?>

<?php $this->includeAtTemplateBase('includes/footer.php'); ?>