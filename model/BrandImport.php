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
    private $sordOrder;

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
                'meta_keyword'      => '',
            ],
            'language_id'   => 0,
            'other_store_languages' => '',
            'data2update'   => ['name']
        ]);
    }
}
