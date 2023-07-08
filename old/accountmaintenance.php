<?php

session_start();

function rage_quit($msg) {
    echo $msg;
    exit(1);
}

$expected_fields = array("username", "name", "email", "password1", "password2", "token");

foreach ($expected_fields as $ef) {
    if ((!isset($_POST[$ef])) || ($_POST[$ef] == '')) {
        rage_quit("ERROR: A necessary piece of information was not provided.");
    }
}
$username = $_POST["username"];
$fullname = $_POST["name"];
$email = $_POST["email"];
$password1 = $_POST["password1"];
$password2 = $_POST["password2"];
$tokenkey = $_POST["token"];


if ($password1 != $password2) {
    rage_quit("ERROR: Requested password fields do not match.");
}

require_once 'libjt.php';

$currtime = time();
$expiration = ($currtime - 86400);

$invites_file = $jt_settings->datafolder . '/invites.json';

if (!file_exists($invites_file)) {
    rage_quit("ERROR. Invitation file does not exist.");
}

$invites = json_decode(file_get_contents($invites_file));

// clean old invites
foreach ($invites as $invkey => $invdata) {
    if ($invdata->timeissued < $expiration) {
        unset($invites->{$invkey});
    }
}

// check if token key exists and is still active
if (!isset($invites->{$tokenkey})) {
    rage_quit("ERROR. That password creation token does not exist, has expired, or has already been used.");
}

$invite_data = clone $invites->{$tokenkey};

if ($invite_data->email != $email) {
    rage_quit("ERROR. The provided email does not match the email on record for that password creation token.");
}

// remove this key, and others that have expired
unset($invites->{$tokenkey});
$save_result = file_put_contents($invites_file, json_encode($invites, JSON_PRETTY_PRINT+JSON_UNESCAPED_UNICODE));
if ($save_result === false) {
    rage_quit("ERROR. Could not update password change invitation list.");
}

$users_file = $jt_settings->datafolder . '/users.json';

if (!file_exists($users_file)) {
    $users = new StdClass();
} else {
    $users = json_decode(file_get_contents($users_file));
}

$isnew = false;

// check if user with that name already exists
if (isset($users->{$username})) {
    if ($email != $users->{$username}->email) {
        rage_quit("ERROR. A user with that username already exists in the system, but with a different email address. Contact the site administrator {$jt_settings->contact_name} ({$jt_settings->contact_email}) for help.");
    }
} else {
    $isnew = true;
    $users->{$username} = new StdClass();
}

// update information
$users->{$username}->email = $email;
$users->{$username}->name = $fullname;
$users->{$username}->passwordhash  = password_hash($password1, PASSWORD_DEFAULT);


// check for and remove redundant accounts
foreach ($users as $uname => $user) {
    if (($user->email == $email) && ($uname != $username)) {
        unset($users->{$uname});
    }
}

$save_result = file_put_contents($users_file, json_encode($users, JSON_PRETTY_PRINT+JSON_UNESCAPED_UNICODE));

if ($save_result === false) {
    rage_quit("ERROR. Could not save changes to user database.");
}

$message = "<p>Your password on the {$jt_settings->journal_name} typesetting framework site has been created or updated.</p>\r\n";
$message .= "<p>For security purposes, it is not listed here.</p>\r\n";
$message .= "<p>If you believe this password change was done in error, please contact the site administrator, \r\n";
$message .= "{$jt_settings->contact_name} (<a href=\"mailto:{$jt_settings->contact_email}\">{$jt_settings->contact_email}</a>).</p>\r\n";
send_email($email, "Password changed on " . $jt_settings->journal_name . " typesetting site", $message);

if ($isnew) {
    $message = "<p>A new account was created successfully on the {$jt_settings->journal_name} typesetting framework site.</p>\r\n";
    $message .= "<p>Username: {$username}</p>\r\n";
    $message .= "<p>Email: {$email}</p>\r\n";
    $message .= "<p>If this was done in error, please review your user list and security precautions.</p>\r\n";
    send_email($jt_settings->contact_email, "New account created on " . $jt_settings->journal_name . " typesetting site", $message);
}

?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <!-- standard metadata -->
        <meta charset="utf-8" />
        <meta name="description" content="<?php echo $jt_settings->journal_name; ?> account maintenance" />
        <meta name="author" content="<?php echo $jt_settings->contact_name; ?>" />
        <meta name="copyright" content="© <?php echo getdate()["year"] . ' ' . $jt_settings->contact_name; ?>" />
        <meta name="keywords" content="account,creation,passwords,typesetting,framework,journal" />

        <!-- if you want to disable search indexing -->
        <meta name="robots" content="noindex,nofollow" />  

        <!-- if mobile ready -->
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <meta name="apple-mobile-web-app-capable" content="yes" />
        <meta name="mobile-web-app-capable" content="yes" />

        <!-- web icon -->
        <link rel="shortcut icon" href="favicon.ico" type="image/x-icon" />
        <title><?php echo $jt_settings->journal_name; ?> account maintenance</title>

        <script>
            
            window.redirectTO = {};
            
            function updateTimer() {
                var secondsLeft = parseInt(document.getElementById("secsleft").innerHTML) - 1;
                if (secondsLeft == 0) {
                    window.location.href = "login.php";
                }
                document.getElementById("secsleft").innerHTML = secondsLeft.toString();                
                window.redirectTO = setTimeout(function() {
                    updateTimer();
                }, 1000);
            }
            
            window.onload = function() {
                updateTimer();
            }
        </script>
        
    </head>
    <body>
        <p>Changes to user database successful. You should now be able to <a href="login.php">log in</a> with your new account password.</p>
        <p>Automatically redirecting in <span id="secsleft">21</span> seconds...</p>
        
    </body>
</html>