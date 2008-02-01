<?php 
	$this->data['icon'] = 'compass_l.png';
	$this->includeAtTemplateBase('includes/header.php'); 
?>

	<div id="content">

		<h2>Welcome to simpleSAMlphp</h2>
		
		<p>You have installed simpleSAMLphp on this web host. Here are some relevant links for your installation:
			<ul>
			<?php
			
				foreach ($data['links'] AS $link) {
					echo '<li><a href="' . htmlspecialchars($link['href']) . '">' . htmlspecialchars($link['text']) . '</a></li>';
				}
			?>
			</ul>
		</p>



		<h2>About simpleSAMLphp</h2>
		<p>Hey! This simpleSAMLphp thing is pretty cool, where can I read more about it?
		You can find more information about <a href="http://rnd.feide.no/simplesamlphp">simpleSAMLphp at the Feide RnD blog</a> over at <a href="http://uninett.no">UNINETT</a>.</p>
		
<?php $this->includeAtTemplateBase('includes/footer.php'); ?>