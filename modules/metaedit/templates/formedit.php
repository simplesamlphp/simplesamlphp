<?php

$this->data['jquery'] = array('version' => '1.6', 'core' => TRUE, 'ui' => TRUE, 'css' => TRUE);
$this->data['head']  = '<link rel="stylesheet" type="text/css" href="/' . $this->data['baseurlpath'] . 'module.php/metaedit/resources/style.css" />' . "\n";
$this->data['head'] .= '<script type="text/javascript">
$(document).ready(function() {
	$("#tabdiv").tabs();
});
</script>';

$this->includeAtTemplateBase('includes/header.php');


echo('<h1>Metadata Editor</h1>');

echo($this->data['form']);

echo('<p style="float: right"><a href="index.php">Return to entity listing <strong>without saving...</strong></a></p>');

$this->includeAtTemplateBase('includes/footer.php');

