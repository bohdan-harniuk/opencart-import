<?php

namespace NewImport\Reader;

require_once 'ReaderInterface.php';
require_once 'CsvException.php';

class Csv implements ReaderInterface
{
    private $data = array();
    private $fileName;
    private $delimiter;

    public function __construct($delimiter = ',')
    {
        $this->delimiter = $delimiter;
    }

    public function cp1251ToUTF8($string) {
        $string = addslashes($string);
        if (true === mb_check_encoding($string, 'cp1251')) {
            return mb_convert_encoding($string, "UTF-8", 'cp1251');
        }
        return $string;
    }

    public function readFile($filename) {
        try {
            if (!file_exists($filename)) {
                throw new CsvException('CSV Reader: \'' . $filename . '\' :file not found!');
            }
            $this->setFileName($filename);
            $handle = fopen($filename, 'r');
            $header = fgetcsv($handle, 1024, $this->delimiter);
            $header = array_map([$this, 'cp1251ToUTF8'], $header);
            while (!feof($handle)) {
                $values = fgetcsv($handle, 1024, $this->delimiter);
                $values = array_map([$this, 'cp1251ToUTF8'], $values);
                if (count($header) == count($values)) {
                    $entry = array_combine($header, $values);
                    $this->data[] = $entry;
                } else {
                    $delta = count($header) - count($values);
                    for ($i = 1; $i <= $delta; $i++) {
                        $values[] = '';
                    }
                    if (count($header) == count($values)) {
                        $entry = array_combine($header, $values);
                        $this->data[] = $entry;
                    }
                }
            }
            fclose($handle);
        } catch (CsvException $exception) {
            echo $exception->getMessage();
        }
        return empty($this->data) ? false : $this->data;
    }

    public function setFileName($fileName) {
        $this->fileName = $fileName;
    }

    public function getFileName() {
        return $this->fileName;
    }

    public function clearData() {
        $this->data = [];
    }
}