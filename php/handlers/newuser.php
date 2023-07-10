<?php
// LICENSE: GNU GPL v3 You should have received a copy of the GNU General
// Public License along with this program. If not, see
// https://www.gnu.org/licenses/.

/////////////////////////// newuser.php //////////////////////////////////
// handler that responds to request to invite a new user to the site    //
//////////////////////////////////////////////////////////////////////////

if (!isset($username) || !isset($accesskey)) {
    jquit('No username or access key provided.');
}

// load authentication and setting libraries
require_once(dirname(__FILE__) . '/../readsettings.php');
require_once(dirname(__FILE__) . '/../libauthentication.php');

if (!verify_by_accesskey($project, $username, $accesskey)) {
    jquit('Invalid access key provided.');
}

if (!isset($newname) || !isset($newemail) || !isset($newusername)) {
    jquit('Insufficient information provided to create new user.');
}

// make username and email all lowercase
$newusername = strtolower($newusername);
$newemail = strtolower($newemail);

// create the user
$cnu = create_new_user($project, $newusername, $newname, $newemail);

if ($cnu === 'userexists') {
    jquit('A user with that username already exists.');
}

if (!$cnu) {
    jquit('Unable to create new user.');
}

// generate a link key to set their password
$nspl = new_set_pwd_link($project, $newusername);

if ($nspl === false) {
    jquit('User created, but unable to send link to set password.');
}

// invite them to set their password

require_once(dirname(__FILE__) . '/../libemail.php');

$project_title = $projects->{$project}->title ??
    'Open Guide';
$project_contact = $projects->{$project}->contactname ?? 'Unknown';
$project_email = $projects->{$project}->contactemail ?? 'unknown';

$fullpath = mb_ereg_replace('/php/jsonhandler.php', '/', full_path());

$fulllink =  $fullpath . "?newpwd=" . rawurlencode($nspl) .
    "&user=" . rawurlencode($newusername) . "&project=" . rawurlencode($project);

$mailcontents =
    "\r\n<p>" . $newname . ",</p>\r\n" .
    "<p>An account has been created for you on \r\n" .
    "<a href=\"" . $fullpath . "\">\r\n" .
    "the typesetting framework for the " . $project_title . "</a>.\r\n" .
    "To make use of it, you will need to set a password\r\n" .
    "by visiting this link:\r\n" .
    "<p><a href=\"" . $fulllink . "\">" .
    mb_ereg_replace('&','&amp;', $fulllink) . "</a></p>\r\n" .
    "<p>Your username is: " . $newusername . "</p>\r\n" .
    "<p>If you believe this account was created in error, please \r\n" .
    "inform the project contact person, \r\n" . $project_contact .
    " (<a href=\"mailto:" . $project_email . "\">" . $project_email .
    "</a>),\r\n to let them know.</p>\r\n";

send_email($newemail, 'Account created on the ' . $project_title .
    ' typesetting framework', $mailcontents);

$rv->success = true;
jsend();


