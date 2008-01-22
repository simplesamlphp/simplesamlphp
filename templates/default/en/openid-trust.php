<?php $this->includeAtTemplateBase('includes/header.php'); ?>

	<div id="header">
		<h1>simpleSAMLphp OpenID</h1>
		<div id="poweredby"><img src="/<?php echo $data['baseurlpath']; ?>resources/icons/bino.png" alt="Bino" /></div>
	</div>
	
	<div id="content">

		<?php if (isset($data['header'])) { echo '<h2>' . $data['header'] . '</h2>'; } ?>
		
		
		<p>[ <a href="/<?php echo $data['baseurlpath']; ?>/openid/provider/server.php/sites">List of trusted sites</a> |
		<a href="/<?php echo $data['baseurlpath']; ?>/openid/provider/server.php/about">About simpleSAMLphp OpenID</a> ]</p>
		
		<div class="form">
		  <p>Do you wish to confirm your identity URL (<code><?php echo htmlspecialchars($data['openidurl']); ?></code>)
		  	with <code><?php echo $data['siteurl']; ?></code>?</p>
		  <form method="post" action="<?php echo $data['trusturl']; ?>">
			<input type="checkbox" name="remember" value="on" id="remember"><label
				for="remember">Remember this decision</label>
			<br />
			<input type="submit" name="trust" value="Confirm" />
			<input type="submit" value="Do not confirm" />
		  </form>
		</div>

		
		<h2>About simpleSAMLphp</h2>
		<p>Hey! This simpleSAMLphp thing is pretty cool, where can I read more about it?
		You can find more information about simpleSAMLphp at <a href="http://rnd.feide.no">the Feide RnD blog</a> over at <a href="http://uninett.no">UNINETT</a>.</p>
		
<?php $this->includeAtTemplateBase('includes/footer.php'); ?>