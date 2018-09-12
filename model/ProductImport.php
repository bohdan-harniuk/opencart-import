<?php

namespace NewImport\Model;

use NewImport\ImportSettings;
use NewImport\Logger\LoggerInterface;
use NewImport\Model\Exceptions\ImportException;
use NewImport\Model\Exceptions\MapFieldsWithDataException;
use NewImport\Persistence\MapperInterface;

/**
 * TODO :: Fill all additional tables and add new data inserting !!!
 */

class ProductImport extends Model implements ModelInterface {
    private $productId;  /// import id
    private $model;
    private $sku;
    private $upc;
    private $ean;
    private $jan;
    private $isbn;
    private $mpn;
    private $location;
    private $quantity;
    private $stockStatusId;
    private $image;
    private $manufacturerId;
    private $shipping;
    private $price;
    private $points;
    private $taxClassId;
    private $dateAvailable;
    private $weight;
    private $weightClassId;
    private $length;
    private $width;
    private $height;
    private $lengthClassId;
    private $subtract;
    private $minimum;
    private $sortOrder;
    private $status;
    private $viewed;
    private $dateAdded;
    private $dateModified;

    private $languageId;
    private $name;
    private $description;
    private $tag;
    private $metaTitle;
    private $metaH1;
    private $metaDescription;
    private $metaKeyword;

    private $categoryId;
    private $categoryMappingFilename = null;

    // This is temporary stubs
    private $additional1;
    private $additional2;
    private $additional3;
    private $additional4;

    private $modelToReadedDataSchema = array();

    private $otherStoreLanguages = array();

    private $data2Update = [];
    private $productDefaultFields2Update = ['model', 'sku', 'quantity', 'stock_status_id', 'image', 'manufacturer_id', 'price', 'weight', 'length', 'width', 'height', 'minimum', 'sort_order', 'status'];
    private $product_descriptionDefaultFields2Update = ['language_id', 'name', 'description', 'meta_title', 'meta_h1', 'meta_description', 'meta_keyword'];

    const ROUTE = "product_id=";

    protected $settingObject;
    private $store_id;
    private $layout_id;
    /**
     * @var \NewImport\Persistence\MapperInterface $mapper
     */
    private $mapper;
    /**
     * @var LoggerInterface $logger
     */
    private $logger;

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
                'product_id'        => 0,
                'model'             => '',
                'sku'               => '',
                'upc'               => '',
                'ean'               => '',
                'jan'               => '',
                'isbn'              => '',
                'mpn'               => '',
                'location'          => '',
                'quantity'          => 0,
                'stock_status_id'   => 0,  // required
                'image'             => 'placeholder.png',
                'manufacturer_id'   => 0,
                'shipping'          => 1,
                'price'             => 0.0,
                'points'            => 0,
                'tax_class_id'      => 0,
                'date_available'    => '',
                'weight'            => 1.0,
                'weight_class_id'   => 1,
                'length'            => 0.00,
                'width'             => 0.00,
                'height'            => 0.00,
                'length_class_id'   => 1,
                'subtract'          => 1,
                'minimum'           => 1,
                'sort_order'        => 0,
                'status'            => 1,
                'viewed'            => 0,
                'date_added'        => date("Y-m-d H-i-s"),
                'date_modified'     => '',

                'name'              => '',
                'description'       => '',
                'tag'               => '',
                'meta_title'        => '',
                'meta_h1'           => '',
                'meta_description'  => '',
                'meta_keyword'      => ''
            ],
            'language_id' => 0,
            'other_store_languages' => '',
            'data2update' => ['name', 'model', 'sku', 'quantity', 'status', 'price'],
            'category_mapping_filename' => '',
        ]);
    }

    private function convertData2ModelField($field) {
        $modelField = '';
        $parts = explode('_', $field);
        foreach ($parts as $part) {
            $modelField .= ucfirst($part);
        }
        return lcfirst($modelField);
    }

    public function setDataSettings(array $settings)
    {
        try {
            if (!isset($settings['fields'])) {
                throw new MapFieldsWithDataException('You must to map Product fields with your data headers!!!');
            } else {
                $this->mapFieldsWithReadedData($settings['fields']);
            }
            if (!isset($settings['language_id'])) {
                throw new ImportException('You must to set imported data language id from your OpenCart store!');
            } else {
                $this->{$this->convertData2ModelField('language_id')} = $settings['language_id'];
            }
            if (!isset($settings['other_store_languages'])) {
                throw new ImportException('You must to set at least one addition language for your store in "Product" instance!<br />If your store has only one language, set field \'other_store_languages\' to false!');
            } else {
                if (!is_array($settings['other_store_languages']) && $settings['other_store_languages'] !== false) {
                    throw new ImportException('Field \'other_store_languages\' has to be set as Array instance or Boolean\'s False!');
                } else {
                    $this->otherStoreLanguages = $settings['other_store_languages'];
                }
            }
            if (!isset($settings['data2update'])) {
                throw new ImportException('You must to set data to updating with import for "Product" instance!');
            } else {
                if (!is_array($settings['data2update'])) {
                    throw new ImportException('Data to updating in "Product" instance have to be an Array instance!');
                } else {
                    $this->data2Update = $settings['data2update'];
                }
            }
            if (isset($settings['category_mapping_filename'])) {
                $this->{$this->convertData2ModelField('category_mapping_filename')} = $settings['category_mapping_filename'];
            }
        } catch (\Exception $exception) {
            if ($exception instanceof MapFieldsWithDataException || $exception instanceof ImportException) {
                echo $exception->getMessage();
                echo "<br />There is required format for \"Product\" instance settings:<br />";
                var_dump($this->getDataSettingsFormat());
                echo "<br />You must to fill at least one value for each key!<br />";
                exit();
            }
        }
        return parent::setDataSettings($settings);
    }

    private function mapFieldsWithReadedData($fields) {
        foreach ($this->getDataSettingsFormat()['fields'] as $field => $defaultValue) {
            $this->{$this->convertData2ModelField($field)} = $defaultValue;
        }
        foreach ($fields as $field => $dataField) {
            $this->modelToReadedDataSchema[$dataField] = $field;
            
        }
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

    private $isNeedToEscape = ['name', 'description', 'tag', 'meta_title', 'meta_h1', 'meta_description', 'meta_keyword'];

    private function importRow($row_fields) {
        foreach ($row_fields as $field => $readedValue) {
            $value = $readedValue;
            if (in_array($field, $this->isNeedToEscape)) {
                $value = $this->db->escape(htmlspecialchars($readedValue, ENT_QUOTES));
            }
            if (key_exists($field, $this->modelToReadedDataSchema)) {
                $field = $this->convertData2ModelField($this->modelToReadedDataSchema[$field]);
                if (property_exists($this, $field)) {
                    $this->{$field} = $value;
                }
            }
        }
        if ($product_id = $this->productExist()) {
            $this->updateData($product_id);
        } else {
            $this->insertData();
        }
    }
    
    private $modelAutoIncrement = 0;

    private function insertData() {
        $sql  = "INSERT INTO " . DB_PREFIX . "product SET";
        $sql .= " `model` = 'M" . $this->getId() . "',";
        $sql .= " `sku` = '" . $this->sku . "',";
        $sql .= " `upc` = '" . $this->upc . "',";
        $sql .= " `ean` = '" . $this->ean . "',";
        $sql .= " `jan` = '" . $this->jan . "',";
        $sql .= " `isbn` = '" . $this->isbn . "',";
        $sql .= " `mpn` = '" . $this->mpn . "',";
        $sql .= " `location` = '" . $this->location . "',";
        $sql .= " `quantity` = '" . (int)$this->quantity . "',";
        $sql .= " `stock_status_id` = '" . (int)$this->stockStatusId . "',";
        $sql .= " `image` = 'catalog/products/" . $this->image . "',";
        $sql .= " `manufacturer_id` = '" . (int)$this->manufacturerId . "',";
        $sql .= " `shipping` = '" . (int)$this->shipping . "',";
        $sql .= " `price` = '" . (float)$this->price . "',";
        $sql .= " `points` = '" . (int)$this->points . "',";
        $sql .= " `tax_class_id` = '" . (int)$this->taxClassId . "',";
        $sql .= " `date_available` = '" . $this->dateAvailable . "',";
        $sql .= " `weight` = '" . (float)$this->weight . "',";
        $sql .= " `weight_class_id` = '" . (int)$this->weightClassId . "',";
        $sql .= " `length` = '" . (float)$this->length . "',";
        $sql .= " `width` = '" . (float)$this->width . "',";
        $sql .= " `height` = '" . (float)$this->height . "',";
        $sql .= " `length_class_id` = '" . (int)$this->lengthClassId . "',";
        $sql .= " `subtract` = '" . (int)$this->subtract . "',";
        $sql .= " `minimum` = '" . (int)$this->minimum . "',";
        $sql .= " `sort_order` = '" . (int)$this->sortOrder . "',";
        $sql .= " `status` = '" . (int)$this->status . "',";
        $sql .= " `viewed` = '" . (int)$this->viewed . "',";
        $sql .= " `date_added` = '" . $this->dateAdded . "',";
        $sql .= " `date_modified` = '" . $this->dateModified . "';";
        $this->db->query($sql);

        $product_id = $this->db->getLastId();

        $sql  = "INSERT INTO " . DB_PREFIX . "product_description SET";
        $sql .= " `product_id` = '" . (int)$product_id . "',";
        $sql .= " `language_id` = '" . (int)$this->languageId . "',";
        $sql .= " `name` = '" . $this->name . "',";
        $sql .= " `description` = '" . $this->description . "',";
        $sql .= " `tag` = '" . $this->tag . "',";
        $sql .= " `meta_title` = '" . $this->metaTitle . "',";
        $sql .= " `meta_h1` = '" . $this->metaH1 . "',";
        $sql .= " `meta_description` = '" . $this->metaDescription . "',";
        $sql .= " `meta_keyword` = '" . $this->metaKeyword . "';";
        $this->db->query($sql);

        $this->setUrlAlias($product_id, $this->name);
        $this->setProductStore($product_id);
        $this->setProductLayout($product_id);
        $this->setProductMainCategory($product_id, $this->categoryId);

        if ($this->otherStoreLanguages !== false) {
            foreach ($this->otherStoreLanguages as $language_id) {
                $sql  = "INSERT INTO " . DB_PREFIX . "product_description SET";
                $sql .= " `product_id` = '" . (int)$product_id . "',";
                $sql .= " `language_id` = '" . (int)$language_id . "',";
                $sql .= " `name` = '" . $this->name . "',";
                $sql .= " `description` = '" . $this->description . "',";
                $sql .= " `tag` = '" . $this->tag . "',";
                $sql .= " `meta_title` = '" . $this->metaTitle . "',";
                $sql .= " `meta_h1` = '" . $this->metaH1 . "',";
                $sql .= " `meta_description` = '" . $this->metaDescription . "',";
                $sql .= " `meta_keyword` = '" . $this->metaKeyword . "';";
                $this->db->query($sql);
            }
        }

        if ($this->getInstanceMapType() == 'system_id') {
            $this->mapper->addMatch($this->getId(), $product_id);

        } else {
            $identificator = $this->{$this->convertData2ModelField($this->getInstanceMapType())};
            $this->mapper->addMatch($identificator, $product_id);
        }
    }

    private function updateData($product_id) {
        if ($fields2Update = $this->isNeedToUpdate('product')) {
            $sql  = "UPDATE " . DB_PREFIX . "product SET";
            foreach ($fields2Update as $field) {
                $sql .= " `" . $field . "` = '" . $this->{$this->convertData2ModelField($field)} . "',";
            }
            if ($sql[strlen($sql) - 1] == ',') {
                $sql = substr($sql, 0, strlen($sql) - 1);
            }
            $sql .= " WHERE `product_id` = '" . $product_id . "';";
            $this->db->query($sql);
        }

        if ($fields2Update = $this->isNeedToUpdate('product_description')) {
            $sql  = "UPDATE " . DB_PREFIX . "product_description SET";
            foreach ($fields2Update as $field) {
                $sql .= " `" . $field . "` = '" . $this->{$this->convertData2ModelField($field)} . "',";
            }
            if ($sql[strlen($sql) - 1] == ',') {
                $sql = substr($sql, 0, strlen($sql) - 1);
            }
            $sql .= " WHERE `product_id` = '" . $product_id . "' AND `language_id` = '" . $this->languageId . "';";
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

    private function productExist() {
        if ($this->getInstanceMapType() == 'system_id') {
            $identificator = $this->getId();
        } else {
            $identificator = $this->{$this->convertData2ModelField($this->getInstanceMapType())};
        }
        $product_id = $this->mapper->getStoreId($identificator);
        if ($product_id) {
            return $product_id;
        } else {
            return false;
        }
    }

    private function getCategoryIdBySystemId($mappingFilename, $system_id) {
        $category_id = $this->mapper->getStoreIdFromInstance($mappingFilename, $system_id);
        if ($category_id !== false) {
            return $category_id;
        } else {
            // TODO: throw an Exception
            return false;
        }
    }

    private function setProductMainCategory($product_id, $systemCategoryId)
    {
        if ($this->categoryMappingFilename !== null) {
            $category_id = $this->getCategoryIdBySystemId($this->categoryMappingFilename, $systemCategoryId);
            $sql  = "INSERT INTO " . DB_PREFIX . "product_to_category SET";
            $sql .= " `product_id` = '" . (int)$product_id . "',";
            $sql .= " `category_id` = '" . (int)$category_id . "',";
            $sql .= " `main_category` = '1';";
            $this->db->query($sql);
        } else {
            // TODO: throw an Exception
        }
    }


    public function setGeneralSettingObject(ImportSettings $object) {
        $this->settingObject = $object;
//        if ($this->getInstanceMapType() == '1s_id') {
//            $this->settingObject->prepareTable("product");
//        }
        $this->settingObject->initialHashSetting($this->reader->getFileName());
        return $this;
    }

    private function setUrlAlias($product_id, $name) {
        $sql  = "INSERT INTO " . DB_PREFIX . "url_alias SET `query` = '" . self::ROUTE . $product_id . "',";
        $sql .= " `keyword` = '" . $this->transliterate($name) . "';";
        $this->db->query($sql);
    }

    private function setProductStore($product_id) {
        $sql  = "INSERT INTO " . DB_PREFIX . "product_to_store SET";
        $sql .= " `product_id` = '" . (int)$product_id . "',";
        $sql .= " `store_id` = '" . (int)$this->store_id . "';";
        $this->db->query($sql);
    }

    private function setProductLayout($product_id) {
        $sql  = "INSERT INTO " . DB_PREFIX . "product_to_layout SET";
        $sql .= " `product_id` = '" . (int)$product_id . "',";
        $sql .= " `store_id` = '" . (int)$this->store_id . "',";
        $sql .= " `layout_id` = '" . (int)$this->layout_id . "';";
        $this->db->query($sql);
    }

    private function transliterate($str)
    {
        $rus = array('quot;', '/','%','&','?',' ','А', 'Б', 'В', 'Г', 'Д', 'Е', 'Ё', 'Ж', 'З', 'И', 'Й', 'К', 'Л', 'М', 'Н', 'О', 'П', 'Р', 'С', 'Т', 'У', 'Ф', 'Х', 'Ц', 'Ч', 'Ш', 'Щ', 'Ъ', 'Ы', 'Ь', 'Э', 'Ю', 'Я', 'а', 'б', 'в', 'г', 'д', 'е', 'ё', 'ж', 'з', 'и', 'й', 'к', 'л', 'м', 'н', 'о', 'п', 'р', 'с', 'т', 'у', 'ф', 'х', 'ц', 'ч', 'ш', 'щ', 'ъ', 'ы', 'ь', 'э', 'ю', 'я','і','ї','ґ','є','І','Ї','Ґ','Є','!',',','-','"','\'');
        $lat = array('_', '_0','_1','_2','_3','_','A', 'B', 'V', 'G', 'D', 'E', 'E', 'Gh', 'Z', 'I', 'Y', 'K', 'L', 'M', 'N', 'O', 'P', 'R', 'S', 'T', 'U', 'F', 'H', 'C', 'Ch', 'Sh', 'Sch', 'Y', 'Y', 'Y', 'E', 'Yu', 'Ya', 'a', 'b', 'v', 'g', 'd', 'e', 'e', 'gh', 'z', 'i', 'y', 'k', 'l', 'm', 'n', 'o', 'p', 'r', 's', 't', 'u', 'f', 'h', 'c', 'ch', 'sh', 'sch', 'y', 'y', 'y', 'e', 'yu', 'ya','i','yi','gg','ye','I','YI','GG','YE','_','_','_','_','_');
        return str_replace($rus, $lat, $str);
    }
    
    public function getId()
    {
        return $this->productId;
    }

}