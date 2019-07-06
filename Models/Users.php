<?php
/**
 * Users model.
 *
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Models;


/**
 * Users class that work with users table.
 */
class Users extends BaseModel
{


    /**
     * @var int Contain user ID that last login failed.
     */
    protected $loginFailedUserId;


    protected $tableName = 'users';


    /**
     * Magic __get
     */
    public function __get($name)
    {
        if (isset($this->{$name})) {
            return $this->{$name};
        }
    }// __get


    /**
     * Get user data by conditions.
     *
     * @param array $data The associative array where key is field - value pairs. This is the conditions to get user data.
     * @return object|null Return object if found the record, return `null` if not found.
     */
    public function get(array $data)
    {
        $sql = 'SELECT * FROM `' . $this->tableName . '`';
        $where = [];

        if (!empty($data)) {
            $sql .= ' WHERE';
            $i = 0;
            foreach ($data as $field => $value) {
                if ($i > 0) {
                    $sql .= ' AND';
                }

                if (is_null($value)) {
                    $sql .= ' `' . $field . '` IS NULL';
                } else {
                    $sql .= ' `' . $field . '` = :' . $field;
                    $where[':' . $field] = $value;
                }

                $i++;
            }// endforeach;
            unset($field, $i, $value);
        }

        $this->Sth = $this->Dbh->prepare($sql);
        foreach ($where as $field => $value) {
            $this->Sth->bindValue($field, $value);
        }// endforeach;
        unset($field, $value);

        $this->Sth->execute();
        return $this->Sth->fetchObject();
    }// get


    /**
     * Check user credentials (email, password).
     *
     * @param string $email The email account.
     * @param string $password User's readable password.
     * @return bool Return `true` if correct email and password. Return `false` for otherwise.
     */
    public function checkLogin(string $email, string $password): bool
    {
        $sql = 'SELECT * FROM `' . $this->tableName . '` WHERE `email` = :email';
        $this->Sth = $this->Dbh->prepare($sql);
        $this->Sth->bindValue(':email', $email);
        $this->Sth->execute();
        unset($sql);

        $result = $this->Sth->fetchObject();

        if (is_object($result)) {
            // if found selected user.
            if (isset($result->password) && password_verify($password, $result->password) === true) {
                // if password is correct.
                // you may have to implement `password_needs_rehash()` here. Read more at https://www.php.net/manual/en/function.password-needs-rehash.php
                return true;
            }
            $this->loginFailedUserId = $result->id;
        }

        // incorrect email or password.
        return false;
    }// checkLogin


}// Users
