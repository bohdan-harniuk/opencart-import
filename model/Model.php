<?php

namespace NewImport\Model;

use NewImport\Persistence\DatabaseAdapter;
use NewImport\Reader\ReaderInterface;

class Model {

    /**
     * @var \NewImport\Reader\ReaderInterface $reader
     */
    protected $reader;
    protected $data;
    protected $db;
    private $settingsFormat = array();

    private $table;
    private $result;

    private $instanceMapType = '1s_id';

    public function __construct()
    {
    }

    public function setReader(ReaderInterface $reader) {
        $this->reader = $reader;
        return $this;
    }

    public function read($fileName) {
        $this->data = $this->reader->readFile($fileName);
        return $this;
    }

    public function getReadedData() {
        return $this->data;
    }

    public function setDatabase(DatabaseAdapter $database) {
        $this->db = $database;
        return $this;
    }

    public function setDataSettings(array $settings) {
        return $this;
    }

    public function setDataSettingsFormat(array $settingsFormat) {
        $this->settingsFormat = $settingsFormat;
    }

    public function getDataSettingsFormat() {
        return $this->settingsFormat;
    }

    public function setImportResult($message) {
        $this->result = $message;
    }

    public function getImportResult() {
        return $this->result;
    }

    public function setInstanceMapType($type) {
        $this->instanceMapType = $type;
        return $this;
    }

    public function getInstanceMapType() {
        return $this->instanceMapType;
    }
}