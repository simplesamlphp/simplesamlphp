<?php $this->includeAtTemplateBase('includes/header.php'); ?>

	<div id="header">
		<h1>simpleSAMLphp status page</h1>
		<div id="poweredby"><img src="/<?php echo $data['baseurlpath']; ?>resources/icons/bino.png" alt="Bino" /></div>
	</div>
	
	<div id="content">

		<h2><?php if (isset($data['header'])) { echo $data['header']; } else { echo "Some error occured"; } ?></h2>
		
		<p>Hi, this is the status page of simpleSAMLphp. Here you can see if your session is timed out, how long it lasts until it times out and all the attributes that is attached to your session.</p>
		
		<p><?php echo $data['valid']; ?>. Your session is valid for <?php echo $data['remaining']; ?> seconds from now.</p>
		
		<h2>Your attributes</h2>
		
			<table>
			<?php
			
			$attributes = $data['attributes'];
			foreach ($attributes AS $name => $value) {
				if (sizeof($value) > 1) {
					echo '<tr><td>' . $name . '</td><td><ul>';
					foreach ($value AS $v) {
						echo '<li>' . $v . '</li>';
					}
					echo '</ul></td></tr>';
				} else {
					echo '<tr><td>' . $name . '</td><td>' . $value[0] . '</td></tr>';
				}
			}
			
			?>
			</table>

		<h2>Logout</h2>

			<p><?php echo $data['logout']; ?></p>
		
		<h2>About simpleSAMLphp</h2>
		<p>Hey! This simpleSAMLphp thing is pretty cool, where can I read more about it?
		You can find more information about simpleSAMLphp at <a href="http://rnd.feide.no">the Feide RnD blog</a> over at <a href="http://uninett.no">UNINETT</a>.</p>
		
<?php $this->includeAtTemplateBase('includes/footer.php'); ?>