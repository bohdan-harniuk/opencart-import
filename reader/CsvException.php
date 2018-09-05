<?php

namespace NewImport\Reader;

use Exception;
use Throwable;

class CsvException extends Exception {

    public function __construct($message = "", $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->message = $this->message . '<br />' . $this->getDetails();
    }

    private function getDetails() {
        return "Error on line " . $this->getLine() . " in file: '" . $this->getFile() . "'";
    }
}