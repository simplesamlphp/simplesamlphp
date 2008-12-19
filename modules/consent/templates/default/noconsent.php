<?php
	$this->data['header'] = $this->t('{consent:noconsent_title}');;
	$this->data['icon'] = 'bomb_l.png';
	$this->includeAtTemplateBase('includes/header.php');
?>


	<h2><?php echo($this->data['header']); ?></h2>
	<p><?php echo($this->t('{consent:noconsent_text}')); ?></p>

<?php
	if($this->data['resumeFrom']) {
		echo('<p><a href="' . htmlspecialchars($this->data['resumeFrom']) . '">');
		echo($this->t('{consent:noconsent_return}'));
		echo('</a></p>');
	}
?>

<?php $this->includeAtTemplateBase('includes/footer.php'); ?>
