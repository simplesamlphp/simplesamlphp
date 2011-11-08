<?php
$this->data['header'] = $this->t('{expirycheck:warning:access_denied}');
$this->includeAtTemplateBase('includes/header.php');
?>

		<h2><?php echo $this->t('{expirycheck:warning:access_denied}');?></h2>
		<p><?php echo $this->t('{expirycheck:warning:no_access_to}', array('%NETID%' => htmlspecialchars($this->data['netId'])));?></p> 
		<p><?php echo $this->t('{expirycheck:warning:expiry_date_text}');?> <b><?php echo htmlspecialchars($this->data['expireOnDate']);?></b></p>
		<p><?php echo $this->t('{expirycheck:warning:contact_home}');?></p>
<?php
$this->includeAtTemplateBase('includes/footer.php');
?>
