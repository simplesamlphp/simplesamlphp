<?php
$this->data['header'] = $this->t('addpage_header');
$this->includeAtTemplateBase('includes/header.php');


echo('<h2>' . $this->t('addpage_header') . '</h2>');

$url = $this->data['url'];
$status = $this->data['status'];

$replaceurl = array('%URL%' => htmlspecialchars($this->data['url']));

echo('<p>' . $this->t('addpage_' . $status, $replaceurl) . '</p>');


if(array_key_exists('errortext', $this->data)) {
	echo('<pre>' . htmlspecialchars($this->data['errortext']) . '</pre>');
}

echo('<p><a href="index.php">' . $this->t('addpage_gofront') . '</a></p>');

$this->includeAtTemplateBase('includes/footer.php');

?>