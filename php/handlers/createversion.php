<?php
// LICENSE: GNU GPL v3 You should have received a copy of the GNU General
// Public License along with this program. If not, see
// https://www.gnu.org/licenses/.

///////////////// creatversion.php ////////////////////////////////////////
// handler that responds to request to create new edition/version for    //
// publication                                                           //
///////////////////////////////////////////////////////////////////////////

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

if (is_dir($versiondir)) {
    jquit('An edition with that version number already exists!');
}

if (!mkdir($versiondir, 0755, true)) {
    jquit('Could not create version directory. ' +
        'Contact your site administrator.');
}

$rv->versioninfo = new StdClass();

if (!isset($project_settings->assignmentTypes->{$assignmentType}->createEdition)) {
    jquit('Project is not configured to create editions. Check your ' +
        'project-settings.json file.');
}

$create_instructions =
    $project_settings->assignmentTypes->{$assignmentType}->createEdition;

$rv->files = array();

// move into assignment directory for processing
if (!chdir($assigndir)) {
    jquit('Could not change to appropriate directory.');
}

require_once(dirname(__FILE__) . '/../../open-guide-editor/open-guide-misc/pipe.php');

foreach($create_instructions as $instruction) {
    $command="true";
    if (isset($instruction->command)) {
        $command = $instruction->command;
        $command = str_replace('%projectdir%','"' . $projectdir . '"', $command);
        $command = str_replace('%documentid%', $assignmentId, $command);
        $command = str_replace('%version%', $version, $command);
    }
    $res = pipe_to_command($command);
    if ($res->returnvalue != 0) {
        jquit('Error in version creation' . ((isset($instruction->outputfile)) ?
           ' (' . $instruction->outputfile . ')' : '') . ': ' . $res->stderr);
    }
    if (isset($instruction->outputfile)) {
        $outputfile = $instruction->outputfile;
        $outputfile = str_replace('%projectdir%','"' . $projectdir . '"', $outputfile);
        $outputfile = str_replace('%documentid%', $assignmentId, $outputfile);
        $outputfile = str_replace('%version%', $version, $outputfile);
        if (file_exists($outputfile)) {
            $moveres = rename($outputfile, "$versiondir/$outputfile");
            if (!$moveres) {
                jquit('Could not move an output file into the version ' .
                    'directory. Contact your site administrator.');
            }
            array_push($rv->files, $instruction->outputfile);
        }
    }
}

$ts = time();
$rv->creationtime = $ts;

// if we made it here, all was well
$rv->success = true;
$rv->error = false;

jsend();
