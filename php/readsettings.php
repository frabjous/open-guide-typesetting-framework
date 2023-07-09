<?php
// LICENSE: GNU GPL v3 You should have received a copy of the GNU General
// Public License along with this program. If not, see
// https://www.gnu.org/licenses/.

///////////////////////// readsettings.php ////////////////////////////////
// reads the data directory, ogst cookie, and project information common //
// to ogst pages and scripts                                             //
///////////////////////////////////////////////////////////////////////////

// report error back to calling script
$settings_error = '';

// read settings file
$settings_file = realpath(dirname(__FILE__) . '/../settings.json');
if (!file_exists($settings_file)) {
    $settings_error = 'Settings file not found.';
    return;
}

$settings_json = file_get_contents($settings_file);
if (!$settings_json || $settings_json == '') {
    $settings_error = 'Could not read content of settings file.';
    return;
}

$settings = json_decode($settings_json);

if (!$settings || !isset($settings->datadir)) {
    $settings_error = 'Could not parse settings file or find data directory.';
    return;
}

// set data directory
$datadir = $settings->datadir;

// ensure it exists
if (!is_dir($datadir) && !mkdir($datadir, 0775, true)) {
    $settings_error = 'Could not find or create data directory.';
    return;
}

// populate projects
$projects = new StdClass();

// look for project names as subdirectory of the data directory
$projectnames = scandir($datadir);
foreach ($projectnames as $projectname) {
    // look for project settings file; skip those without them
    if (!file_exists("$datadir/$projectname/project-settings.json")) {
        continue;
    }
    $projjson = file_get_contents("$datadir/$projectname/project-settings.json");
    if (!$projjson || $projjson == '') { continue; }
    // read project settings; skip those that cannot be read
    $project_settings = json_decode($projjson);
    if (!$project_settings) { continue; }
    $projects->{$projectname} = $project_settings;
}

// read cookie stuff if available
$cookie_projectname = '';
$cookie_username = '';
$cookie_loginaccesskey = '';

if (isset($_COOKIE['open-guide-typesetting-framework-saved-login'])) {
    $cookiestr = $_COOKIE['open-guide-typesetting-framework-saved-login'];
    $cookieparts = explode('|',$cookiestr);
    if (count($cookieparts) == 3) {
        $cookie_projectname = $cookieparts[0];
        $cookie_username = $cookieparts[1];
        $cookie_loginaccesskey = $cookieparts[2];
    }
}

// some functions other scripts may need

function get_projectdir($project) {
    global $datadir;
    $projectdir = "$datadir/$project";
    if (!is_dir($projectdir)) { return false; }
    return "$datadir/$project";
}

function get_project_settings($project) {
    $projectdir = get_projectdir($project);
    if (!file_exists("$projectdir/project_settings.json")) {
        return (new StdClass());
    }
    $json = file_get_contents("$projectdir/project_settings.json");
    if (!$json) { return (new StdClass()); }
    $project_settings = json_decode($json);
    if (!$project_settings) { return (new StdClass()); }
    return $project_settings;
}

