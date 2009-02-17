<?php 

$this->data['header'] = $this->t('{consentSimpleAdmin:consentsimpleadmin:header}');
$this->includeAtTemplateBase('includes/header.php'); 

?>



<p><?php

echo '<p>' . $this->t('{consentSimpleAdmin:consentsimpleadmin:granted}', array(
	'%NO%' => (string)$this->data['consents'],
	'%OF%' => (string)$this->data['consentServices'],
)) . '</p>';


echo '<p>' . $this->t('{consentSimpleAdmin:consentsimpleadmin:info}') . '</p>';


?></p>

<!-- <p>You have granted <?php echo $this->data['consents']; ?> consents to <?php echo $this->data['consentServices']; ?> different services.</p>

<p>If you withdraw all consents given, you will be asked again each time you visit a new service, whether or not you would like to accept that a given set of attributes are transferred.</p> -->

<form method="get" action="consentAdmin.php">

	<input type="submit" name="withdraw" value="<?php echo $this->t('{consentSimpleAdmin:consentsimpleadmin:withdraw}'); ?>" />

</form>
<!--  Withdraw all consent given -->

<?php $this->includeAtTemplateBase('includes/footer.php'); ?>
