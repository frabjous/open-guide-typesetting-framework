<?php
// LICENSE: GNU GPL v3 You should have received a copy of the GNU General
// Public License along with this program. If not, see
// https://www.gnu.org/licenses/.

//////////////////////// filehandler.php ///////////////////////////////////
// responds to file upload requests from the browser depending on request //
////////////////////////////////////////////////////////////////////////////

session_start();

require_once('../open-guide-editor/open-guide-misc/send-as-json.php');

$rv = new StdClass();

if (!isset($_POST)) {
    jquit('Can only respond to POST requests.');
}

if (!isset($_FILES)) {
    jquit('No files uploaded.');
}

if (!isset($_POST["



if (!isset($postcmd) || $postcmd == '') {
    jquit('No command given.');
}

if (!isset($project)) {
    jquit('No project specified.');
}

if (!file_exists('handlers/' . $postcmd . '.php')) {
    jquit('Bad command name passed to json handler.');
}

require_once('handlers/' . $postcmd . '.php');

// in most cases won't get here, but in case we do
jsend();
