<?php
if(array_key_exists('header', $this->data)) {
	if($this->getTag($this->data['header']) !== NULL) {
		$this->data['header'] = $this->t($this->data['header']);
	}
}

$this->includeAtTemplateBase('includes/header.php');
?>

<div id="content">

	<h2><?php if (isset($this->data['header'])) { echo($this->data['header']); } else { echo($this->t('{status:some_error_occured}')); } ?></h2>
	
	<p><?php echo($this->t('{status:intro}')); ?></p>
	
	<p><?php echo($this->t('{status:validfor}', array('%SECONDS%' => $this->data['remaining']))); ?></p>
	
	<?php
	if(isset($this->data['sessionsize'])) {
		echo('<p>' . $this->t('{status:sessionsize}', array('%SIZE%' => $this->data['sessionsize'])) . '</p>');
	}
	?>
	
	<h2><?php echo($this->t('{status:attributes_header}')); ?></h2>
	
		<table width="100%" class="attributes">
		<?php
		
		$attributes = $this->data['attributes'];
		foreach ($attributes AS $name => $value) {
			
			$txtname = '<code style="color: blue">' . $name . '</code>';
			if ($this->getTag('attribute_' . htmlspecialchars(strtolower($name))) !== NULL) {
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

<?php
if (isset($this->data['logout'])) {
	echo('<h2>' . $this->t('{status:logout}') . '</h2>');
	echo('<p>' . $this->data['logout'] . '</p>');
}

if (isset($this->data['logouturl'])) {
	echo('<h2>' . $this->t('{status:logout}') . '</h2>');
	echo('<p>[ <a href="' . htmlspecialchars($this->data['logouturl']) . '">' . $this->t('{status:logout}') . '</a> ]</p>');
}
?>

	<h2><?php echo $this->t('{frontpage:about_header}'); ?></h2>
	<p><?php echo $this->t('{frontpage:about_text}'); ?></p>
	
<?php $this->includeAtTemplateBase('includes/footer.php'); ?>