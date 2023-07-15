<?php
// LICENSE: GNU GPL v3 You should have received a copy of the GNU General
// Public License along with this program. If not, see
// https://www.gnu.org/licenses/.

///////////////// savemetadata.php /////////////////////////////////////
// handler that responds to request to save metadata                  //
////////////////////////////////////////////////////////////////////////

if (!isset($username) || !isset($accesskey)) {
    jquit('No username or access key provided.');
}

if (!isset($metadata) || !isset($assignmentId) ||
    !isset($assignmentType)) {
    jquit('Insufficient information provided to identify assignment.');
}

// load authentication and setting libraries
require_once(dirname(__FILE__) . '/../readsettings.php');
require_once(dirname(__FILE__) . '/../libauthentication.php');

if (!verify_by_accesskey($project, $username, $accesskey)) {
    jquit('Invalid accesskey provided.');
}

$rv->metadata = $metadata;
$rv->assignmentType = $assignmentType;
$rv->assignmentId = $assignmentId;
$rv->success = true;
$rv->error = false;

jsend();
