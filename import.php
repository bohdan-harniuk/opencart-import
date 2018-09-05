<?php
namespace NewImport;
use NewImport\Logger\Logger;
use NewImport\Model\CategoryImport;
use NewImport\Model\OptionImport;
use NewImport\Model\ProductImport;
use NewImport\Model\Model;
use NewImport\Persistence\MappingAdapter;
use NewImport\Reader\ReaderInterface;

require_once __DIR__ . '/importSettings.php';
require_once __DIR__ . '/persistence/MapperInterface.php';
require_once __DIR__ . '/Logger/LoggerInterface.php';
require_once __DIR__ . '/Logger/logger.php';
require_once __DIR__ . '/persistence/mappingadapter.php';

class Import {

    private $category;
    private $color;
    private $size;
    private $product;
    private $product_qty;

    private $reader;
    private $logger;
    private $mapper;
    private $db;
    private $settings;

    public function __construct(
        ReaderInterface $reader,
        $logger_mode = null
    ) {
        $this->category = BASE_DIR .  CATEGORY_FILENAME;
        $this->color = BASE_DIR . COLOR_FILENAME;
        $this->size = BASE_DIR . SIZE_FILENAME;
        $this->product = BASE_DIR . PRODUCT_FILENAME;
        $this->product_qty = BASE_DIR . PRODUCT_QTY_FILENAME;

        $this->reader = $reader;
        $this->db = new Persistence\DatabaseAdapter(DB_DATABASE, DB_HOSTNAME, DB_USERNAME, DB_PASSWORD);
        $this->settings = new ImportSettings($this->db);
        $this->logger = new Logger();
        if ($logger_mode == null) {
            $logger_mode = 'developer';
        }
        $this->logger
            ->setFilename('new_store_import.log')
            ->setMode($logger_mode);
        $this->mapper = new MappingAdapter(IMPORT_MAP_FOLDER);
    }

    public function startImport()
    {
        $this->logger->output(sprintf("Імпорт запущено %s <br/>", date("Y-m-d H:i:s")));
        /// Category Import
        $category = new CategoryImport($this->logger, $this->mapper);
        $categoryImport = $category
            ->setReader($this->reader)
            ->read($this->category)
            ->setDatabase($this->db)
            ->setDataSettings([
                'fields' => [
                        'category_id' => 'id',
                        'name'        => 'name',
                        'parent_id'   => 'parent_id'
                    ],
                'language_id'     => 3,
                'other_store_languages' => [1],
                'data2update'     => ['name']

            ])
            ->setGeneralSettingObject($this->settings)
            ->importData();
        echo $categoryImport->getImportResult();
        /// Color option value Import
        $option = new OptionImport($this->logger, $this->mapper, 'color');
        $colorImport = $option
            ->setReader($this->reader)
            ->read($this->color)
            ->setDatabase($this->db)
            ->setDataSettings([
                'fields' => [
                    'option_value_id' => 'id',
                    'name'            => 'name'
                ],
                'language_id'     => 3,
                'other_store_languages' => [1],
                'data2update'     => ['name'],
                'option_id'       => 13
            ])
            ->setGeneralSettingObject($this->settings)
            ->importData();
        echo $colorImport->getImportResult();
        /// Size option value Import
        $option = new OptionImport($this->logger, $this->mapper, 'size');
        $sizeImport = $option
            ->setReader($this->reader)
            ->read($this->size)
            ->setDatabase($this->db)
            ->setDataSettings([
                'fields' => [
                    'option_value_id' => 'id',
                    'name'            => 'name'
                ],
                'language_id'     => 3,
                'other_store_languages' => [1],
                'data2update'     => ['name'],
                'option_id'       => 11
            ])
            ->setGeneralSettingObject($this->settings)
            ->importData();
        echo $sizeImport->getImportResult();
        /// Product Import
        $classArray = [];
        $classArray = explode('\\', get_class($category));
        $categoryMappingFileName = $classArray[count($classArray) - 1] . '.json';

        $productImport = new ProductImport($this->logger, $this->mapper);
        $productImport->getDataSettingsFormat();
        $result = $productImport
            ->setReader($this->reader)
            ->read($this->product)
            ->setDatabase($this->db)
            ->setDataSettings([
                'fields' => [
                    'product_id' => 'id',
                    'sku'        => 'sku',
                    'model'      => 'model',
                    'quantity'   => 'qty',
                    'name'       => 'name',
                    'price'      => 'price',
                    'status'     => 'status',

                    'category_id'=> 'category',
                    'additional1'   => 'color_id',
                    'additional2'   => 'size_id',
                    'additional3'   => 'textile',
                    'additional4'   => 'season',
                ],
                'language_id'     => 3,
                'other_store_languages' => [1],
                'data2update'     => ['name', 'quantity', 'price', 'status'],
                'category_mapping_filename' => $categoryMappingFileName
            ])
            ->setInstanceMapType('sku')
            ->setGeneralSettingObject($this->settings)
            ->importData();
        echo $result->getImportResult();
        $this->logger->output(sprintf("Імпорт завершено %s <br/>", date("Y-m-d H:i:s")));
    }
}