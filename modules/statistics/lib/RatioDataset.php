<?php

namespace SimpleSAML\Module\statistics;

/**
 * @author Andreas Åkre Solberg <andreas.solberg@uninett.no>
 * @package SimpleSAMLphp
 */
class RatioDataset extends StatDataset
{
    /**
     * @return void
     */
    public function aggregateSummary()
    {
        /**
         * Aggregate summary table from dataset. To be used in the table view.
         */
        $this->summary = [];
        $noofvalues = [];
        foreach ($this->results as $slot => $res) {
            foreach ($res as $key => $value) {
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


    /**
     * @param string $k
     * @param array $a
     * @return int
     */
    private function ag($k, $a)
    {
        if (array_key_exists($k, $a)) {
            return $a[$k];
        }
        return 0;
    }


    /**
     * @param int $v1
     * @param int $v2
     * @return int|float
     */
    private function divide($v1, $v2)
    {
        if ($v2 == 0) {
            return 0;
        }
        return ($v1 / $v2);
    }


    /**
     * @param array $result1
     * @param array $result2
     * @return array
     */
    public function combine($result1, $result2)
    {
        $combined = [];

        foreach ($result2 as $tick => $val) {
            $combined[$tick] = [];
            foreach ($val as $index => $num) {
                $combined[$tick][$index] = $this->divide(
                    $this->ag($index, $result1[$tick]),
                    $this->ag($index, $result2[$tick])
                );
            }
        }
        return $combined;
    }


    /**
     * @return null
     */
    public function getPieData()
    {
        return null;
    }
}
