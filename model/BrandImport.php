<?php
/**
 * Created by PhpStorm.
 * User: Max Cage
 * Date: 25.02.19
 * Time: 17:18
 */

namespace NewImport\Model;

use NewImport\ImportSettings;
use NewImport\Logger\LoggerInterface;
use NewImport\Model\Exceptions\ImportException;
use NewImport\Model\Exceptions\MapFieldsWithDataException;
use NewImport\Persistence\MapperInterface;

class BrandImport extends Model implements ModelInterface {
    private $id;
    private $image;
    private $sortOrder;

    private $name;
    private $description;
    private $metaTitle;
    private $metaH1;
    private $metaDescription;
    private $metaKeyword;

    private $languageId;

    const MODEL_2DATA_SCHEMA = [
        'manufacturer_id'   => 'id',
        'image'             => 'image',
        'sort_order'        => 'sortOrder',

        'name'              => 'name',
        'description'       => 'description',
        'meta_title'        => 'metaTitle',
        'meta_h1'           => 'metaH1',
        'meta_description'  => 'metaDescription',
        'meta_keyword'      => 'metaKeyword',

        'language_id'       => 'languageId'
    ];

    private $modelToReadedDataSchema = array();

    private $otherStoreLanguages = array();

    private $data2Update = [];
    private $manufacturerDefaultFields2Update = ['image'];
    private $manufacturer_descriptionDefaultFields2Update = ['language_id', 'name', 'description', 'meta_title', 'meta_h1', 'meta_description', 'meta_keyword'];

    const ROUTE = "manufacturer_id=";

    protected $settingObject;
    /**
     * @var \NewImport\Persistence\MapperInterface $mapper
     */
    private $mapper;
    /**
     * @var LoggerInterface $logger
     */
    private $logger;
    private $store_id;
    private $layout_id;

    public function __construct(LoggerInterface $logger, MapperInterface $mapper, $store_id = 0, $layout_id = 0)
    {
        $this->store_id = $store_id;
        $this->layout_id = $layout_id;

        $this->logger = $logger;

        $classArray = [];
        $classArray = explode('\\', get_class($this));
        $className = $classArray[count($classArray) - 1];
        $this->mapper = $mapper
            ->setFileName($className . '.json')
            ->setInitialData([])
            ->readData();

        $this->setDataSettingsFormat([
            'fields'    => [
                'manufacturer_id'   => 0,
                'image'             => '',
                'sort_order'        => 0,

                'name'              => '',
                'description'       => '',
                'meta_title'        => '',
                'meta_h1'           => '',
                'meta_description'  => '',
                'meta_keyword'      => ''
            ],
            'language_id'   => 0,
            'other_store_languages' => '',
            'data2update'   => ['name']
        ]);
    }

    public function setDataSettings(array $settings)
    {
        try {
            if (!isset($settings['fields'])) {
                throw new MapFieldsWithDataException('You must to map Brands fields with your data headers!!!');
            } else {
                $this->mapFieldsWithReadedData($settings['fields']);
            }
            if (!isset($settings['language_id'])) {
                throw new ImportException('You must to set imported data language id from your OpenCart store!');
            } else {
                $this->{$this::MODEL_2DATA_SCHEMA['language_id']} = $settings['language_id'];
            }
            if (!isset($settings['other_store_languages'])) {
                throw new ImportException('You must to set at least one addition language for your store in Brand instance!<br />If your store has only one language, set field \'other_store_languages\' to false!');
            } else {
                if (!is_array($settings['other_store_languages']) && $settings['other_store_languages'] !== false) {
                    throw new ImportException('Field \'other_store_languages\' has to be set as Array instance or Boolean\'s False!');
                } else {
                    $this->otherStoreLanguages = $settings['other_store_languages'];
                }
            }
            if (!isset($settings['data2update'])) {
                throw new ImportException('You must to set data to updating with import for category instance!');
            } else {
                if (!is_array($settings['data2update'])) {
                    throw new ImportException('Data to updating in category instance have to be an Array instance!');
                } else {
                    $this->data2Update = $settings['data2update'];
                }
            }
        } catch (\Exception $exception) {
            if ($exception instanceof MapFieldsWithDataException || $exception instanceof ImportException) {
                echo $exception->getMessage();
                echo "<br />There is required format for category setting:<br />";
                var_dump($this->getDataSettingsFormat());
                echo "<br />You must to fill at least one value for each key!<br />";
                exit();
            }
        }
        return parent::setDataSettings($settings); // TODO: Change the autogenerated stub
    }

    public function mapFieldsWithReadedData($fields) {
        foreach ($this->getDataSettingsFormat()['fields'] as $field => $defaultValue) {
            $this->{$this::MODEL_2DATA_SCHEMA[$field]} = $defaultValue;
        }
        foreach ($fields as $field => $dataField) {
            $this->modelToReadedDataSchema[$dataField] = $field;
        }
    }

    public function setGeneralSettingObject(ImportSettings $object)
    {
        $this->settingObject = $object;
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
                throw new ImportException("...File '" . $this->render->getFileName() . "' didn`t change from previous import ...<br />", 1);
            }
        } catch (ImportException $exception) {
            echo '<br />' , $exception->getMessage();
            if ($exception->getErrorType() == 'strict') {
                exit();
            }
        }
        if ($processedRows == count($this->data)) {
            $this->setImportResult("Imported " . $processedRows . " from " . count($this->data) . " rows that read from file '" . $this->reader->getFileName() . "';<br />");
        }
        $this->mapper->saveData();
        $this->reader->clearData();
        return $this;
    }

    private $isNeedToEscape = ['name', 'description', 'tag', 'meta_title', 'meta_h1', 'meta_description', 'meta_keyword'];

    private function importRow($row_fields) {
        foreach ($row_fields as $field => $readedValue) {
            $value = $readedValue;
            if (in_array($field, $this->isNeedToEscape)) {
                $value = $this->db->escape(htmlspecialchars($readedValue, ENT_QUOTES));
            }
            if (key_exists($field, $this->modelToReadedDataSchema)) {
                $this->{$this::MODEL_2DATA_SCHEMA[$this->modelToReadedDataSchema[$field]]} = $value;
            }
        }
        if ($category_id = $this->manufacturerExist()) {
            $this->updateData($category_id);
        } else {
            $this->insertData();
        }
    }

    private function insertData()
    {
        $sql = "INSERT INTO " . DB_PREFIX . "manufacturer SET";
        if (!empty($this->image)) {
            $sql .= " `image` = 'catalog/brand/" . $this->image . "',";
        } else {
            $sql .= " `image` = '" . $this->image . "',";
        }
        $sql .= " `sort_order` = '" . $this->sortOrder . "';";

        $this->db->query($sql);

        $manufacturer_id = $this->db->getLastId();

        $sql = "INSERT INTO " . DB_PREFIX . "manufacturer_description SET";
        $sql .= "`manufacturer_id` = '" . $manufacturer_id . "',";
        $sql .= "`language_id` = '" . $this->languageId . "',";
        $sql .= "`name` = '" . $this->name . "',";
        $sql .= "`description` = '" . $this->description . "',";
        $sql .= "`meta_title` = '" . $this->metaTitle . "',";
        $sql .= "`meta_h1` = '" . $this->metaH1 . "',";
        $sql .= "`meta_description` = '" . $this->metaDescription . "',";
        $sql .= "`meta_keyword` = '" . $this->metaDescription . "';";

        $this->db->query($sql);

        $this->setUrlAlias($manufacturer_id, $this->name);
        $this->setManufacturerStore($manufacturer_id);

        if ($this->otherStoreLanguages !== false) {
            foreach ($this->otherStoreLanguages as $language_id) {
                $sql = "INSERT INTO " . DB_PREFIX . "manufacturer_description SET";
                $sql .= "`manufacturer_id` = '" . $manufacturer_id . "',";
                $sql .= "`language_id` = '" . $language_id . "',";
                $sql .= "`name` = '" . $this->name . "',";
                $sql .= "`description` = '" . $this->description . "',";
                $sql .= "`meta_title` = '" . $this->metaTitle . "',";
                $sql .= "`meta_h1` = '" . $this->metaH1 . "',";
                $sql .= "`meta_description` = '" . $this->metaDescription . "',";
                $sql .= "`meta_keyword` = '" . $this->metaDescription . "';";

                $this->db->query($sql);
            }
        }
        $this->mapper->addMatch($this->id, $manufacturer_id);
    }

    private function updateData($manufacturer_id)
    {
        if ($fields2Update = $this->isNeedToUpdate('manufacturer')) {
            $sql = "UPDATE " . DB_PREFIX . "manufacturer SET";
            if ($sql[strlen($sql) - 1] == ',') {
                $sql = substr($sql, 0, strlen($sql) - 2);
            }
            $sql .= " WHERE `manufacturer_id` = '" . (int)$manufacturer_id . "';";
            $this->db->query($sql);
        }
    }

    private function isNeedToUpdate($table)
    {
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

    private function manufacturerExist()
    {
        $manufacturer_id = $this->mapper->getStoreId($this->id);

        if ($manufacturer_id !== false) {
            return $manufacturer_id;
        } else {
            return false;
        }
    }

    private function getManufacturerIdBySystemId($system_id)
    {
        $manufacturer_id = $this->mapper->getStoreId($system_id);
        if ($manufacturer_id !== false) {
            return $manufacturer_id;
        } else {
            // TODO: throw an Exception
            return 0;
        }
    }

    private function setUrlAlias($manufacturer_id, $name)
    {
        $sql = "INSERT INTO " . DB_PREFIX . "url_alias SET `query` = '" . self::ROUTE . $manufacturer_id . "',";
        $sql .= " `keyword` = '" . $this->transliterate($name) . "';";
        $this->db->query($sql);
    }

    private function setManufacturerStore($manufacturer_id)
    {
        $sql = "INSERT INTO " . DB_PREFIX . "manufacturer_to_store SET";
        $sql .= " `manufacturer_id` = '" . (int)$manufacturer_id . "',";
        $sql .= " `store_id` = '" . (int)$this->store_id . "';";
        $this->db->query($sql);
    }

    private function transliterate($str)
    {
        $rus = array('/','%','&','?',' ','А', 'Б', 'В', 'Г', 'Д', 'Е', 'Ё', 'Ж', 'З', 'И', 'Й', 'К', 'Л', 'М', 'Н', 'О', 'П', 'Р', 'С', 'Т', 'У', 'Ф', 'Х', 'Ц', 'Ч', 'Ш', 'Щ', 'Ъ', 'Ы', 'Ь', 'Э', 'Ю', 'Я', 'а', 'б', 'в', 'г', 'д', 'е', 'ё', 'ж', 'з', 'и', 'й', 'к', 'л', 'м', 'н', 'о', 'п', 'р', 'с', 'т', 'у', 'ф', 'х', 'ц', 'ч', 'ш', 'щ', 'ъ', 'ы', 'ь', 'э', 'ю', 'я','і','ї','ґ','є','І','Ї','Ґ','Є','!',',','-','"','\'');
        $lat = array('_0','_1','_2','_3','_','A', 'B', 'V', 'G', 'D', 'E', 'E', 'Gh', 'Z', 'I', 'Y', 'K', 'L', 'M', 'N', 'O', 'P', 'R', 'S', 'T', 'U', 'F', 'H', 'C', 'Ch', 'Sh', 'Sch', 'Y', 'Y', 'Y', 'E', 'Yu', 'Ya', 'a', 'b', 'v', 'g', 'd', 'e', 'e', 'gh', 'z', 'i', 'y', 'k', 'l', 'm', 'n', 'o', 'p', 'r', 's', 't', 'u', 'f', 'h', 'c', 'ch', 'sh', 'sch', 'y', 'y', 'y', 'e', 'yu', 'ya','i','yi','gg','ye','I','YI','GG','YE','_','_','_','_','_');
        return str_replace($rus, $lat, $str);
    }
}
