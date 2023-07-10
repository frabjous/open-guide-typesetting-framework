<?php
// LICENSE: GNU GPL v3 You should have received a copy of the GNU General
// Public License along with this program. If not, see
// https://www.gnu.org/licenses/.

//////////// mydetails.php /////////////////////////////////////////////
// handler that responds to request to see details for user           //
////////////////////////////////////////////////////////////////////////

if (!isset($username) || !isset($accesskey)) {
    jquit('No username or access key provided.');
}

// load authentication and setting libraries
require_once(dirname(__FILE__) . '/../readsettings.php');
require_once(dirname(__FILE__) . '/../libauthentication.php');

if (!verify_by_accesskey($project, $username, $accesskey)) {
    jquit('Invalid accesskey provided.');
}

$users = load_users($project);

if (!isset($users->{$username})) {
    jquit('User whose details were requested does not exist.');
}

$rv->mydetails = $users->{$username};
jsend();
