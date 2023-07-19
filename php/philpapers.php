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



