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
require_once(dirname(__FILE__) . '/../libassignments.php');

$assigndir = get_assignment_dir($assignmentType, $assignmentId);

if (!$assigndir) {
    jquit('Unable to find or create directory for document.' .
    ' Contact your site administrator.');
}

// create json file with metadata
$metadata_file = "$assigndir/metadata.json";
$json_save = file_put_contents($metadata_file,
    json_encode($metadata, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
if (!$json_save || $json_save == 0) {
    jquit('Unable to save metadata. Contact your site administrator.');
}

// TODO: create yaml file with metadata for pandoc

$rv->success = true;
$rv->error = false;

jsend();
