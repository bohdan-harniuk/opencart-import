<?php

namespace NewImport\Model;

use NewImport\ImportSettings;
use NewImport\Logger\LoggerInterface;
use NewImport\Model\Exceptions\ImportException;
use NewImport\Model\Exceptions\MapFieldsWithDataException;
use NewImport\Persistence\MapperInterface;

class CategoryImport extends Model implements ModelInterface {
    private $id;  /// import id
    private $image;
    private $parentId;
    private $top;
    private $column;
    private $sortOrder;
    private $status;
    private $dateAdded;
    private $dateModified;

    private $name;
    private $description;
    private $metaTitle;
    private $metaH1;
    private $metaDescription;
    private $metaKeyword;

    private $languageId;

    const MODEL_2DATA_SCHEMA = [
        'category_id'       => 'id',
        'image'             => 'image',
        'parent_id'         => 'parentId',
        'top'               => 'top',
        'column'            => 'column',
        'sort_order'        => 'sortOrder',
        'status'            => 'status',
        'date_added'        => 'dateAdded',
        'date_modified'     => 'dateModified',

        'name'              => 'name',
        'description'       => 'description',
        'meta_title'        => 'metaTitle',
        'meta_h1'           => 'metaH1',
        'meta_description'  => 'metaDescription',
        'meta_keyword'      => 'metaKeyword',

        'language_id'       => 'languageId',

    ];

    private $modelToReadedDataSchema = array();

    private $otherStoreLanguages = array();

    private $data2Update = [];
    private $categoryDefaultFields2Update = ['image', 'parent_id', 'top', 'column', 'sort_order', 'status', 'date_added', 'date_modified'];
    private $category_descriptionDefaultFields2Update = ['language_id', 'name', 'description', 'meta_title', 'meta_h1', 'meta_description', 'meta_keyword'];

    const ROUTE = "category_id=";

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

    public function __construct(
        LoggerInterface $logger,
        MapperInterface $mapper,
        $store_id = 0,
        $layout_id = 0
    ) {
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
            'fields' => [
                    'category_id'       => 0,
                    'image'             => '',
                    'parent_id'         => 0,
                    'top'               => 0,
                    'column'            => 1,
                    'sort_order'        => 0,
                    'status'            => 1,
                    'date_added'        => date("Y-m-d H-i-s"),
                    'date_modified'     => '',

                    'name'              => '',
                    'description'       => '',
                    'meta_title'        => '',
                    'meta_h1'           => '',
                    'meta_description'  => '',
                    'meta_keyword'      => ''
                ],
            'language_id' => 0,
            'other_store_languages' => '',
            'data2update' => ['name']

        ]);
    }

    public function setDataSettings(array $settings)
    {
        try {
            if (!isset($settings['fields'])) {
                throw new MapFieldsWithDataException('You must to map Category fields with your data headers!!!');
            } else {
                $this->mapFieldsWithReadedData($settings['fields']);
            }
            if (!isset($settings['language_id'])) {
                throw new ImportException('You must to set imported data language id from your OpenCart store!');
            } else {
                $this->{$this::MODEL_2DATA_SCHEMA['language_id']} = $settings['language_id'];
            }
            if (!isset($settings['other_store_languages'])) {
                throw new ImportException('You must to set at least one addition language for your store in Category instance!<br />If your store has only one language, set field \'other_store_languages\' to false!');
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
                echo "<br />There is required format for category settings:<br />";
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
//        $this->settingObject->prepareTable("category");
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
//                $this->settingObject->clearHash($this->reader->getFileName());
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
        if ($category_id = $this->categoryExist()) {
            $this->updateData($category_id);
        } else {
            $this->insertData();
        }
    }

    private function insertData() {
        $sql  = "INSERT INTO " . DB_PREFIX . "category SET";
        if (!empty($this->image)) {
            $sql .= " `image` = 'catalog/category/" . $this->image . "',";
        } else {
            $sql .= " `image` = " . $this->image . "',";
        }
        $sql .= " `parent_id` = '" . (int)$this->getCategoryIdBySystemId($this->parentId) . "',";
        $sql .= " `top` = '" . $this->top . "',";
        $sql .= " `column` = '" . $this->column . "',";
        $sql .= " `sort_order` = '" . $this->sortOrder . "',";
        $sql .= " `status` = '" . $this->status . "',";
        $sql .= " `date_added` = '" . $this->dateAdded . "',";
        $sql .= " `date_modified` = '" . $this->dateModified . "';";
//        $sql .= " `1s_id` = '" . $this->id . "';";
        $this->db->query($sql);

        $category_id = $this->db->getLastId();

        $sql  = "INSERT INTO " . DB_PREFIX . "category_description SET";
        $sql .= " `category_id` = '" . $category_id . "',";
        $sql .= " `language_id` = '" . $this->languageId . "',";
        $sql .= " `name` = '" . $this->name . "',";
        $sql .= " `description` = '" . $this->description . "',";
        $sql .= " `meta_title` = '" . $this->metaTitle . "',";
        $sql .= " `meta_h1` = '" . $this->metaH1 . "',";
        $sql .= " `meta_description` = '" . $this->metaDescription . "',";
        $sql .= " `meta_keyword` = '" . $this->metaKeyword . "';";
        $this->db->query($sql);

        $this->setUrlAlias($category_id, $this->name);
        $this->setCategoryPath($category_id, $this->getCategoryIdBySystemId($this->parentId));
        $this->setCategoryStore($category_id);
        $this->setCategoryLayout($category_id);

        if ($this->otherStoreLanguages !== false) {
            foreach ($this->otherStoreLanguages as $language_id) {
                $sql  = "INSERT INTO " . DB_PREFIX . "category_description SET";
                $sql .= " `category_id` = '" . $category_id . "',";
                $sql .= " `language_id` = '" . $language_id . "',";
                $sql .= " `name` = '" . $this->name . "',";
                $sql .= " `description` = '" . $this->description . "',";
                $sql .= " `meta_title` = '" . $this->metaTitle . "',";
                $sql .= " `meta_h1` = '" . $this->metaH1 . "',";
                $sql .= " `meta_description` = '" . $this->metaDescription . "',";
                $sql .= " `meta_keyword` = '" . $this->metaKeyword . "';";
                $this->db->query($sql);
            }
        }

        $this->mapper->addMatch($this->id, $category_id);
    }

    private function updateData($category_id) {
        if ($fields2Update = $this->isNeedToUpdate('category')) {
            $sql  = "UPDATE " . DB_PREFIX . "category SET";
            foreach ($fields2Update as $field) {
                if ($field == 'parent_id') {
                    $sql .= " `" . $field . "` = '" . $this->getCategoryIdBySystemId($this->{$this::MODEL_2DATA_SCHEMA[$field]}) . "',";
                } else {
                    $sql .= " `" . $field . "` = '" . $this->{$this::MODEL_2DATA_SCHEMA[$field]} . "',";
                }
            }
            if ($sql[strlen($sql) - 1] == ',') {
                $sql = substr($sql, 0, strlen($sql) - 2);
            }
            $sql .= " WHERE `category_id` = '" . (int)$category_id . "';";
            $this->db->query($sql);
        }

        if ($fields2Update = $this->isNeedToUpdate('category_description')) {
            $sql  = "UPDATE " . DB_PREFIX . "category_description SET";
            foreach ($fields2Update as $field) {
                $sql .= " `" . $field . "` = '" . $this->{$this::MODEL_2DATA_SCHEMA[$field]} . "',";
            }
            if ($sql[strlen($sql) - 1] == ',') {
                $sql = substr($sql, 0, strlen($sql) - 1);
            }
            $sql .= " WHERE `category_id` = '" . $category_id . "' AND `language_id` = '" . $this->languageId . "';";
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

    private function categoryExist() {
//        $sql = "SELECT * FROM " . DB_PREFIX . "category WHERE `1s_id` = '" . $this->id . "';";
//        $category = $this->db->select($sql);

        $category_id = $this->mapper->getStoreId($this->id);

        if ($category_id !== false) {
            return $category_id;
        } else {
            return false;
        }
    }

    private function getCategoryIdBySystemId($system_id) {
        $category_id = $this->mapper->getStoreId($system_id);
        if ($category_id !== false) {
            return $category_id;
        } else {
            // TODO: throw an Exception
            return 0;
        }
    }

    private function setUrlAlias($category_id, $name) {
        $sql  = "INSERT INTO " . DB_PREFIX . "url_alias SET `query` = '" . self::ROUTE . $category_id . "',";
        $sql .= " `keyword` = '" . $this->transliterate($name) . "';";
        $this->db->query($sql);
    }

    private function setCategoryPath($category_id, $parent_id) {
        $level = 0;
        $sql = "SELECT * FROM `" . DB_PREFIX . "category_path` WHERE";
        $sql .= " category_id = '" . $parent_id . "' ORDER BY `level` ASC";
        $result[] = $this->db->select($sql);
        foreach ($result as $element) {
            if (!empty($element)) {
                $sql = "INSERT INTO `" . DB_PREFIX . "category_path` SET";
                $sql .= " `category_id` = '" . (int)$category_id . "',";
                $sql .= " `path_id` = '" . (int)$element['path_id'] . "',";
                $sql .= " `level` = '" . (int)$level . "'";
                $this->db->query($sql);
                $level++;
            }
        }
        $sql = "INSERT INTO `" . DB_PREFIX . "category_path` SET";
        $sql .= " `category_id` = '" . (int)$category_id . "',";
        $sql .= " `path_id` = '" . (int)$category_id . "',";
        $sql .= " `level` = '" . (int)$level . "'";
        $this->db->query($sql);
    }

    private function setCategoryStore($category_id) {
        $sql  = "INSERT INTO " . DB_PREFIX . "category_to_store SET";
        $sql .= " `category_id` = '" . (int)$category_id . "',";
        $sql .= " `store_id` = '" . (int)$this->store_id . "';";
        $this->db->query($sql);
    }

    private function setCategoryLayout($category_id) {
        $sql  = "INSERT INTO " . DB_PREFIX . "category_to_layout SET";
        $sql .= " `category_id` = '" . (int)$category_id . "',";
        $sql .= " `store_id` = '" . (int)$this->store_id . "',";
        $sql .= " `layout_id` = '" . (int)$this->layout_id . "';";
        $this->db->query($sql);
    }

    private function transliterate($str)
    {
        $rus = array('/','%','&','?',' ','А', 'Б', 'В', 'Г', 'Д', 'Е', 'Ё', 'Ж', 'З', 'И', 'Й', 'К', 'Л', 'М', 'Н', 'О', 'П', 'Р', 'С', 'Т', 'У', 'Ф', 'Х', 'Ц', 'Ч', 'Ш', 'Щ', 'Ъ', 'Ы', 'Ь', 'Э', 'Ю', 'Я', 'а', 'б', 'в', 'г', 'д', 'е', 'ё', 'ж', 'з', 'и', 'й', 'к', 'л', 'м', 'н', 'о', 'п', 'р', 'с', 'т', 'у', 'ф', 'х', 'ц', 'ч', 'ш', 'щ', 'ъ', 'ы', 'ь', 'э', 'ю', 'я','і','ї','ґ','є','І','Ї','Ґ','Є','!',',','-','"','\'');
        $lat = array('_0','_1','_2','_3','_','A', 'B', 'V', 'G', 'D', 'E', 'E', 'Gh', 'Z', 'I', 'Y', 'K', 'L', 'M', 'N', 'O', 'P', 'R', 'S', 'T', 'U', 'F', 'H', 'C', 'Ch', 'Sh', 'Sch', 'Y', 'Y', 'Y', 'E', 'Yu', 'Ya', 'a', 'b', 'v', 'g', 'd', 'e', 'e', 'gh', 'z', 'i', 'y', 'k', 'l', 'm', 'n', 'o', 'p', 'r', 's', 't', 'u', 'f', 'h', 'c', 'ch', 'sh', 'sch', 'y', 'y', 'y', 'e', 'yu', 'ya','i','yi','gg','ye','I','YI','GG','YE','_','_','_','_','_');
        return str_replace($rus, $lat, $str);
    }
}