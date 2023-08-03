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
    exec($cmd, $o, $e);
    if ($e != 0) {
        ragequit('Error running command: ' . $cmd . PHP_EOL . 'Quitting.');
    }
}

if (php_sapi_name() != 'cli' || isset($_SERVER["SERVER_PROTOCOL"])) {
    ragequit("Must be run from command line.");
}

$maindir = dirname(dirname(__FILE__));

chdir($maindir);


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
