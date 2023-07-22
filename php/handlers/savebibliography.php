<?php
// LICENSE: GNU GPL v3 You should have received a copy of the GNU General
// Public License along with this program. If not, see
// https://www.gnu.org/licenses/.

///////////////// savebibliography.php //////////////////////////////////
// handler that responds to request to save bibliography for document  //
/////////////////////////////////////////////////////////////////////////

if (!isset($username) || !isset($accesskey)) {
    jquit('No username or access key provided.');
}

if (!isset($bibdata) || !isset($assignmentId) ||
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

// create json file with ALL bibliography information
$full_biblio_info_file = "$assigndir/all-bibinfo.json";
$json_save = file_put_contents($full_biblio_info_file,
    json_encode($bibdata, JSON_UNESCAPED_UNICODE));
if (!$json_save || $json_save == 0) {
    jquit('Unable to save bibliography data. Contact your site administrator.');
}

// create csl-json file
$cslarray = array();
foreach ($bibdata as $id => $bibentry) {
    foreach( array('abbreviation', 'possibilities',
        'philpapersid', 'extractedfrom') as $extrafield) {
        if (isset($bibentry->{$extrafield})) {
            unset($bibentry->{$extrafield});
        }
    }
    array_push($cslarray, $bibentry);
}

$cslbibfile = "$assigndir/bibliography.json";
$csljsonsave = file_put_contents($cslbibfile,
    json_encode($cslarray, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
if (!$csljsonsave || $csljsonsave == 0) {
    jquit('Unable to save bibliography. Contact your site administrator.');
}



// if we made it here, all was well
$rv->biblastsaved = time();

$rv->success = true;
$rv->error = false;

jsend();
