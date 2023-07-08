<?php

session_start();

$rv = new StdClass();
$rv->success = true;

function send_and_exit() {
    global $rv;
    echo json_encode($rv, JSON_UNESCAPED_UNICODE);
    exit(0);
}

function rage_quit($msg) {
    global $rv;
    $rv->success = false;
    $rv->errmsg = $msg;
    send_and_exit();
}

$reset_only = true;
if (isset($_SESSION["_jt_user"])) {
    $reset_only = false;
}


// check post variables
$username = $_POST["username"] ?? '';
$fullname = $_POST["fullname"] ?? '';
$email = $_POST["email"] ?? '';

if (($reset_only) && ($email == '')) {
    rage_quit("No email address provided.");
}

if (($email == '') && ($username == '') && ($email == '')) {
    rage_quit("No email or name or username provided.");
}

require_once 'libjt.php';

if ($email == '') {
    $usersfile = $jt_settings->datafolder . '/users.json';
    if (!file_exists($usersfile)) {
        rage_quit("No users database found.");
    }
    $users = json_decode(file_get_contents($usersfile));
    if (($username != '') && (isset($users->{$username}))) {
        $email = $users->{$username}->email;
        $fullname = $users->{$username}->name;
    } else {
        foreach ($users as $this_username => $this_user) {
            if ($this_user->name == $fullname) {
                $email = $this_user->email;
                $username = $this_username;
                break;
            }
        }
    }
} else {
    // in reset only mode, email address must exist in database; check for it
    if ($reset_only) {
        $usersfile = $jt_settings->datafolder . '/users.json';
        if (!file_exists($usersfile)) {
            rage_quit("No users database found.");
        }
        $users = json_decode(file_get_contents($usersfile));
        $found = false;
        foreach ($users as $this_username => $this_user) {
            if ($this_user->email == $email) {
                $found = true;
                $username = $this_username;
                $fullname = $this_user->name;
                break;
            }
        }
        if (!$found) {
            rage_quit("ERROR: Email address in question cannot be found in database.");
        }
    }
}

if ($email == '') {
    rage_quit("Could not determine email address to send invitation. User with that name or full name does not exist.");
}

$invites_file = $jt_settings->datafolder . '/invites.json';

if (file_exists($invites_file)) {
    $invites = json_decode(file_get_contents($invites_file));
} else {
    $invites = new StdClass();
}

require $_SERVER["DOCUMENT_ROOT"] . '/kcklib/hostinfo.php';

do {
    $tokenkey = generate_password(20);
} while (isset($invites->{$tokenkey}));

$token_details = new StdClass();
$token_details->email = $email;
$token_details->name = $fullname;
$token_details->username = $username;
$token_details->timeissued = time();

$invites->{$tokenkey} = $token_details;

$save_result = file_put_contents($invites_file, json_encode($invites, JSON_PRETTY_PRINT+JSON_UNESCAPED_UNICODE));

if ($save_result === false) {
    rage_quit("Error in creating account or password reset invitation. Could not save.");
}

$url_with_token = dirname(full_url()) . '/newaccountpassword.php?token=' . urlencode($tokenkey);

$message = "<p>You have been invited either to create a new account or change \r\n your password on the {$jt_settings->journal_name} typesetting framework site.</p>\r\n";
$message .= "<p>Please do so by visiting the following URL, making sure that the 20-character token is included:<br><br>\r\n";
$message .= "<a href=\"{$url_with_token}\">{$url_with_token}</a></p>\r\n";
$message .= "<p>This token will expire in one day.</p>\r\n";
$message .= "<p>If you believe this was done in error, please contact the site administrator, \r\n";
$message .= "{$jt_settings->contact_name} (<a href=\"mailto:{$jt_settings->contact_email}\">{$jt_settings->contact_email}</a>).</p>\r\n";
if (!send_email($email, "Account on {$jt_settings->journal_name} typesetting site", $message)) {
    rage_quit("Could not send invitation email.");
}
send_and_exit();
