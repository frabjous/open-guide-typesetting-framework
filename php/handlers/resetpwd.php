<?php
// LICENSE: GNU GPL v3 You should have received a copy of the GNU General
// Public License along with this program. If not, see
// https://www.gnu.org/licenses/.

////////////////// resetpwd.php //////////////////////////////
// handler that processes requests to reset password        //
//////////////////////////////////////////////////////////////

$rv->error = false;

if (!isset($email)) {
    $rv->success = false;
    $rv->resetErrMsg = 'Email address not provided.';
    jsend();
}

//email addresses are case insensitive
$email = strtolower($email);

// load authentication and setting libraries
require_once(dirname(__FILE__) . '/../readsettings.php');
require_once(dirname(__FILE__) . '/../libauthentication.php');

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
$project_contact_email = $projects->{$project}->contactemail ?? 'unknown';
$request_url = $_SERVER["REQUEST_URL"];
$request_url = mb_ereg_replace('php\/jsonhandler.*','',$request_url);

$fulllink =  $request_url . "?newpwd=" . rawurlencode($newpwdlink) .
    "&user=" . rawurlencode($user) . "&project=" . rawurlencode($project);

send_email($email, 'Reset password for the ' . $project_title .
    ' typesetting framework', "\r\n" .
    "<p>A password reset request was made for you on the typesetting\r\n" .
    "framework for the " . $project_title . ". To reset your password,\r\n" .
    "click the link below:</p>\r\n" .
    "<p><a href=\"" . $fulllink . "\">" .
    mb_ereg_replace('&','&amp;', $fulllink) . "</a></p>\r\n" .
    "<p>If this password reset request was made in error, please \r\n" .
    "inform the project contact person: \r\n" . $project_contact .
    " (<a href=\"mailto:" . $project_email . "\">" . $project_email .
    "</a>)\r\nto let them know.</p>\r\n");

$rv->success = true;
jsend();
