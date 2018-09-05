<?php
namespace NewImport\Persistence;

class DatabaseAdapter
{
    private $connection;
    private static $dbName;
    private static $host;
    private static $username;
    private static $password;

    public function __construct($dbName, $host, $username, $password)
    {
        self::$dbName = $dbName;
        self::$host = $host;
        self::$username = $username;
        self::$password = $password;

        $this->connection = new \PDO("mysql:dbname=$dbName;host=$host", $username, $password,
            array(\PDO::MYSQL_ATTR_INIT_COMMAND=>'SET NAMES UTF8'));
        $this->connection->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
    }

    public function clear($table) {
        $query = $this->connection->prepare("DELETE FROM " . $table . ";");
        $query->execute();
        $query = $this->connection->prepare("ALTER TABLE " . $table . " AUTO_INCREMENT = 1;");
        $query->execute();
    }

    public function select($sql) {
        $query = $this->connection->prepare($sql);
        $query->execute();
        $result = $query->fetchAll(\PDO::FETCH_ASSOC);
        if (is_array($result) && count($result) == 1) {
            return array_shift($result);
        }
        return $result;
    }

    public function query($sql) {
        $query = $this->connection->prepare($sql);
        $query->execute();
        if ($query->rowCount()){
            return true;
        } else{
            return false;
        }
    }

    public function getLastId() {
        return $this->connection->lastInsertId();
    }

    public function escape($value) {
//        return str_replace(array("\\", "\0", "\n", "\r", "\x1a", "'", '"'), array("\\\\", "\\0", "\\n", "\\r", "\Z", "\'", '\"'), $value);
        return addslashes($value);
    }
}