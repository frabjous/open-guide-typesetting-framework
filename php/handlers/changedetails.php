<?php
// LICENSE: GNU GPL v3 You should have received a copy of the GNU General
// Public License along with this program. If not, see
// https://www.gnu.org/licenses/.

//////////////// changedetails.php /////////////////////////////////////
// handler that responds to request to change details for user        //
////////////////////////////////////////////////////////////////////////

if (!isset($username) || !isset($accesskey)) {
    jquit('No username or access key provided.');
}

if (!isset($detailsemail) || !isset($detailsname)) {
    jquit('No new email or name given.');
}

// make email all lowercase
$detailsemail = strtolower($detailsemail);

// load authentication and setting libraries
require_once(dirname(__FILE__) . '/../readsettings.php');
require_once(dirname(__FILE__) . '/../libauthentication.php');

if (!verify_by_accesskey($project, $username, $accesskey)) {
    jquit('Invalid accesskey provided.');
}

$rv->success = set_user_details($project, $username,
    $detailsname, $detailsemail);

jsend();
