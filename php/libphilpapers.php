<?php
// LICENSE: GNU GPL v3 You should have received a copy of the GNU General
// Public License along with this program. If not, see
// https://www.gnu.org/licenses/.

/////////////// libphilpapers.php //////////////////////////////////////
// functions for interacting with the philpapers database             //
////////////////////////////////////////////////////////////////////////

$curl = curl_init();


function curl_get($url) {
    global $curl;
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HEADER, false);
    curl_setopt($curl, CURLOPT_URL, $url);
    $result = curl_exec($curl);
    if (curl_error($curl) != ''){
        error_log(curl_error($curl));
    }
    return $result;
}

function plain_to_bib($plain) {
    global $curl;
    $escaped = curl_escape($curl, $plain);
    $url = 'https://philpapers.org/s/' . $escaped;
    return curl_get($url);
}

function plain_to_keys($plain) {
    global $curl;
    $escaped = curl_escape($curl, $plain);
    $url = 'https://philpapers.org/s/' . $escaped;
    return curl_get($url);
}

function rage_quit($errmsg) {
    error_log($errmsg);
    exit(1);
}

function show_help() {
    echo <<<EOF

Usage: philpapers.php [options] [item1] [item2]

Options may be:
--id   : remaining items will be interepreted as PhilPapers IDs
--help : show this help


EOF;
}
