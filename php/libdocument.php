<?php
// LICENSE: GNU GPL v3 You should have received a copy of the GNU General
// Public License along with this program. If not, see
// https://www.gnu.org/licenses/.

///////////////////////// libdocument.php ///////////////////////////////
// functions for working with markdown documents                       //
/////////////////////////////////////////////////////////////////////////


function extract_bibliography($markdown) {
    $lines = explode(PHP_EOL, $markdown);
    $ln = count($lines);
    while ($ln > 0) {
        $ln--;
        $line = $lines[$ln];
        $condensed = mb_ereg_replace('[^a-z]','',strtolower($line));
        if (in_array($condensed, array('bibliography',
            'workscited','references','thebibliography'))) {
            $bibarray = array_values(array_filter(
                array_slice($lines, $ln+1),
                    function ($l) {
                        return mb_ereg_match('.*[A-Z]', $l); 
                    }
            ));
            $savedname = '';
            for ($i=0; $i<count($bibarray); $i++) {
                $l = $bibarray[$i];
                // remove asterisks
                $s = mb_ereg_replace('\*','',$l);
                // remove double quotes
                $s = mb_ereg_replace('["“”]','',$s);
                // remove leading hyphens
                $s = mb_ereg_replace('^-*\s*','',$s);
                // remove escaped hyphens
                $s = mb_ereg_replace('\\\-','',$s);
                // remove escaped single quotes
                $s = mb_ereg_replace("\\\'","'",$s);
                // add name from previous check if starts with numeral
                if (mb_ereg_match('[0-9].*', $s)) {
                    if ($savedname != '') {
                        $s = $savedname . ' ' . $s;
                    }
                } else {
                    $savedname = trim(mb_ereg_replace('[0-9].*','',$s));
                }
                // swapout
                $bibarray[$i] = $s;
            }

            return array(
                implode(PHP_EOL,
                    array_slice($lines, 0 , (($ln>0) ? ($ln-1) : 0))),
                implode(PHP_EOL, $bibarray)
            );
        }
    }
    return array($markdown, '');
}

function fix_markdown($markdown. $splitsentences) {
    $lines = explode(PHP_EOL, $markdown);
    $firstline = true;
    $outcome = '';
    $found_acknowledgements = false;
    foreach ($lines as $line) {
    }
}

// remove title
// remove abstract
// remove fix sections including those with numbers
// add acknowledgements, add unnumbered to it
// split sentences
