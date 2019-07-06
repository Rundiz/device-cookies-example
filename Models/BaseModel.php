<?php
/**
 * Base model
 *
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Models;


abstract class BaseModel
{


    /**
     * @var \PDO
     */
    protected $Dbh;


    /**
     * @var \PDOStatement
     */
    protected $Sth;


    /**
     * Class constructor.
     */
    public function __construct(\PDO $dbh)
    {
        $this->Dbh = $dbh;
    }// __construct


    /**
     * Delete data from DB table.
     * 
     * @link https://github.com/doctrine/dbal/blob/master/lib/Doctrine/DBAL/Connection.php#L643 Source code copied from here.
     * @param string $tableName The table name. This table name will NOT auto add prefix. The table name will be auto wrap with back-tick (`...`).
     * @param array $identifier The identifier for use in `WHERE` statement. It is associative array where column name is the key and its value is the value pairs.
     * @return bool Return PDOStatement::execute(). Return `true` on success, `false` for otherwise.
     * @throws \InvalidArgumentException Throw the error if `$identifier` is incorrect value.
     */
    public function delete(string $tableName, array $identifier)
    {
        if (empty($identifier)) {
            throw new \InvalidArgumentException('The argument $identifier is required associative array column - value pairs.');
        }

        $columns = [];
        $placeholders = [];
        $values = [];
        $conditions = [];

        foreach ($identifier as $columnName => $value) {
            $columns[] = '`' . $columnName . '`';
            $conditions[] = '`' . $columnName . '` = ?';
            $values[] = $value;
        }// endforeach;
        unset($columnName, $value);

        $sql = 'DELETE FROM `' . $tableName . '` WHERE ' . implode(' AND ', $conditions);
        $this->Sth = $this->Dbh->prepare($sql);
        unset($columns, $placeholders, $sql);

        return $this->Sth->execute($values);
    }// delete


    /**
     * Insert data into DB table.
     * 
     * @link https://github.com/doctrine/dbal/blob/master/lib/Doctrine/DBAL/Connection.php#L749 Source code copied from here.
     * @param string $tableName The table name. This table name will NOT auto add prefix. The table name will be auto wrap with back-tick (`...`).
     * @param array $data The associative array where column name is the key and its value is the value pairs. The column name will be auto wrap with back-tick (`...`).
     * @return bool Return PDOStatement::execute(). Return `true` on success, `false` for otherwise.
     * @throws \InvalidArgumentException Throw the error if `$data` is invalid.
     */
    public function insert(string $tableName, array $data): bool
    {
        if (empty($data)) {
            throw new \InvalidArgumentException('The argument $data is required associative array column - value pairs.');
        }

        $columns = [];
        $placeholders = [];
        $values = [];

        foreach ($data as $columnName => $value) {
            $columns[] = '`' . $columnName . '`';
            $placeholders[] = '?';
            $values[] = $value;
        }// endforeach;
        unset($columnName, $value);

        $sql = 'INSERT INTO `' . $tableName . '` (' . implode(', ', $columns) . ') VALUES (' . implode(', ', $placeholders) . ')';
        $this->Sth = $this->Dbh->prepare($sql);
        unset($columns, $placeholders, $sql);

        return $this->Sth->execute($values);
    }// insert


    /**
     * Get PDO statement after called `insert()`, `update()`, `delete()`.
     * 
     * @return \PDOStatement|null Return `\PDOStatement` object if exists, `null` if not exists.
     */
    public function PDOStatement()
    {
        return $this->Sth;
    }// PDOStatement


    /**
     * Update data into DB table.
     * 
     * @link https://github.com/doctrine/dbal/blob/master/lib/Doctrine/DBAL/Connection.php#L714 Source code copied from here.
     * @param string $tableName The table name. This table name will NOT auto add prefix. The table name will be auto wrap with back-tick (`...`).
     * @param array $data The associative array where column name is the key and its value is the value pairs. The column name will be auto wrap with back-tick (`...`).
     * @param array $identifier The identifier for use in `WHERE` statement. It is associative array where column name is the key and its value is the value pairs.
     * @return bool Return PDOStatement::execute(). Return `true` on success, `false` for otherwise.
     * @throws \InvalidArgumentException Throw the error if `$data` or `$identifier` is incorrect value.
     */
    public function update(string $tableName, array $data, array $identifier): bool
    {
        if (empty($data)) {
            throw new \InvalidArgumentException('The argument $data is required associative array column - value pairs.');
        }

        if (empty($identifier)) {
            throw new \InvalidArgumentException('The argument $identifier is required associative array column - value pairs.');
        }

        $columns = [];
        $placeholders = [];
        $values = [];
        $conditions = [];

        foreach ($data as $columnName => $value) {
            $columns[] = '`' . $columnName . '`';
            $placeholders[] = '`' . $columnName . '` = ?';
            $values[] = $value;
        }// endforeach;
        unset($columnName, $value);

        foreach ($identifier as $columnName => $value) {
            $columns[] = '`' . $columnName . '`';
            $conditions[] = '`' . $columnName . '` = ?';
            $values[] = $value;
        }// endforeach;
        unset($columnName, $value);

        $sql = 'UPDATE `' . $tableName . '` SET ' . implode(', ', $placeholders) . ' WHERE ' . implode(' AND ', $conditions);
        $this->Sth = $this->Dbh->prepare($sql);
        unset($columns, $placeholders, $sql);

        return $this->Sth->execute($values);
    }// update


}// BaseModel