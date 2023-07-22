<?php
// LICENSE: GNU GPL v3 You should have received a copy of the GNU General
// Public License along with this program. If not, see
// https://www.gnu.org/licenses/.

///////////////// applybibliography.php //////////////////////////////////
// handler that responds to request to apply a bibliography to a file,  //
// which means trying to identify and fix its citations                 //
//////////////////////////////////////////////////////////////////////////

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

$assigndir = get_assignment_dir($assignmentType, $assignmentId);

if (!$assigndir) {
    jquit('Unable to find or create directory for document.' .
    ' Contact your site administrator.');
}

// read json file with ALL bibliography information
$full_biblio_info_file = "$assigndir/all-bibinfo.json";

if (!file_exists($full_biblio_info_file)) {
    jquit('Cannot find bibliography information file.');
}
$bibdata = json_decode(file_get_contents($full_biblio_info_file)) ?? false;
if (!$bibdata) {
    jquit('Could not read data from bibliography information file.');
}

// read main document
$mainfile = "$assigndir/main.md";

if (!file_exists($mainfile)) {
    error_log('==============================='.$mainfile);
    jquit("Could not find main document to which to apply bibliography.");
}
$mainmd = file_get_contents($mainfile);

// TODO: put real stuff here
$rv->mainmd = $mainmd;

// record application of bibliography
$biblastappliedfile = "$assigndir/biblastapplied";
touch($biblastappliedfile);

// if we made it here, all was well
$rv->biblastapplied = time();

$rv->success = true;
$rv->error = false;

jsend();
