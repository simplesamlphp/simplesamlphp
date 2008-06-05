<?php
	$this->data['header'] = $this->t('errorreport_header');
	$this->data['icon'] = 'bomb_l.png';
	$this->includeAtTemplateBase('includes/header.php');
?>
<div id="content">
<h2><? echo $this->t('errorreport_header'); ?></h2>
<p><? echo $this->t('errorreport_text'); ?></p>
<?php $this->includeAtTemplateBase('includes/footer.php'); ?>