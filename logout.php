<?php

session_start();
require 'getjtsettings.php';

if (isset($_SESSION["_jt_user"])) {
    unset($_SESSION["_jt_user"]);
}


if (isset($_SESSION["_ke_allowed_folders"])) {
    for ($i=0; $i<count($_SESSION["_ke_allowed_folders"]); $i++) {
        if ($_SESSION["_ke_allowed_folders"][$i] == $jt_settings->datafolder) {
            array_splice($_SESSION["_ke_allowed_folders"],$i,1);
            break;
        }
    }
    if (count($_SESSION["_ke_allowed_folders"]) == 0) {
        unset($_SESSION["_ke_allowed_folders"]);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8" />
        <meta name="robots" content="noindex,nofollow" />  
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <meta name="apple-mobile-web-app-capable" content="yes" />
        <meta name="mobile-web-app-capable" content="yes" />
        <title>Logged out</title>
    </head>
    <body>
        <h1>Logged out</h1>
        <p>You have been logged out. You may close this window, or navigate away.</p>
    </body>
</html>