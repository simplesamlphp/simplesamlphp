<?php

$this->data['jquery'] = ['core' => true, 'ui' => true, 'css' => true];
$this->data['head'] = '<link rel="stylesheet" type="text/css" href="/'.
    $this->data['baseurlpath'].'module.php/oauth/assets/css/oauth.css" />'."\n";
$this->data['head'] .= '<script type="text/javascript" src="/'.
    $this->data['baseurlpath'].'module.php/oauth/assets/js/oauth.js"></script>';

$this->includeAtTemplateBase('includes/header.php');

echo '<h1>OAuth Client</h1>';

echo $this->data['form'];

echo '<p style="float: right"><a href="registry.php">'.
    'Return to entity listing <strong>without saving...</strong></a></p>';

$this->includeAtTemplateBase('includes/footer.php');
