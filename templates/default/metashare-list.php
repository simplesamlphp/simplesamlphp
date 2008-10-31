<?php
$this->data['header'] = $this->t('front_header');
$this->includeAtTemplateBase('includes/header.php');


echo('<h2>' . $this->t('front_header') . '</h2>');
echo('<p>' . $this->t('front_desc') . '</p>');

echo('<h3>' . $this->t('add_title') . '</h3>');
echo('<p>' . $this->t('add_desc') . '</p>');
echo('<form action="add.php">');
echo('<p>');
echo($this->t('add_entityid') . '<br/>');
echo('<input type="text" name="url" size="70" />');
echo('<input type="submit" value="' . $this->t('add_do') . '" />');
echo('</p>');
echo('</form>');

echo('<h3>' . $this->t('entities_title') . '</h3>');
if(count($this->data['entities']) > 0) {
	echo('<p>' . $this->t('downloadall_desc') . '</p>');
	echo('<p><a href="downloadall.php">' . $this->t('downloadall_link') .
		'</a> [<a href="downloadall.php?mimetype=text/plain">' . $this->t('text') . '</a>]</p>');
	echo('<p>' . $this->t('entities_desc') . '</p>');
	echo('<ul>');
	foreach($this->data['entities'] as $entityId) {
		$dllink = 'download.php?entityid=' . urlencode($entityId);
		echo('<li>');
		echo('<a href="' . htmlspecialchars($dllink) . '">' .
			htmlspecialchars($entityId) . '</a>');
		echo(' [<a href="' . htmlspecialchars($dllink . '&mimetype=text/plain') . '">' .
			$this->t('text') . '</a>]');
		echo('</li>');
	}
	echo('</ul>');
} else {
	echo('<p>' . $this->t('entities_empty') . '</p>');
}

$this->includeAtTemplateBase('includes/footer.php');
?>