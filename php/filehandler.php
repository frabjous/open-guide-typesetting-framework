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

if (!$assignment_dir && (!($uploadtype == 'bibimport' && $assignment_id == 'none'))) {
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
    // create markdown file and extracted bibliography
    //
    require_once(dirname(__FILE__) . '/../open-guide-editor/open-guide-misc/pipe.php');
    $conv_command = $project_settings->assignmentTypes->{$assignment_type}->convert;
    $conv_command = str_replace('%upload%', '"' . $mainfilename . '"', $conv_command);
    $conv_result = pipe_to_command($conv_command, '');
    if ($conv_result->returnvalue != 0) {
        jquit('Could not convert uploaded file to markdown. ' .
            $conv_result->stderr);
    }
    require_once(dirname(__FILE__) . '/libdocument.php');
    $markdown = $conv_result->stdout;
    [$markdown, $bibcontents] = extract_bibliography($markdown);
    // read metadata file if it exists, useful for fixing up the main file
    $metadatafile = $assignment_dir . '/metadata.json';
    $metadata = new StdClass();
    if (file_exists($metadatafile)) {
        $metadata = json_decode(file_get_contents($metadatafile)) ??
            (new StdClass());
    }
    // fix up main file
    $splitsentences =
        (isset($project_settings->assignmentTypes->{$assignment_type}->splitsentences) &&
        $project_settings->assignmentTypes->{$assignment_type}->splitsentences);
    $importreplacements = new StdClass();
    if (isset($project_settings->importreplacements)) {
        $importreplacements = $project_settings->importreplacements;
    }
    $markdown = fix_markdown($markdown, $metadata,
        $importreplacements, $splitsentences);

    // TODO: create oge-settings.json

    // save main file
    $markdown_file = $assignment_dir . '/main.md';
    // back up old main
    if (file_exists($markdown_file)) {
        $newname = $assignment_dir . '/previous-' .
            strval(filemtime($markdown_file)) . '-main.md';
        rename($markdown_file, $newname);
    }
    $saveresult = file_put_contents($markdown_file, $markdown);
    if (!$saveresult || $saveresult == 0) {
        jquit('Could not save markdown file.');
    }
    if ($bibcontents != '') {
        $bibfile = $assignment_dir . '/extracted-bib.txt';
        if (file_exists($bibfile)) {
            $newbibfile = $assignment_dir . '/previous-' .
                strval(filemtime($bibfile)) . '-extracted-bib.txt';
            rename($bibfile, $newbibfile);
        }
        $saveresult = file_put_contents($bibfile, $bibcontents);
        if (!$saveresult || $saveresult == 0) {
            jquit('Could not save extracted bibliography.');
        }
        $rv->extractedbib = true;
    }

    $rv->error = false;
    jsend();
}

if ($uploadtype == 'auxfiles') {
    $ctr = 0;
    while (isset($_FILES["files" . strval($ctr)])) {
        $fileinfo = $_FILES["files" . strval($ctr)];
        if ($fileinfo["error"] !== 0) {
            jquit("Error when uploading file.");
        }
        $basename = $fileinfo["name"];
        $tmpname = $fileinfo["tmp_name"];
        $fullfilename = $assignment_dir . '/' . $basename;
        // back up existing file with that name
        if (file_exists($fullfilename)) {
            $to = $assignment_dir . '/' . 'previous-' .
                strval(time()) . '-' . $basename;
            rename($fullfilename, $to);
        }
        $moveresult = move_uploaded_file($tmpname, $fullfilename);
        if (!$moveresult) {
            jquit("Could not rename/move uploaded file.");
        }
        $ctr++;
    }
    $rv->error = false;
    jsend();
}

if ($uploadtype == 'bibimport') {
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
    if (!in_array($extension, array('bib','json','ris','xml','yaml','yml'))) {
        jquit('File with inappropriate extension for bibliography ' +
            'import uploaded.');
    }
    $filecontents = file_get_contents($tmpname);
    $json = '';
    if ($extension == 'json') {
        $json = $filecontents;
    } else {
        $cmd = 'pandoc -t csljson ';
        if ($extension == 'bib') {
            $cmd .= '-f bibtex ';
        }
        if ($extension == 'ris') {
            $cmd .= '-f ris ';
        }
        if ($extension == 'xml') {
            $cmd .= '-f endnotexml ';
        }
        require_once(dirname(__FILE__) . '/../open-guide-editor/open-guide-misc/pipe.php');
        $piperes = pipe_to_command($cmd, $filecontents);
        if ($piperes->returnvalue != 0) {
            jquit('Could not convert file: ' . $piperes->stderr);
        }
        $json = $piperes->stdout;
    }
    $rv->additions = json_decode($json);
    // delete the uploaded file now that we have its contents
    unlink($tmpname);
    if (!is_array($rv->additions)) {
        jquit('Wrong kind of json found in file.');
    }
    if (!$rv->additions || count($rv->additions) == 0) {
        jquit('Unable to decode bibliography items from file.');
    }
    jsend();
}

jquit('Unrecognized upload type.');
