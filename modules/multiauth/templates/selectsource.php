<?php
$this->data['header'] = $this->t('{multiauth:multiauth:select_source_header}');

$this->includeAtTemplateBase('includes/header.php');
?>

<h2><?php echo $this->t('{multiauth:multiauth:select_source_header}'); ?></h2>

<p><?php echo $this->t('{multiauth:multiauth:select_source_text}'); ?></p>

<form action="<?php echo htmlentities($_SERVER['PHP_SELF']); ?>" method="get">
<input type="hidden" name="AuthState" value="<?php echo htmlspecialchars($this->data['authstate']); ?>" />
<ul>
<?php
foreach($this->data['sources'] as $source) {
	echo '<li class="' . htmlspecialchars($source['css_class']) . ' authsource">';
	if ($source['source'] === $this->data['preferred']) {
		$autofocus = ' autofocus="autofocus"';
	} else {
		$autofocus = '';
	}
	echo '<button type="submit" name="source"' . $autofocus . ' ' .
		'id="button-' . htmlspecialchars($source['source']) . '" ' .
		'value="' . htmlspecialchars($source['source']) . '">';
	echo htmlspecialchars($this->t($source['text']));
	echo '</button>';
	echo '</li>';
}
?>
</ul>
</form>
<?php $this->includeAtTemplateBase('includes/footer.php'); ?>
