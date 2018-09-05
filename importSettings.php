<?php
namespace NewImport;
use NewImport\Persistence\DatabaseAdapter;

require_once __DIR__ . '/settings.php';

class ImportSettings {
    private $db;
    public function __construct(DatabaseAdapter $db)
    {
        $this->db = $db;
        $this->install();
    }

    public function isChanged($file) {
        $hash = hash_file('md5', $file);
        $sql = "SELECT * FROM " . DB_PREFIX . "new_import_settings WHERE";
        $sql .= " `code` = 'file_hash'";
        $sql .= " AND `key` = '" . basename($file, '.csv') . "';";
        $file_hash = $this->db->select($sql);
        if ($hash === $file_hash['value']) {
            return false;
        } else {
            return true;
        }
    }

    public function initialHashSetting($file) {
        $sql = "SELECT * FROM " . DB_PREFIX . "new_import_settings WHERE";
        $sql .= " `code` = 'file_hash'";
        $sql .= " AND `key` = '" . basename($file, '.csv') . "';";
        $previous = $this->db->select($sql);

        if (empty($previous)) {
            $sql = "INSERT INTO " . DB_PREFIX . "new_import_settings";
            $sql .= " SET `code` = 'file_hash',";
            $sql .= " `key` = '" . basename($file, '.csv') . "',";
            $sql .= " `value` = '';";
            $this->db->query($sql);
        }
    }

    public function hashFile($file) {
        $hash = hash_file('md5', $file);

        $sql = "UPDATE " . DB_PREFIX . "new_import_settings";
        $sql .= " SET `value` = '" . $hash . "' WHERE";
        $sql .= " `code` = 'file_hash'";
        $sql .= " AND `key` = '" . basename($file, '.csv') . "';";
        $this->db->query($sql);
    }

    public function clearHash($file) {
        $sql = "UPDATE " . DB_PREFIX . "new_import_settings";
        $sql .= " SET `value` = '' WHERE";
        $sql .= " `code` = 'file_hash'";
        $sql .= " AND `key` = '" . basename($file, '.csv') . "';";
        $this->db->query($sql);
    }

    private function install() {
        $query = "CREATE TABLE IF NOT EXISTS `"  . DB_PREFIX . "new_import_settings`(";
        $query .= "`setting_id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,";
        $query .= "`code` VARCHAR(50) NOT NULL DEFAULT '',";
        $query .= "`key` VARCHAR(50) NOT NULL DEFAULT '',";
        $query .= "`value` VARCHAR(255) NOT NULL DEFAULT '',";
        $query .= "PRIMARY KEY (`setting_id`)) DEFAULT CHARSET=utf8;";
        $this->db->query($query);
    }

    public function prepareTable($table) {
        $sql = "SELECT * FROM " . DB_PREFIX . "new_import_settings WHERE";
        $sql .= " `code` = 'modified_tables'";
        $sql .= " AND `key` = '" . $table . "';";
        $option_modified = $this->db->select($sql);

        $table_fields = $this->getTableFields($table);
        $new_table_fields = [];
        foreach ($table_fields as $field) {
            $new_table_fields[] = $field['COLUMN_NAME'];
        }

        if (empty($option_modified) && !in_array('1s_id', $new_table_fields)) {
            $sql = "ALTER TABLE " . DB_PREFIX . $table . " ADD 1s_id VARCHAR(11) NOT NULL DEFAULT '';";
            $this->db->query($sql);

            $sql = "INSERT INTO " . DB_PREFIX . "new_import_settings";
            $sql .= " SET `code` = 'modified_tables',";
            $sql .= " `key` = '" . $table . "',";
            $sql .= " `value` = '1';";
            $this->db->query($sql);
        }
    }

    private function getTableFields($table_name) {
        $sql = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS";
        $sql .= " WHERE TABLE_SCHEMA = '" . DB_DATABASE . "' AND TABLE_NAME = '" . DB_PREFIX . $table_name . "';";
        return $this->db->select($sql);
    }

    public function uninstall() {
        $this->db->query("DROP TABLE IF EXISTS `"  . DB_PREFIX . "new_import_settings`;");
    }
}