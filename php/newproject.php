#!/usr/bin/env php
<?php
// LICENSE: GNU GPL v3 You should have received a copy of the GNU General
// Public License along with this program. If not, see
// https://www.gnu.org/licenses/.

/////////////// newproject.php //////////////////////////////////////////
// cli tool for setting up site and creating a first project           //
/////////////////////////////////////////////////////////////////////////

function ragequit($msg) {
    fwrite(STDERR, $msg . PHP_EOL);
    exit(1);
}

function run_or_quit($cmd) {

    while (@ ob_end_flush()); // end all output buffers if any

    $proc = popen("$cmd 2>&1 ; echo Exit status : $?", 'r');

    $live_output = "";
    $complete_output = "";

    while (!feof($proc)) {
        $live_output = fread($proc, 4096);
        $complete_output = $complete_output . $live_output;
        echo "$live_output";
        @ flush();
    }

    pclose($proc);

    // get exit status
    preg_match('/[0-9]+$/', $complete_output, $matches);

    // return exit status and intended output
    $ret_val = new stdClass();
    $ret_val->exit_status = intval($matches[0]);
   $ret_val->output = str_replace("Exit status : " . $matches[0], '', $complete_output);

    // quit if didn't work
    if ($ret_val->exit_status !== 0) {
        ragequit('Error running command: ' . $cmd . PHP_EOL .
            PHP_EOL . $ret_val->output . PHP_EOL . ' Quitting.');
    }

   return $ret_val;
}

if (php_sapi_name() != 'cli' || isset($_SERVER["SERVER_PROTOCOL"])) {
    ragequit("Must be run from command line.");
}

$maindir = dirname(dirname(__FILE__));

chdir($maindir);

// install oge git repo
if (!file_exists("open-guide-editor/index.php")) {
    echo 'Installing open-guide editor.' .PHP_EOL.
    run_or_quit('git clone --recurse-submodules --depth 1 ' .
        'https://github.com/frabjous/open-guide-editor.git');
    echo 'Installed.' . PHP_EOL;
}

// install oge node packages
if (!is_dir("open-guide-editor/node_modules")) {
    echo 'Installing open guide editor node packages.' .PHP_EOL;
    chdir('open-guide-editor');
    run_or_quit('npm install');
    chdir($maindir);
    echo 'Installed.' . PHP_EOL;
}

// bundle oge dependencies
if (!file_exists("open-guide-editor/editor-bundle.js")) {
    echo 'Bundling open guide editor packages.' .PHP_EOL;
    chdir('open-guide-editor');
    run_or_quit('node_modules/.bin/rollup js/editor.mjs -f iife -o editor.bundle.js -p @rollup/plugin-node-resolve');
    chdir($maindir);
    echo 'Bundled.' . PHP_EOL;
}

$datadir = '';
$datadirset = false;

$settings = new StdClass();

// read settings file
if (file_exists('settings.json')) {
    $settings = json_decode(file_get_contents('settings.json') ?? '');
    if (!$settings) { $settings = new StdClass(); }
    if (isset($settings->datadir)) {
        $datadir = $settings->datadir;
        $datadirset = true;
    }
}

// ensure data directory exists
while (!is_dir($datadir)) {
    $datadir = readline('Location of data directory: ');
    if (!is_dir($datadir)) {
        mkdir($datadir, 0755, true);
    }
}

$settings->datadir = $datadir;

// save settings file
if (!$datadirset) {
    $saveres = file_put_contents('settings.json', json_encode($settings,
        JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT));
    if (!$saveres || $saveres === 0) {
        ragequit('Could not save settings file.');
    }
}

$project = '';
while ($project) {
    $project = readline('New project name: ');
    if (!mb_ereg_match('^[A-Za-z0-9]*$', $project)) {
        echo 'Project names should have only letters and numerals.' . PHP_EOL;
        $project = '';
    }
}

$contact = '';
while ($contact == '') {
    $contact = readline('Contact name for project: ');
}

$email = '';
while ($email == '') {
    $email = readline('Contact email for project: ');
    if (!mb_ereg_match('.*@', $email)) {
        echo 'Email addresses should contain @.' . PHP_EOL;
    }
}

$projectdir = "$datadir/$project";

if (file_exists("$projectdir/project-settings.json")) {
    ragequit("Project with a settings file already exists. Quitting.");
}

if (!is_dir($projectdir) && !mkdir($projectdir, 0755, true)) {
    ragequit("Could not create directory for project.");
}

$project_settings = json_decode(file_get_contents('sample-project-settings-journal.json'));

require_once('php/libauthentication.php');



/*
if (count($argv) < 2) {
    show_help();
    ragequit('No arguments provided.');
}

$arguments = array_slice($argv, 1);

$idmode = false;
$ids = array();
$bibentries = array();
$maxcount = 1;
$bibtexmode = false;
$jsonmode = false;

while (count($arguments) > 0) {
    // read next argument
    $arg = array_shift($arguments);

    if ($arg == '--help') {
        show_help();
        continue;
    }

    if ($arg == '--count') {
        $maxcount = intval(array_shift($arguments)) ?? 1;
    }

    if ($arg == '--idmode') {
        $idmode = true;
        continue;
    }

    if ($arg == '--bibtex') {
        $bibtexmode = true;
        continue;
    }

    if ($arg == '--json') {
        $jsonmode = true;
        continue;
    }

    if ($idmode) {
        array_push($ids, $arg);
        continue;
    }

    array_push($bibentries, $arg);
}

if (!$jsonmode && !$bibtexmode) {
    $jsonmode = true;
}

if (count($ids) > 0) {
    if ($jsonmode) {
        echo ids_to_json($ids);
    }
    if ($bibtexmode) {
        echo ids_to_bib($ids);
    }
}

if (count($bibentries) > 0) {
    if ($jsonmode) {
        echo plain_array_to_json($bibentries, $maxcount);
    }
    if ($bibtexmode) {
        echo plain_array_to_bib($bibentries, $maxcount);
    }
}

exit(0);
 */
