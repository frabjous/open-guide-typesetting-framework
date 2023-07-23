<?php
// LICENSE: GNU GPL v3 You should have received a copy of the GNU General
// Public License along with this program. If not, see
// https://www.gnu.org/licenses/.

///////////////// createblankmain.php //////////////////////////////////
// handler that responds to request to create blank main file         //
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

$assigndir = get_assignment_dir($assignmentType, $assignmentId);

if (!$assigndir) {
    jquit('Unable to find or create directory for document. ' .
    'Contact your site administrator.');
}

// create blank file
$touchresult = touch("$assigndir/main.md");

if (!$touchresult) {
    jquit('Could not create blank file. Please contact your ' .
        'site administrator.');
}

$ogesave = create_oge_settings($assignmentType, $assignmentId);

if (!$ogesave) {
    jquit('Could not create settings for the open guide editor. ' +
        'Please contact your site administrator.');
}

// if we made it here, all was well
$rv->success = true;
$rv->error = false;

jsend();
