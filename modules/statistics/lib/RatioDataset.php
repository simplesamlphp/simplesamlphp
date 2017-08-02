<?php
/*
 * @author Andreas Åkre Solberg <andreas.solberg@uninett.no>
 * @package SimpleSAMLphp
 */
class sspmod_statistics_RatioDataset extends sspmod_statistics_StatDataset
{
    public function aggregateSummary()
    {
        /**
         * Aggregate summary table from dataset. To be used in the table view.
         */
        $this->summary = array(); 
        $noofvalues = array();
        foreach ($this->results as $slot => $res) {
            foreach ($res AS $key => $value) {
                if (array_key_exists($key, $this->summary)) {
                    $this->summary[$key] += $value;
                    if ($value > 0) {
                        $noofvalues[$key]++;
                    }
                } else {
                    $this->summary[$key] = $value;
                    if ($value > 0) {
                        $noofvalues[$key] = 1;
                    } else {
                        $noofvalues[$key] = 0;
                    }
                }
            }
        }

        foreach ($this->summary as $key => $val) {
            $this->summary[$key] = $this->divide($this->summary[$key], $noofvalues[$key]);
        }

        asort($this->summary);
        $this->summary = array_reverse($this->summary, true);
    }

    private function ag($k, $a)
    {
        if (array_key_exists($k, $a)) {
            return $a[$k];
        }
        return 0;
    }

    private function divide($v1, $v2)
    {
        if ($v2 == 0) {
            return 0;
        }
        return ($v1 / $v2);
    }

    public function combine($result1, $result2)
    {
        $combined = array();

        foreach ($result2 as $tick => $val) {
            $combined[$tick] = array();
            foreach ($val as $index => $num) {
                $combined[$tick][$index] = $this->divide( 
                    $this->ag($index, $result1[$tick]),
                    $this->ag($index, $result2[$tick])
                );
            }
        }
        return $combined;
    }

    public function getPieData()
    {
        return null;
    }
}

