<?php

require_once 'reader/ReaderInterface.php';
require_once 'model/Exceptions/ImportException.php';
require_once 'model/Exceptions/MapFieldsWithDataException.php';
require_once 'model/ModelInterface.php';
require_once 'model/Model.php';
require_once 'model/BrandImport.php';
require_once 'model/CategoryImport.php';
require_once 'model/OptionImport.php';
require_once 'model/ProductImport.php';

if (!defined('HTTP_SERVER')) require_once __DIR__ . '/../../../config.php';

define('BASE_DIR', DIR_APPLICATION . '/../import_files/');
define('IMPORT_MAP_FOLDER', __DIR__ . '/storage/');

define('BRAND_FILENAME', 'brand.csv');
define('CATEGORY_FILENAME', 'category.csv');
define('COLOR_FILENAME', 'color.csv');
define('SIZE_FILENAME', 'size.csv');
define('PRODUCT_FILENAME', 'product.csv');
define('PRODUCT_QTY_FILENAME', 'product_qty.csv');
