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


<!-- 	GET INFOCARD SECTION -->
	<?php
		if (strcmp($this->data['CardGenerator'],'')>0) {
		
			if(strcmp($this->data['form'],"validate")==0){
				echo '<h2>'.$this->t('getcardform_title').'</h2>';
				echo '<form action = ?AuthState='.htmlspecialchars($this->data['stateparams']['AuthState'])." method='post'>";
					echo '<table border="0">';
					echo "<tr><td>".$this->t('form_username').": </td><td><input type='text' name='username' value='usuario' /></td></tr>";
					echo "<tr><td>".$this->t('form_password').": </td><td><input type='password' name='password' value='clave' /></td></tr>";
					echo "<tr><td></td><td><input type='submit' name='get_button' value='".$this->t('get_button')."' /></td></tr>";
					echo "<input type='hidden' name='form' value='".$this->data['form']."'/>";
				echo '</table>';
				echo '</form>';
				
			} else if(strcmp($this->data['form'],"selfIssued")==0){ //ASK FOR A SELF-ISSUED CARD
				echo '<h2>'.$this->t('getcardform_self_title').'</h2>';
				echo '<p>'.$this->t('getcardform_self_text').'</p>';
				echo	'<form name="ctl00" id="ctl00" method="post" action="?AuthState='.htmlspecialchars($this->data['stateparams']['AuthState']).'">';
					echo	'<OBJECT type="application/x-informationCard" name="xmlToken">';
						echo '<PARAM Name="issuer" Value="http://schemas.xmlsoap.org/ws/2005/05/identity/issuer/self" />';
						if ($this->data['InfoCard']['issuerPolicy']!='') echo '<PARAM Name="issuerPolicy" Value="'.$this->data['InfoCard']['issuerPolicy']."\">\n";
						if ($this->data['InfoCard']['tokenType']!='') echo '<PARAM Name="tokenType" Value="'.$this->data['InfoCard']['tokenType']."\">\n";
						if ($this->data['InfoCard']['privacyURL']!='') echo '<PARAM Name="privacyUrl" Value="'.$this->data['InfoCard']['privacyURL']."\">\n";
						if ($this->data['InfoCard']['privacyVersion']!='')echo '<PARAM Name="privacyVersion" Value="'.$this->data['InfoCard']['privacyVersion']."\">\n";
						echo '<PARAM Name="requiredClaims" Value="http://schemas.xmlsoap.org/ws/2005/05/identity/claims/privatepersonalidentifier">';
					echo '</OBJECT>';
					echo "<input type='hidden' name='username' value='".htmlspecialchars($this->data['username'])."'/>";
					echo "<input type='hidden' name='password' value='".htmlspecialchars($this->data['password'])."'/>";
					echo "<input type='hidden' name='form' value='".$this->data['form']."'/>";
					echo "<input type='image' src='resources/infocard_self_114x80.png' style='cursor:pointer' />";
				echo '</form>';
			} else {
				echo '<h2>'.$this->t('getcardform_finished_title').'</h2>';
				echo '<p>'.$this->t('getcardform_finished_text').'</p>';
				echo '<p> <a href="login-infocard.php?AuthState='.htmlspecialchars($this->data['stateparams']['AuthState']).'">LOGIN</a></p>';
			}
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
