<?php

session_start();
require_once '../libjt.php';


$params = array('doc','pskey','set');

$allset = true;
foreach ($params as $param) {
    $allset = ($allset && (isset($_GET[$param])));
}


function send_finish_email() {
    global $fromemail, $jt_settings, $doc, $set, $pskey;
    $message = "Comments have been saved for document #" . $doc . ' (set ' . $set . ')' . "\r\n" .
       "<br><br>" .
       "Please see <a href=\"{$_SERVER["HTTP_REFERER"]}&editormode=true\">{$_SERVER["HTTP_REFERER"]}&editormode=true</a>." . "\r\n";
    $subject = "Journal typesetting comments saved";

    // DETERMINE who to send email to
    // default to site contact
    $toemail = $jt_settings->contact_email;
    // look for editorname file and read email address
    $setdir = $jt_settings->datafolder . '/docs/' . $doc . '/proofs/' . $set;
    if (file_exists($setdir . '/editorname.txt')) {
        $setcreator = trim(file_get_contents($setdir . '/editorname.txt'));
        $usersfile = $jt_settings->datafolder . '/users.json';
        if (file_exists($usersfile)) {
            $users = json_decode(file_get_contents($usersfile));
            foreach ($users as $username => $userdetails) {
                if ($username == $setcreator) {
                    if (isset($userdetails->email)) {
                        $toemail = $userdetails->email;
                    }
                    break;
                }
            }
        }
    }

    // send email
    send_email($toemail, $subject, $message);
}


function rage_quit() {
    echo 'An error occurred.' . PHP_EOL;
    exit;
}

if ($allset) {
    // read parameters
    $pskey = $_GET["pskey"];
    $doc = $_GET["doc"];
    $set = $_GET["set"];

    // make sure folder exists, with key
    $setdir = $jt_settings->datafolder . '/docs/' . $doc . '/proofs/' . $set;
    if (!file_exists($setdir . '/pskey.txt')) {
        rage_quit();
    }

    //check key
    if (trim(file_get_contents($setdir . '/pskey.txt')) != trim($pskey)) {
        rage_quit();
    }

    send_finish_email();

    require $_SERVER["DOCUMENT_ROOT"] . '/kcklib/hostinfo.php';

    header('Location:' . full_path() . '?done=true');
    exit(0);
}

if (!((isset($_GET["done"])) && ($_GET["done"]=="true"))) {
    rage_quit();
}

?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <!-- standard metadata -->
        <meta charset="utf-8" />
        <meta name="robots" content="noindex,nofollow" />  
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <meta name="apple-mobile-web-app-capable" content="yes" />
        <meta name="mobile-web-app-capable" content="yes" />
        <link rel="shortcut icon" href="favicon.ico" type="image/x-icon" />
        <title>Editors contacted</title>
    </head>
    <body>
        <h2>Editors contacted</h2>
        <p>Thank you for submitting your comments and corrections. The editors have been notified. You may close this window.</p>
    </body>
</html>
