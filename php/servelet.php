<?php
// LICENSE: GNU GPL v3 You should have received a copy of the GNU General
// Public License along with this program. If not, see
// https://www.gnu.org/licenses/.

//////////////////////// servelet.php //////////////////////////////////////
// responds to file download requests by serving files if authenticated   //
////////////////////////////////////////////////////////////////////////////

session_start();

$getparams = array('project','assignmenttype','assignmentid',
    'accesskey','username','filename');

function bad_request($str) {
    header("HTTP/1.1 400 Bad Request");
    header("Content-Type: text/plain");
    echo($str);
    exit();
}

function forbidden() {
    header("HTTP/1.1 403 Forbidden");
    header("Content-Type: text/plain");
    echo($str);
    exit();
}

foreach($getparams as $param) {
    if (!isset($_GET[$param])) {
        bad_request('Needed parameter not specified.');
    }
    $GLOBALS[$param] = $_GET[$param];
}

// load authentication and setting libraries
require_once(dirname(__FILE__) . '/readsettings.php');
require_once(dirname(__FILE__) . '/libauthentication.php');

if (!verify_by_accesskey($project, $username, $accesskey)) {
    forbidden('Invalid access key provided.');
}

require_once(dirname(__FILE__) . '/libassignments.php');

$assignment_dir = get_assignment_dir(
    $assignmenttype, $assignmentid, false);

if (!($assignment_dir)) {
    bad_request('Assignment/document directory not found.');
}

$fullfilename = $assignment_dir . '/' . $filename;

if (!file_exists($fullfilename)) {
    bad_request('File for download not found.');
}

require_once(dirname(__FILE__) .
    '/../open-guide-editor/open-guide-misc/libservelet.php');

$opts = array(
    "attachmentname" => $filename,
    "download" => true,
    "filename" => $fullfilename
);

servelet_send($opts);

