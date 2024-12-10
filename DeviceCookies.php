<?php
/**
 * Device cookies
 *
 * @license http://opensource.org/licenses/MIT MIT
 * @link https://www.owasp.org/index.php/Slow_Down_Online_Guessing_Attacks_with_Device_Cookies Documentation.
 */


/**
 * Device cookies class for help prevent brute-force attack.
 */
class DeviceCookies
{


    /**
     * @var \PDO
     */
    protected $Dbh;


    /**
     * @var string The name of device cookie.
     */
    protected $deviceCookieName = 'deviceCookie';


    /**
     * @var int The number of days that this cookie will be expired.
     */
    protected $deviceCookieExpire = 730;


    /**
     * @var int Current failed attempts with in time period.
     */
    protected $currentFailedAttempt = 0;


    /**
     * @var array|null Contain lockout result object from `$UserDeviceCookieLockout->isInLockoutList()` method.
     */
    protected $lockoutResult;


    /**
     * @var int Max number of authentication attempts allowed during "time period".
     */
    protected $maxAttempt = 10;


    /**
     * @var string Server’s secret cryptographic key.
     */
    protected $secretKey = 'SkfEED4aKrNWFUNqgqf6hrFsJQ6K6Jhh';


    /**
     * @var int Time period (in minutes).
     */
    protected $timePeriod = 60;


    /**
     * Class constructor.
     *
     * @param array $options The options in associative array format.
     */
    public function __construct(array $options)
    {
        foreach ($options as $option => $value) {
            if (property_exists($this, $option)) {
                $this->{$option} = $value;
            }
        }// endforeach;
        unset($option, $value);
    }// __construct


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
     * Entry point for authentication request
     *
     * @param string $login The login ID such as email.
     * @return string Return what to do next. The value will be `reject`, `rejectvalid`, `authenticate`.<br>
     *                          The `reject` means it is untrusted clients or invalid device cookie and is in lockout.<br>
     *                          The `rejectvalid` means there is valid device cookie but entered wrong credentials too many attempts until gets lockout.<br>
     *                          The `authenticate` means you are able to continue authenticate login.
     */
    public function checkEntryPoint(string $login): string
    {
        $UserDeviceCookieLockout = new \Models\UserDeviceCookieLockout($this->Dbh);
        $output = '';

        if ($this->hasDeviceCookie() === true) {
            // 1. if the incoming request contains a device cookie.
            // --- a. validate device cookie
            $validateDeviceCookieResult = $this->validateDeviceCookie($login);

            if ($validateDeviceCookieResult !== true) {
                // b. if device cookie is not valid.
                // proceed to step 2.
                $this->removeDeviceCookie();
                $step2 = true;
            } elseif ($UserDeviceCookieLockout->isInLockoutList($this->getDeviceCookie()) === true) {
                // c. if the device cookie is in the lockout list (valid but in lockout list).
                // reject authentication attempt∎
                $output = 'rejectvalid';
                $this->lockoutResult = $UserDeviceCookieLockout->getLockoutResult();
            } else {
                // d. else
                // authenticate user∎
                $output = 'authenticate';
            }
        } else {
            $step2 = true;
        }// endif;

        if (isset($step2) && $step2 === true) {
            $Users = new \Models\Users($this->Dbh);
            $row = $Users->get(['email' => $login]);
            if (!empty($row) && is_object($row)) {
                $user_id = $row->id;
            } else {
                $user_id = null;
            }
            unset($row, $Users);

            if ($UserDeviceCookieLockout->isInLockoutList(null, $user_id) === true) {
                // 2. if authentication from untrusted clients is locked out for the specific user.
                // reject authentication attempt∎
                $output = 'reject';
                $this->lockoutResult = $UserDeviceCookieLockout->getLockoutResult();
            } else {
                // 3. else
                // authenticate user∎
                $output = 'authenticate';
            }// endif;

            unset($user_id);
        } else {
            if (empty($output)) {
                // i don't think someone will be in this condition.
                $output = 'reject';
                $this->lockoutResult = $UserDeviceCookieLockout->getLockoutResult();
            }
        }// endif;

        return $output;
    }// checkEntryPoint


    /**
     * Generate nonce
     *
     * @link https://stackoverflow.com/a/4356295/128761 Original source code.
     * @param int $length The string length.
     * @return string Return generated nonce.
     */
    protected function generateNonce(int $length = 32): string
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }// generateNonce


    /**
     * Get device cookie content
     *
     * @return string Return cookie value or content.
     */
    public function getDeviceCookie(): string
    {
        if ($this->hasDeviceCookie() === true) {
            $cookieValue = $_COOKIE[$this->deviceCookieName];
            return $cookieValue;
        }

        return '';
    }// getDeviceCookie


    /**
     * Get device cookie as array.
     *
     * @param string|null The cookie value. Leave null to get it from cookie variable.
     *@return array Return array where 0 is login, 1 is nonce, 2 is signature.
     */
    public function getDeviceCookieArray($cookieValue = null): array
    {
        if (is_null($cookieValue)) {
            $cookieValue = $this->getDeviceCookie();
        }
        if (!is_string($cookieValue)) {
            throw new \InvalidArgumentException('The argument `$cookieValue` must be string or null.');
        }
        $exploded = explode(',', $cookieValue);

        if (is_array($exploded) && count($exploded) >= 3) {
            $output = $exploded;
        } else {
            $output = [
                '',
                null,
                null,
            ];
        }

        unset($cookieValue, $exploded);
        return $output;
    }// getDeviceCookieArray


    /**
     * Get HMAC signature content.
     *
     * @param string The login name.
     * @param string NONCE.
     * @return string Return generated string from HMAC.
     */
    protected function getHmacSignature(string $login, string $nonce): string
    {
        return hash_hmac('sha512', $login . ',' . $nonce, $this->secretKey);
    }// getHmacSignature


    /**
     * Get reject message.
     *
     * Get reject message and merge with some data you specified.
     *
     * @param array $result The lockout result got from `$UserDeviceCookieLockout->getLockoutResult()` method.
     * @param string|null $message The reject message.
     * @return string Return formatted message.
     */
    public function getRejectMessage(array $result, $message = null): string
    {
        if (empty($result)) {
            if (empty($message)) {
                $message = 'Unable to login, please try again later.';
            }
            return $message;
        }

        if (is_array($result)) {
            if (function_exists('array_key_first')) {
                $row = $result[array_key_first($result)];
            } else {
                reset($result);
                $row = $result[key($result)];
            }
        }

        if ($message === null || empty($message)) {
            $message = 'Unable to login until %s, please try again later.';
        }

        if (!is_string($message)) {
            throw new \InvalidArgumentException('The argument `$message` must be string or null.');
        }

        return sprintf($message, $row->lockout_until);
    }// getRejectMessage


    /**
     * Check if the incoming request contains a device cookie.
     *
     * This is just check that there is device cookie or not. It was not check for valid or invalid device cookie.
     *
     * @return bool Return `true` if there is device cookie. Return `false` if not.
     */
    public function hasDeviceCookie(): bool
    {
        if (isset($_COOKIE[$this->deviceCookieName])) {
            return true;
        }

        return false;
    }// hasDeviceCookie


    /**
     * Issue new device cookie to user’s client.
     *
     * Issue a browser cookie with a value.
     *
     * @param string $login The login name (or internal ID).
     */
    public function issueNewDeviceCookie(string $login)
    {
        $nonce = $this->generateNonce(32);
        $signature = $this->getHmacSignature($login, $nonce);
        setcookie($this->deviceCookieName, $login . ',' . $nonce . ',' . $signature, (time() + ($this->deviceCookieExpire * 24 * 60 * 60)), '/');
    }// issueNewDeviceCookie


    /**
     * Register failed authentication attempt.
     *
     * @param array $data The associative array where the key is field of `user_devicecookie_lockout` table..
     * @throws \InvalidArgumentException Throw the error if `$data` is invalid.
     */
    public function registerFailedAuth(array $data)
    {
        if (!isset($data['user_id'])) {
            throw new \InvalidArgumentException('The `$data` must contain `user_id` in the array key.');
        }

        // get additional data from previous cookie.
        if (isset($data['user_email']) && $this->validateDeviceCookie($data['user_email']) === true) {
            // if a valid device cookie presented.
            $validDeviceCookie = true;// mark that valid device cookie is presented.
            list($login, $nonce, $signature) = $this->getDeviceCookieArray();
            $data['devicecookie_nonce'] = $nonce;
            $data['devicecookie_signature'] = $signature;
            unset($login, $nonce, $signature);
        }

        // sanitize $data
        if (isset($data['devicecookie_nonce']) && empty($data['devicecookie_nonce'])) {
            $data['devicecookie_nonce'] = null;
            unset($validDeviceCookie);
        }
        if (isset($data['devicecookie_signature']) && empty($data['devicecookie_signature'])) {
            $data['devicecookie_signature'] = null;
            unset($validDeviceCookie);
        }

        // 1. register a failed authentication attempt
        $UserDeviceCookieFailedAttempts = new \Models\UserDeviceCookieFailedAttempts($this->Dbh);
        $UserDeviceCookieFailedAttempts->addFailedAttempt($data);

        // 2. depending on whether a valid device cookie is present in the request, count the number of failed authentication attempts within period T
        if (!isset($data['devicecookie_nonce']) || !isset($data['devicecookie_signature'])) {
            // a. all untrusted clients
            $where = [];
            $where['devicecookie_signature'] = null;
            $failedAttempts = $UserDeviceCookieFailedAttempts->countFailedAttemptInPeriod($this->timePeriod, $where);
        } else {
            // b. a specific device cookie
            $where = [];
            $where['devicecookie_signature'] = $data['devicecookie_signature'];
            $failedAttempts = $UserDeviceCookieFailedAttempts->countFailedAttemptInPeriod($this->timePeriod, $where);
        }
        $this->currentFailedAttempt = $failedAttempts;
        unset($UserDeviceCookieFailedAttempts, $where);

        // 3. if "number of failed attempts within period T" > N
        if ($failedAttempts > $this->maxAttempt) {
            $dataUpdate = [];
            $dataUpdate['user_id'] = $data['user_id'];
            $Datetime = new \Datetime();
            $Datetime->add(new \DateInterval('PT' . $this->timePeriod . 'M'));
            $dataUpdate['lockout_until'] = $Datetime->format('Y-m-d H:i:s');
            unset($Datetime);

            if (
                isset($validDeviceCookie) && 
                $validDeviceCookie === true
            ) {
                // a. if a valid device cookie is presented
                // put the device cookie into the lockout list for device cookies until now+T
                $dataUpdate['devicecookie_nonce'] = $data['devicecookie_nonce'];
                $dataUpdate['devicecookie_signature'] = $data['devicecookie_signature'];
            } else {
                // b. else
                // lockout all authentication attempts for a specific user from all untrusted clients until now+T
                $dataUpdate['lockout_untrusted_clients'] = 1;
            }

            $UserDeviceCookieLockout = new \Models\UserDeviceCookieLockout($this->Dbh);
            $UserDeviceCookieLockout->AddUpdateLockoutList($dataUpdate);
            unset($UserDeviceCookieLockout);
        }
    }// registerFailedAuth


    /**
     * Remove a device cookie.
     */
    public function removeDeviceCookie()
    {
        setcookie($this->deviceCookieName, '', (time() - ($this->deviceCookieExpire * 24 * 60 * 60)), '/');
    }// removeDeviceCookie


    /**
     * Validate device cookie.
     *
     * @partam string $userLogin The login ID input from user.
     * @return bool Return `true` if device cookie is correct and the `login` contain in the cookie is matched the user who is trying to authenticate. Return `false` for otherwise.
     */
    public function validateDeviceCookie(string $userLogin): bool
    {
        if ($this->hasDeviceCookie() === true) {
            $cookieValue = $_COOKIE[$this->deviceCookieName];
            list($login, $nonce, $signature) = $this->getDeviceCookieArray($cookieValue);

            if ($userLogin . ',' . $nonce . ',' . $signature === $cookieValue) {
                // 1. Validate that the device cookie is formatted as described
                if (
                    hash_equals(
                        $this->getHmacSignature($userLogin, $nonce),
                        $signature
                    )
                ) {
                    // 2. Validate that SIGNATURE == HMAC(secret-key, "LOGIN,NONCE")
                    if ($login === $userLogin) {
                        // 3. Validate that LOGIN represents the user who is actually trying to authenticate
                        return true;
                    }
                }
            }
        }

        return false;
    }// validateDeviceCookie


}// DeviceCookies