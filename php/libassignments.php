<?php
// LICENSE: GNU GPL v3 You should have received a copy of the GNU General
// Public License along with this program. If not, see
// https://www.gnu.org/licenses/.

///////////////////// libassignments.php ////////////////////////////////
// general functions for working with assignments in projects          //
/////////////////////////////////////////////////////////////////////////

// note: assumes 'readsettings.php' already sourced
// and $project already set by caller

// we'll make this global for the sake of this script
$projectdir = get_projectdir($project);
$project_settings = get_project_settings($project);

function get_assignment_dir($assignment_type, $assignment_id, $ensure = true) {
    $parent_dir = get_assignment_type_dir($assignment_type, $ensure);
    if (!$parent_dir) { return false; }
    $dir = $parent_dir . '/' . $assignment_id;
    if (!is_dir($dir)) {
        if (!$ensure) { return false; }
        if (!mkdir($dir, 0755, true)) { return false; }
    }
    return $dir;
}

function get_assignment_type_dir($assignment_type, $ensure = true) {
    global $projectdir;
    $dir = $projectdir . '/' . $assignment_type . 's';
    if (!is_dir($dir)) {
        if (!$ensure) { return false; };
        if (!mkdir($dir, 0755, true)) { return false; }
    }
    return $dir;
}

function create_oge_settings($assignment_type, $assignment_id) {
    global $project_settings, $projectdir;
    $assignment_dir =  get_assignment_dir($assignment_type,
        $assignment_id, true);
    if (!$assignment_dir) { return false; }
    // create oge-settings.json
    $ogesettings = new StdClass();
    $ogesettings->rootdocument = 'main.md';
    $ogesettings->bibliographies = array('bibliography.json');
    $ogesettings->routines = new StdClass();
    $ogesettings->routines->md = new StdClass();
    $outputinfo = new StdClass();
    if (isset($project_settings->assignmentTypes->{$assignment_type}->output)) {
        $outputinfo = $project_settings->assignmentTypes->{$assignment_type}->output;
    }
    // look for commands for each output extension
    foreach ($outputinfo as $outputext => $extinfo) {
        if (isset($extinfo->editorcommand)) {
            $ogesettings->routines->md->{$outputext} = new StdClass();
            $command = $extinfo->editorcommand;
            // fill in project directory in commands
            $command = str_replace('%projectdir%',
                '"' . $projectdir . '"', $command);
            $ogesettings->routines->md->{$outputext}->command = $command;
        }
    }
    $ogesettings_file = $assignment_dir . '/oge-settings.json';
    return file_put_contents($ogesettings_file, json_encode($ogesettings,
        JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT));
}
