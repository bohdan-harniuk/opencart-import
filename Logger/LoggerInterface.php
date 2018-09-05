<?php

namespace NewImport\Logger;

interface LoggerInterface {
    public function output($message);
    public function setFilename($filename);
    public function setMode($mode);
}