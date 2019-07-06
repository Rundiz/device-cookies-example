<?php
/**
 * User device cookie failed attempts model.
 *
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Models;


class UserDeviceCookieFailedAttempts extends BaseModel
{


    protected $tableName = 'user_devicecookie_failedattempts';


    /**
     * Add/register failed authentication attempt.
     *
     * @param array $data The associative array where key is field.
     * @throws \InvalidArgumentException Throw the error if `$data` is invalid.
     */
    public function addFailedAttempt(array $data)
    {
        if (!isset($data['user_id'])) {
            throw new \InvalidArgumentException('The `$data` must contain `user_id` in the array key.');
        }

        $this->insert($this->tableName, $data);
    }// addFailedAttempt


    /**
     * Count the number of failed authentication within period.
     *
     * @param int $timePeriod The time period.
     * @param array $where The associative array where key is field.
     * @return int Return total number of failed authentication counted.
     */
    public function countFailedAttemptInPeriod(int $timePeriod, array $where = []): int
    {
        $sql = 'SELECT COUNT(*) AS `total_failed` FROM `' . $this->tableName . '`WHERE `datetime` >= NOW() - INTERVAL :time_period MINUTE';
        $values = [];
        $values[':time_period'] = $timePeriod;

        foreach ($where as $field => $value) {
            $sql .= ' AND ';
            if (is_null($value)) {
                $sql .= ' `' . $field . '` IS NULL';
            } else {
                $sql .= ' `' . $field . '` = :' . $field;
                $values[':' . $field] = $value;
            }
        }// endforeach;
        unset($field, $value);

        $this->Sth = $this->Dbh->prepare($sql);
        foreach ($values as $placeholder => $value) {
            $this->Sth->bindValue($placeholder, $value);
        }// endforeach;
        unset($placeholder, $sql, $value, $values);

        $this->Sth->execute();
        $result = $this->Sth->fetchObject();

        if (is_object($result) && isset($result->total_failed)) {
            return intval($result->total_failed);
        } else {
            return 0;
        }
    }// countFailedAttemptInPeriod


}// UserDeviceCookieFailedAttempts