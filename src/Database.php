<?php

namespace TSM;

use \PDO;
use \PDOException;

class Database
{
    /**
     * Host for database connection
     * @var string
     */
    protected static $host;

    /**
     * Database name
     * @var string
     */
    protected static $name;

    /**
     * Database user
     * @var string
     */
    protected static $user;

    /**
     * Database password
     * @var string
     */
    protected static $pass;

    /**
     * Database port
     * @var int
     */
    protected static $port;

    /**
     * Name of the table to be manipulated
     * @var string
     */
    private $table;

    /**
     * Database connection instance
     * @var PDO
     */
    private $connection;

    /**
     * Configure the class with database connection details
     * @param string  $host
     * @param string  $name
     * @param string  $user
     * @param string  $pass
     * @param integer $port
     */
    public static function config($host, $name, $user, $pass, $port = 3306)
    {
        self::$host = $host;
        self::$name = $name;
        self::$user = $user;
        self::$pass = $pass;
        self::$port = $port;
    }

    /**
     * Initializes the table and sets up the database connection
     * @param string $table
     */
    public function __construct($table = null)
    {
        $this->table = $table;
        $this->setConnection();
    }

    /**
     * Sets up the database connection
     */
    private function setConnection()
    {
        try {
            $dsn = 'mysql:host=' . self::$host . ';dbname=' . self::$name . ';port=' . self::$port;
            $this->connection = new PDO($dsn, self::$user, self::$pass);
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            throw new PDOException('ERROR: ' . $e->getMessage());
        }
    }

    /**
     * Executes a database query
     * @param  string $query
     * @param  array  $params
     * @return PDOStatement
     */
    public function execute($query, $params = [])
    {
        try {
            $statement = $this->connection->prepare($query);
            $statement->execute($params);
            return $statement;
        } catch (PDOException $e) {
            throw new PDOException('ERROR: ' . $e->getMessage());
        }
    }

    /**
     * Inserts data into the database
     * @param  array $values [ field => value ]
     * @return integer       Inserted ID
     */
    public function insert($values)
    {
        $fields = array_keys($values);
        $binds = array_pad([], count($fields), '?');
        $query = 'INSERT INTO ' . $this->table . ' (' . implode(',', $fields) . ') VALUES (' . implode(',', $binds) . ')';
        $this->execute($query, array_values($values));
        return $this->connection->lastInsertId();
    }

    /**
     * Performs a select query on the database
     * @param  string $where
     * @param  string $order
     * @param  string $limit
     * @param  string $fields
     * @return PDOStatement
     */
    public function select($where = null, $order = null, $limit = null, $fields = '*')
    {
        $where = strlen($where) ? 'WHERE ' . $where : '';
        $order = strlen($order) ? 'ORDER BY ' . $order : '';
        $limit = strlen($limit) ? 'LIMIT ' . $limit : '';
        $query = 'SELECT ' . $fields . ' FROM ' . $this->table . ' ' . $where . ' ' . $order . ' ' . $limit;
        return $this->execute($query);
    }

    /**
     * Performs an update query on the database
     * @param  string $where
     * @param  array  $values [ field => value ]
     * @return boolean
     */
    public function update($where, $values)
    {
        $fields = array_keys($values);
        $query = 'UPDATE ' . $this->table . ' SET ' . implode('=?,', $fields) . '=? WHERE ' . $where;
        $this->execute($query, array_values($values));
        return true;
    }

    /**
     * Performs a delete query on the database
     * @param  string $where
     * @return boolean
     */
    public function delete($where)
    {
        $query = 'DELETE FROM ' . $this->table . ' WHERE ' . $where;
        $this->execute($query);
        return true;
    }

    /**
     * Find a record by ID
     * @param int $id
     * @param string $fields
     * @return mixed|null
     */
    public function find($id, $fields = '*')
    {
        $query = 'SELECT ' . $fields . ' FROM ' . $this->table . ' WHERE id = :id';
        $statement = $this->execute($query, ['id' => $id]);
        return $statement->fetch(PDO::FETCH_ASSOC) ?: null;
    }
}
