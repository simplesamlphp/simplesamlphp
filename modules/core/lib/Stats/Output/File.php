<?php

namespace SimpleSAML\Module\core\Stats\Output;

/**
 * Statistics logger that writes to a set of log files
 *
 * @package SimpleSAMLphp
 */

class File extends \SimpleSAML\Stats\Output
{
    /**
     * The log directory.
     * @var string
     */
    private $logDir;

    /**
     * The file handle for the current file.
     * @var resource|null|false
     */
    private $file = null;

    /**
     * The current file date.
     * @var string
     */
    private $fileDate = null;

    /**
     * Initialize the output.
     *
     * @param \SimpleSAML\Configuration $config  The configuration for this output.
     */
    public function __construct(\SimpleSAML\Configuration $config)
    {
        $this->logDir = $config->getPathValue('directory');
        if ($this->logDir === null) {
            throw new \Exception('Missing "directory" option for core:File');
        }
        if (!is_dir($this->logDir)) {
            throw new \Exception('Could not find log directory: '.var_export($this->logDir, true));
        }
    }

    /**
     * Open a log file.
     *
     * @param string $date  The date for the log file.
     */
    private function openLog($date)
    {
        assert(is_string($date));

        if ($this->file !== null && $this->file !== false) {
            fclose($this->file);
            $this->file = null;
        }

        $fileName = $this->logDir.'/'.$date.'.log';
        $this->file = @fopen($fileName, 'a');
        if ($this->file === false) {
            throw new \SimpleSAML\Error\Exception('Error opening log file: '.var_export($fileName, true));
        }

        // Disable output buffering
        stream_set_write_buffer($this->file, 0);

        $this->fileDate = $date;
    }

    /**
     * Write a stats event.
     *
     * @param array $data  The event.
     */
    public function emit(array $data)
    {
        assert(isset($data['time']));

        $time = $data['time'];
        $milliseconds = (int) (($time - (int) $time) * 1000);

        $timestamp = gmdate('Y-m-d\TH:i:s', $time).sprintf('.%03dZ', $milliseconds);

        $outDate = substr($timestamp, 0, 10); // The date-part of the timstamp

        if ($outDate !== $this->fileDate) {
            $this->openLog($outDate);
        }

        $line = $timestamp.' '.json_encode($data)."\n";
        fwrite($this->file, $line);
    }
}
