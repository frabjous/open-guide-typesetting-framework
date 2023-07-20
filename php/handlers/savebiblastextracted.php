<?php
// LICENSE: GNU GPL v3 You should have received a copy of the GNU General
// Public License along with this program. If not, see
// https://www.gnu.org/licenses/.

/////////////////// savebiblastextracted.php /////////////////////////////
// handler that responds to request to save the time bib last extracted //
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

$assigndir = get_assignment_dir($assignmentType, $assignmentId, false);

if (!$assigndir) {
    jquit('Unable to find directory for document.' .
    ' Contact your site administrator.');
}

$trackfile = $assigndir . '/biblastextracted';

$touchresult = touch($trackfile);

if (!$touchresult) {
    jquit('Unable to mark document’s extracted bibliography as ' +
        'processed. Contact your site administrator.');
}

// if we made it here, all was well
$rv->success = true;
$rv->error = false;

jsend();
