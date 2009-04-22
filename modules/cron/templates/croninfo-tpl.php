<?php

$this->data['header'] = 'Cron information page';
$this->includeAtTemplateBase('includes/header.php');

?>

	<p>Cron is a way to run things regularly on unix systems.</p>
	
	<p>Here is a suggestion for a crontab file:</p>
	<pre style="font-size: x-small; color: #444; padding: 1em; border: 1px solid #eee; margin: .4em "><code><?php
		
		foreach ($this->data['urls'] AS $url ) {
			echo "# " . $url['title'] . "\n";
			echo "" . $url['int'] . " curl --silent \"" . $url['href'] . "\" > /dev/null 2>&1\n";
		}
		
		?>
	</code></pre>
	
	<p>Click here to run the cron jobs:
	<ul>
		<?php
		
		foreach ($this->data['urls'] AS $url ) {
			echo '<li><a href="' . $url['href'] . '">' . $url['title'] . '</a></li>';
		}
		
		?>
		
	</ul>

    
</div>

<?php
$this->includeAtTemplateBase('includes/footer.php');
?>