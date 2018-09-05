<?php

namespace NewImport\Persistence;

interface MapperInterface {
    public function getSystemId($storeId);
    public function getStoreId($systemId);
    public function setFileName($filename);
    public function addMatch($systemId, $storeId);
    public function readData();
    public function saveData();
}