<?php
/*
* AUTHOR: Samuel Muñoz Hidalgo
* EMAIL: samuel.mh@gmail.com
* LAST REVISION: 22-DEC-08
* DESCRIPTION: InfoCard module template.
*/
	$this->includeAtTemplateBase('includes/header.php'); 
	if (isset($this->data['error'])) { ?>
		<div style="border-left: 1px solid #e8e8e8; border-bottom: 1px solid #e8e8e8; background: #f5f5f5">
		<img src="/<?php echo $this->data['baseurlpath']; ?>resources/icons/experience/gtk-dialog-error.48x48.png" style="float: left; margin: 15px " />
		<h2><?php echo $this->t('error_header'); ?></h2>
		
		<p><?php echo $this->t($this->data['error']); ?> </p>
		</div>
	<?php } ?>

	<h2 style="break: both"><?php echo $this->t('user_IC_header'); ?></h2>
	
	<p><?php echo $this->t('user_IC_text'); ?></p>
	
	<form name="ctl00" id="ctl00" method="post" action="?AuthState=<?php echo htmlspecialchars($this->data['stateparams']['AuthState'])?>">

<!--		<ic:informationCard xmlns:ic="<?php echo $this->data['InfoCard']['schema'] ?>" name="xmlToken" 
			issuer="<?php echo $this->data['InfoCard']['issuer']; ?>"
			<?php 
				if ($this->data['InfoCard']['issuerPolicy']!='') echo 'issuerPolicy="'.$this->data['InfoCard']['issuerPolicy'].'"';
				if ($this->data['InfoCard']['tokenType']!='') echo 'tokenType="'.$this->data['InfoCard']['tokenType'].'"';			
				if ($this->data['InfoCard']['privacyURL']!='') echo 'privacyUrl="'.$this->data['InfoCard']['privacyURL'].'"';
				if ($this->data['InfoCard']['privacyVersion']!='') echo 'privacyVersion="'.$this->data['InfoCard']['privacyVersion'].'"'; ?>>
			<?php
				$schema = $this->data['InfoCard']['schema']."/claims/";
				foreach ($this->data['InfoCard']['requiredClaims'] as $claim=>$data) {
					echo "<ic:add claimType = \"".$schema.$claim."\" optional=\"false\" />\n";
				}
				foreach ($this->data['InfoCard']['optionalClaims'] as $claim=>$data) {
					echo "<ic:add claimType = \"".$schema.$claim."\" optional=\"true\" />\n";
				}
				unset($value);?>
		</ic:informationCard>-->
		
		<OBJECT type="application/x-informationCard" name="xmlToken">
			<?php 
				echo '<PARAM Name="issuer" Value="'.$this->data['InfoCard']['issuer']."\">\n";
				if ($this->data['InfoCard']['issuerPolicy']!='') echo '<PARAM Name="issuerPolicy" Value="'.$this->data['InfoCard']['issuerPolicy']."\">\n";
				if ($this->data['InfoCard']['tokenType']!='') echo '<PARAM Name="tokenType" Value="'.$this->data['InfoCard']['tokenType']."\">\n";
				if ($this->data['InfoCard']['privacyURL']!='') echo '<PARAM Name="privacyUrl" Value="'.$this->data['InfoCard']['privacyURL']."\">\n";
				if ($this->data['InfoCard']['privacyVersion']!='')echo '<PARAM Name="privacyVersion" Value="'.$this->data['InfoCard']['privacyVersion']."\">\n";?>
			<PARAM Name="requiredClaims" Value="<?php
				$schema = $this->data['InfoCard']['schema']."/claims/";
				foreach ($this->data['InfoCard']['requiredClaims'] as $claim=>$data) {
					echo $schema.$claim." ";
				}?>">
			<PARAM Name="optionalClaims" Value="<?php
				$schema = $this->data['InfoCard']['schema']."/claims/";
				foreach ($this->data['InfoCard']['optionalClaims'] as $claim=>$data) {
					echo $schema.$claim." ";
				}?>">
		</OBJECT>
		
		<input type='image' src="<?php echo $this->data['IClogo']; ?>" style='cursor:pointer' />
	</form>
	
<!-- 	GET INFOCARD SECTION -->
	<?php
		if (strcmp($this->data['CardGenerator'],'')>0) {
			echo '<h2>'.$this->t('get_IC').'</h2>';
			echo '<a href="'.$this->data['CardGenerator'].'?AuthState='.$this->data['stateparams']['AuthState'].'">'.$this->t('get_IC_link').'</a>';
	 	}
	?>
	 
<!-- 	 HELP SECTION -->
	<h2><?php echo $this->t('help_header'); ?></h2>	
	<p><?php echo $this->t('help_text'); ?></p>
	<?php
		if ((array_key_exists('contact_info_URL',$this->data)) && ($this->data['contact_info_URL']!=null)) 
			echo "<p><a href='".$this->data['contact_info_URL']."'>".$this->t('contact_info')."</a><p/>";
		if ((array_key_exists('help_desk_email_URL',$this->data)) && ($this->data['help_desk_email_URL']!=null)) 
			echo "<p><a href='".$this->data['help_desk_email_URL']."'>".$this->t('help_desk_email')."</a></p>";
	?>
	
<?php $this->includeAtTemplateBase('includes/footer.php'); ?>