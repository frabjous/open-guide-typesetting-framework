<?php
// LICENSE: GNU GPL v3 You should have received a copy of the GNU General
// Public License along with this program. If not, see
// https://www.gnu.org/licenses/.

/////////////// proofsaccess.php /////////////////////////////////////////
// determines whether or not access to something involving page proofs  //
// is authenticated, and sends an error if not                          //
//////////////////////////////////////////////////////////////////////////

function itsanogo($text) {
    header('Content-Type: text/plain; charset=UTF-8');
    header("HTTP/1.1 403 Forbidden");
    echo $text;
    exit();
}

if (!isset($_GET["key"])) {
    itsanogo('Access forbidden. No access key provided.');
}

$key = $_GET["key"];

require_once(dirname(__FILE__) . '/../php/readsettings.php');
require_once(dirname(__FILE__) . '/../php/libauthentication.php');

$keyfile = "$datadir/proofkeys.json";

if (!file_exists($keyfile)) {
    itsanogo('No access keys have been set for this site.');
}

$json = file_get_contents($keyfile);

$keys = json_decode($json);

if (!$keys) {
    itanogo('Could not decode access key file.');
}

$accessinfo = false;
foreach ($keys as $hash => $keyinfo) {
    if (password_verify($key, $hash) {
        $accessinfo = $keyinfo;
    }
}

if (!$accessinfo) {
    itsanogo('Invalid access key provided.');
}
