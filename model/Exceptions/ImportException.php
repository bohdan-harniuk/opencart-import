<?php

namespace NewImport\Model\Exceptions;

use Exception;
use Throwable;

class ImportException extends Exception {

    private $errorType = 'strict';

    public function __construct($message = "", $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->message = $this->message . '<br />' . $this->getDetails();
        if ($code == 0) {
            $this->errorType = 'strict';
        } else if ($code == 1) {
            $this->errorType = 'light';
        }
    }

    private function getDetails() {
        if ($this->code == 0) {
            return "Error on line " . $this->getLine() . " in file: '" . $this->getFile() . "'";
        } else {
            return "";
        }
    }

    public function getErrorType() {
        return $this->errorType;
    }
}