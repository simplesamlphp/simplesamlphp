<?php
/*
* AUTHOR: Samuel Muñoz Hidalgo
* EMAIL: samuel.mh@gmail.com
* LAST REVISION: 1-DEC-08
* DESCRIPTION: 'login-infocard' module template.
*/
	$this->includeAtTemplateBase('includes/header.php'); 
	if (!array_key_exists('icon', $this->data)) $this->data['icon'] = 'lock.png';
	if (isset($this->data['error'])) { ?>
		<div style="border-left: 1px solid #e8e8e8; border-bottom: 1px solid #e8e8e8; background: #f5f5f5">
		<img src="/<?php echo $this->data['baseurlpath']; ?>resources/icons/bomb.png" style="float: left; margin: 15px " />
		<h2><?php echo $this->t('error_header'); ?></h2>
		
		<p><?php echo $this->t($this->data['error']); ?> </p>
		</div>
	<?php } ?>

	<h2 style="break: both"><?php echo $this->t('user_IC_header'); ?></h2>
	
	<p><?php echo $this->t('user_IC_text'); ?></p>
	
	<form name="ctl00" id="ctl00" method="post" action="?">
		<?php foreach ($this->data['stateparams'] as $name => $value) {
		echo('<input type="hidden" name="' . htmlspecialchars($name) . '" value="' . htmlspecialchars($value) . '" />');
		}?>
		<ic:informationCard xmlns:ic="<?php echo $this->data['InfoCard']['schema'] ?>" name='xmlToken' 
			issuer="<?php echo $this->data['InfoCard']['issuer']; ?>"
			issuerPolicy="<?php echo $this->data['InfoCard']['issuerPolicy']; ?>"
			tokenType="<?php echo $this->data['InfoCard']['tokenType']; ?>"			
			privacyUrl="<?php echo $this->data['InfoCard']['privacyURL']; ?>"
			privacyVersion="<?php echo $this->data['InfoCard']['privacyVersion']; ?>">
			<?php
				$schema = $this->data['InfoCard']['schema']."/claims/";
				foreach ($this->data['InfoCard']['requiredClaims'] as $claim=>$data) {
					echo "<ic:add claimType = \"$schema".$claim."\" optional=\"false\" />\n";
				}
				foreach ($this->data['InfoCard']['optionalClaims'] as $claim=>$data) {
					echo "<ic:add claimType = \"$schema".$claim."\" optional=\"true\" />\n";
				}
				unset($value);?>
		</ic:informationCard>
		<input type='image' src="<?php echo $this->data['IClogo']; ?>" align='center'  style='cursor:pointer' />
	</form>
	
	<?php if (strcmp($this->data['CardGenerator'],'')>0) {
	echo '<h2>Or get one</h2>';
	echo '<table border="0">';
	echo "<form action=\"". $this->data['CardGenerator'] ."\" method='post'>";
		echo "<tr><td>Username: </td><td><input type='text' name='username' value='usuario' /></tr></td>";
		echo "<tr><td>Password: </td><td><input type='password' name='password' value='clave' /></tr></td>";
		echo "<tr><td></td><td><input type='submit' name='Get_card' value='Get InfoCard' /></tr></td>";
	echo '</form>';
	echo '</table>';
	 } ?>
	<h2><?php echo $this->t('help_header'); ?></h2>	
	<p><?php echo $this->t('help_text'); ?></p>
<?php $this->includeAtTemplateBase('includes/footer.php'); ?>