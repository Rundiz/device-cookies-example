<?php
/**
 * Configurations.
 */


 /**
 * Device cookie configuration.
 *
 * @link https://www.owasp.org/index.php/Slow_Down_Online_Guessing_Attacks_with_Device_Cookies Guide and description.
 * @link http://www.unit-conversion.info/texttools/random-string-generator/ Generate secret key online.
 * @link https://passwordsgenerator.net/ Generate secret key online.
 * @link https://keygen.io/ Generate secret key online.
 * @link http://string-functions.com/length.aspx Count string length online.
 * @link https://www.charactercountonline.com/ Count string length online.
 * @link https://codebeautify.org/calculate-string-length Count string length online.
 */
$timePeriod = 60;// time period (in minutes).
$maxAttempt = 10;// max number of authentication attempts allowed during "time period".
$secretKey = 'SkfEED4aKrNWFUNqgqf6hrFsJQ6K6Jhh';// server’s secret cryptographic key. (recommended 32 characters length.)
$deviceCookieExpire = 730;// The number of days that this cookie will be expired.


/**
 * PDO configuration
 *
 * @link https://www.php.net/manual/en/pdo.construct.php Document.
 */
$pdoDbName = 'test_device_cookies';
$pdoDbHost = 'localhost';
$pdoUser = 'user';
$pdoPassword = 'pass';
$pdoOptions = [
    \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
    \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_OBJ,
];
