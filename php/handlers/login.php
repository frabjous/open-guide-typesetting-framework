<?php

$rv->error = false;

// verify name given
if (!isset($ogstname)) {
    $rv->success = false;
    $rv->nosuchuser = true;
    $rv->loginErrMsg = 'Login name not provided.';
    jsend();
}
// remove rest of email address if full thing given
$ogstname = mb_ereg_replace('@.*','',$ogstname);

// make all lowercase
$ogstname = strtolower($ogstname);

// verify that a password was given
if (!isset($ogstpwd)) {
    $rv->success = false;
    $rv->wrongpassword = true;
    $rv->loginErrMsg('No password provided.');
    jsend();
}

// load authentication and setting libraries
require_once(dirname(__FILE__) . '/../readsettings.php');
require_once(dirname(__FILE__) . '/../libauthentication.php');

// verify password
$pwd_result = verify_by_password($project, $ogstname, $ogstpwd);

if ($pwd_result === 'nosuchuser') {
    $rv->success = false;
    $rv->nosuchuser = true;
    $rv->loginErrMsg = 'User with name ' . $ogstname . ' does not exist';
    jsend();
}

if (!$pwd_result) {
    $rv->success = false;
    $rv->wrongpassword = true;
    $rv->loginErrMsg = 'Incorrect password provided.';
    jsend();
}

// login was a success
$rv->success = true;
$rv->wrongpassword = false;
$rv->nosuchuser = false;
$rv->loginErrMsg = '';
$rv->loggedinuser = $ogstname;

// generate a new access key
$rv->loginaccesskey = new_access_key($project, $ogstname);

// create cookie if want to remember
if (isset($ogstremember) && $ogstremember) {
    setcookie(
        'open-guide-typesetting-framework-saved-login',
        $project . '|' . $ogstname . '|' . $rv->loginaccesskey,
        array(
            'expires' => time() + 34473600,
            'path' => '/',
            'SameSite' => 'Strict'
        )
    );
}

