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



$rv->success = true;
