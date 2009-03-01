

<?php

if(array_key_exists('htmlContentPost', $this->data)) {
	foreach(array_reverse($this->data['htmlContentPost']) AS $c) {
		echo $c;
	}
}
?>



		<hr />

		<img src="/<?php echo $this->data['baseurlpath']; ?>resources/icons/ssplogo-fish-small.png" alt="Small fish logo" style="float: right" />		
		Copyright &copy; 2007-2009 <a href="http://rnd.feide.no/">Feide RnD</a>
		
		<br style="clear: right" />
	
	</div><!-- #content -->

</div><!-- #wrap -->

</body>
</html>