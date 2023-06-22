<?php

session_start();

$rv = new StdClass();
$rv->error = false;

function send_and_exit() {
    global $rv;
    echo json_encode($rv, JSON_UNESCAPED_UNICODE);
    exit(0);
}

function rage_quit() {
    global $rv;
    $rv->error = true;
    $rv->errmsg = "There was an error changing your password.";
    send_and_exit();
}

// check if needed variobles are set
if ((!isset($_SESSION["_jt_user"])) || (!isset($_POST["newpassword1"])) || (!isset($_POST["newpassword2"]))) {
    rage_quit();
}

//check if passwords match
if ($_POST["newpassword1"] != $_POST["newpassword2"]) {
    rage_quit();
}

$newpassword = $_POST["newpassword1"];

require 'libjt.php';

$usersfile = $jt_settings->datafolder . '/users.json';
if (!file_exists($usersfile)) {
    rage_quit();
}
$users = json_decode(file_get_contents($usersfile));
$jtuser = $_SESSION["_jt_user"];

// make sure user exists
if (!isset($users->{$jtuser})) {
    rage_quit();
}

$users->{$jtuser}->passwordhash = password_hash($newpassword, PASSWORD_DEFAULT);

file_put_contents($usersfile, json_encode($users, JSON_PRETTY_PRINT+JSON_UNESCAPED_UNICODE));

$message = "<p>Your password on the {$jt_settings->journal_name} typesetting framework site has been changed.</p>\r\n";
$message .= "<p>For security purposes, it is not listed here.</p>\r\n";
$message .= "<p>If you believe this password reset was done in error, please contact the site administrator, \r\n";
$message .= "{$jt_settings->contact_name} (<a href=\"mailto:{$jt_settings->contact_email}\">{$jt_settings->contact_email}</a>).</p>\r\n";
send_email($users->{$jtuser}->email, "Password changed on " . $jt_settings->journal_name . " typesetting site", $message);

send_and_exit();