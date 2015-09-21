<?php
/**
 * Functions used to present a table of attributes and their values.
 */

function present_list($attr) {
	if (is_array($attr) && count($attr) > 1) {
		$str = '<ul>';
		foreach ($attr as $value) {
			$str .= '<li>' . htmlspecialchars($attr) . '</li>';
		}
		$str .= '</ul>';
		return $str;
	} else {
		return htmlspecialchars($attr[0]);
	}
}

function present_assoc($attr) {
	if (is_array($attr)) {
		
		$str = '<dl>';
		foreach ($attr AS $key => $value) {
			$str .= "\n" . '<dt>' . htmlspecialchars($key) . '</dt><dd>' . present_list($value) . '</dd>';
		}
		$str .= '</dl>';
		return $str;
	} else {
		return htmlspecialchars($attr);
	}
}

function present_attributes($t, $attributes, $nameParent) {
	$alternate = array('odd', 'even'); $i = 0;
	
	$parentStr = (strlen($nameParent) > 0)? strtolower($nameParent) . '_': '';
	$str = (strlen($nameParent) > 0)? '<table class="attributes" summary="attribute overview">':
		'<table id="table_with_attributes"  class="attributes" summary="attribute overview">';

	foreach ($attributes as $name => $value) {
	
		$nameraw = $name;
		$name = $t->getAttributeTranslation($parentStr . $nameraw);

		if (preg_match('/^child_/', $nameraw)) {
			$parentName = preg_replace('/^child_/', '', $nameraw);
			foreach($value AS $child) {
				$str .= '<tr class="odd"><td colspan="2" style="padding: 2em">' . present_attributes($t, $child, $parentName) . '</td></tr>';
			}
		} else {	
			if (sizeof($value) > 1) {
				$str .= '<tr class="' . $alternate[($i++ % 2)] . '"><td class="attrname">';

				if ($nameraw !== $name)
					$str .= htmlspecialchars($name).'<br/>';
				$str .= '<tt>'.htmlspecialchars($nameraw).'</tt>';
				$str .= '</td><td class="attrvalue"><ul>';
				foreach ($value AS $listitem) {
					if ($nameraw === 'jpegPhoto') {
						$str .= '<li><img src="data:image/jpeg;base64,' . htmlspecialchars($listitem) . '" /></li>';
					} else {
						$str .= '<li>' . present_assoc($listitem) . '</li>';
					}
				}
				$str .= '</ul></td></tr>';
			} elseif(isset($value[0])) {
				$str .= '<tr class="' . $alternate[($i++ % 2)] . '"><td class="attrname">';
				if ($nameraw !== $name)
					$str .= htmlspecialchars($name).'<br/>';
				$str .= '<tt>'.htmlspecialchars($nameraw).'</tt>';
				$str .= '</td>';
				if ($nameraw === 'jpegPhoto') {
					$str .= '<td class="attrvalue"><img src="data:image/jpeg;base64,' . htmlspecialchars($value[0]) . '" /></td></tr>';
				} else {
					$str .= '<td class="attrvalue">' . htmlspecialchars($value[0]) . '</td></tr>';
				}
			}
		}
		$str .= "\n";
	}
	$str .= '</table>';
	return $str;
}
