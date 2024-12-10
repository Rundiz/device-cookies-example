<?php
/**
 * User device cookie lockout model.
 *
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Models;


class UserDeviceCookieLockout extends BaseModel
{


    /**
     * @var array|null Contain lockout result object from `isInLockoutList()` method.
     */
    protected $lockoutResult;


    protected $tableName = 'user_devicecookie_lockout';


    /**
     * Put out the device cookie in the lockout list or lockout all untrusted clients.
     *
     * Check first that the specific data is already exists then update the data, otherwise add the data.
     *
     * @param array $data The associative array where key is field.
     * @throws \InvalidArgumentException Throw the error if `$data` is invalid.
     */
    public function AddUpdateLockoutList(array $data)
    {
        if (!isset($data['user_id'])) {
            throw new \InvalidArgumentException('The `$data` must contain `user_id` in the array key.');
        }

        if (!isset($data['lockout_until'])) {
            throw new \InvalidArgumentException('The `$data` must contain `lockout_until` in the array key.');
        }

        if (!isset($data['devicecookie_signature']) && !isset($data['lockout_untrusted_clients'])) {
            throw new \InvalidArgumentException('The `$data` must contain `devicecookie_signature` OR `lockout_untrusted_clients` in the array key.');
        }

        // check that data already exists in DB.
        $sql = 'SELECT `user_id`, `devicecookie_nonce`, `devicecookie_signature`, `lockout_untrusted_clients`, `lockout_until` FROM `' . $this->tableName . '` WHERE `user_id` = :user_id';
        $where = [];
        $where[':user_id'] = $data['user_id'];

        if (isset($data['devicecookie_signature'])) {
            $sql .= ' AND`devicecookie_signature` = :devicecookie_signature';
            $where[':devicecookie_signature'] = $data['devicecookie_signature'];
        }

        if (isset($data['lockout_untrusted_clients'])) {
            $sql .= ' AND `lockout_untrusted_clients` = :lockout_untrusted_clients';
            $where[':lockout_untrusted_clients'] = $data['lockout_untrusted_clients'];
        }

        $this->Sth = $this->Dbh->prepare($sql);
        foreach ($where as $field => $value) {
            $this->Sth->bindValue($field, $value);
        }// endforeach;
        unset($field, $sql, $value, $where);
        $this->Sth->execute();

        $result = $this->Sth->fetchAll();
        if (is_array($result) && count($result) >= 1) {
            $useInsert = false;
        } else {
            $useInsert = true;
        }
        unset($result);

        // if not exists use insert, otherwise use update.
        if (isset($useInsert) && $useInsert === true) {
            $this->insert($this->tableName, $data);
        } else {
            $where = [];
            $where['user_id'] = $data['user_id'];
            $dataUpdate = [];
            $dataUpdate['lockout_until'] = $data['lockout_until'];

            if (isset($data['devicecookie_signature'])) {
                $where['devicecookie_signature'] = $data['devicecookie_signature'];
            }

            if (isset($data['lockout_untrusted_clients'])) {
                $where['lockout_untrusted_clients'] = $data['lockout_untrusted_clients'];
            }
            // reset
            $data = [];
            $this->update($this->tableName, $dataUpdate, $where);
            unset($dataUpdate, $where);
        }

        unset($useInsert);
    }// AddUpdateLockoutList


    /**
     * Get result object after called to `isInLockoutList()` method.
     *
     *@return array Return result object if required method was called, return null if nothing.
     */
    public function getLockoutResult(): array
    {
        if (is_array($this->lockoutResult)) {
            return $this->lockoutResult;
        }

        return [];
    }// getLockoutResult


    /**
     * Check if current user is in lockout list.
     *
     * If device cookie content is specified, then it will check for specific device cookie.<br>
     * If device cookie is `null` then it will check for untrusted clients.
     *
     * @param string|null $deviceCookie The device cookie content.
     * @param int|null $user_id The user id. For checking from untrusted clients.
     * @return bool Return `true` if current user is in the lockout list, return `false` for otherwise.
     */
    public function isInLockoutList($deviceCookie = null, $user_id = null): bool
    {
        if (!is_string($deviceCookie) && !is_null($deviceCookie)) {
            throw new \InvalidArgumentException('The argument `$deviceCookie` must be string or null.');
        }
        if (!is_int($user_id) && !is_null($user_id)) {
            throw new \InvalidArgumentException('The argument `$user_id` must be integer or null.');
        }

        $sql = 'SELECT `user_id`, `devicecookie_nonce`, `devicecookie_signature`, `lockout_untrusted_clients`, `lockout_until` FROM `' . $this->tableName . '` WHERE `lockout_until` >= NOW()';
        $where = [];

        if ($deviceCookie !== null) {
            // if there is device cookie.
            $exploded = explode(',', $deviceCookie);
            if (is_array($exploded) && count($exploded) >= 3) {
                list($login, $nonce, $signature) = $exploded;
            } else {
                list($login, $nonce, $signature) = [
                    '',
                    null,
                    null,
                ];
            }
            unset($exploded);

            if (is_null($signature)) {
                $sql .= ' AND `devicecookie_signature` IS NULL';
            } else {
                $sql .= ' AND `devicecookie_signature` = :devicecookie_signature';
                $where[':devicecookie_signature'] = $signature;
            }

            unset($login, $nonce, $signature);
        } else {
            // if there is NO device cookie.
            if (!is_null($user_id) && !empty($user_id)) {
                // if there is a specific user_id.
                $sql .= ' AND `user_id` = :user_id';
                $where[':user_id'] = $user_id;
            } elseif (is_null($user_id)) {
                // if no specific user_id.
                // this is may because of client enter invalid user login ID that cause this to be null.
                // just return false and let them authenticate.
                unset($sql, $where);
                return false;
            }

            $sql .= ' AND `lockout_untrusted_clients` = :lockout_untrusted_clients';
            $where[':lockout_untrusted_clients'] = 1;
        }// endif;

        $this->Sth = $this->Dbh->prepare($sql);
        foreach ($where as $field => $value) {
            $this->Sth->bindValue($field, $value);
        }// endforeach;
        unset($field, $sql, $value, $where);
        $this->Sth->execute();

        $result = $this->Sth->fetchAll();
        if (is_array($result) && count($result) >= 1) {
            // if found in lockout list
            $this->lockoutResult = $result;
            unset($result);
            return true;
        } else {
            unset($result);
            return false;
        }
    }// isInLockoutList


}// UserDeviceCookieLockout