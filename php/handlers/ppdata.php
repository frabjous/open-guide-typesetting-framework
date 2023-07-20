<?php
// LICENSE: GNU GPL v3 You should have received a copy of the GNU General
// Public License along with this program. If not, see
// https://www.gnu.org/licenses/.

/////////////////// ppdata.php /////////////////////////////////////////
// handler that responds to request get PhilPapers data for an id     //
////////////////////////////////////////////////////////////////////////

if (!isset($username) || !isset($accesskey)) {
    jquit('No username or access key provided.');
}

if (!isset($philpapersid)) {
    jquit('Phil papers ids not specified.');
}

// load authentication and setting libraries
require_once(dirname(__FILE__) . '/../readsettings.php');
require_once(dirname(__FILE__) . '/../libauthentication.php');

if (!verify_by_accesskey($project, $username, $accesskey)) {
    jquit('Invalid accesskey provided.');
}

require_once(dirname(__FILE__) . '/../libphilpapers.php');

$rv->data = id_to_obj($philpapersid);
// philpapers freaks out if these are done too quickly
sleep(2);

// if we made it here, all was well
$rv->success = true;
$rv->error = false;

jsend();
