<?php

session_start();
function rage_quit() {
    $referer = explode('?', $_SERVER["HTTP_REFERER"])[0] . '?fail=true';
    header('Location: ' . $referer);
    exit(0);
}

if ((!isset($_POST["loginid"])) || (!isset($_POST["loginpwd"]))) {
    rage_quit();
}

$loginid = trim($_POST["loginid"]);
$loginpwd = trim($_POST["loginpwd"]);

if (($loginid == '') || ($loginpwd == '')) {
    rage_quit();
}

require 'getjtsettings.php';
$usersfile = $jt_settings->datafolder . '/users.json';
if (!file_exists($usersfile)) {
    rage_quit();
}
$users = json_decode(file_get_contents($usersfile));

foreach ($users as $username => $userdetails) {
    if (($username == $loginid) || ($userdetails->email == $loginid)) {
        if (password_verify($loginpwd, $userdetails->passwordhash)) {
            $_SESSION["_jt_user"] = $username;
            if (!isset($_SESSION["_ke_allowed_folders"])) {
                $_SESSION["_ke_allowed_folders"] = array();
            }
            array_push($_SESSION["_ke_allowed_folders"], $jt_settings->datafolder);
            $redirect = dirname($_SERVER["HTTP_REFERER"]);
            if (isset($_SESSION["loginreferer"])) {
                $redirect = $_SESSION["loginreferer"];
            }
            header('Location: ' . $redirect);
            exit(0);
        }
    }
}

rage_quit();