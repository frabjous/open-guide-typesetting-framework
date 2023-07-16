<?php
// LICENSE: GNU GPL v3 You should have received a copy of the GNU General
// Public License along with this program. If not, see
// https://www.gnu.org/licenses/.

//////////////////////// filehandler.php ///////////////////////////////////
// responds to file upload requests from the browser depending on request //
////////////////////////////////////////////////////////////////////////////

session_start();

require_once('../open-guide-editor/open-guide-misc/send-as-json.php');

$rv = new StdClass();

if (!isset($_POST)) {
    jquit('Can only respond to POST requests.');
}

if (!isset($_FILES)) {
    jquit('No files uploaded.');
}

if (!isset($_POST["requestjson"])) {
    jquit('No request information provided.');
}

$reqObj = json_decode($_POST["requestjson"]) ?? null;

if ($reqObj === null) {
    jquit('Unable to decode request object.');
}

if (!isset($reqObj->uploadtype)) {
    jquit('No upload type specified.');
}

if (!isset($reqObj->project) ||
    !isset($reqObj->assignmentType) ||
    !isset($reqObj->assignmentId) ||
    !isset($reqObj->accesskey) ||
    !isset($reqObj->username)) {
    jquit('Insufficient information provided to process upload.');
}

$project = $reqObj->project;
$username = $reqObj->username;
$accesskey = $reqObj->accesskey;
$assignment_type = $reqObj->assignmentType;
$assignment_id = $reqObj->assignmentId;

// load authentication and setting libraries
require_once(dirname(__FILE__) . '/readsettings.php');
require_once(dirname(__FILE__) . '/libauthentication.php');

if (!verify_by_accesskey($project, $username, $accesskey)) {
    jquit('Invalid access key provided.');
}

require_once(dirname(__FILE__) . '/libassignments.php');

$assignment_dir = get_assignment_dir($assignment_type, $assignment_id);

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