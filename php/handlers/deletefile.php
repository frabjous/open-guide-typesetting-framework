<?php
// LICENSE: GNU GPL v3 You should have received a copy of the GNU General
// Public License along with this program. If not, see
// https://www.gnu.org/licenses/.

//////////////////// deletefile.php //////////////////////////////////////
// handles request to delete a file from a typesetting assignment       //
//////////////////////////////////////////////////////////////////////////

// authenticate request

if (!isset($username) || !isset($accesskey)) {
    jquit('No username or access key provided.');
}

if (!isset($filetodelete) || !isset($assignmentType) ||
    !isset($assignmentId)) {
    jquit('Inadequate information supplied to handle deletion request.');
}
// load authentication and setting libraries
require_once(dirname(__FILE__) . '/../readsettings.php');
require_once(dirname(__FILE__) . '/../libauthentication.php');

if (!verify_by_accesskey($project, $username, $accesskey)) {
    jquit('Invalid access key provided.');
}

require_once(dirname(__FILE__) . '/../libassignments.php');

$assigndir = get_assignment_dir($assignmentType, $assignmentId, false);

if (!$assigndir) {
    jquit('Cannot determine the document/assignment directory.');
}

$deletion_file = $assigndir . '/' . $filetodelete;

$exists_now = file_exists($deletion_file);

if ($exists_now) {
    $dresult = unlink($deletion_file);
    if (!$dresult) {
        jquit('Could not delete file. Please inform ' .
            'your site administrator.');
    }
}

$rv->error = false;
jsend();
