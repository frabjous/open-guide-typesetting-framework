<?php
// LICENSE: GNU GPL v3 You should have received a copy of the GNU General
// Public License along with this program. If not, see
// https://www.gnu.org/licenses/.

/////////////////////////// allusers.php /////////////////////////////////
// handler that responds to request to see names and addresses of users //
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

$users = load_users($project);

// remove certain details not needed in browser
foreach($users as $user => $userdetails) {
    foreach(['passwordhash', 'keylist', 'newpwdlinks'] as $k) {
        if (isset($users->{$user}->{$k})) {
            unset($users->{$user}->{$k});
        }
    }
}

$rv->usersinfo = $users;
jsend();

