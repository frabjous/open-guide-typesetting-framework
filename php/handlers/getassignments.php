<?php
// LICENSE: GNU GPL v3 You should have received a copy of the GNU General
// Public License along with this program. If not, see
// https://www.gnu.org/licenses/.

//////////////////// getassignments.php ////////////////////////////////
// retrieves data about all assignments, either current or archived   //
// depending on value of $archive from caller                         //
////////////////////////////////////////////////////////////////////////

// authenticate request

if (!isset($username) || !isset($accesskey)) {
    jquit('No username or access key provided.');
}

// load authentication and setting libraries
require_once(dirname(__FILE__) . '/../readsettings.php');
require_once(dirname(__FILE__) . '/../libauthentication.php');

if (!verify_by_accesskey($project, $username, $accesskey)) {
    jquit('Invalid access key provided.');
}

require_once(dirname(__FILE__) . '/../libassignments.php');

if (!(isset($project_settings->assignmentTypes))) {
    jquit('Project settings do not have any document/assignment types. ' +
        'Please check your site configuration.');
}

foreach($project_settings->assignmentTypes as $assignment_type => $assign_type_spec) {
    $rv->{$assignment_type} = new StdClass();
    // read type directory
    $type_dir = get_assignment_type_dir($assignment_type);
    if (!$type_dir) { continue; }
    $contents = scandir($type_dir);
    if (!$contents || (count($contents) == 0)) { continue; }
    foreach ($contents as $assignment_id) {
        if ($assignment_id == '.' || $assignment_id == '..') {
            continue;
        };
        $assignment_dir = $type_dir . '/' . $assignment_id;
        if (!is_dir($assignment_dir)) {
            continue;
        }
        // include archive file if archive, not otherwise
        $archive_file = $assignment_dir . '/archived';
        if (file_exists($archive_file) != $archive) {
            continue;
        }
        // create a return value
        $rv->{$assignment_type}->{$assignment_id} = new StdClass();
        // read metadata
        $metadata_file = $assignment_dir . '/metadata.json';
        if (file_exists($metadata_file)) {
            $rv->{$assignment_type}->{$assignment_id}->metadata =
                json_decode(file_get_contents($metadata_file)) ??
                (new StdClass());
        }
        // get file list
        $filenames = scandir($assignment_dir);
        $filenames = array_values(array_filter($filenames, function($fn) {
            if (in_array($fn, array(
                '.','..','archived','proofs'
            ))) { return false; }
            return true;
        }));
        if (count($filenames) > 0) {
            $rv->{$assignment_type}->{$assignment_id}->filenames = $filenames;
        }
        // check when bib last extracted, bib last applied, etc.
        if (in_array('biblastextracted', $filenames)) {
            $rv->{$assignment_type}->{$assignment_id}->biblastextracted =
                filemtime($assignment_dir . '/biblastextracted');
        }
        if (in_array('biblastapplied', $filenames)) {
            $rv->{$assignment_type}->{$assignment_id}->biblastapplied =
                filemtime($assignment_dir . '/biblastapplied');
        }
        if (in_array('extracted-bib.txt', $filenames)) {
            $rv->{$assignment_type}->{$assignment_id}->extractbibmtime =
                filemtime($assignment_dir . '/extracted-bib.txt');
        }
        if (in_array('bibliography.json', $filenames)) {
            $rv->{$assignment_type}->{$assignment_id}->biblastchanged =
                filemtime($assignment_dir . '/bibliography.json');
        }

    }
}

    // should have: title (header), metadata, files/upload, bibl, proofs, publication
    // (title): identify the work, and its id
    // maybe put archive button on right of title?
    // (metadata): custom for type; jhap has title, author name, email, affiliation
    // volume, number, special volume title, special volume editors
    // for reviews, also, title of reviewed work, author of reviewed work,
    // editor of reviewed work, publication details, = place, publisher,year,
    // pages, cost, hardcover/soft. ISBN
    // (files): main upload: download, replace
    // supplementary
    // edit LaTeX (now markdown
    // (bibliography):edit/complete
    // (proofs): list, each with editor link, author link
    // create new proofs button
    // (publication): optimized pdf creation/download,
    // extract abstract, extract references
    // (maybe put abstract in metadata)

    // Files: uploaded, random supplementary, bibliography.json,
    // main.md (better name), metadata.json, status.json, abstract?
    // + now, oge-setting.json
    // subfolder: proofs
    // subfolder: publication


$rv->error = false;
jsend();
