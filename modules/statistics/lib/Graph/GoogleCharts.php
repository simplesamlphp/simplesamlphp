<?php
/*
 * sspmod_statistics_Graph_GoogleCharts will help you to create a Google Chart
 * using the Google Charts API. 
 *
 * @author Andreas Åkre Solberg <andreas.solberg@uninett.no>
 * @package simpleSAMLphp
 * @version $Id$
 */
class sspmod_statistics_Graph_GoogleCharts {

	private $x, $y;

	/**
	 * Constructor.
	 *
	 * Takes dimension of graph as parameters. X and Y.
	 *
	 * @param $x 	X dimension. Default 800.
	 * @param $y 	Y dimension. Default 350.
	 */
	public function __construct($x = 800, $y = 350) {
		$this->x = $x; $this->y = $y;
	}

	private function encodeaxis($axis) {
		return join('|', $axis);
	}

	# t:10.0,58.0,95.0
	private function encodedata($data) {
		return 't:' . join(',', $data);
	}
	
	/**
	 * Generate a Google Charts URL which points to a generated image.
	 * More documentation on Google Charts here: 
	 *   http://code.google.com/apis/chart/
	 *
	 * @param $axis		Axis
	 * @param $axpis	Axis positions
	 * @param $values	Dataset values
	 * @param $max		Max value. Will be the topmost value on the Y-axis.
	 */
	public function show($axis, $axispos, $values, $max) {
	
		$nv = count($values);
		$url = 'http://chart.apis.google.com/chart?' .
			
			// Dimension of graph. Default is 800x350
			'chs=' . $this->x . 'x' . $this->y . 
			
			// Dateset values.
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
	
	/**
	 * Takes a input value, and generates a value that suits better as a max
	 * value on the Y-axis. In example 37.6 will not make a good max value, instead
	 * it will return 40. It will always return an equal or larger number than it gets
	 * as input.
	 *
	 * Here is some test code:
	 * <code>
	 * 		$foo = array(0, 2, 2.3, 2.6, 6, 10, 15, 98, 198, 256, 487, 563, 763, 801, 899, 999, 987, 198234.485, 283746);
	 *		foreach ($foo AS $f) {
	 *			echo '<p>' . $f . ' => ' . sspmod_statistics_Graph_GoogleCharts::roof($f);
	 *		}
	 * </code>
	 * 
	 * @param $in 	Input value.
	 */
	public static function roof($in) {
		if ($in < 1) return 1;
		$base = log10($in);
		$r =  ceil(5*$in / pow(10, ceil($base)));
		return ($r/5)*pow(10, ceil($base));
	}

}

?>