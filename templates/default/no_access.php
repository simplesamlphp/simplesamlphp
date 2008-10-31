<?php
$this->data['header'] = $this->t('access_denied');
$this->includeAtTemplateBase('includes/header.php');
$this->includeInlineTranslation('spname', $this->data['sp_name']);
?>

		<h2><?php echo $this->t('access_denied');?></h2>
		<p><?php echo $this->t('no_access_to');?></p>
		<p><b><?php echo $this->t('spname');?></b></p>
		<p><?php echo $this->t('contact_home');?></p>
<?php
$this->includeAtTemplateBase('includes/footer.php');
?>