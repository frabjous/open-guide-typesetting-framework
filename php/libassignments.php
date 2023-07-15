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
    global $projectdir;
    $dir = $projectdir . '/' . $assignment_type . 's/' . $assignment_id;
    if (!is_dir($dir)) {
        if (!$ensure) { return false; }
        if (!mkdir($dir, 0755, true)) { return false; }
    }
    return $dir;
}
