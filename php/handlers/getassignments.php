<?php
// LICENSE: GNU GPL v3 You should have received a copy of the GNU General
// Public License along with this program. If not, see
// https://www.gnu.org/licenses/.

//////////////////// getassignments.php ////////////////////////////////
// retrieves data about all assignments, either current or archived   //
// depending on value of $archive from caller                         //
////////////////////////////////////////////////////////////////////////

// authenticate request

if (!isset($username) || !isset($accesskey)) {
    jquit('No username or access key provided.');
}

// load authentication and setting libraries
require_once(dirname(__FILE__) . '/../readsettings.php');
require_once(dirname(__FILE__) . '/../libauthentication.php');

if (!verify_by_accesskey($project, $username, $accesskey)) {
    jquit('Invalid access key provided.');
}

// TODO change this
$rv->article = new StdClass();
$rv->review = new StdClass();
$rv->article->a = new StdClass();
$rv->article->b = new StdClass();
$rv->article->a->something = 'here';
$rv->article->b->something = 'here';
$rv->review->c = new StdClass();
$rv->review->c->something = 'there';

$rv->error = false;
jsend();
