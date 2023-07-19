#!/usr/bin/env php
<?php
// LICENSE: GNU GPL v3 You should have received a copy of the GNU General
// Public License along with this program. If not, see
// https://www.gnu.org/licenses/.

/////////////// philpapers.php /////////////////////////////////////////
// cli wrapper around philpapers.php                                  //
////////////////////////////////////////////////////////////////////////

require_once(dirname(__FILE__) . '/libphilpapers.php');

if (php_sapi_name() != 'cli') {
    rage_quit("Must be run from command line.");
}

if (count($argv) < 2) {
    show_help();
    rage_quit('No arguments provided.');
}

$arguments = array_slice($argv, 1);

$idmode = false;
$ids = array();
$bibentries = array();
$maxcount = 1;

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

    if ($idmode) {
        array_push($ids, $arg);
        continue;
    }

    array_push($bibentries, $arg);
}

foreach ($ids as $id) {
    echo "I should do the id $id";
}

foreach ($bibentries as $bibentry) {
    echo plain_to_bib($bibentry, $maxcount);
}

