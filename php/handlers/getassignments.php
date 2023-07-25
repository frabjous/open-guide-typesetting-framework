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
            $mtime = filemtime($assignment_dir . '/bibliography.json');
            $rv->{$assignment_type}->{$assignment_id}->biblastchanged =
                $mtime;
            $rv->{$assignment_type}->{$assignment_id}->biblastsaved =
                $mtime;
        }
        // read bibliography
        if (in_array('all-bibinfo.json', $filenames)) {
            $rv->{$assignment_type}->{$assignment_id}->bibdata = json_decode(file_get_contents(
                $assignment_dir . '/all-bibinfo.json'
            )) ?? false;
        }
        // get info on proofs
        $proofsdir = $assignment_dir . '/proofs';
        if (!is_dir($proofsdir)) { continue; }
        $rv->{$assignment_type}->{$assignment_id}->proofsets = array();
        $proofsets = scandir($proofsdir);
        // we need info on keys
        $keyfile = "$datadir/proofkeys.json";
        $proofkeys = json_decode(file_get_contents($keyfile) ?? '');
        if (!$proofkeys) { $proofkeys = new StdClass(); }
        foreach ($proofsets as $proofset) {
            if ($proofset == '.' || $proofset == '..') { continue; }
            if (!is_dir("$proofsdir/$proofset")) { continue; }
            $ts = intval($proofset);
            if ($ts == 0) { continue; }
            $newset = new StdClass();
            $newset->settime = $ts;
            $newset->outputfiles = array();
            $newset->key = '';
            $prooffiles = scandir("$proofsdir/$proofset");
            foreach ($prooffiles as $pfile) {
                if (substr($pfile, 0, strlen($assignment_id)+1) ==
                    ($assignment_id . '.')) {
                    array_push($newset->outputfiles, $pfile);
                }
            }
            foreach($proofkeys as $key => $prdata) {
                if (($prdata->project == $project) &&
                    ($prdata->assignmentId == $assignment_id) &&
                    ($prdata->assignmentType == $assignment_type) &&
                    ($prdata->proofset == $proofset)) {
                    if (isset($prdata->editor) && ($prdata->editor)) {
                        $newset->ekey = $key;
                    } else {
                        $newset->akey = $key;
                    }
                    if (isset($newset->ekey) && (isset($newset->akey))) {
                        break;
                    }
                }
            }
            if ($newset->ekey != '' && $newset->akey != '') {
                array_push(
                    $rv->{$assignment_type}->{$assignment_id}->proofsets,
                    $newset
                );
            }
        }
        // get info on publication edition versions
        $editionsdir = $assignment_dir . '/editions';
        if (is_dir($editionsdir)) {
            $versions = scandir($editionsdir);
            foreach ($versions as $version) {
                if ($version == '.' || $version == '..') { continue; }
                $versiondir = "$editionsdir/$version";
                if (!is_dir($versiondir)) { continue; }
                if (!isset($rv->{$assignment_type}->{$assignment_id}->editions)) {
                    $rv->{$assignment_type}->{$assignment_id}->editions = new StdClass();
                }
                $versioninfo = new StdClass();
                $versioninfo->creationtime = filemtime($versiondir);
                $versiondircontents = scandir($versiondir);
                $versioninfo->files = array();
                foreach($versiondircontents as $file) {
                    if ($file != '.' && $file != '..') {
                        array_push($versioninfo->files, $file);
                    }
                }
                $rv->{$assignment_type}->{$assignment_id}->editions->{$version} =
                    $versioninfo;
            }
        }
    }
}

$rv->error = false;
jsend();
