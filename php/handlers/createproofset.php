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
    jquit('Unable to find or create directory for document.' .
    ' Contact your site administrator.');
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

$ogesettings = json_decode(file_get_contents($ogesettings_file)) ?? false;

if(!$ogesettings) {
    jquit('Could not load settings for editor for processing files.');
}

if (!isset($ogesettings->routines->md)) {
    jquit('No routines set for creating proofs. Check your ' .
        'oge-settings.json file.');
}

require_once(dirname(__FILE__) . '/../../open-guide-editor/php/libprocessing.php');

foreach($ogesettings->routines->md as $outext => $routine) {
    $opts = new StdClass();
    $opts->routine = $routine;
    $opts->rootdocument = 'main.md';
    $opts->savedfile = 'main.md';
    $opts->outputfile = 'main.' . $outext;
}

// if we made it here, all was well
$rv->success = true;
$rv->error = false;

jsend();
