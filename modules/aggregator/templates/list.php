<?php
$this->data['header'] = $this->t('{aggregator:aggregator:aggregator_header}');
$this->includeAtTemplateBase('includes/header.php');

echo('<h1>'. $this->data['header'] . '</h1>');

if (count($this->data['sources']) === 0) {
	echo('<p>' . $this->t('{aggregator:aggregator:no_aggregators}') . '</p>');
} else {

	echo('<ul>');

	foreach ($this->data['sources'] as $source) {
		$encId = urlencode($source);
		$encName = htmlspecialchars($source);
		echo('<li>');
		echo('<a href="?id=' . $encId . '">' . $encName . '</a>');
		echo(' <a href="?id=' . $encId . '&amp;mimetype=text/plain">[' . $this->t('{aggregator:aggregator:text}') . ']</a>');
		echo(' <a href="?id=' . $encId . '&amp;mimetype=application/xml">[xml]</a>');
		echo('</li>');
	}

	echo('</ul>');
}

$this->includeAtTemplateBase('includes/footer.php');
?>