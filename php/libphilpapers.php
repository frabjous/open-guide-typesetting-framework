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

function id_to_bib($id) {
    global $curl;
    $url = 'https://philpapers.org/formats/item.bib?id=' . $id;
    return curl_get($url);
}

function plain_to_bib($plain, $maxcount = 5) {
    $ids = plain_to_ids($plain, $maxcount);
    $rv = '';
    foreach ($ids as $id) {
        $rv .= PHP_EOL . id_to_bib($id);
    }
    return $rv;
}

function plain_to_ids($plain, $maxcount = 5) {
    global $curl;
    $escaped = curl_escape($curl, $plain);
    $url = 'https://philpapers.org/s/' . $escaped;
    $search = curl_get($url);
    if (!$search) { return array(); }
    $portions = explode('<ol class=\'entryList\'>', $search, 2);
    if (count($portions) < 2) { return array(); }
    $entries = explode("\n<li id='e", $portions[1], ($maxcount + 1));
    if (count($entries) < 2) { return array(); }
    $rv = array();
    for ($i=1; $i<count($entries); $i++) {
        $ids = explode("'",$entries[$i],2);
        if (count($ids) > 1) {
            array_push($rv, $ids[0]);
        }
    }
    return $rv;
}

function rage_quit($errmsg) {
    error_log($errmsg);
    exit(1);
}

function show_help() {
    echo <<<EOF

Usage: philpapers.php [options] [item1] [item2]

Options may be:
--count [n] : return n entries for each search item, rather than 1
--id        : remaining items will be interepreted as PhilPapers IDs
--help      : show this help


EOF;
}
