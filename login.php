<?php
/**
 * An example of login page that implement device cookies to prevent brute-force attack.
 *
 * @link https://www.owasp.org/index.php/Slow_Down_Online_Guessing_Attacks_with_Device_Cookies OWASP Device cookies reference.
 */
 
 
session_start();


if (
    !isset($_SERVER['REQUEST_METHOD']) || 
    (
        isset($_SERVER['REQUEST_METHOD']) && 
        strtolower($_SERVER['REQUEST_METHOD']) !== 'post'
    )
) {
    header('Location: form.php');
    exit();
}


require 'config.php';
require_once 'Models/BaseModel.php';
require_once 'Models/Users.php';
require_once 'Models/UserDeviceCookieFailedAttempts.php';
require_once 'Models/UserDeviceCookieLockout.php';
require_once 'DeviceCookies.php';


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


if ($_POST) {
    // get user input and place them in variable.
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = filter_input(INPUT_POST, 'password');

    // form validation. ---------------------
    if (empty($email) || empty($password)) {
        $output = 'Please enter email and password!';
        http_response_code(400);
        $formValidated = false;
    } else {
        $formValidated = true;
    }
    // end form validation. ----------------

    if (isset($formValidated) && $formValidated === true) {
        // if form validation passed.
        // initialize new classes.
        $DeviceCookies = new DeviceCookies([
            'Dbh' => $dbh,
            'deviceCookieExpire' => $deviceCookieExpire,
            'maxAttempt' => $maxAttempt,
            'secretKey' => $secretKey,
            'timePeriod' => $timePeriod,
        ]);
        $Users = new \Models\Users($dbh);
        $UserDeviceCookieLockout = new \Models\UserDeviceCookieLockout($dbh);

        // Entry point for authentication request. --------------------
        $entrypoint = $DeviceCookies->checkEntryPoint($email);
        if ($entrypoint === 'authenticate') {
            // if checked and allow to authenticate user.
            $goAuthenticateUser = true;// mark that it is able to authenticate user.
        } else {
            // if stuck at lockout.
            $output = $DeviceCookies->getRejectMessage($DeviceCookies->lockoutResult);
            http_response_code(403);
            if ($entrypoint === 'rejectvalid') {
                // @todo You may send a login link via email to bypass the lockout here.
            }
        }
        unset($entrypoint);
        // End entry point for authentication request. ---------------

        // Authenticate user. ------------------------------------
        if (isset($goAuthenticateUser) && $goAuthenticateUser === true) {
            // 1. check user credentials
            $checkLoginResult = $Users->checkLogin($email, $password);
            if (isset($checkLoginResult) && $checkLoginResult === true) {
                // 2. if credentials are valid.
                // a. issue new device cookie to user’s client
                $DeviceCookies->issueNewDeviceCookie($email);
                // b. proceed with authenticated user
                $output = 'You had logged in successfully.';
            } else {
                // 3. else
                // a. register failed authentication attempt
                if (is_numeric($Users->loginFailedUserId)) {
                    $data = [];
                    $data['user_id'] = $Users->loginFailedUserId;
                    $data['user_email'] = $email;
                    $DeviceCookies->registerFailedAuth($data);
                    unset($data);
                }
                // b. finish with failed user’s authentication
                if (
                    is_numeric($DeviceCookies->currentFailedAttempt) &&
                    $DeviceCookies->currentFailedAttempt > 0 && 
                    is_numeric($DeviceCookies->maxAttempt) &&
                    is_numeric($DeviceCookies->timePeriod)
                ) {
                    $output = sprintf(
                        'Incorrect email or password!<br>You had entered wrong credentials for %d times, you had maximum try for %d times within %d minutes.',
                        $DeviceCookies->currentFailedAttempt,
                        $DeviceCookies->maxAttempt,
                        $DeviceCookies->timePeriod
                    );
                } else {
                    $output = 'Incorrect email or password!';
                }
                http_response_code(401);
            }
        }// endif; $goAuthenticateUser
        unset($checkLoginResult, $goAuthenticateUser);
        // End authenticate user. --------------------------------

        unset($DeviceCookies, $Users, $UserDeviceCookieLockout);
    }// endif; $formValidated

    unset($formValidated);

    // display output message. ---------------------------------
    if (isset($output) && is_scalar($output)) {
        // if there is output message.
        echo $output;
    }
    // end display output message. ----------------------------
}


// disconnect PDO.
unset($dbh);
