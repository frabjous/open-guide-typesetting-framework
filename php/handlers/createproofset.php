<?php
// LICENSE: GNU GPL v3 You should have received a copy of the GNU General
// Public License along with this program. If not, see
// https://www.gnu.org/licenses/.

///////////////// createproofset.php //////////////////////////////////////
// handler that responds to request to create a proof set for a document //
///////////////////////////////////////////////////////////////////////////

if (!isset($username) || !isset($accesskey)) {
    jquit('No username or access key provided.');
}

if (!isset($assignmentId) || !isset($assignmentType)) {
    jquit('Insufficient information provided to identify assignment.');
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

$proofsdir = "$assigndir/proofs";

if (!is_dir($proofsdir) && !mkdir($proofsdir, 0755, true)) {
    jquit('Could not find or create directory for proofs.');
}

// load oge settings so we know how to process the files
$ogesettings_file = "$assigndir/oge-settings.json";

if (!file_exists($ogesettings_file)) {
    jquit('Could not find editor settings for processing files.');
}

$ogesettings = json_decode(file_get_contents($ogesettings_file) ?? 'xx');

if(!$ogesettings) {
    jquit('Could not load settings for editor for processing files.');
}

if (!isset($ogesettings->routines->md)) {
    jquit('No routines set for creating proofs. Check your ' .
        'oge-settings.json file.');
}

// proof set is named after current timestamp
$ts = time();
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
