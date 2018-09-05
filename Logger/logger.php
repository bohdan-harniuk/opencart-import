<?php
namespace NewImport\Logger;

class Logger implements LoggerInterface
{
    protected $filename = 'store_import.log';
    protected $loggerMode = 'developer';

    public function output($message) {
        switch ($this->loggerMode) {
            case 'developer':
                echo $message;
                break;
            case 'production':
                error_log(str_replace(['<br>', '<br/>', '<br />'], "\n", $message), 3, DIR_LOGS . $this->filename);
                break;
        }
    }

    public function setFilename($filename) {
        $this->filename = $filename;
        return $this;
    }

    public function setMode($mode) {
        $this->loggerMode = $mode;
        return $this;
    }

}