<?php
/**
 * An example of garbage collection to remove old data that is no longer used from DB.
 *
 * This file should be call from cron job once a day.
 */


require 'config.php';


// connect to db.
try {
    $dbh = new PDO(
        'mysql:dbname=' . $pdoDbName . ';host=' . $pdoDbHost . ';charset=UTF8', 
        $pdoUser, 
        $pdoPassword, 
        $pdoOptions
    );
} catch (\PDOException $e) {
    echo 'Connection failed: ' . $e->getMessage();
    exit();
}


$sql = 'DELETE FROM `user_devicecookie_failedattempts` WHERE `datetime` < NOW() - INTERVAL :time_period MINUTE';
$Sth = $dbh->prepare($sql);
$Sth->bindValue(':time_period', ($timePeriod + 10));
$Sth->execute();
$affected1 = $Sth->rowCount();
echo $affected1 . ' row deleted.' . PHP_EOL;
unset($sql, $Sth);


$sql = 'DELETE FROM `user_devicecookie_lockout` WHERE `lockout_until` < NOW()';
$Sth = $dbh->prepare($sql);
$Sth->execute();
$affected2 = $Sth->rowCount();
echo $affected2 . ' row deleted.' . PHP_EOL;
unset($sql, $Sth);


// disconnect PDO.
unset($dbh);