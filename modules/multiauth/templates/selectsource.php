<?php
$this->data['header'] = $this->t('{multiauth:multiauth:select_source_header}');

$this->includeAtTemplateBase('includes/header.php');
?>

<h2><?php echo $this->t('{multiauth:multiauth:select_source_header}'); ?></h2>

<p><?php echo $this->t('{multiauth:multiauth:select_source_text}'); ?></p>

<ul>
<?php
foreach($this->data['sources'] as $source) {
	echo '<li><a href="?source=' . htmlspecialchars($source) .
		'&AuthState=' . htmlspecialchars($this->data['authstate']) . '">' .
		htmlspecialchars($source) . '</a></li>';
}
?>
</ul>

<?php $this->includeAtTemplateBase('includes/footer.php'); ?>
