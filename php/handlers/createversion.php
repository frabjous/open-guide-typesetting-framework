<?php
// LICENSE: GNU GPL v3 You should have received a copy of the GNU General
// Public License along with this program. If not, see
// https://www.gnu.org/licenses/.

///////////////// creatversion.php ////////////////////////////////////////
// handler that responds to request to create new edition/version for    //
// publication                                                           //
///////////////////////////////////////////////////////////////////////////

if (!isset($username) || !isset($accesskey)) {
    jquit('No username or access key provided.');
}

if (!isset($assignmentId) || !isset($assignmentType)) {
    jquit('Insufficient information provided to identify assignment.');
}

if (!isset($version)) {
    jquit('Version to create not specified.');
}

// load authentication and setting libraries
require_once(dirname(__FILE__) . '/../readsettings.php');
require_once(dirname(__FILE__) . '/../libauthentication.php');

if (!verify_by_accesskey($project, $username, $accesskey)) {
    jquit('Invalid accesskey provided.');
}
require_once(dirname(__FILE__) . '/../libassignments.php');

// look for document and proofs directories
$assigndir = get_assignment_dir($assignmentType, $assignmentId);

if (!$assigndir) {
    jquit('Unable to find or create directory for document. ' .
    'Contact your site administrator.');
}

$editionsdir = "$assigndir/editions";

$versiondir = "$editionsdir/$version";

if (is_dir($versiondir)) {
    jquit('An edition with that version number already exists!');
}

if (!mkdir($versiondir, 0755, true)) {
    jquit('Could not create version directory. ' +
        'Contact your site administrator.');
}

$rv->versioninfo = new StdClass();

if (!isset($project_settings->assignmentTypes->{$assignmentType}->createEdition)) {
    jquit('Project is not configured to create editions. Check your ' +
        'project-settings.json file.');
}

$create_instructions = 
    $project_settings->assignmentTypes->{$assignmentType}->createEdition;

$rv->files = array();

foreach($create_instructions as $instruction) {
    $command="true";
    if (isset($instruction->command)) {
        $command = $instruction->command;
        $command = str_replace('%projectdir%',"'" . $projectdir . "'");
    }
}


$ts = time();
$rv->creationtime = $ts;




// proof set is named after current timestamp
$proofdir = $proofsdir . '/' . strval($ts);

if (!mkdir($proofdir, 0755, true)) {
    jquit('Could not create directory for proof set.');
}

$rv->proofset = new StdClass();
$rv->proofset->settime = $ts;
$rv->proofset->outputfiles = array();

require_once(dirname(__FILE__) . '/../../open-guide-editor/php/libprocessing.php');

// move into assignment directory for processing
if (!chdir($assigndir)) {
    jquit('Could not change to appropriate directory.');
}

// process each output file
foreach($ogesettings->routines->md as $outext => $routine) {
    $opts = new StdClass();
    $opts->routine = $routine;
    $opts->rootdocument = 'main.md';
    $opts->savedfile = 'main.md';
    $opts->outputfile = 'main.' . $outext;
    $cmd = fill_processing_variables($opts, false);
    $result = pipe_to_command($cmd);
    if ($result->returnvalue != 0) {
        jquit('Error when processing markdown to ' . $outext . ': ' .
            $result->stderr);
    }
    $prooffilename = $proofdir . '/' . $assignmentId . '.' . $outext;
    $copyresult = copy($opts->outputfile, $prooffilename);
    if (!$copyresult) {
        jquit('Could not copy output file into proofs directory.');
    }
    // convert PDF to pages
    if ($outext == 'pdf' && file_exists($prooffilename)) {
        $pagesdir = "$proofdir/pages";
        if (!mkdir($pagesdir, 0755, true)) {
            jquit('Could not create directory for pdf pages.');
        }
        $convresult = pipe_to_command('mutool draw -o "' .
            $pagesdir . '/page%02d.svg" "' . $prooffilename . '"');
        if ($convresult->returnvalue != 0) {
            jquit('Could not convert pdf pages to images: ' .
                $convresult->stderr);
        }
    }
    array_push($rv->proofset->outputfiles, basename($prooffilename));
}
// copy main file
$mfcopyres = copy('main.md', "$proofdir/main-" . strval($ts) . '.md');
if (!$mfcopyres) {
    jquit('Could not make a copy of the main file.');
}

// save keys
$keyfile = "$datadir/proofkeys.json";
$keys = false;
if (file_exists($keyfile)) {
    $keys = json_decode(file_get_contents($keyfile));
}
if (!$keys) { $keys = new StdClass(); }

$ekey = random_string(24);
while (isset($keys->{$ekey})) {
    $ekey = random_string(24);
}
$rv->proofset->ekey = $ekey;

$keys->{$ekey} = new StdClass();
$keys->{$ekey}->project = $project;
$keys->{$ekey}->username = $username;
$keys->{$ekey}->assignmentId = $assignmentId;
$keys->{$ekey}->assignmentType = $assignmentType;
$keys->{$ekey}->proofset = strval($ts);
$keys->{$ekey}->editor = true;

$akey = random_string(24);
while (isset($keys->{$akey})) {
    $akey = random_string(24);
}
$rv->proofset->akey = $akey;

$keys->{$akey} = new StdClass();
$keys->{$akey}->project = $project;
$keys->{$akey}->username = $username;
$keys->{$akey}->assignmentId = $assignmentId;
$keys->{$akey}->assignmentType = $assignmentType;
$keys->{$akey}->proofset = strval($ts);

$saveres = file_put_contents($keyfile, json_encode($keys,
    JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));

if (!$saveres || $saveres == 0) {
    jquit('Could not save new proof access key.');
}

// if we made it here, all was well
$rv->success = true;
$rv->error = false;

jsend();
