<?php
// LICENSE: GNU GPL v3 You should have received a copy of the GNU General
// Public License along with this program. If not, see
// https://www.gnu.org/licenses/.

///////////////// extractfromversion.php ///////////////////////////////////
// handler that extracts certain info from text files saved for a version //
////////////////////////////////////////////////////////////////////////////

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

if (!is_dir($versiondir)) {
    jquit('Cannot find version.');
}

$rv->extractos = new StdClass();

$files = scandir($versiondir);

foreach ($files as $file) {
    $ext = pathinfo($file, PATHINFO_EXTENSION);
    $filename = pathinfo($file, PATHINFO_FILENAME);
    if ($ext == 'txt') {
        $rv->extractos->{$filename} =
            file_get_contents("$versiondir/$file");
    }
}

// if we made it here, all was well
$rv->success = true;
$rv->error = false;

jsend();
