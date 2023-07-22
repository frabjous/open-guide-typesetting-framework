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

function fix_markdown($markdown, $metadata, $splitsentences = false) {
    $lines = explode(PHP_EOL, $markdown);
    $firstline = true;
    $outcome = '';
    $found_acknowledgements = false;
    foreach ($lines as $ln => $line) {
        // look for title, abstract, author, affiliation in first few lines
        // remove them (by skipping them with continue) if found
        if ($ln < 6) {
            if (mb_ereg_match('\*+Abstract', $line)) {
                continue;
            }
            if (mb_ereg_match('Abstract: ', $line)) {
                continue;
            }
            if ((isset($metadata->title)) &&
                (squish($metadata->title) == squish($line))) {
                continue;
            }
            if ((isset($metadata->author)) &&
                ((squish(join_authors($metadata->author,false,false)) == squish($line)) ||
                (squish(join_authors($metadata->author,false,true)) == squish($line)) ||
                (squish(join_authors($metadata->author,true,false)) == squish($line)) ||
                (squish(join_authors($metadata->author,true,true)) == squish($line)))) {
                continue;
            }
            if ((isset($metadata->author) &&
                (squish($metadata->author[0]->email) == squish($line)) ||
                (squish($metadata->email[0]->affiliation = squish($line)))) {
                continue;
            }
        }
        if (squish($line) == 'acknowledgements') {
            $found_acknowledgements = true;
            // strip surrounding asterisks
            $line = mb_ereg_replace('^\*+(.*[^\*])\*+$','\1', $line);
        }
    }
}

// remove fix sections including those with numbers
// add acknowledgements, add unnumbered to it
// split sentences

// note: join_authors is based on metadata name arrays
// and join_names is based on csl json name arrays
function join_authors($authors, $withemails = false, $withaffils = false) {
    $rv = '';
    foreach ($authors as $n => $authorinfo) {
        if ($n > 0) {
            if ($n < (count($authors) - 1)) {
                $rv .= ', ';
            } else {
                $rv .= ' and ';
            }
        }
        $rv .= $authorinfo->name;
        if ($withemails) {
            $rv .= ' ' . $authorinfo->email;
        }
        if ($withaffils) {
            $rv .= ' ' . $authorinfo->affiliation;
        }
    }
}

function join_names($names) {
    $rv = '';
    foreach ($names as $n => $name) {
        if ($n > 0) {
            if ($n < (count($names) - 1)) {
                $rv .= ', ';
            } else {
                $rv .= ' and ';
            }
        }
        if (isset($name->given)) {
            $rv .= $name->given . ' ';
        }
        if (isset($name->{"non-dropping-particle"})) {
            $rv .= $name->{"non-dropping-particle"} . ' ';
        }
        $ (isset($name->family)) {
            $rv .= $name->family;
        }
    }
    return $rv;
}

function squish($s) {
    return strtolower(mb_ereg_replace('[^A-Za-z]','',$s));
}
