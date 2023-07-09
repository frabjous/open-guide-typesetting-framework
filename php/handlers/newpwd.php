<?php
// LICENSE: GNU GPL v3 You should have received a copy of the GNU General
// Public License along with this program. If not, see
// https://www.gnu.org/licenses/.

////////////////// newpwd.php //////////////////////////////////
// handler that processes requests to set new password        //
///////////////////////////////////////////////////////////////

$rv->error = false;

// ensure we have what we need for the change
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
        $rv->pwdChangeErrMsg = 'No password change key given.';
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

// report errors
if ($setres !== true) {
    $rv->success = false;
    if ($setres === false) { $setres = 'Error saving password.'; }
    $rv->pwdChangeErrMsg = $setres;
    jsend();
}

// look for user and find email
$users = load_users($project);
$email = $users->{$username}->email;

require_once(dirname(__FILE__) . '/../libemail.php');

$project_title = $projects->{$project}->title ??
    'Open Guide';
$project_contact = $projects->{$project}->contactname ?? 'Unknown';
$project_email = $projects->{$project}->contactemail ?? 'unknown';

$fullpath = mb_ereg_replace('/php/jsonhandler.php', '/', full_path());

$mailcontents =
    "\r\n<p>Your password on \r\n" .
    "<a href=\"" . $fullpath . "\">\r\n" .
    "the typesetting framework for the " . $project_title . "</a>\r\n" .
    " has been changed. For security reasons, the new\r\n" .
    " password is not given here.</p>\r\n" .
    "<p>If this password change was made in error, please \r\n" .
    "inform the project contact person, \r\n" . $project_contact .
    " (<a href=\"mailto:" . $project_email . "\">" . $project_email .
    "</a>),\r\n to let them know.</p>\r\n";

send_email($email, 'Password changed on the ' . $project_title .
    ' typesetting framework', $mailcontents);

$rv->success = true;
jsend();
