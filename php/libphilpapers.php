<?php
// LICENSE: GNU GPL v3 You should have received a copy of the GNU General
// Public License along with this program. If not, see
// https://www.gnu.org/licenses/.

/////////////// libphilpapers.php //////////////////////////////////////
// functions for interacting with the philpapers database             //
////////////////////////////////////////////////////////////////////////

require_once(dirname(__FILE__) . '/../open-guide-editor/open-guide-misc/pipe.php');

$curl = curl_init();

// fix philpapers' annoying habit of sticking location
// inside 
function bib_fix($bib) {
    $bib = mb_ereg_replace('publisher = {([^}:]*): ',
        "address = {\\1},\n    publisher = {", $bib);
    // move extra whitespace
    $bib = mb_ereg_replace("\n\s\s*","\n    ",$bib);
    return $bib;
}

function bib_to_json($bib) {
    $res = pipe_to_command('pandoc -f bibtex -t csljson', $bib);
    if ($res->returnvalue != 0) {
        error_log($res->stderr);
    }
    return $res->stdout;
}

function bib_to_obj($bib) {
    return json_decode(bib_to_json($bib)) ?? array();
}

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
    $bib = curl_get($url);
    return bib_fix($bib);
}

function id_to_obj($id) {
    return bib_to_obj(id_to_bib($id));
}

function ids_to_bib($ids) {
    $rv = '';
    foreach ($ids as $id) {
        $rv .= PHP_EOL . id_to_bib($id);
    }
    return $rv;
}

function ids_to_json($ids) {
    return bib_to_json(ids_to_bib($ids));
}

function ids_to_obj($ids) {
    return bib_to_obj(ids_to_bib($ids));
}

function plain_array_to_bib($arr, $maxcount = 5) {
    $rv = '';
    foreach ($arr as $plain) {
        $rv .=  plain_to_bib($plain, $maxcount);
    }
    return $rv;
}

function plain_array_to_json($arr, $maxcount = 5) {
    return json_encode(plain_array_to_obj($arr, $maxcount),
        JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE);
}

function plain_array_to_obj($arr, $maxcount = 5) {
    $rv = array();
    foreach ($arr as $plain) {
        $toadd = plain_to_obj($plain, $maxcount);
        $rv = array_merge($rv, $toadd);
    }
    return $rv;
}

function plain_to_bib($plain, $maxcount = 5) {
    $ids = plain_to_ids($plain, $maxcount);
    return ids_to_bib($ids);
}

function plain_to_json($plain, $maxcount = 5) {
    return bib_to_json(plain_to_bib($plain, $maxcount));
}

function plain_to_obj($plain, $maxcount = 5) {
    return bib_to_obj(plain_to_bib($plain, $maxcount));
}

function plain_to_ids($plain, $maxcount = 5) {
    global $curl;
    $escaped = curl_escape($curl, $plain);
    $url = 'https://philpapers.org/s/' . $escaped;
    $search = curl_get($url);
    if (!$search) { return array(); }
    error_log('==============='.$search.'=================');
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

function ragequit($errmsg) {
    error_log($errmsg);
    exit(1);
}

function show_help() {
    echo <<<EOF

Usage: philpapers.php [options] [item1] [item2]

Options may be:
--bibtex    : use bibtex mode
--count [n] : return n entries for each search item, rather than 1
--id        : remaining items will be interepreted as PhilPapers IDs
--json      : use csl json mode
--help      : show this help


EOF;
}
