<?php
// LICENSE: GNU GPL v3 You should have received a copy of the GNU General
// Public License along with this program. If not, see
// https://www.gnu.org/licenses/.

/////////////////// ppidsforitem.php ////////////////////////////////////
// handler that gathers PhilPapers IDs for a given text item          //
////////////////////////////////////////////////////////////////////////

if (!isset($username) || !isset($accesskey)) {
    jquit('No username or access key provided.');
}

if (!isset($itemtext)) {
    jquit('No textual representation of bibliographic item given.');
}

// load authentication and setting libraries
require_once(dirname(__FILE__) . '/../readsettings.php');
require_once(dirname(__FILE__) . '/../libauthentication.php');

if (!verify_by_accesskey($project, $username, $accesskey)) {
    jquit('Invalid accesskey provided.');
}

// initiate return value
$rv->ids = array();

// don't bother with an empty string
if ($itemtext == '') {
    $rv->error = false;
    $rv->success = true;
    jsend();
}

// get library
require_once(dirname(__FILE__). '/../libphilpapers.php');

// get the actual ids
$rv->ids = plain_to_ids($itemtext, 5);

// if we made it here, all was well
$rv->success = true;
$rv->error = false;

// always add a second so these aren't done too quickly
// and philpapers complains
sleep(2);
jsend();
