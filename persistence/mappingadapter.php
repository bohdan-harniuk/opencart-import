<?php
namespace NewImport\Persistence;

class MappingAdapter implements MapperInterface
{
    protected $storageFolder;
    protected $filename;
    protected $data = [];
    protected $initialData;

    protected $systemKeyData = [];
    protected $storeKeyData = [];

    protected $externalData = [];

    public function __construct($storageFolder)
    {
        $this->storageFolder = $storageFolder;
    }

    public function setFileName($filename)
    {
        $this->filename = $filename;
        return $this;
    }

    public function addMatch($systemId, $storeId)
    {
        $this->data[] = [
            'system_id' => $systemId,
            'store_id'  => $storeId
        ];
        if (count($this->systemKeyData) > 0) {
            $this->systemKeyData[$systemId] = $storeId;
        }
        if (count($this->storeKeyData) > 0) {
            $this->storeKeyData[$storeId] = $systemId;
        }
    }

    public function getSystemId($storeId)
    {
        if (count($this->storeKeyData) === 0) {
            foreach ($this->data as $item) {
                $this->storeKeyData[$item['store_id']] = $item['system_id'];
            }
        }
        if (key_exists($storeId, $this->storeKeyData)) {
            return $this->storeKeyData[$storeId];
        } else {
            return false;
        }
    }

    public function getStoreId($systemId)
    {
        if (count($this->systemKeyData) === 0) {
            foreach ($this->data as $item) {
                $this->systemKeyData[$item['system_id']] = $item['store_id'];
            }
        }
        if (key_exists($systemId, $this->systemKeyData)) {
            return $this->systemKeyData[$systemId];
        } else {
            return false;
        }
    }

    public function getStoreIdFromInstance($instanceFileName, $instanceSystemId, $instanceStorageFolder = null) {
        if (count($this->externalData) === 0 || !key_exists($instanceFileName, $this->externalData)) {
            $this->externalData[$instanceFileName] = [];
            if ($instanceStorageFolder === null) {
                $instanceStorageFolder = $this->storageFolder;
            }
            $file = $instanceStorageFolder . $instanceFileName;
            $rawData = $this->readFromFile($file);
            foreach ($rawData as $item) {
                $this->externalData[$instanceFileName][$item['system_id']] = $item['store_id'];
            }
        }

        if (key_exists($instanceSystemId, $this->externalData[$instanceFileName])) {
            return $this->externalData[$instanceFileName][$instanceSystemId];
        } else {
            return false;
        }
    }

    public function readData()
    {
        $file = $this->storageFolder . $this->filename;
        if (file_exists($file)) {
            $this->data = json_decode(file_get_contents($file), true);
        } else {
            $initialData = $this->getInitialData();
            if (count($initialData) > 0) {
                $initialData = json_encode($initialData);
                file_put_contents($file, $initialData);
                $this->data = json_decode($initialData, true);
            } else {
                file_put_contents($file, '');
            }
        }
        return $this;
    }

    public function readFromFile($file)
    {
        $result = [];
        if (file_exists($file)) {
            $result = json_decode(file_get_contents($file), true);
        }
        return $result;
    }

    public function saveData()
    {
        $file = $this->storageFolder . $this->filename;
        file_put_contents($file, json_encode($this->data));
        $this->clearData();
    }

    private function clearData()
    {
        $this->data = [];
    }

    public function setInitialData($data)
    {
        $this->initialData = $data;
        return $this;
    }

    private function getInitialData()
    {
        return $this->initialData;
    }

}