<?php
$this->data['header'] = 'Statistics';
$this->includeAtTemplateBase('includes/header.php');

echo('<div id="content">');
echo('<h1>'. $this->data['available.rules'][$this->data['selected.rule']]['name'] . '</h1>');
echo('<p>' . $this->data['available.rules'][$this->data['selected.rule']]['descr'] . '</p>');

echo '<div class="selecttime" style="border: 1px solid #999; background: #ccc; margin: .5em; padding: .5em">';




echo '<div style="display: inline">';
echo '<form style="display: inline"><select onChange="submit();" name="rule">';
foreach ($this->data['available.rules'] AS $key => $rule) {
	if ($key === $this->data['selected.rule']) {
		echo '<option selected="selected" value="' . $key . '">' . $rule['name'] . '</option>';
	} else {
		echo '<option value="' . $key . '">' . $rule['name'] . '</option>';
	}
}
echo '</select></form>';
echo '</div>';



echo '<div style="display: inline">';
echo '<form style="display: inline">';
echo '<input type="hidden" name="rule" value="' . $this->data['selected.rule'] . '" />';
echo '<select onChange="submit();" name="time">';
foreach ($this->data['available.times'] AS $key => $timedescr) {
	if ($key == $this->data['selected.time']) {
		echo '<option selected="selected" value="' . $key . '">' . $timedescr . '</option>';
	} else {
		echo '<option  value="' . $key . '">' . $timedescr . '</option>';
	}
}
echo '</select></form>';
echo '</div>';


echo '<div style="display: inline">';
echo '<form style="display: inline">';
echo '<input type="hidden" name="rule" value="' . $this->data['selected.rule'] . '" />';
echo '<input type="hidden" name="time" value="' . $this->data['selected.time'] . '" />';
echo '<select onChange="submit();" name="d">';
foreach ($this->data['availdelimiters'] AS $key => $delim) {
	if ($key == '_') {
		echo '<option value="_">Total</option>';
	} elseif ($delim == $_REQUEST['d']) {
		echo '<option selected="selected" value="' . $delim . '">' . $delim . '</option>';
	} else {
		echo '<option  value="' . $delim . '">' . $delim . '</option>';
	}
}
echo '</select></form>';
echo '</div>';


echo '<br style="clear: both; height: 0px">';
echo '</div>';





echo '<img src="' . htmlspecialchars($this->data['imgurl']) . '" />';








#echo $this->data['selected.time'];






// echo '<h3>Debug information</h3>';
// echo '<input style="width: 80%" value="' . htmlspecialchars($this->data['imgurl']) . '" />';
// 
// echo '<table style="">';
// foreach ($this->data['debugdata'] AS $dd) {
// 	echo '<tr><td style="padding-right: 2em; border: 1px solid #ccc">' . $dd[0] . '</td><td style="padding-right: 2em; border: 1px solid #ccc">' . $dd[1] . '</td></tr>';
// }
// echo '</table>';






// 
// if (count($this->data['sources']) === 0) {
// 	echo('<p>' . $this->t('{aggregator:dict:no_aggregators}') . '</p>');
// } else {
// 
// 	echo('<ul>');
// 
// 	foreach ($this->data['sources'] as $source) {
// 		$encId = urlencode($source);
// 		$encName = htmlspecialchars($source);
// 		echo('<li>');
// 		echo('<a href="?id=' . $encId . '">' . $encName . '</a>');
// 		echo(' <a href="?id=' . $encId . '&amp;mimetype=text/plain">[' . $this->t('{aggregator:dict:text}') . ']</a>');
// 		echo(' <a href="?id=' . $encId . '&amp;mimetype=application/xml">[xml]</a>');
// 		echo('</li>');
// 	}
// 
// 	echo('</ul>');
// }

$this->includeAtTemplateBase('includes/footer.php');
?>