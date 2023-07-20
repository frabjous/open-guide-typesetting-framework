<?php
// LICENSE: GNU GPL v3 You should have received a copy of the GNU General
// Public License along with this program. If not, see
// https://www.gnu.org/licenses/.

/////////////////// extractbib.php /////////////////////////////////////
// handler that responds to request to save metadata                  //
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
$rv->additions = array();

// if no file, just send back empty array
if (!file_exists($extracted_bibfile)) {
    $rv->success = true;
    $rv->error = false;
    jsend();
}

$ext_bibfile_contents = file_get_contents($extracted_bibfile);

$plain_entries = explode(PHP_EOL, $ext_bibfile_contents);

require_once(dirname(__FILE__) . '/../libphilpapers.php');

foreach ($plain_entries as $plain) {
    if ($plain == '') { continue; }
    $addition = new StdClass();
    $addition->extractedfrom = $plain;
    $ids = plain_to_ids($plain);
    set_time_limit(30);
    sleep(1);
    $addition->possibilities = new StdClass();
    foreach ($ids as $id) {
        $addition->possibilities->{$id} =
            id_to_obj($id);
        sleep(1);
        if ((count($addition->possibilities->{$id}) > 0) &&
            (!isset($addition->info))) {
            $addition->info = $addition->possibilities->{$id}[0];
        }
    }
    array_push($rv->additions, $addition);
}

// if we made it here, all was well
$rv->success = true;
$rv->error = false;

jsend();
