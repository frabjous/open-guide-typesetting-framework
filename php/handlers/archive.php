<?php
// LICENSE: GNU GPL v3 You should have received a copy of the GNU General
// Public License along with this program. If not, see
// https://www.gnu.org/licenses/.

//////////////////// archive.php ////////////////////////////////////////
// handles request to archive or unarchive a typesetting assignment    //
/////////////////////////////////////////////////////////////////////////

// authenticate request

if (!isset($username) || !isset($accesskey)) {
    jquit('No username or access key provided.');
}

if (!isset($makearchived) || !isset($assignmentType) ||
    !isset($assignmentId)) {
    jquit('Inadequate information supplied to handle archiving request.');
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

$archive_file = "$assigndir/archived";

$archived_now = file_exists($archive_file);

if ($makearchived && !$archived_now) {
    $tresult = touch($archive_file);
    if (!$tresult) {
        jquit('Could not make archived. Please inform your site ' .
            'administrator.');
    }
}

if (!$makearchived && $archived_now) {
    $dresult = unlink($archive_file);
    if (!$dresult) {
        jquit('Could not unarchive document/assignment. Please inform ' .
            'your site administrator.');
    }
}

$rv->error = false;
jsend();

