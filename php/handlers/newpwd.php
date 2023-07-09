<?php
// LICENSE: GNU GPL v3 You should have received a copy of the GNU General
// Public License along with this program. If not, see
// https://www.gnu.org/licenses/.

////////////////// newpwd.php //////////////////////////////////
// handler that processes requests to set new password        //
///////////////////////////////////////////////////////////////

$rv->error = false;

if (!isset($ogstnewpwd1) || !isset($ogstnewpwd2))  {
    $rv->success = false;
    $rv->pwdChangeErrMsg = 'New password not provided.';
    jsend();
}

if ($ogstnewpwd1 !== $ogstnewpwd2)  {
    $rv->success = false;
    $rv->pwdChangeErrMsg = 'Requested passwords do not match.';
    jsend();
}

if (!isset($username)) {
    $rv->success = false;
    $rv->pwdChangeErrMsg = 'No username provided.';
    jsend();
}

// load authentication and setting libraries
require_once(dirname(__FILE__) . '/../readsettings.php');
require_once(dirname(__FILE__) . '/../libauthentication.php');

// verify validity of request
if (isset($wasloggedin) && $wasloggedin) {
    if (!isset($accesskey) || $accesskey == '') {
        $rv->success = false;
        $rv->pwdChangeErrMsg = 'No accesskey for logged in user given.';
        jsend();
    }
    if (!verify_by_accesskey($project, $username, $accesskey)) {
        $rv->success = false;
        $rv->pwdChangeErrMsg = 'Invalid access key provided.';
        jsend();
    }
} else {
    if (!isset($newpwdlink) || $newpwdlink == '') {
        $rv->success = false;
        $rv->pwdChangeErrMsg = 'No password change key given.'
        jsend();
    }
    if (!verify_newpwd_link($project, $username, $newpwdlink)) {
        $rv->success = false;
        $rv->pwdChangeErrMsg = 'Invalid or expired password change key given.';
        jsend();
    }
}

// actually set new password
$setres = set_new_password($project, $username, $ogstnewpwd1);

if ($setres !== true) {
    $rv->success = false;
    $rv->pwdChangeErrMsg = $setres;
    jsend();
}
//TODO
// look for user with that email
$users = load_users($project);

$user = '';
foreach($users as $username => $userdata) {
    if ($userdata->email == $email) {
        $user = $username;
        break;
    }
}

// if not found, complain
if ($user == '') {
    $rv->success = false;
    $rv->resetErrMsg = 'No user found with that email address. ' .
        'Contact your project leader about (re)gaining access.';
    jsend();
}

$newpwdlink = new_set_pwd_link($project, $user);

require_once(dirname(__FILE__) . '/../libemail.php');

$project_title = $projects->{$project}->title ??
    'Open Guide';
$project_contact = $projects->{$project}->contactname ?? 'Unknown';
$project_email = $projects->{$project}->contactemail ?? 'unknown';

$fullpath = mb_ereg_replace('/php/jsonhandler.php', '/', full_path());

$fulllink =  $fullpath . "?newpwd=" . rawurlencode($newpwdlink) .
    "&user=" . rawurlencode($user) . "&project=" . rawurlencode($project);

$mailcontents =
    "\r\n<p>A password reset request was made for you on the typesetting\r\n" .
    "framework for the " . $project_title . ". To reset your password,\r\n" .
    "click the link below:</p>\r\n" .
    "<p><a href=\"" . $fulllink . "\">" .
    mb_ereg_replace('&','&amp;', $fulllink) . "</a></p>\r\n" .
    "<p>If this password reset request was made in error, please \r\n" .
    "inform the project contact person, \r\n" . $project_contact .
    " (<a href=\"mailto:" . $project_email . "\">" . $project_email .
    "</a>),\r\n to let them know.</p>\r\n";

send_email($email, 'Reset password for the ' . $project_title .
    ' typesetting framework', $mailcontents);

$rv->success = true;
jsend();
