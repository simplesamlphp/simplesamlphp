<?php
/*
 * @author Andreas Åkre Solberg <andreas.solberg@uninett.no>
 * @package simpleSAMLphp
 * @version $Id$
 */
class sspmod_statistics_Graph_GoogleCharts {

	private $x, $y;

	/**
	 * Constructor
	 */
	public function __construct($x, $y) {
		$this->x = $x; $this->y = $y;
	}

	private function encodeaxis($axis) {
		return join('|', $axis);
	}

	# t:10.0,58.0,95.0
	private function encodedata($data) {
		return 't:' . join(',', $data);
	}
	
	public function show($axis, $axispos, $values, $max) {
	
		$nv = count($values);
		$url = 'http://chart.apis.google.com/chart?' .
			'chs=800x350' .
			'&chd=' . $this->encodedata($values) .
			'&cht=lc' .
			'&chxt=x,y' .
			'&chxl=0:|' . $this->encodeaxis($axis) . # . $'|1:||top' .
			'&chxp=0,' . join(',', $axispos) . 
#			'&chxp=0,0.3,0.4' .
			'&chxr=0,0,1|1,0,' . $max . 
#			'&chm=R,CCCCCC,0,0.25,0.5' .
			'&chg=' . (2400/(count($values)-1)) . ',20,3,3';   // lines
		return $url;
	}
	
	public static function roof($in) {
		if ($in < 1) return 1;
		$base = log10($in);
		$r =  ceil(5*$in / pow(10, ceil($base)));
		return ($r/5)*pow(10, ceil($base));
	}
	// $foo = array(0, 2, 2.3, 2.6, 6, 10, 15, 98, 198, 256, 487, 563, 763, 801, 899, 999, 987, 198234.485, 283746);
	// foreach ($foo AS $f) {
	// 	echo '<p>' . $f . ' => ' . roof($f);
	// }
	// exit;

}

?>