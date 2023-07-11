<?php
// LICENSE: GNU GPL v3 You should have received a copy of the GNU General
// Public License along with this program. If not, see
// https://www.gnu.org/licenses/.

///////////////////// allarchived.php //////////////////////////////////
// handler that responds to requests for all archived assignments by  //
// calling getassignments.php with $archive set to true               //
////////////////////////////////////////////////////////////////////////

$archive = true;
require_once (dirname(__FILE__) . '/getassignments.php');
