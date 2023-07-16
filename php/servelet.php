<?php
// LICENSE: GNU GPL v3 You should have received a copy of the GNU General
// Public License along with this program. If not, see
// https://www.gnu.org/licenses/.

//////////////////////// servelet.php //////////////////////////////////////
// responds to file download requests by serving files if authenticated   //
////////////////////////////////////////////////////////////////////////////

session_start();

require_once('../open-guide-editor/open-guide-misc/send-as-json.php');

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

$assignment_dir = get_assignment_dir($assignment_type, $assignment_id);

if (!($assignment_dir)) {
    bad_request();
}

$fullfilename = $assignment_dir . '/' . $filename;

if (!file_exists($fullfilename) {
}

if (!$assignment_dir) {
    jquit('Could not find or create document/assignment directory.');
}

$uploadtype = $reqObj->uploadtype;

if ($uploadtype == 'mainfile') {
    if (!isset($_FILES["files0"])) {
        jquit('Could not find appropriate file in upload.');
    }
    $fileinfo = $_FILES["files0"];
    if ($fileinfo["error"] !== 0) {
        jquit('Error when uploading file.');
    }
    $origfilename = $fileinfo["name"];
    $tmpname = $fileinfo["tmp_name"];
    $extension = strtolower(pathinfo($origfilename, PATHINFO_EXTENSION));
    if (!in_array($extension, array('docx', 'tex', 'md', 'markdown',
        'htm', 'html', 'xhtml', 'epub', 'latex', 'rtf', 'odt'))) {
        jquit('File with inappropriate extension for main file uploaded.');
    }
    $rv->extension = $extension;
    // rename any exisiting mainfile
    $files_in_dir = scandir($assignment_dir);
    foreach ($files_in_dir as $filename) {
        if (substr($filename,0,11) == 'mainupload.') {
            $from = $assignment_dir . '/' . $filename;
            $to = $assignment_dir . '/' . 'previous-' . strval(time()) .
                '-' . $filename;
            rename($from, $to);
        }
    }
    $mainfilename = $assignment_dir . '/mainupload.' . $extension;
    $moveresult = move_uploaded_file($tmpname, $mainfilename);
    if (!$moveresult) {
        jquit('Could not rename/move uploaded file.');
    }
    $rv->error = false;
    jsend();
}

jquit('Unrecognized upload type.');
