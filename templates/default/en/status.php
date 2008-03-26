<?php $this->includeAtTemplateBase('includes/header.php'); ?>

<div id="content">

	<h2><?php if (isset($this->data['header'])) { echo $this->data['header']; } else { echo "Some error occured"; } ?></h2>
	
	<p>Hi, this is the status page of simpleSAMLphp. Here you can see if your session is timed out, how long it lasts until it times out and all the attributes that is attached to your session.</p>
	
	<p>Your session is valid for <?php echo $this->data['remaining']; ?> seconds from now.</p>
	
	<p>Session size: <?php echo isset($this->data['sessionsize']) ? $this->data['sessionsize'] : 'na'; ?>
	
	<h2>Your attributes</h2>
	
		<table width="100%" class="attributes">
		<?php
		
		$attributes = $this->data['attributes'];
		foreach ($attributes AS $name => $value) {
			
			$txtname = '<code style="color: blue">' . $name . '</code>';
			if ($this->t('attribute_' . htmlspecialchars(strtolower($name)), false)) {
				$txtname = $this->t('attribute_' . htmlspecialchars(strtolower($name))) . '<br /><code style="color: blue">' . $name . '</code>';
			}
			
			if (sizeof($value) > 1) {
				echo '<tr><td>' . $txtname . '</td><td><ul>';
				foreach ($value AS $v) {
					echo '<li>' . htmlspecialchars($v) . '</li>';
				}
				echo '</ul></td></tr>';
			} else {
				echo '<tr><td>' . $txtname . '</td><td>' . htmlspecialchars($value[0]) . '</td></tr>';
			}
		}
		
		?>
		</table>

	<?php if (isset($this->data['logout'])) { ?>
	<h2>Logout</h2>

		<p><?php echo $this->data['logout']; ?></p>

	<?php } ?>
	
	<h2>About simpleSAMLphp</h2>
	<p>Hey! This simpleSAMLphp thing is pretty cool, where can I read more about it?
	You can find more information about simpleSAMLphp at <a href="http://rnd.feide.no">the Feide RnD blog</a> over at <a href="http://uninett.no">UNINETT</a>.</p>
	
<?php $this->includeAtTemplateBase('includes/footer.php'); ?>