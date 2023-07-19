<?php
// LICENSE: GNU GPL v3 You should have received a copy of the GNU General
// Public License along with this program. If not, see
// https://www.gnu.org/licenses/.

/////////////// libphilpapers.php //////////////////////////////////////
// functions for interacting with the philpapers database             //
////////////////////////////////////////////////////////////////////////


function curl_get($url) {
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HEADER, false);
    curl_setopt($curl, CURLOPT_URL, $url);
    $result = curl_exec($curl);
    if (curl_error($curl) != ''){
        error_log(curl_error($curl));
    }
    curl_close($url);
    return $result;
}

function plain_to_bib($plain) {
    $escaped = curl_escape($plain);
    $url = 'https://philpapers.org/s/' . $escaped;
    return curl_get($url);
}

function rage_quit($errmsg) {
    error_log($errmsg);
    exit(1);
}
