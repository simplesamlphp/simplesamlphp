<?php $this->includeAtTemplateBase('includes/header.php'); ?>

	<div id="header">
		<h1>SAML 2.0 IdP Discovery Service</h1>
		<div id="poweredby"><img src="/<?php echo $data['baseurlpath']; ?>resources/icons/bino.png" alt="Bino" /></div>
	</div>
	
	<div id="content">

		<h2><?php if (isset($data['header'])) { echo $data['header']; } else { echo "Select your IdP"; } ?></h2>
		
		<p>Please select the identity provider where you want to authenticate:</p>
		
		
		<?php
		
		foreach ($data['idplist'] AS $idpentry) {
		
			echo '<h3>' . $idpentry['name'] . '</h3>';
			echo '<p>' . $idpentry['description'] . '<br />';
			echo '[ <a href="' . $data['urlpattern'] . $idpentry['entityid'] . '">Select this IdP</a>]</p>';
		
		}
		
		
		?>

		
<?php $this->includeAtTemplateBase('includes/footer.php'); ?>
