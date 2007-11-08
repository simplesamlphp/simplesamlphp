<?php $this->includeAtTemplateBase('includes/header.php'); ?>

	<div id="header">
		<h1>simpleSAMLphp OpenID</h1>
		<div id="poweredby"><img src="/<?php echo $data['baseurlpath']; ?>resources/icons/bino.png" alt="Bino" /></div>
	</div>
	
	<div id="content">

		<?php if (isset($data['header'])) { echo '<h2>' . $data['header'] . '</h2>'; } ?>
		
		<p>[ <a href="/<?php echo $data['baseurlpath']; ?>/openid/provider/server.php/sites">List of trusted sites</a> |
		About simpleSAMLphp OpenID ]</p>

		
		<p>Welcome to the simpleSAMLphp OpenID provider.</p>


		<p>
		  To use this server, you will have to set up a URL to use as an identifier.
		  Insert the following markup into the <code>&lt;head&gt;</code> of the HTML
		  document at that URL:
		</p>
<pre>&lt;link rel="openid.server" href="<?php echo $data['openidserver']; ?>" /&gt;
&lt;link rel="openid.delegation" href="<?php echo $data['openiddelegation']; ?>" /&gt;
		
		</pre>
		
		
		<p><?php
			
			if (isset($data['userid'])) {
				echo 'You are now logged in as ' . $data['userid']; 
			} else {
				echo '<a href="' . $data['initssourl'] . '">Login</a>';
			}
		
		?>
		
		<p>
		  Then configure this server so that you can log in with that URL. Once you
		  have configured the server, and marked up your identity URL, you can verify
		  that it is working by using the <a href="http://www.openidenabled.com/"
		  >openidenabled.com</a>
		  <a href="http://www.openidenabled.com/resources/openid-test/checkup">OpenID Checkup tool</a>:
		  <form method="post"
				action="http://www.openidenabled.com/resources/openid-test/checkup/start">
			<label for="checkup">OpenID URL:
			</label><input id="checkup" type="text" name="openid_url" />
			<input type="submit" value="Check" />
		  </form>
		</p>

		
		<h2>About simpleSAMLphp</h2>
		<p>Hey! This simpleSAMLphp thing is pretty cool, where can I read more about it?
		You can find more information about simpleSAMLphp at <a href="http://rnd.feide.no">the Feide RnD blog</a> over at <a href="http://uninett.no">UNINETT</a>.</p>
		
<?php $this->includeAtTemplateBase('includes/footer.php'); ?>

