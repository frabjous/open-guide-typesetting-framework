<?php
// LICENSE: GNU GPL v3 You should have received a copy of the GNU General
// Public License along with this program. If not, see
// https://www.gnu.org/licenses/.

/////////////////// extractbibitems.php ////////////////////////////////
// handler that responds to request to get extracted bib items        //
////////////////////////////////////////////////////////////////////////

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

$assigndir = get_assignment_dir($assignmentType, $assignmentId, false);

if (!$assigndir) {
    jquit('Unable to find directory for document.' .
    ' Contact your site administrator.');
}

$extracted_bibfile = $assigndir . '/extracted-bib.txt';

// initialize return list
$rv->bibitems = array();

// if no file, just send back empty array
if (!file_exists($extracted_bibfile)) {
    $rv->success = true;
    $rv->error = false;
    jsend();
}

$ext_bibfile_contents = file_get_contents($extracted_bibfile);

$plain_entries = explode(PHP_EOL, $ext_bibfile_contents);

$rv->bibitems = $plain_entries;

// if we made it here, all was well
$rv->success = true;
$rv->error = false;

jsend();
