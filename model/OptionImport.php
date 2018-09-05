<?php

namespace NewImport\Model;

use NewImport\ImportSettings;
use NewImport\Logger\LoggerInterface;
use NewImport\Model\Exceptions\ImportException;
use NewImport\Model\Exceptions\MapFieldsWithDataException;
use NewImport\Persistence\MapperInterface;

class OptionImport extends Model implements ModelInterface {
    private $id;  /// import id
    private $optionId;
    private $image;
    private $sortOrder;

    private $name;

    private $languageId;

    const MODEL_2DATA_SCHEMA = [
        'option_value_id'   => 'id',
        'option_id'         => 'optionId',
        'image'             => 'image',
        'sort_order'        => 'sortOrder',

        'name'              => 'name',

        'language_id'       => 'languageId',
    ];

    private $modelToReadedDataSchema = array();

    private $otherStoreLanguages = array();

    private $data2Update = [];
    private $option_valueDefaultFields2Update = ['image', 'sort_order'];
    private $option_value_descriptionDefaultFields2Update = ['language_id', 'name'];

    protected $settingObject;

    /**
     * @var \NewImport\Persistence\MapperInterface $mapper
     */
    private $mapper;
    /**
     * @var \NewImport\Logger\Logger $logger
     */
    private $logger;

    public function __construct(
        LoggerInterface $logger,
        MapperInterface $mapper,
        $option_name = ''
    ) {
        $this->logger = $logger;

        $classArray = [];
        $classArray = explode('\\', get_class($this));
        $className = $classArray[count($classArray) - 1];
        $this->mapper = $mapper
            ->setFileName($className . '_' . $option_name . '.json')
            ->setInitialData([])
            ->readData();

        $this->setDataSettingsFormat([
            'fields' => [
                'option_value_id'   => 0,
                'option_id'         => 0,
                'image'             => null,
                'sort_order'        => 0,

                'name'              => '',
            ],
            'language_id' => 0,
            'other_store_languages' => '',
            'data2update' => ['name', 'image', 'sort_order'],
            'option_id'   => 0,
        ]);
    }

    public function setDataSettings(array $settings)
    {
        try {
            if (!isset($settings['fields'])) {
                throw new MapFieldsWithDataException('You must to map Option Value fields with your data headers!!!');
            } else {
                $this->mapFieldsWithReadedData($settings['fields']);
            }
            if (!isset($settings['language_id'])) {
                throw new ImportException('You must to set imported data language id from your OpenCart store!');
            } else {
                $this->{$this::MODEL_2DATA_SCHEMA['language_id']} = $settings['language_id'];
            }
            if (!isset($settings['other_store_languages'])) {
                throw new ImportException('You must to set at least one addition language for your store in "Option Value" instance!<br />If your store has only one language, set field \'other_store_languages\' to false!');
            } else {
                if (!is_array($settings['other_store_languages']) && $settings['other_store_languages'] !== false) {
                    throw new ImportException('Field \'other_store_languages\' has to be set as Array instance or Boolean\'s False!');
                } else {
                    $this->otherStoreLanguages = $settings['other_store_languages'];
                }
            }
            if (!isset($settings['data2update'])) {
                throw new ImportException('You must to set data to updating with import for "Option Value" instance!');
            } else {
                if (!is_array($settings['data2update'])) {
                    throw new ImportException('Data to updating in "Option Value" instance have to be an Array instance!');
                } else {
                    $this->data2Update = $settings['data2update'];
                }
            }
            if (!isset($settings['option_id'])) {
                throw new ImportException('You must to set option id field for "Option Value" instance!');
            } else {
                $this->optionId = $settings['option_id'];
            }

        } catch (\Exception $exception) {
            if ($exception instanceof MapFieldsWithDataException || $exception instanceof ImportException) {
                echo $exception->getMessage();
                echo "<br />There is required format for \"Option Value\" instance settings:<br />";
                var_dump($this->getDataSettingsFormat());
                echo "<br />You must to fill at least one value for each key!<br />";
                exit();
            }
        }
        return parent::setDataSettings($settings);
    }

    private function mapFieldsWithReadedData($fields) {
        foreach ($this->getDataSettingsFormat()['fields'] as $field => $defaultValue) {
            $this->{$this::MODEL_2DATA_SCHEMA[$field]} = $defaultValue;
        }
        foreach ($fields as $field => $dataField) {
            $this->modelToReadedDataSchema[$dataField] = $field;
        }
    }

    public function setGeneralSettingObject(ImportSettings $object) {
        $this->settingObject = $object;
        $this->settingObject->prepareTable("option_value");
        $this->settingObject->initialHashSetting($this->reader->getFileName());
        return $this;
    }

    public function importData() {
        try {
            $processedRows = 0;
            if ($this->settingObject->isChanged($this->reader->getFileName())) {
                foreach ($this->getReadedData() as $row) {
                    $this->importRow($row);
                    $processedRows++;
                }
                $this->settingObject->hashFile($this->reader->getFileName());
            } else {
                throw new ImportException("...File '" . $this->reader->getFileName() . "' didn't change from previous import...<br/>", 1);
            }
        } catch (ImportException $exception) {
            echo '<br />' . $exception->getMessage();
            if ($exception->getErrorType() == 'strict') {
                exit();
            }
        }
        if ($processedRows == count($this->data)) {
            $this->setImportResult("Imported " . $processedRows . " from " . count($this->data) . " rows that read from file '" . $this->reader->getFileName() . "';<br/>");
        }
        $this->mapper->saveData();
        $this->reader->clearData();
        return $this;
    }

    private function importRow($row_fields) {
        foreach ($row_fields as $field => $readedValue) {
            $this->{$this::MODEL_2DATA_SCHEMA[$this->modelToReadedDataSchema[$field]]} = $readedValue;
        }
        if ($optionValueId = $this->optionValueExist()) {
            $this->updateData($optionValueId);
        } else {
            $this->insertData();
        }
    }

    private function insertData() {
        $sql  = "INSERT INTO " . DB_PREFIX . "option_value SET";
        $sql .= " `image` = '" . $this->image . "',";
        $sql .= " `option_id` = '" . $this->optionId . "',";
        $sql .= " `sort_order` = '" . $this->sortOrder . "';";
//        $sql .= " `1s_id` = '" . $this->id . "';";
        $this->db->query($sql);

        $option_value_id = $this->db->getLastId();

        $sql  = "INSERT INTO " . DB_PREFIX . "option_value_description SET";
        $sql .= " `option_value_id` = '" . $option_value_id . "',";
        $sql .= " `language_id` = '" . $this->languageId . "',";
        $sql .= " `option_id` = '" . $this->optionId . "',";
        $sql .= " `name` = '" . $this->name . "';";
        $this->db->query($sql);

        if ($this->otherStoreLanguages !== false) {
            foreach ($this->otherStoreLanguages as $language_id) {
                $sql  = "INSERT INTO " . DB_PREFIX . "option_value_description SET";
                $sql .= " `option_value_id` = '" . $option_value_id . "',";
                $sql .= " `language_id` = '" . $language_id . "',";
                $sql .= " `option_id` = '" . $this->optionId . "',";
                $sql .= " `name` = '" . $this->name . "';";
                $this->db->query($sql);
            }
        }
        if (!in_array('sort_order', $this->data2Update)) {
            $this->sortOrder++;
        }
        $this->mapper->addMatch($this->id, $option_value_id);
    }

    private function updateData($option_value_id) {
        if ($fields2Update = $this->isNeedToUpdate('option_value')) {
            $sql  = "UPDATE " . DB_PREFIX . "option_value SET";
            foreach ($fields2Update as $field) {
                $sql .= " `" . $field . "` = '" . $this->{$this::MODEL_2DATA_SCHEMA[$field]} . "',";
            }
            if ($sql[strlen($sql) - 1] == ',') {
                $sql = substr($sql, 0, strlen($sql) - 2);
            }
            $sql .= " WHERE `option_id` = '" . $this->optionId . "' AND `option_value_id` = '" . $option_value_id . "';";
            $this->db->query($sql);
        }

        if ($fields2Update = $this->isNeedToUpdate('option_value_description')) {
            $sql  = "UPDATE " . DB_PREFIX . "option_value_description SET";
            foreach ($fields2Update as $field) {
                $sql .= " `" . $field . "` = '" . $this->{$this::MODEL_2DATA_SCHEMA[$field]} . "',";
            }
            if ($sql[strlen($sql) - 1] == ',') {
                $sql = substr($sql, 0, strlen($sql) - 1);
            }
            $sql .= " WHERE `option_id` = '" . $this->optionId . "' AND `option_value_id` = '" . $option_value_id . "' AND `language_id` = '" . $this->languageId . "';";
            $this->db->query($sql);
        }
    }

    private function isNeedToUpdate($table) {
        $fields2Update = [];
        foreach ($this->{$table . 'DefaultFields2Update'} as $defaultField) {
            if (in_array($defaultField, $this->data2Update)) {
                array_push($fields2Update, $defaultField);
            }
        }
        if (empty($fields2Update)) {
            return false;
        } else return $fields2Update;
    }

    private function optionValueExist() {
//        $sql = "SELECT * FROM " . DB_PREFIX . "option_value WHERE `1s_id` = '" . $this->id . "' AND `option_id` = '" . $this->optionId . "';";
//        $optionValue = $this->db->select($sql);
        $optionValueId = $this->mapper->getStoreId($this->id);
        if ($optionValueId) {
            return $optionValueId;
        } else {
            return false;
        }
    }

//    private function getOptionValueIdBy1sId($_1s_id) {
//        $sql = "SELECT * FROM " . DB_PREFIX . "option_value WHERE `1s_id` = '" . $_1s_id . "';";
//        $optionValue = $this->db->select($sql);
//        if ($optionValue) {
//            return $optionValue['option_value_id'];
//        } else {
//            return false;
//        }
//    }

}