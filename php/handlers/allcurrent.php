<?php
// LICENSE: GNU GPL v3 You should have received a copy of the GNU General
// Public License along with this program. If not, see
// https://www.gnu.org/licenses/.

//////////////////////// allcurrent.php ////////////////////////////////
// handler that responds to requests for all current assignments by   //
// calling getassignments.php with $archive set to false              //
////////////////////////////////////////////////////////////////////////

$archive = false;
require_once (dirname(__FILE__) . '/getassignments.php');
