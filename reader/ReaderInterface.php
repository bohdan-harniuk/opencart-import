<?php

namespace NewImport\Reader;

interface ReaderInterface {
    /**
     * @param $file string 'path to file'
     * @return mixed
     */
    public function readFile($file);
    public function setFileName($fileName);
    public function getFileName();
}