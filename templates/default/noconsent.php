<?php 
	$this->data['header'] = $this->t('{consent:noconsent_title}');;
	$this->data['icon'] = 'bomb_l.png';
	$this->includeAtTemplateBase('includes/header.php'); 
?>


<div id="content">

	<h2><?php echo($this->data['header']); ?></h2>
	<p><?php echo($this->t('{consent:noconsent_text}')); ?></p>

<?php $this->includeAtTemplateBase('includes/footer.php'); ?>