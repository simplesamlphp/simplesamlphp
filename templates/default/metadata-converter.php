<?php
$this->data['header'] = 'Metadata parser';
$this->includeAtTemplateBase('includes/header.php');
?>

<div id="content">

<h2>Metadata parser</h2>

<form action="?" method="post">

<p>XML metadata</p>
<p>
<textarea rows="20" cols="75" name="xmldata"><?php echo htmlspecialchars($this->data['xmldata']); ?></textarea>
</p>
<p>
<input type="submit" value="Parse" />
</p>
</form>

<?php

$output = $this->data['output'];

if($output !== NULL) {

	echo('<h2>Converted metadata</h2>' . "\n");

	foreach($output as $type => $text) {
		if($text === '') {
			continue;
		}

		echo('<h3>' . htmlspecialchars($type) . '</h3>' . "\n");
		echo('<pre>' . htmlspecialchars($text) . '</pre>' . "\n");
	}
}

?>

<?php
$this->includeAtTemplateBase('includes/footer.php');
?>