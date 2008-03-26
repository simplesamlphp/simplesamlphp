<?php 
	$this->data['header'] = 'No consent was given';
	$this->data['icon'] = 'bomb_l.png';
	$this->includeAtTemplateBase('includes/header.php'); 
?>


<div id="content">

	<h2><?php echo $this->data['title']; ?></h2>


You did not accept to give consent.


<?php $this->includeAtTemplateBase('includes/footer.php'); ?>