<?php $this->includeAtTemplateBase('includes/header.php'); ?>

	<div id="header">
		<h1>simpleSAMLphp is installed</h1>
		<div id="poweredby"><img src="resources/icons/compass_l.png" alt="Bino" /></div>
	</div>
	
	<div id="content">

		<h2>Welcome to simpleSAMlphp</h2>
		
		<p>You have installed simpleSAMLphp on this web host.</p>
		
		<p>Relevant links for your installation:
			<ul>
			<?php
			
				foreach ($data['links'] AS $link) {
					echo '<li><a href="' . $link['href'] . '">' . $link['text'] . '</a></li>';
				}
			?>
				<!-- li><a href="saml2/sp/metadata.php">Look at your SAML 2.0 SP metadata</a> - you can send this metadata document to your IdP.</a></li>
				<li><a href="saml2/idp/metadata.php">Look at your SAML 2.0 IdP metadata</a></a></li>
				<li><a href="example-simple/saml2-example.php">SAML 2.0 SP example</a></li>
				<li><a href="example-simple/shib13-example.php">Shibboleth 1.3 SP example</a></li>
				<li><a href="openid/provider/server.php">OpenID Provider site</a></li -->
			</ul>
		</p>
		
		<h2>Diagnostics</h2>
		<p>Here are some help tools to diagnose what is wrong if things do not work as expected.</p>
		
		<p>Misconfiguration of NameVirtualHosts and similar things are pretty common in Apache. simpleSAMLphp relies on getting correct information from Apache what relates to port number information about ssl and so on. Here is a diagnostics page that shows what simpleSAMLphp is getting from Apache:
			<ul>
				<li><a href="example-simple/hostnames.php">Diagnostics on hostname, port and protocol</a></li>
			</ul>
		</p>
		


		<h2>About simpleSAMLphp</h2>
		<p>Hey! This simpleSAMLphp thing is pretty cool, where can I read more about it?
		You can find more information about simpleSAMLphp at <a href="http://rnd.feide.no">the Feide RnD blog</a> over at <a href="http://uninett.no">UNINETT</a>.</p>
		
<?php $this->includeAtTemplateBase('includes/footer.php'); ?>