<?php

namespace SimpleSAML\Module\statistics;

/*
 * @author Andreas Åkre Solberg <andreas.solberg@uninett.no>
 * @package SimpleSAMLphp
 */

class DateHandlerMonth extends DateHandler
{
    /**
     * Constructor
     *
     * @param integer $offset Date offset
     */
    public function __construct($offset)
    {
        $this->offset = $offset;
    }

    public function toSlot($epoch, $slotsize)
    {
        $dsttime = $this->getDST($epoch) + $epoch;
        $parsed = getdate($dsttime);
        $slot = (($parsed['year'] - 2000) * 12) + $parsed['mon'] - 1;
        return $slot;
    }

    public function fromSlot($slot, $slotsize)
    {
        $month = ($slot % 12);
        $year = 2000 + intval(floor($slot / 12));
        return mktime(0, 0, 0, $month + 1, 1, $year);
    }

    public function prettyHeader($from, $to, $slotsize, $dateformat)
    {
        $month = ($from % 12) + 1;
        $year = 2000 + intval(floor($from / 12));
        return $year.'-'.$month;
    }
}
